<?php

function elog( $msg ) {
    $elogfile = "/home/us3/lims/etc/elog.txt";
    $msg = "[" .  date('m/d/Y H:i:s', time()) . "] $msg\n";
    error_log( "$msg\n", 3, $elogfile );
}

function elogo( $msg, $obj ) {
    elog( $msg . "\n" . json_encode( $obj, JSON_PRETTY_PRINT ) . "\n" );
}

function elogr( $msg ) {
    elog( $msg . "\nRequest:\n" . json_encode( $_REQUEST, JSON_PRETTY_PRINT ) . "\n" );
}

function elogs( $msg ) {
    elog( $msg . "\nSession:\n" . json_encode( $_SESSION, JSON_PRETTY_PRINT ) . "\n" );
}

function elogrs( $msg ) {
    elogr( $msg . "\nSession:\n" . json_encode( $_SESSION, JSON_PRETTY_PRINT ) . "\n" );
}
