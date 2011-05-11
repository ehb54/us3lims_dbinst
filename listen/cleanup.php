<?php

$db        = $argv[ 1 ];;
$requestID = $argv[ 2 ];
$gfacID    = "";
$me        = "cleanup.php";

include "listen-config.php";
write_log( "$me: debug db=$db; requestID=$requestID" );

$us3_link = mysql_connect( $dbhost, $user, $passwd );

if ( ! $us3_link )
{
   write_log( "$me: could not connect: $dbhost, $user, $passwd" );
   mail_to_user( "fail", "Internal Error $requestID\nCould not connect to DB" );
   exit( -1 );
}

$result = mysql_select_db( $db, $us3_link );

if ( ! $result )
{
   write_log( "$me: could not select DB $db" );
   mail_to_user( "fail", "Internal Error $requestID\n$could not select DB $db" );
   exit( -1 );
}

// First get basic info for email messages
$query  = "SELECT email, investigatorGUID FROM HPCAnalysisRequest " .
          "WHERE HPCAnalysisRequestID=$requestID";
$result = mysql_query( $query, $us3_link );

if ( ! $result )
{
   write_log( "$me: Bad query: $query" );
   mail_to_user( "fail", "Internal Error $requestID\n$query\n" . mysql_error( $us3_link ) );
   exit( -1 );
}

list( $email_address, $investigatorGUID ) =  mysql_fetch_array( $result );

$query  = "SELECT personID FROM people " .
          "WHERE personGUID='$investigatorGUID'";
$result = mysql_query( $query, $us3_link );

list( $personID ) = mysql_fetch_array( $result );

/*
$query  = "SELECT clusterName, submitTime, queueStatus, method "              .
          "FROM HPCAnalysisRequest h LEFT JOIN HPCAnalysisResult "            .
          "ON h.HPCAnalysisRequestID=HPCAnalysisResult.HPCAnalysisRequestID " .
          "WHERE h.HPCAnalysisRequestID=$requestID";
*/
$query  = "SELECT clusterName, submitTime, queueStatus, method "              .
          "FROM HPCAnalysisRequest h, HPCAnalysisResult r "                   .
          "WHERE h.HPCAnalysisRequestID=$requestID "                          .
          "AND h.HPCAnalysisRequestID=r.HPCAnalysisRequestID";

$result = mysql_query( $query, $us3_link );

if ( ! $result )
{
   write_log( "$me: Bad query:\n$query\n" . mysql_error( $us3_link ) );
   exit( -1 );
}

if ( mysql_num_rows( $result ) == 0 )
{
   write_log( "$me: US3 Table error - No records for requestID: $requestID" );
   exit( -1 );
}

list( $cluster, $submittime, $queuestatus, $jobtype ) = mysql_fetch_array( $result );

// Get the GFAC ID
$query = "SELECT HPCAnalysisResultID, gfacID FROM HPCAnalysisResult " .
         "WHERE HPCAnalysisRequestID=$requestID";

$result = mysql_query( $query, $us3_link );

if ( ! $result )
{
   write_log( "$me: Bad query: $query" );
   mail_to_user( "fail", "Internal Error $requestID\n$query\n" . mysql_error( $us3_link ) );
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
   write_log( "$me: Could not connect to DB $gDB" );
   mail_to_user( "fail", "Internal Error $requestID\nCould not connect to DB $gDB" );
   exit( -1 );
}

