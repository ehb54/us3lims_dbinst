<?php

include "/home/us3/bin/listen-config.php";

$me = "manage-us3-pipe";
write_log( "$me: Starting" );

$handle = fopen( $pipe, "r+" );

if ( $handle == NULL ) 
{
  write_log( "$me: Cannot open pipe" );
  exit( -1 );
}

$msg = "";

// From a pipe, we don't know when the message terminates, so the sender
// added a null to indicate the end of each message
do 
{
   $input = fgetc( $handle );   // Read one character at a time
   $msg  .= $input;

   if ( $input[ 0 ] == chr( 0 ) )
   {
      // Go do some work
      $msg = rtrim( $msg );
      process( $msg );
      if ( $msg == "Stop listen" ) break;
      write_log( "$me: $msg" );
      $msg = "";
   }
} while ( true );

exit();

function process( $msg )
{
   global $dbhost;

   $list                   = explode( ": ", $msg );
   list( $db, $requestID ) = explode( "-",  array_shift( $list ) );
   $message                = implode( ": ", $list );

   // Convert to integer
   settype( $requestID, 'integer' );

   if ( preg_match( "/^Starting/i", $message ) )
     $action = "starting";

   else if ( preg_match( "/^Abort/i", $message ) )
     $action = "aborted";

   else if ( preg_match( "/^Finished/i", $message ) )
     $action = "finished";

   else
     $action = "update";

   update_db( $dbhost, $db, $action, $message, $requestID );
}

function update_db( $dbhost, $db, $action, $message, $requestID )
{
   global $me;
   global $user;
   global $passwd;
   global $guser;
   global $gpasswd;
   global $gDB;
   global $home;

   $resource = mysql_connect( $dbhost, $user, $passwd );

   if ( ! $resource )
   {
      write_log( "$me: Could not connect to DB" );
      return;
   }

   if ( ! mysql_select_db( $db, $resource ) )
   {
     write_log( "$me: Could not select DB $db - " . mysql_error( $resource ) );
     return;
   }

   $query = "SELECT HPCAnalysisResultID, gfacID FROM HPCAnalysisResult " .
            "WHERE HPCAnalysisRequestID=$requestID "             .
            "ORDER BY HPCAnalysisResultID DESC "                 .
            "LIMIT 1";

   $result = mysql_query( $query, $resource );
   
   if ( ! $result )
   {
     write_log( "$me: Bad query: $query" );
     return;
   }

   list( $resultID, $gfacID ) = mysql_fetch_row( $result );

   $query = "UPDATE HPCAnalysisResult SET ";

   $set_global = false;

   switch ( $action )
   {
      case "starting":
         $query .= "queueStatus='running'," .
                   "startTime=now(), ";
         $set_global = true;
         break;

      case "aborted":
         $query .= "queueStatus='aborted'," .
                   "endTime=now(), ";
         break;

      case "finished":
         $query .= "queueStatus='completed'," .
                   "endTime=now(), ";
         break;

      default:
         $query .= "queueStatus='running',";
         break;
   }

   $query .= "lastMessage='" . mysql_real_escape_string( $message ) . "'" .
             "WHERE HPCAnalysisResultID=$resultID";

   mysql_query( $query, $resource );
   mysql_close( $resource );

   if ( $set_global )
   {
      $resource = mysql_connect( $dbhost, $guser, $gpasswd );

      if ( ! $resource )
      {
         write_log( "$me: Could not connect to DB host $dbhost" );
         return;
      }

      if ( ! mysql_select_db( $gDB, $resource ) )
      {
        write_log( "$me: Could not select DB $gDB - " . mysql_error( $resource ) );
        return;
      }

      $query = "UPDATE analysis SET status='RUNNING' " .
               "WHERE gfacID='$gfacID'";


      mysql_query( $query, $resource );
      mysql_close( $resource );
   }

   // There can be more than one instance of cleanup.php running at one time

   if ( $action == "finished"  ||  $action == "aborted" )
   {
      $cleanup = "$home/bin/cleanup.php";
      $php     = "/usr/bin/php";
      $cmd = "nohup $php $cleanup $db $requestID >>$home/etc/cleanup.log 2>&1 </dev/null &";
      write_log( "$me: $cmd" );
      exec( $cmd );
   }
}
?>
