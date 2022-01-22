<?php

function dt_duration_seconds ( $datetime_start, $datetime_end ) {
    return ($datetime_end->getTimestamp() - $datetime_start->getTimestamp());
}

function dt_now () {
    return new DateTime( "now" );
}

function elog( $msg ) {
    if ( !( strpos($msg, 'queue_content') !== false) ) {
        return;
    }
    $us3home  = exec( "ls -d ~us3" );
    $elogfile = "$us3home/lims/etc/elog.txt";
    $msg = "[" .  date('m/d/Y H:i:s', time()) . "] [" .  $_SERVER['REMOTE_ADDR'] . "] $msg";
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
