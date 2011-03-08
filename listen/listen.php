<?php

include "listen-config.php";

$socket = socket_create(  AF_INET,  SOCK_DGRAM,  SOL_UDP );

// Listen on all interfaces
if ( ! socket_bind( $socket, 0, $listen_port ) )
{
  $msg = "listen bind failed: " . socket_strerror( socket_last_error( $socket ) );
  write_log( $msg );

  exit();
};

$handle = fopen( $pipe, "r+" );

$cmd = "nohup php manage-us3-pipe.php 2>/dev/null >>manage.log </dev/null &";
exec( $cmd );

do
{
  socket_recvfrom( $socket, $buf, 200, 0, $from, $port );
  fwrite( $handle, $buf . chr( 0 ) );

} while ( trim( $buf ) != "Stop listen" );

socket_close( $socket );
?>
