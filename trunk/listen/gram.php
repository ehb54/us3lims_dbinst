<?php
$args = $_SERVER[ 'argv' ];
array_shift( $args );

$buf = implode( ";", $args );

$socket = socket_create(  AF_INET,  SOCK_DGRAM,  SOL_UDP );

socket_sendto( $socket, $buf, strlen( $buf ), 0, 'localhost', 12233 );
socket_close ( $socket );
?>
