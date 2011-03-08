<?php

$db        = $argv[ 1 ];;
$requestID = $argv[ 2 ];

include "listen-config.php";
write_log( "cleanup debug db=$db; requestID=$requestID" );

$us3_link = mysql_connect( $dbhost, $user, $passwd );

if ( ! $us3_link )
{
   write_log( "cleanup.php: could not connect: $dbhost, $user, $passwd" );
   mail_to_user( "fail", "Internal Error $requestID\n$query" );
   exit( -1 );
}

$result = mysql_select_db( $db, $us3_link );

if ( ! $result )
{
   write_log( "cleanup.php: could not select DB $db" );
   mail_to_user( "fail", "Internal Error $requestID\n$query" );
   exit( -1 );
}

// First get basic info for email messages
$query  = "SELECT email, investigatorGUID FROM HPCAnalysisRequest " .
          "WHERE HPCAnalysisRequestID=$requestID";
$result = mysql_query( $query, $us3_link );

if ( ! $result )
{
   write_log( "cleanup.php: Bad query: $query" );
   mail_to_user( "fail", "Internal Error $requestIDi\n$query" );
   exit( -1 );
}

list( $email_address, $investigatorGUID ) =  mysql_fetch_array( $result );

$query  = "SELECT personID FROM people " .
          "WHERE personGUID='$investigatorGUID'";
$result = mysql_query( $query, $us3_link );

list( $personID ) = mysql_fetch_array( $result );

$query  = "SELECT clusterName, submitTime, queueStatus, method "              .
          "FROM HPCAnalysisRequest h LEFT JOIN HPCAnalysisResult "            .
          "ON h.HPCAnalysisRequestID=HPCAnalysisResult.HPCAnalysisRequestID " .
          "WHERE h.HPCAnalysisRequestID=$requestID";

$result = mysql_query( $query, $us3_link );

if ( ! $result )
{
   write_log( "cleanup.php: Bad query: $query" );
   exit( -1 );
}
list( $cluster, $submittime, $queuestatus, $jobtype ) = mysql_fetch_array( $result );

// Get the GFAC ID
$query = "SELECT HPCAnalysisResultID, gfacID FROM HPCAnalysisResult " .
         "WHERE HPCAnalysisRequestID=$requestID";

$result = mysql_query( $query, $us3_link );

if ( ! $result )
{
   write_log( "cleanup.php: Bad query: $query" );
   mail_to_user( "fail", "Internal Error $requestID" );
   exit( -1 );
}

list( $HPCAnalysisResultID, $gfacID ) = mysql_fetch_array( $result ); 

// We may need to wait a bit for the results to get posted
$tries = 0;

////////
// Get data from global GFAC DB and insert it into US3 DB
$gfac_link = mysql_connect( $dbhost, $guser, $gpasswd );

$result = mysql_select_db( $gDB, $gfac_link );

if ( ! $result )
{
   write_log( "cleanup.php: Could not connect to DB $gDB" );
   mail_to_user( "fail", "Internal Error $requestID" );
   exit( -1 );
}


while ( true )
{
   $query = "SELECT status FROM analysis " .
            "WHERE gfacID='$gfacID'";

   $result         = mysql_query( $query, $gfac_link );
   list( $status ) = mysql_fetch_array( $result );

   if ( $status == "KILLED" || $status == "COMPLETE" || $status == "FAILED" )
      break;

   $tries++;

   if ( $tries > 40 ) // Wait 10 minutes max
   {
      write_log( "Global DB not updating for GFAC job $gfacID" );
      mail_to_user( "fail", "Internal Error. Request ID = $requestID" );
      exit( -1 );
   }
   else
   {
      sleep( 15 );
   }
}

$query = "SELECT stderr, stdout, tarfile FROM analysis " .
         "WHERE gfacID='$gfacID'";

$result = mysql_query( $query, $gfac_link );

if ( ! $result )
{
   write_log( "cleanup.php: Bad query: $query" );
   mail_to_user( "fail", "Internal error " . mysql_query( $gfac_link ) );
   exit( -1 );
}

list( $stderr, $stdout, $tarfile ) = mysql_fetch_array( $result );

// Delete data from GFAC DB
$query = "DELETE from analysis WHERE gfacID='$gfacID'";

// Don't delete for TESTING
//mysql_query( $query, $gfac_link );
mysql_close( $gfac_link );

/////////
// Insert data into HPCAnalysis

$query = "UPDATE HPCAnalysisResult SET "                              .
         "stderr='" . mysql_real_escape_string( $stderr, $us3_link ) . "', " .
         "stdout='" . mysql_real_escape_string( $stdout, $us3_link ) . "' "  .
         "WHERE HPCAnalysisResultID=$HPCAnalysisResultID";

$result = mysql_query( $query, $us3_link );

if ( ! $result )
{
   echo "Bad query: $query\n";
   write_log( "cleanup.php: Bad query: $query" );
   mail_to_user( "fail", $query );
   exit( -1 );
}
// Save the tarfile and expand it

if ( strlen( $tarfile ) == 0 )
{
   write_log( "cleanup.php: No tarfile" );
   mail_to_user( "fail", "No results" );
   exit();
}

mkdir( "$work/$gfacID" );
chdir( "$work/$gfacID" );

$f = fopen( "analysis.tar", "w" );
fwrite( $f, $tarfile );
fclose( $f );

$tar_out = array();
exec( "tar -xf analysis.tar 2>&1", $tar_out, $err );

if ( $err != 0 )
{
   chdir( $work );
   exec( "rm -r $gfacID" );
   $output = implode( "\n", $tar_out );

   write_log( "cleanup.php: Bad tarfile: $output" );
   mail_to_user( "fail", "Bad tarfile" );
   exit();
}

