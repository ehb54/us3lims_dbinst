<?php
/*
 * checkinstance.php
 *
 * Check that a session is set and that it is for the current DB instance
 *
 */
include 'config.php';
include 'require_https.php';

if ( !function_exists( 'session_cookie_setup_call' ) ) {
    /**
     * The exact argument list session_set_cookie_params() needs, for a given
     * PHP_VERSION_ID. A separate, pure function so a test can check both
     * branches without needing a second PHP binary.
     *
     * The options-array form is PHP 7.3+; on 7.2 (EL8's default, what roles
     * installs) it is silently ignored -- the array is coerced to the string
     * "Array" for the first positional parameter, which PHP then logs as a
     * notice and otherwise does nothing useful with, leaving every flag
     * unset. 7.2 also has no SameSite parameter at all; the standard
     * workaround (every other PHP SameSite shim pre-7.3 does the same) is to
     * smuggle it into the path string, which PHP writes into the Set-Cookie
     * header verbatim and unvalidated.
     *
     * @return array the argument list, for call_user_func_array().
     */
    function session_cookie_setup_call( $version_id ) {
        if ( $version_id < 70300 ) {
            return array( 0, '/; samesite=Lax', '', true, true );
        }
        return array( array(
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ) );
    }
}

if ( !$is_cli ) {
    $sess_name = "PHPSESS_" . $dbname;
    session_name( $sess_name );

    // require_https.php above has already redirected (or failed closed) any
    // plain-HTTP request, so every session cookie set from here on can
    // always be Secure; explicit flags here don't depend on php.ini's
    // defaults being sane on every deployment.
    call_user_func_array( 'session_set_cookie_params', session_cookie_setup_call( PHP_VERSION_ID ) );
    session_start();
}

