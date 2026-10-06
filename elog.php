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
                'loginid', 'username', 'user_id', 'new_submitter' ] as $needle ) {
        if ( stripos( (string) $key, $needle ) !== false ) {
            return true;
        }
    }
    return false;
}

## A per-host secret for the digest below, generated once and reused so a
## person's entries keep matching across log lines. Unsalted SHA-1 is
## reversible by dictionary against the people table, and by brute force for
## something as short as a phone number; keying the hash on a secret nothing
## in elog.txt ever reveals closes that.
function elog_hmac_key() {
    static $cached = null;
    if ( $cached !== null ) {
        return $cached;
    }

    $us3pwentry = function_exists( 'posix_getpwnam' ) ? posix_getpwnam( 'us3' ) : false;
    $keyfile    = ( $us3pwentry ? $us3pwentry['dir'] : '/home/us3' ) . '/lims/etc/elog_hmac_key';

    $existing = @file_get_contents( $keyfile );
    if ( $existing !== false && $existing !== '' ) {
        return $cached = $existing;
    }

    $key = random_bytes( 32 );

    ## First-writer-wins: link() never replaces an existing target (unlike
    ## rename(), which would), so a second process racing this one -- or a
    ## second account that can't read the first one's key file and so looks
    ## unset to it -- adopts the file the first process created instead of
    ## installing its own and silently changing every digest already written
    ## under the old key.
    $tmp = $keyfile . '.' . getmypid() . '.tmp';
    if ( @file_put_contents( $tmp, $key ) !== false ) {
        @chmod( $tmp, 0600 );
        if ( ! @link( $tmp, $keyfile ) && ! is_file( $keyfile ) ) {
            error_log( "elog: could not create $keyfile" );
        }
        @unlink( $tmp );
    }

    $existing = @file_get_contents( $keyfile );
    return $cached = ( $existing !== false && $existing !== '' ) ? $existing : $key;
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
            $out[ $key ] = '[id:' . substr( hash_hmac( 'sha256', (string) $value, elog_hmac_key() ), 0, 8 ) . ']';
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
    $us3pwentry = function_exists( 'posix_getpwnam' ) ? posix_getpwnam( 'us3' ) : false;
    $elogfile   = ( $us3pwentry ? $us3pwentry['dir'] : '/home/us3' ) . '/lims/etc/elog.txt';

    ## Lock the log file itself rather than a separate lock file: whichever
    ## account created a separate lock file first would be the only one able
    ## to open it, leaving every other account's rollover (and its over-limit
    ## growth) silent forever. Any account that can write elog.txt can lock
    ## elog.txt. 'c' creates the file if missing, without truncating it.
    $fh = @fopen( $elogfile, 'c' );
    if ( $fh === false ) {
        error_log( "elog: could not open $elogfile" );
        return;
    }

    $created = @filesize( $elogfile ) === 0;
    if ( ! @flock( $fh, LOCK_EX ) ) {
        error_log( "elog: could not lock $elogfile" );
        fclose( $fh );
        return;
    }

    ## Rollover under the same lock: two requests over the limit at once must
    ## not both rename to the same ".1", which only one of them can hold,
    ## silently losing whichever rollover lost the race.
    if ( @filesize( $elogfile ) > ELOG_MAX_BYTES ) {
        if ( @rename( $elogfile, "$elogfile.1" ) ) {
            $created = true;
        } else {
            ## Surfaced to the system log, not swallowed: an account that
            ## cannot write ~us3/lims/etc otherwise fails this every time
            ## with no sign anywhere that it is happening.
            error_log( "elog: could not roll over $elogfile to $elogfile.1" );
        }
    }

    $line = "[" .  date('m/d/Y H:i:s', time()) . "] [" .  ( $_SERVER['REMOTE_ADDR'] ?? 'cli' ) . "] $msg";
    error_log( "$line\n", 3, $elogfile );
    flock( $fh, LOCK_UN );
    fclose( $fh );

    if ( $created ) {
        ## error_log() creates the file under the web process's umask, which
        ## is commonly 0644; elog.txt holds debug detail even after
        ## filtering, so it should not be world-readable.
        @chmod( $elogfile, 0640 );
    }
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
