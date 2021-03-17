<?php
/*
 * checkinstance.php
 *
 * Check that a session is set and that it is for the current DB instance
 *
 */
include 'config.php';

if ( !$is_cli ) {
    $sess_name = "PHPSESS_" . $dbname;
    session_name( $sess_name );
    session_start();
}

