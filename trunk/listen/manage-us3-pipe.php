<?php

include "listen-config.php";

$handle = fopen( $pipe, "r+" );

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
      process( rtrim( $msg ) );
      $msg = "";
      if ( $msg == "Stop listen" ) break;
   }
} while ( true );

exit();

function process( $msg )
{
   global $dbhost;

   write_log( $msg );

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
   global $user;
   global $passwd;

   $resource = mysql_connect( $dbhost, $user, $passwd );

   if ( ! $resource )
   {
      write_log( "manage-us3-pipe: Could not connect to DB" );
      return;
   }

   if ( ! mysql_select_db( $db, $resource ) )
   {
     write_log( "manage-us3-pipe: Could not select DB $db" . mysql_error( $resource ) );
     return;
   }

   $query = "SELECT HPCAnalysisResultID FROM HPCAnalysisResult " .
            "WHERE HPCAnalysisRequestID=$requestID "             .
            "ORDER BY HPCAnalysisResultID DESC "                 .
            "LIMIT 1";

   $result = mysql_query( $query, $resource );
   
   if ( ! $result )
   {
     write_log( "manage-us3-pipe: Bad query: $query" );
     return;
   }

   list( $resultID ) = mysql_fetch_row( $result );

   $query = "UPDATE HPCAnalysisResult SET ";

   switch ( $action )
   {
      case "starting":
         $query .= "queueStatus='running'," .
                   "startTime=now(), ";
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

   $query .= "updateTime=now(), " .
             "lastMessage='" . mysql_real_escape_string( $message ) . "'" .
             "WHERE HPCAnalysisResultID=$resultID";

   mysql_query( $query, $resource );
   mysql_close( $resource );

   // There can be more than one instance of chenup.php running at one time

   if ( $action == "finished"  ||  $action == "aborted" )
   {
      $cmd = "nohup php cleanup.php $db $requestID 2>/dev/null >>cleanup.log </dev/null &";
      write_log( $cmd );
      exec( $cmd );
   }
}
?>
