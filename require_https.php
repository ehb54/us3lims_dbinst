<?php
// Credentials and sessions are HTTPS only: redirect plain HTTP to the configured site.

/**
 * Where a plain HTTP request should be sent, or null when there is nowhere safe.
 *
 * The host comes from $org_site and never from the request. A Host header is
 * attacker-controlled, and an empty $org_site used to leave 'https://' followed
 * directly by REQUEST_URI: for a request to '//elsewhere.example/x' that is
 * 'https:////elsewhere.example/x', which a browser reads as a redirect to
 * elsewhere.example. So the host is validated, and without one this returns
 * null rather than a target that points off the server.
 *
 * REQUEST_URI is appended as-is, which is safe once the host is present: the
 * authority ends at the first slash, so a path beginning '//' stays a path.
 */
function require_https_target( $org_site, $request_uri )
{
    $host = strtok( (string) $org_site, '/' );

    if ( $host === false || ! preg_match( '/^[A-Za-z0-9]([A-Za-z0-9.-]*[A-Za-z0-9])?(:\d{1,5})?$/D', $host ) )
    {
        return null;
    }

    return 'https://' . $host . (string) $request_uri;
}

if ( PHP_SAPI !== 'cli' && ( empty( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS'] === 'off' ) )
{
    $target = require_https_target( isset( $org_site ) ? $org_site : '',
                                    isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/' );

    if ( $target === null )
    {
        // Nothing is served over plain HTTP, so a missing site name fails closed
        // rather than falling back to the request's own host.
        header( 'Retry-After: 300' );
        header( 'HTTP/1.1 503 Service Unavailable', true, 503 );
        exit( "This server has no configured HTTPS site name.\n" );
    }

    header( 'Location: ' . $target, true, 301 );
    exit();
}