while ( true )
{
   $query = "SELECT status, cluster, id FROM analysis " .
            "WHERE gfacID='$gfacID'";

   $result = mysql_query( $query, $gfac_link );
   if ( ! $result )
   {
      write_log( "$me: Could not select GFAC status for $gfacID" );
      mail_to_user( "fail", "Could not select GFAC status for $gfacID" );
      exit( -1 );
   }
   
   list( $status, $cluster, $id ) = mysql_fetch_array( $result );

   if ( $cluster == 'bcf'  || $cluster == 'alamo' )
   {
         get_local_files( $gfac_link, $cluster, $requestID, $id, $gfacID );
         break;
   }

   if ( $status == "KILLED" || $status == "COMPLETE" || $status == "FAILED" )
      break;

   $tries++;

   if ( $tries > 80 ) // Wait 20 minutes max
   {
      write_log( "$me: Global DB not updating for GFAC job $gfacID" );
      mail_to_user( "fail", "Internal Error. Request ID = $requestID\n" .
                    "Global DB not updating for GFAC job $gfacID" );
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
   write_log( "$me: Bad query:\n$query\n" . mysql_error( $gfac_link ) );
   mail_to_user( "fail", "Internal error " . mysql_error( $gfac_link ) );
   exit( -1 );
}

list( $stderr, $stdout, $tarfile ) = mysql_fetch_array( $result );

// Delete data from GFAC DB
$query = "DELETE from analysis WHERE gfacID='$gfacID'";

$result = mysql_query( $query, $gfac_link );

if ( ! $result )
{
   // Just log it and continue
   write_log( "$me: Bad query:\n$query\n" . mysql_error( $gfac_link ) );
}

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
   write_log( "$me: Bad query:\n$query\n" . mysql_error( $us3_link ) );
   mail_to_user( "fail", "Bad query:\n$query\n" . mysql_error( $us3_link ) );
   exit( -1 );
}

// Save the tarfile and expand it

if ( strlen( $tarfile ) == 0 )
{
   write_log( "$me: No tarfile" );
   mail_to_user( "fail", "No results" );
   exit( -1 );
}

// Shouldn't happen
if ( ! is_dir( "$work" ) )
{
   write_log( "$me: $work directory does not exist" );
   mail_to_user( "fail", "$work directory does not exist" );
   exit( -1 );
}

if ( ! is_dir( "$work/$gfacID" ) ) mkdir( "$work/$gfacID", 0770 );
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

   write_log( "$me: Bad output tarfile: $output" );
   mail_to_user( "fail", "Bad output file" );
   exit( -1 );
}

// Insert the model files and noise files
$files    = file( "analysis_files.txt", FILE_IGNORE_NEW_LINES );
$noiseIDs = array();

foreach ( $files as $file )
{
   $split = explode( ";", $file );

   if ( count( $split ) > 1 )
   {
      list( $fn, $meniscus, $mc_iteration, $variance ) = explode( ";", $file );
   
      list( $other, $mc_iteration ) = explode( "=", $mc_iteration );
      list( $other, $variance     ) = explode( "=", $variance );
      list( $other, $meniscus     ) = explode( "=", $meniscus );
   }
   else
      $fn = $file;

   if ( filesize( $fn ) < 100 )
   {
      write_log( "$me:fn is invalid $fn" );
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
               "editedDataID=1, "          .
               "modelID=1, "             .
               "noiseType='$type',"      .
               "xml='" . mysql_real_escape_string( $xml, $us3_link ) . "'";

      // Add later after all files are processed: editDataID, modelID

      $result = mysql_query( $query, $us3_link );

      if ( ! $result )
      {
         write_log( "$me: Bad query:\n$query\n" . mysql_error( $us3_link ) );
         mail_to_user( "fail", "Internal error\n$query\n" . mysql_error( $us3_link ) );
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
         write_log( "$me: Bad query:\n$query " . mysql_error( $us3_link ) );
         mail_to_user( "fail", "Internal error\n$query\n" . mysql_error( $us3_link ) );
         exit( -1 );
      }

      $modelID   = mysql_insert_id( $us3_link );
      $id        = $modelID;
      $file_type = "model";

      $query = "INSERT INTO modelPerson SET " .
               "modelID=$modelID, personID=$personID";
      $result = mysql_query( $query, $us3_link );
   }

   $query = "INSERT INTO HPCAnalysisResultData SET "       .
            "HPCAnalysisResultID='$HPCAnalysisResultID', " .
            "HPCAnalysisResultType='$file_type', "         .
            "resultID=$id";

   $result = mysql_query( $query, $us3_link );

   if ( ! $result )
   {
      write_log( "$me: Bad query:\n$query\n" . mysql_error( $us3_link ) );
      mail_to_user( "fail", "Internal error\n$query\n" . mysql_error( $us3_link ) );
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
      write_log( "$me: Bad query:\n$query\n" . mysql_error( $us3_link ) );
      mail_to_user( "fail", "Bad query\n$query\n" . mysql_error( $us3_link ) );
      exit( -1 );
   }
}

// Clean up
chdir ( $work );
exec( "rm -rf $gfacID" );

mysql_close( $us3_link );

/////////
// Send email 

