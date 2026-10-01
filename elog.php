<?php

function dt_duration_seconds ( $datetime_start, $datetime_end ) {
    return ((int)$datetime_end->format('Uv') - (int)$datetime_start->format('Uv')) / 1000;
}

function dt_now () {
    return new DateTime( "now" );
}

function elog( $msg ) {
    // Without a shell: SELinux httpd_t may prohibit it.
    $us3pwentry = function_exists( 'posix_getpwnam' ) ? posix_getpwnam( 'us3' ) : false;
    $elogfile   = ( $us3pwentry ? $us3pwentry['dir'] : '/home/us3' ) . '/lims/etc/elog.txt';
    $msg = "[" .  date('m/d/Y H:i:s', time()) . "] [" .  ( $_SERVER['REMOTE_ADDR'] ?? 'cli' ) . "] $msg";
    error_log( "$msg\n", 3, $elogfile );
}

function elogo( $msg, $obj ) {
    elog( $msg . "\n" . json_encode( $obj, JSON_PRETTY_PRINT ) . "\n" );
}

function elogr( $msg ) {
    elog( $msg . "\nRequest:\n" . json_encode( $_REQUEST, JSON_PRETTY_PRINT ) . "\n" );
}

function elogp( $msg ) {
    elog( $msg . "\nPost:\n" . json_encode( $_POST, JSON_PRETTY_PRINT ) . "\n" );
}

function elogrp( $msg ) {
    elogr( $msg . "\nPost:\n" . json_encode( $_POST, JSON_PRETTY_PRINT ) . "\n" );
}

function elogs( $msg ) {
    elog( $msg . "\nSession:\n" . json_encode( $_SESSION, JSON_PRETTY_PRINT ) . "\n" );
}

function elogrs( $msg ) {
    elogr( $msg . "\nSession:\n" . json_encode( $_SESSION, JSON_PRETTY_PRINT ) . "\n" );
}

function elogrsp( $msg ) {
    elogrp( $msg . "\nSession:\n" . json_encode( $_SESSION, JSON_PRETTY_PRINT ) . "\n" );
}
