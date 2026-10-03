<?php

function dt_duration_seconds ( $datetime_start, $datetime_end ) {
    return ((int)$datetime_end->format('Uv') - (int)$datetime_start->format('Uv')) / 1000;
}

function dt_now () {
    return new DateTime( "now" );
}

## Largest elog.txt kept before the previous contents are rolled aside. These dumps
## run to kilobytes per request and nothing else rotates the file.
define( 'ELOG_MAX_BYTES', 8 * 1024 * 1024 );

## Keys whose value is never written: credentials, tokens and the CSRF token.
## Matched as a substring, case-insensitively, so password/passwd/db_pass all hit.
function elog_secret_key( $key ) {
    foreach ( [ 'pass', 'pwd', 'secret', 'token', 'csrf', 'apikey', 'api_key',
                'credential', 'cookie', 'sessid' ] as $needle ) {
        if ( stripos( (string) $key, $needle ) !== false ) {
            return true;
        }
    }
    return false;
}

## Keys holding someone's identity. Replaced by a short digest rather than dropped,
## so entries for the same person can still be matched up while debugging.
function elog_identity_key( $key ) {
    foreach ( [ 'email', 'firstname', 'lastname', 'fullname', 'phone', 'address',
                'loginid', 'username' ] as $needle ) {
        if ( stripos( (string) $key, $needle ) !== false ) {
            return true;
        }
    }
    return false;
}

## elog.txt is plain text under ~us3/lims/etc, readable by anyone who can read the
## file, so the request, POST and session dumps are filtered where they are encoded.
## Filtering here rather than at the call sites means a new elog call cannot
## reintroduce the leak.
function elog_filter( $data ) {
    if ( !is_array( $data ) && !is_object( $data ) ) {
        return $data;
    }
    $out = [];
    foreach ( (array) $data as $key => $value ) {
        if ( elog_secret_key( $key ) ) {
            $out[ $key ] = '[redacted]';
        } elseif ( elog_identity_key( $key ) && is_scalar( $value ) && $value !== '' ) {
            $out[ $key ] = '[id:' . substr( sha1( (string) $value ), 0, 8 ) . ']';
        } elseif ( is_array( $value ) || is_object( $value ) ) {
            $out[ $key ] = elog_filter( $value );
        } else {
            $out[ $key ] = $value;
        }
    }
    return $out;
}

function elog_json( $data ) {
    return json_encode( elog_filter( $data ), JSON_PRETTY_PRINT );
}

function elog( $msg ) {
    // Without a shell: SELinux httpd_t may prohibit it.
    $us3pwentry = function_exists( 'posix_getpwnam' ) ? posix_getpwnam( 'us3' ) : false;
    $elogfile   = ( $us3pwentry ? $us3pwentry['dir'] : '/home/us3' ) . '/lims/etc/elog.txt';
    if ( @filesize( $elogfile ) > ELOG_MAX_BYTES ) {
        @rename( $elogfile, "$elogfile.1" );
    }
    $msg = "[" .  date('m/d/Y H:i:s', time()) . "] [" .  ( $_SERVER['REMOTE_ADDR'] ?? 'cli' ) . "] $msg";
    error_log( "$msg\n", 3, $elogfile );
}

function elogo( $msg, $obj ) {
    elog( $msg . "\n" . elog_json( $obj ) . "\n" );
}

function elogr( $msg ) {
    elog( $msg . "\nRequest:\n" . elog_json( $_REQUEST ) . "\n" );
}

function elogp( $msg ) {
    elog( $msg . "\nPost:\n" . elog_json( $_POST ) . "\n" );
}

function elogrp( $msg ) {
    elogr( $msg . "\nPost:\n" . elog_json( $_POST ) . "\n" );
}

function elogs( $msg ) {
    elog( $msg . "\nSession:\n" . elog_json( $_SESSION ) . "\n" );
}

function elogrs( $msg ) {
    elogr( $msg . "\nSession:\n" . elog_json( $_SESSION ) . "\n" );
}

function elogrsp( $msg ) {
    elogrp( $msg . "\nSession:\n" . elog_json( $_SESSION ) . "\n" );
}