mail_to_user( "success", "" );
exit( 0 );

function mail_to_user( $type, $msg )
{
   global $email_address;
   global $submittime;
   global $queuestatus;
   global $status;
   global $cluster;
   global $jobtype;
   global $org_name;
   global $admin_email;
   global $dbhost;
   global $requestID;
   global $gfacID;

   $headers  = "From: $org_name Admin<$admin_email>"     . "\n";
   $headers .= "Cc: $org_name Admin<$admin_email>"       . "\n";

   // Set the reply address
   $headers .= "Reply-To: $org_name<$admin_email>"      . "\n";
   $headers .= "Return-Path: $org_name<$admin_email>"   . "\n";

   // Try to avoid spam filters
   $now = time();
   $headers .= "Message-ID: <" . $now . "cleanup@$dbhost>\n";
   $headers .= "X-Mailer: PHP v" . phpversion()         . "\n";
   $headers .= "MIME-Version: 1.0"                      . "\n";
   $headers .= "Content-Transfer-Encoding: 8bit"        . "\n";

   $subject       = "UltraScan Job Notification - $type - " . substr( $gfacID, 0, 16 );
   $message       = "
   Your UltraScan job is complete:

   Submission Time:  $submittime
   Analysis ID    :  $gfacID
   Status         :  $queuestatus
   GFAC Status    :  $status
   Cluster        :  $cluster
   Job Type       :  $jobtype
   ";

   if ( $type != "success" ) $message .= "Error Message  :  $msg\n";

   // Handle the error case where an error occurs before fetching the
   // user's email address
   if ( $email_address == "" ) $email_address = $admin_email;

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

function get_local_files( $gfac_link, $cluster, $requestID, $id, $gfacID )
{
   global $work;
   global $me;
   global $db;
   global $status;

   // Figure out local working directory
   if ( ! is_dir( "$work/$gfacID" ) ) mkdir( "$work/$gfacID", 0770 );
   chdir( "$work/$gfacID" );

   // Figure out remote directory
   $remoteDir = sprintf( "$work/$db-%06d", $requestID );

   // Get stdout, stderr, output/analysis-results.tar
   $output = array();
   $cmd = "scp us3@$cluster.uthscsa.edu:$remoteDir/stdout . 2>&1";

   exec( $cmd, $output, $stat );
   if ( $stat != 0 ) 
      write_log( "$me: Bad exec:\n$cmd\n" . implode( "\n", $output ) );
     
   $cmd = "scp us3@$cluster.uthscsa.edu:$remoteDir/stderr . 2>&1";
   exec( $cmd, $output, $stat );
   if ( $stat != 0 ) 
      write_log( "$me: Bad exec:\n$cmd\n" . implode( "\n", $output ) );

   $cmd = "scp us3@$cluster.uthscsa.edu:$remoteDir/output/analysis-results.tar . 2>&1";
   exec( $cmd, $output, $stat );
   if ( $stat != 0 ) 
      write_log( "$me: Bad exec:\n$cmd\n" . implode( "\n", $output ) );

   // Write the files to gfacDB
   if ( file_exists( "stderr" ) ) $stderr  = file_get_contents( "stderr" );
   if ( file_exists( "stdout" ) ) $stdout  = file_get_contents( "stdout" );
   if ( file_exists( "analysis-results.tar" ) ) 
      $tarfile = file_get_contents( "analysis-results.tar" );

   $query = "UPDATE analysis SET " .
            "stderr='"  . mysql_real_escape_string( $stderr,  $gfac_link ) . "'," .
            "stdout='"  . mysql_real_escape_string( $stdout,  $gfac_link ) . "'," .
            "tarfile='" . mysql_real_escape_string( $tarfile, $gfac_link ) . "'";

   $result = mysql_query( $query, $gfac_link );

   if ( ! $result )
   {
      write_log( "$me: Bad query:\n$query\n" . mysql_error( $gfac_link ) );
      echo "Bad query\n";
      exit( -1 );
   }

   $status = "COMPLETE";

   // Delete the temporary files
   if ( file_exists( "stderr" ) )               unlink ( "stderr" );
   if ( file_exists( "stdout" ) )               unlink ( "stdout" );
   if ( file_exists( "analysis-results.tar" ) ) unlink ( "analysis-results.tar" );
}
?>
