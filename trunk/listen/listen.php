<?php

include "/home/us3/bin/listen-config.php";
$me = "listen.php";

$socket = socket_create(  AF_INET,  SOCK_DGRAM,  SOL_UDP );

// Listen on all interfaces
if ( ! socket_bind( $socket, 0, $listen_port ) )
{
  $msg = "listen bind failed: " . socket_strerror( socket_last_error( $socket ) );
  write_log( "$me: $msg" );

  exit();
};

$handle = fopen( $pipe, "r+" );

$php = "/usr/bin/php";

$cmd = "nohup $php $home/bin/manage-us3-pipe.php 2>&1 >>$home/etc/manage.log </dev/null &";
exec( $cmd );

do
{
  socket_recvfrom( $socket, $buf, 200, 0, $from, $port );
  fwrite( $handle, $buf . chr( 0 ) );

} while ( trim( $buf ) != "Stop listen" );

socket_close( $socket );
?>
