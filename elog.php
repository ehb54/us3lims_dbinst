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
    ## rename(), which would), so a second process racing this one adopts
    ## the file the first process created instead of installing its own and
    ## silently changing every digest already written under the old key.
    ## That is the race case only. A second account that can't read the
    ## first one's key file still reaches this same link() call -- it just
    ## fails harmlessly, since the target already exists -- and readback
    ## below then falls through to the in-memory $key already warned about
    ## above, not to the first account's actual key.
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
        ## Not "after creating it": this account may have lost the link()
        ## race to another account's write, not created anything itself,
        ## and still can't read the result either way.
        error_log( "elog: could not read back $keyfile; using a fresh in-memory key for"
                 . " this request only" );
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

    ## Bounded retry, not a one-shot open: the inode-mismatch check further
    ## down can still, in principle, find this fd pointing at a different
    ## inode than the path now does. Handled by closing and reopening the
    ## path, which then names the current file.
    for ( $attempt = 0; $attempt < 3; $attempt++ ) {
        $existed_before = file_exists( $elogfile );

        ## umask(0007) for the window a fresh file is created in: fopen('c+',
        ## ...) would otherwise create it under the process's own umask
        ## (commonly 0644 or 0640, leaving the other account locked out)
        ## until the chmod() below runs, and elog.txt holds debug detail
        ## even after filtering. 0666 & ~0007 = 0660, matching dbutils#45
        ## step 5's own fresh-provisioning mode.
        $old_umask = umask( 0007 );
        ## Lock the log file itself rather than a separate lock file:
        ## whichever account created a separate lock file first would be the
        ## only one able to open it, leaving every other account's rollover
        ## (and its over-limit growth) silent forever. Any account that can
        ## write elog.txt can lock elog.txt. 'c+' creates the file if
        ## missing, without truncating it, and opens it for reading as well
        ## as writing -- the rollover copy below reads back through this
        ## same fd, so a write-only 'c' silently copies nothing.
        $fh = @fopen( $elogfile, 'c+' );
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
        ##
        ## The inode-mismatch check below is defense in depth, not a case
        ## elog()'s own rollover can trigger: rollover truncates this
        ## process's own fd in place rather than renaming the live path (see
        ## the rollover block further down), so nothing inside elog() can
        ## make $path_stat's and $fh_stat's inodes disagree. Left in for
        ## something outside elog() renaming the path instead.
        clearstatcache( true, $elogfile );
        $path_stat = @stat( $elogfile );
        $fh_stat    = @fstat( $fh );
        if ( $path_stat !== false && $fh_stat !== false && $path_stat[ 'ino' ] !== $fh_stat[ 'ino' ] ) {
            flock( $fh, LOCK_UN );
            fclose( $fh );
            continue;
        }

        ## Rollover under the same lock, by copying this process's own
        ## already-open, already-locked fd to ".1" and truncating it, not
        ## rename()+recreate on the live path itself (an earlier design,
        ## long gone from this file -- no chmod/chgrp of the live path
        ## exists anywhere below anymore). That design's rename() left the
        ## live path a brand new inode that whichever account's rollover
        ## won then owned exclusively; a chmod/chgrp afterward could widen
        ## the *group* for the other account in a split web/us3 layout, but
        ## never the owner, so that account could still neither write nor
        ## even open the new live path -- a lockout with no way back short
        ## of a human fixing the file by hand. Truncating the
        ## existing fd in place keeps $elogfile's own inode, owner, group
        ## and mode exactly as they already were, so nothing is ever locked
        ## out by a rollover, and there is no window where the live file is
        ## momentarily missing or under the wrong mode.
        ##
        ## The copy lands in a temp file first, never in ".1" directly:
        ## written under it, so a reader never sees a partial ".1"; on a
        ## full disk the previous ".1" is untouched instead of lost; and
        ## rename() onto ".1" replaces a symlink there rather than writing
        ## through it. stream_copy_to_stream() streams rather than buffering
        ## the whole file in memory (an elog.txt near memory_limit would
        ## otherwise abort every call), and its return value is compared
        ## against the known size before anything is truncated, so a short
        ## copy is caught instead of silently emptying the live log.
        if ( $path_stat !== false && $path_stat[ 'size' ] > ELOG_MAX_BYTES ) {
            rewind( $fh );

            ## Both this glob and the 'x' open below run under this call's
            ## exclusive flock() on $elogfile (acquired above), so no other
            ## process can be concurrently, legitimately mid-rollover right
            ## now, whatever pid its tmp file names -- anything matching
            ## this glob is therefore either debris from a rollover that was
            ## killed before it reached the rename() further down (nothing
            ## else ever cleans those up), or a symlink an attacker planted
            ## ahead of time at a predictable pid-based name, pointing at a
            ## file this process cannot otherwise write. Removing it before
            ## creating this call's own tmp file handles both the same way:
            ## a plain unlink() removes a symlink without ever following
            ## it, so the attacker's target is never touched either way.
            foreach ( glob( "$elogfile.1.*.tmp" ) ?: [] as $stale ) {
                @unlink( $stale );
            }

            $tmpfile   = "$elogfile.1." . getmypid() . '.tmp';
            ## 'x' (O_EXCL), not 'w': belt-and-suspenders
            ## alongside the cleanup above, for the narrower race where
            ## something recreates this exact name again between that
            ## unlink() and this fopen(). 'w' would truncate and write
            ## through whatever is there by then, symlink included; 'x'
            ## fails outright instead of following it.
            $old_umask = umask( 0077 );
            $tmp_fh    = @fopen( $tmpfile, 'x' );
            umask( $old_umask );
            $copied = ( $tmp_fh !== false ) ? @stream_copy_to_stream( $fh, $tmp_fh ) : false;
            if ( $tmp_fh !== false ) {
                fclose( $tmp_fh );
            }
            if ( $copied === $path_stat[ 'size' ] ) {
                ## & 0770, not & 0777: a legacy 0644 live file
                ## would otherwise carry its o+r straight into '.1' even
                ## though elog.txt itself gets narrowed off o+r/o+w by the
                ## check further down -- '.1' deserves the same narrowing,
                ## not whatever mode the live file happened to have.
                @chmod( $tmpfile, $path_stat[ 'mode' ] & 0770 );
                @chgrp( $tmpfile, $path_stat[ 'gid' ] );
                if ( @rename( $tmpfile, "$elogfile.1" ) ) {
                    ftruncate( $fh, 0 );
                    rewind( $fh );
                } else {
                    @unlink( $tmpfile );
                    error_log( "elog: could not install $elogfile.1" );
                }
            } else {
                @unlink( $tmpfile );
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

        ## Not gated on whether a rollover just happened: that and an
        ## already-world-readable file used to be mutually exclusive
        ## branches of this same if/elseif, so a file that happened to need
        ## both narrowing and rolling over on the same call kept its unsafe
        ## mode until some later call rolled it over again. Rollover by
        ## itself needs nothing restored here -- $elogfile's own mode and
        ## group never changed, by design -- it just must not suppress the
        ## narrowing check below.
        if ( ! $existed_before ) {
            ## Genuinely new: fopen('c+', ...) above already created it at
            ## 0660 under the umask(0007) set for that window, matching
            ## dbutils#45 step 5's own fresh-provisioning mode. This chmod
            ## is defense in depth against anything that left the umask
            ## window differently, not the only thing narrowing the file.
            ##
            ## This branch is also why clearing elog.txt should be done
            ## with "> elog.txt" or ": > elog.txt" rather than deleting it:
            ## whichever account's request happens to notice it missing
            ## recreates it in the directory's own mode (0755 us3:us3 under
            ## the roles-provisioned default), owned by that account alone --
            ## the directory itself is not setgid, so a file this chmod()
            ## widens to 0660 is still only group-writable by whichever
            ## group that account's own primary group happens to be, not
            ## necessarily the other account's group.
            @chmod( $elogfile, 0660 );
        } elseif ( $path_stat !== false && ( $path_stat[ 'mode' ] & 0007 ) !== 0 ) {
            ## An existing file inherited from before this scheme (e.g.
            ## upgraded from main, still 0644) would otherwise stay
            ## world-readable until its next rollover, which may be months
            ## away. Narrowed on every call instead, preserving whatever
            ## owner/group access it already has.
            ##
            ## This also removes a legacy 0666's o+w, which matters because
            ## nothing here makes the *directory* setgid: on a roles host
            ## (0755 us3:us3 ~us3/lims/etc) apache has never been able to
            ## rotate elog.txt at all -- rotation renames inside the
            ## directory, which needs write access to the directory itself,
            ## not just to the file -- so a legacy 0666 is apache's only way
            ## to keep logging there, and this narrowing removes it the
            ## first time it runs. Accepted, not fixed here: making the
            ## directory itself setgid-writable by both accounts is a
            ## larger change (a dedicated elog subdirectory, provisioned
            ## 2770 us3:<web group>, is the clean version of that) that
            ## dbutils#45's step 5 does not attempt.
            @chmod( $elogfile, $path_stat[ 'mode' ] & 0777 & ~0007 );
        }

        return;
    }

    error_log( "elog: $elogfile's inode kept changing out from under this process; giving up" );
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
