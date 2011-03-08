<?php

// A utility to send a message to the listening port

$args = $_SERVER[ 'argv' ];
array_shift( $args );

$buf = implode( ";", $args );

$socket = socket_create(  AF_INET,  SOCK_DGRAM,  SOL_UDP );

include 'listen-config.php';

socket_sendto( $socket, $buf, strlen( $buf ), 0, $dbhost, $listen_port );
socket_close ( $socket );
?>
