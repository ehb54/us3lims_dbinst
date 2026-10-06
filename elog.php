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

## Where elog.txt and elog_hmac_key live. $GLOBALS['global_elog_dir'] (unset
## in production) lets a test point this at a throwaway directory instead of
## ~us3/lims/etc, the same seam common's remote_exec/circuit_breaker use for
## their own state directories.
function elog_state_dir() {
    $override = $GLOBALS[ 'global_elog_dir' ] ?? null;
    if ( is_string( $override ) && $override !== '' ) {
        return rtrim( $override, '/' );
    }
    $us3pwentry = function_exists( 'posix_getpwnam' ) ? posix_getpwnam( 'us3' ) : false;
    return ( $us3pwentry ? $us3pwentry[ 'dir' ] : '/home/us3' ) . '/lims/etc';
}

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
    static $warned = false;
    if ( $cached !== null ) {
        return $cached;
    }

    $keyfile = elog_state_dir() . '/elog_hmac_key';

    $existing = @file_get_contents( $keyfile );
    if ( $existing !== false && $existing !== '' ) {
        return $cached = $existing;
    }

    ## file_exists() (not just the read above) distinguishes "never created
    ## yet" -- the ordinary first-run case, nothing to warn about -- from
    ## "exists but this account can't use it" (unreadable, or provisioned
    ## empty for an account other than the one that reads it here): the
    ## latter means every request falls through to a fresh in-memory key
    ## below, silently, so digests for the same person never match from one
    ## request to the next. link() below can't fix an existing empty file
    ## (it never replaces an existing target), so this can otherwise persist
    ## forever with nothing in any log to explain it.
    if ( file_exists( $keyfile ) && !$warned ) {
        $warned = true;
        error_log( "elog: $keyfile exists but is empty or unreadable by this account;"
                 . " using a fresh in-memory key for this request only -- identity digests"
                 . " will not match other accounts' until it is provisioned readable and"
                 . " non-empty, 0640 us3:<web group>" );
    }

    $key = random_bytes( 32 );

    ## First-writer-wins: link() never replaces an existing target (unlike
    ## rename(), which would), so a second process racing this one -- or a
    ## second account that can't read the first one's key file and so looks
    ## unset to it -- adopts the file the first process created instead of
    ## installing its own and silently changing every digest already written
    ## under the old key.
    $tmp = $keyfile . '.' . getmypid() . '.tmp';
    ## umask(0077) for the window between creating $tmp and the chmod() right
    ## after: file_put_contents() otherwise creates it under the process's
    ## own umask (commonly 0644), leaving it world-readable -- holding the
    ## HMAC key itself -- until that chmod() runs.
    $old_umask = umask( 0077 );
    $write_ok  = @file_put_contents( $tmp, $key ) !== false;
    umask( $old_umask );
    if ( $write_ok ) {
        @chmod( $tmp, 0600 );
        if ( ! @link( $tmp, $keyfile ) && ! is_file( $keyfile ) ) {
            error_log( "elog: could not create $keyfile" );
        }
        @unlink( $tmp );
    }

    $existing = @file_get_contents( $keyfile );
    if ( ( $existing === false || $existing === '' ) && !$warned ) {
        $warned = true;
        error_log( "elog: could not read back $keyfile after creating it; using a fresh"
                 . " in-memory key for this request only" );
    }
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
    $elogfile = elog_state_dir() . '/elog.txt';

    ## Bounded retry, not a one-shot open: a process that opened $elogfile
    ## just before another account's rollover renamed it away would be
    ## locking the OLD inode (now elog.txt.1), not the path it still thinks
    ## is elog.txt -- writing through that fd would silently land in the
    ## rolled-over file instead of the live one. Detected below by comparing
    ## the open fd's inode against the path's current one, and handled by
    ## closing and reopening the path, which then names the fresh file.
    for ( $attempt = 0; $attempt < 3; $attempt++ ) {
        $existed_before = file_exists( $elogfile );

        ## umask(0027) for the window a fresh file is created in: fopen('c',
        ## ...) would otherwise create it under the process's own umask
        ## (commonly 0644, world-readable) until the chmod() below runs, and
        ## elog.txt holds debug detail even after filtering.
        $old_umask = umask( 0027 );
        ## Lock the log file itself rather than a separate lock file:
        ## whichever account created a separate lock file first would be the
        ## only one able to open it, leaving every other account's rollover
        ## (and its over-limit growth) silent forever. Any account that can
        ## write elog.txt can lock elog.txt. 'c' creates the file if missing,
        ## without truncating it.
        $fh = @fopen( $elogfile, 'c' );
        umask( $old_umask );
        if ( $fh === false ) {
            error_log( "elog: could not open $elogfile" );
            return;
        }

        if ( ! @flock( $fh, LOCK_EX ) ) {
            error_log( "elog: could not lock $elogfile" );
            fclose( $fh );
            return;
        }

        ## clearstatcache() first: PHP's stat cache would otherwise serve
        ## the lstat() this path got on an earlier call in this same
        ## request (e.g. the pre-lock filesize() a prior version of this
        ## function made), which can predate both this lock and any
        ## rollover that happened while waiting for it.
        clearstatcache( true, $elogfile );
        $path_stat = @stat( $elogfile );
        $fh_stat    = @fstat( $fh );
        if ( $path_stat !== false && $fh_stat !== false && $path_stat[ 'ino' ] !== $fh_stat[ 'ino' ] ) {
            flock( $fh, LOCK_UN );
            fclose( $fh );
            continue;
        }

        $rolled_stat = false;
        ## Rollover under the same lock: two requests over the limit at once
        ## must not both rename to the same ".1", which only one of them can
        ## hold, silently losing whichever rollover lost the race.
        if ( $path_stat !== false && $path_stat[ 'size' ] > ELOG_MAX_BYTES ) {
            if ( @rename( $elogfile, "$elogfile.1" ) ) {
                $rolled_stat = $path_stat;
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

        if ( $rolled_stat !== false ) {
            ## The rolled file's own mode/group wins, not a hardcoded
            ## default: a deliberately shared 0660 us3:<web group> file (so
            ## a split web/us3 account layout can both write it) must stay
            ## shared after rotation, not get reset to a single-account mode
            ## on every rollover.
            @chmod( $elogfile, $rolled_stat[ 'mode' ] & 0777 );
            @chgrp( $elogfile, $rolled_stat[ 'gid' ] );
        } elseif ( ! $existed_before ) {
            ## Genuinely new, not a rollover: error_log() created it under
            ## the web process's umask, so narrow it from whatever that left.
            @chmod( $elogfile, 0640 );
        } elseif ( $path_stat !== false && ( $path_stat[ 'mode' ] & 0007 ) !== 0 ) {
            ## An existing file inherited from before this scheme (e.g.
            ## upgraded from main, still 0644) would otherwise stay
            ## world-readable until its next rollover, which may be months
            ## away. Narrowed on every call instead, preserving whatever
            ## owner/group access it already has.
            @chmod( $elogfile, $path_stat[ 'mode' ] & 0777 & ~0007 );
        }

        return;
    }

    error_log( "elog: $elogfile kept getting rolled over from under this process; giving up" );
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