// Insert the model files and noise files
$files    = file( "analysis_files.txt", FILE_IGNORE_NEW_LINES );
$noiseIDs = array();

foreach ( $files as $file )
{
   list( $fn, $meniscus, $mc_iteration, $variance ) = explode( ";", $file );
   
   list( $other, $mc_iteration ) = explode( "=", $mc_iteration );
   list( $other, $variance     ) = explode( "=", $variance );
   list( $other, $meniscus     ) = explode( "=", $meniscus );

   if ( filesize( $fn ) < 100 )
   {
      write_log( "cleanup.php:fn is invalid $fn" );
      mail_to_user( "fail", "Internal error\n$fn is invalid" );
      exit( -1 );
   }

   if ( preg_match( "/\.noise/", $fn ) > 0 ) // It's a noise file
   {
      $xml        = file_get_contents( $fn );
      $noise_data = parse_xml( $xml, "noise" );
      $type       = ( $noise_data[ 'type' ] == "ri" ) ? "ri_noise" : "ti_noise";
      $desc       = $noise_data[ 'description' ];
      $modelGUID  = $noise_data[ 'modelGUID' ];
      $noiseGUID  = $noise_data[ 'noiseGUID' ];

      $query = "INSERT INTO noise SET "  .
               "noiseGUID='$noiseGUID'," .
               "modelGUID='$modelGUID'," .
               "editDataID=1, "          .
               "modelID=0, "             .
               "noiseType='$type',"      .
               "xml='" . mysql_real_escape_string( $xml, $us3_link ) . "'";

      // Add later after all files are processed: editDataID, modelID

      $result = mysql_query( $query, $us3_link );

      if ( ! $result )
      {
         write_log( "cleanup.php: Bad query: $query" );
         mail_to_user( "fail", "Internal error\n$query" );
         exit( -1 );
      }

      $id        = mysql_insert_id( $us3_link );
      $file_type = "noise";
      $noiseIDs[] = $id;
   }
   else  // It's a model file
   {
      $xml         = file_get_contents( $fn );
      $model_data  = parse_xml( $xml, "model" );
      $description = $model_data[ 'description' ];
      $modelGUID   = $model_data[ 'modelGUID' ];
      $editGUID    = $model_data[ 'editGUID' ];

      $query = "INSERT INTO model SET "       .
               "modelGUID='$modelGUID',"      .
               "editedDataID="                .
               "(SELECT editedDataID FROM editedData WHERE editGUID='$editGUID')," .
               "description='$description',"  .
               "MCIteration='$mc_iteration'," .
               "meniscus='$meniscus'," .
               "variance='$variance'," .
               "xml='" . mysql_real_escape_string( $xml, $us3_link ) . "'";

      $result = mysql_query( $query, $us3_link );

      if ( ! $result )
      {
         write_log( "cleanup.php: Bad query: $query" );
         mail_to_user( "fail", "Internal error\n$query" );
         exit( -1 );
      }

      $modelID   = mysql_insert_id( $us3_link );
      $file_type = "model";

      $query = "INSERT INTO modelPerson SET " .
               "modelID=$modelID, personID=$personID";
      $result = mysql_query( $query, $us3_link );
   }

   $query = "INSERT INTO HPCAnalysisResultData SET "       .
            "HPCAnalysisResultID='$HPCAnalysisResultID', " .
            "HPCAnalysisResultType='$file_type', "         .
            "resultID=$modelID";

   $result = mysql_query( $query, $us3_link );

   if ( ! $result )
   {
      write_log( "cleanup.php: Bad query: $query" );
      mail_to_user( "fail", "Internal error\n$query" );
      exit( -1 );
   }
}

// Now fix up noise entries
// For noise files, there is, at most two: ti_noise and ri_noise
// In this case there will only be one modelID

foreach ( $noiseIDs as $noiseID )
{   
   $query = "UPDATE noise SET "                                                 .
            "editedDataID="                                                     .
            "(SELECT editedDataID FROM editedData WHERE editGUID='$editGUID')," .
            "modelID=$modelID "                                                 .
            "WHERE noiseID=$noiseID";

   $result = mysql_query( $query, $us3_link );

   if ( ! $result )
   {
      write_log( "cleanup.php: Bad query: $query" );
      mail_to_user( "fail", "Internal error\n$query" );
      exit( -1 );
   }
}

// Clean up
chdir ( $work );
exec( "rm -r $gfacID" );

mysql_close( $us3_link );

/////////
// Send email 

mail_to_user( "success", "" );
exit();

function mail_to_user( $type, $msg )
{
   global $email_address;
   global $submittime;
   global $queuestatus;
   global $cluster;
   global $jobtype;
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
   $headers .= "Message-ID: <" . $now . "cleanup@$dbhost>$requestID\n";
   $headers .= "X-Mailer: PHP v" . phpversion()         . "\n";
   $headers .= "MIME-Version: 1.0"                      . "\n";
   $headers .= "Content-Transfer-Encoding: 8bit"        . "\n";

   $subject       = "UltraScan Job Notification - $type";
   $message       = "
   Your UltraScan job is complete:

   Submission Time:  $submittime
   Status         :  $queuestatus
   Cluster        :  $cluster
   Job Type       :  $jobtype
   ";

   if ( $type != "success" ) $message .= "Error Message  :  $msg\n";

   mail( $email_address, $subject, $message, $headers );
}

function parse_xml( $xml, $type )
{
   $parser = new XMLReader();
   $parser->xml( $xml );

   $results = array();

   while ( $parser->read() )
   {
      if ( $parser->name == $type )
      {
         while ( $parser->moveToNextAttribute() ) 
         {
            $results[ $parser->name ] = $parser->value;
         }

         break;
      }
   }

   $parser->close();
   return $results;
}

?>
