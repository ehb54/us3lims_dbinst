<?php
/*
 * checkinstance.php
 *
 * Check that a session is set and that it is for the current DB instance
 *
 */
include 'config.php';
include 'require_https.php';

if ( !$is_cli ) {
    $sess_name = "PHPSESS_" . $dbname;
    session_name( $sess_name );

    // require_https.php above has already redirected (or failed closed) any
    // plain-HTTP request, so every session cookie set from here on can
    // always be Secure; explicit flags here don't depend on php.ini's
    // defaults being sane on every deployment.
    session_set_cookie_params( array(
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ) );
    session_start();
}

