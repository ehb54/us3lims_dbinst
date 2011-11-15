<?php

include "listen-config.php";

$me      = "error-check.php";
$message = "";
$updateTime = 0;

// Get data from global GFAC DB 
$gfac_link = mysql_connect( $dbhost, $guser, $gpasswd );

$result = mysql_select_db( $gDB, $gfac_link );

if ( ! $result )
{
   write_log( "$me: Could not connect to DB $gDB" );
   mail_to_admin( "fail", "Internal Error: Could not select DB $gDB" );
   exit();
}
   
$query = "SELECT gfacID, us3_db, cluster, status, queue_msg, " .
                "UNIX_TIMESTAMP(time), time from analysis";
$result = mysql_query( $query, $gfac_link );

if ( ! $result )
{
   write_log( "$me: Query failed $query - " .  mysql_error( $gfac_link ) );
   mail_to_admin( "fail", "Query failed $query\n" .  mysql_error( $gfac_link ) );
   exit();
}

$rows = mysql_num_rows( $result );

if ( $rows == 0 ) exit();  // Nothing to do

for ( $i = 0; $i < $rows; $i++ )
{
   list( $gfacID, $us3_db, $cluster, $status, $queue_msg, $time, $submittime ) 
            = mysql_fetch_array( $result );

   // Don't worry about this entry if it's already been logged
   if ( substr( $queue_msg, 0, 7 ) == "Handled" ) continue;

   // Sometimes during testing, the us3_db entry is not set
   if ( strlen( $us3_db ) == 0 ) 
   {
      write_log( "$me: GFAC DB is NULL - $gfacID" );
      mail_to_admin( "fail", "GFAC DB is NULL\n$gfacID" );

      $query2 = "UPDATE analysis SET queue_msg='Handled' WHERE gfacID='$gfacID'";
      $result2 = mysql_query( $query2, $gfac_link );

      if ( ! $result2 )
         write_log( "$me: Query failed $query2 - " .  mysql_error( $gfac_link ) );

      continue;
   }

   switch ( $status )
   {
      case "SUBMITTED": 
         submitted( $time );
         break;  

      case "RUNNING":
         running();
         break;

      case "COMPLETE":
         complete();
         break;

      case "CANCELLED":
      case "CANCELED":
      case "FAILED":
         failed();
         break;

      default:
         break;
   }
}

exit();

function submitted( $updatetime )
{
   global $me;
   global $gfac_link;
   global $gfacID;

   $now = time();

   if ( $updatetime + 86400 < $now )
   {
      write_log( "$me: Job idle too long - id: $gfacID" );
      mail_to_admin( "hang", "Job idle too long - id: $gfacID" );
      $query = "UPDATE analysis SET queue_msg='Handled' WHERE gfacID='$gfacID'";
      $result = mysql_query( $query, $gfac_link );

      if ( ! $result )
         write_log( "$me: Query failed $query - " .  mysql_error( $gfac_link ) );
  }
}

function running()
{
   global $me;
   global $gfac_link;
   global $gfacID;
   global $message;
   global $updateTime;
   global $cluster;

   $now = time();

   get_us3_data();  // Sets $updatetime also

   if ( $updateTime + 600 > $now ) return;

   // The job started, but we havent received a message lately.

   // Get job status.  If running then ignore the job for now, otherwise 
   // the job failed.
   
   if ( preg_match( "/^US3-Experiment/i", $gfacID ) )
   {
      $url = "http://gw33.quarry.iu.teragrid.org:8080/ogce-rest/job/jobstatus/$gfacID";
      try
      {
         $post = new HttpRequest( $url, HttpRequest::METH_GET );
         $http = $post->send();
         $xml  = $post->getResponseBody();      
      }
      catch ( HttpException $e )
      {
         write_log( "$me: Status not available - marking failed -  $gfacID" );
         failed();
         return;
      }

      // Parse the result
      $message = "";                         // Set in next line
      $gfac_status = parse_response( $xml );

      if ( $gfac_status != "ACTIVE" ) 
      {
         write_log( "$me: Job failed - $gfac_status - $gfacID - $message" );
         failed();
      }
   }
   else // Local job
   {
      $system = "$cluster.uthscsa.edu";
      $system = preg_replace( "/\-local/", "", $system );
      $cmd    = "/usr/bin/ssh -x us3@$system qstat -a $gfacID 2>&1";

      $result = exec( $cmd );

      if ( $result != ""  ||  ! preg_match( "/^Unknown/", $result ) )
      {
         $values = preg_split( "/\s+/", $result );
         if ( $values[ 9 ] == "R" ) return;       // Still running
      }

      // At this point, the job may have completed or failed.  Just clean up.
      cleanup();
   }
}

function complete()
{
   // This could occur if there is a dropped udp packet or the listener is down.
   // The fix is to wait a bit and if the record is still here, call cleanup.

   global $me;
   global $gfac_link;
   global $gfacID;

   sleep( 10 );
   $query  = "SELECT count(*) FROM analysis WHERE gfacID='$gfacID'";
   $result = mysql_query( $query, $gfac_link );
  
   if ( ! $result )
   {
      write_log( "$me: Query failed $query - " .  mysql_error( $gfac_link ) );
      mail_to_admin( "fail", "Query failed $query\n" .  mysql_error( $gfac_link ) );
      exit();
   }

   list( $count ) = mysql_fetch_array( $result );

   if ( $count == 0 ) return;

   // If we get here cleanup didn't get scheduled
   cleanup();
}

function failed()
{
   // Just cleanup
   cleanup();
}

function cleanup()
{
   global $us3_db;
   global $home;

   $requestID = get_us3_data();
   if ( $requestID == 0 ) return;

   $php     = "/usr/bin/php";
   $cleanup = "$home/bin/cleanup.php";
  
   $cmd = "nohup $php $cleanup $us3_db $requestID >>$home/etc/cleanup.log 2>&1 </dev/null &";
   exec( $cmd );
}

function get_us3_data()
{
   global $me;
   global $gfacID;
   global $dbhost;
   global $user;
   global $passwd;
   global $us3_db;
   global $updateTime;

   $us3_link = mysql_connect( $dbhost, $user, $passwd );

   if ( ! $us3_link )
   {
      write_log( "$me: could not connect: $dbhost, $user, $passwd" );
      mail_to_admin( "fail", "Could not connect to $dbhost" );
      exit();
   }


   $result = mysql_select_db( $us3_db, $us3_link );

   if ( ! $result )
   {
      write_log( "$me: could not select DB $us3_db" );
      mail_to_admin( "fail", "Could not select DB $us3_db, $dbhost, $user, $passwd" );
      return 0;
   }

   $query = "SELECT HPCAnalysisRequestID, UNIX_TIMESTAMP(updateTime) " .
            "FROM HPCAnalysisResult WHERE gfacID='$gfacID'";
   $result = mysql_query( $query, $us3_link );

   if ( ! $result )
   {
      write_log( "$me: Query failed $query - " .  mysql_error( $us3_link ) );
      mail_to_admin( "fail", "Query failed $query\n" .  mysql_error( $us3_link ) );
      return 0;
   }

   list( $requestID, $updateTime ) = mysql_fetch_array( $result );
   mysql_close( $us3_link );

   return $requestID;
}

function parse_response( $xml )
{
   global $message;

   $status  = "";
   $message = "";

   $parser = new XMLReader();
   $parser->xml( $xml );

   while( $parser->read() )
   {
      $type = $parser->nodeType;

      if ( $type == XMLReader::ELEMENT )
         $name = $parser->name;

      else if ( $type == XMLReader::TEXT )
      {
         if ( $name == "status" ) 
            $status  = $parser->value;
         else 
            $message = $parser->value; 
      }
   }
      
   $parser->close();
   return $status;
}

function mail_to_admin( $type, $msg )
{
   global $submittime;
   global $status;
   global $cluster;
   global $org_name;
   global $admin_email;
   global $dbhost;
   global $requestID;

   $headers  = "From: $org_name Admin<$admin_email>"     . "\n";
   $headers .= "Cc: $org_name Admin<$admin_email>"       . "\n";

   // Set the reply address
   $headers .= "Reply-To: $org_name<$admin_email>"      . "\n";
   $headers .= "Return-Path: $org_name<$admin_email>"   . "\n";

   // Try to avoid spam filters
   $now = time();
   $headers .= "Message-ID: <" . $now . "error-check@$dbhost>$requestID\n";
   $headers .= "X-Mailer: PHP v" . phpversion()         . "\n";
   $headers .= "MIME-Version: 1.0"                      . "\n";
   $headers .= "Content-Transfer-Encoding: 8bit"        . "\n";

   $subject       = "US3 Error Notification";
   $message       = "
   UltraScan job error notification from error-check.php:

   Submission Time:  $submittime
   GFAC Status    :  $status
   Cluster        :  $cluster
   ";

   $message .= "Error Message  :  $msg\n";

   mail( $admin_email, $subject, $message, $headers );
}
?>
