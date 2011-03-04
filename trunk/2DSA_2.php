<?php
/*
 * 2DSA_2.php
 *
 * Final database update and submission for the 2DSA analysis
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( ($_SESSION['userlevel'] != 2) &&
     ($_SESSION['userlevel'] != 3) &&
     ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) )    // only data analyst and up
{
  header('Location: index.php');
  exit();
} 

// Verify that job submission is ok now
include 'lib/motd.php';
if ( motd_isblocked() && ($_SESSION['userlevel'] < 4) )
{
  header("Location: index.php");
  exit();
}

// define( 'DEBUG', true );

include 'config.php';
include 'db.php';
include 'lib/utility.php';
include 'lib/payload_manager.php';
include 'lib/HPC_analysis.php';
include 'lib/file_writer.php';
include 'lib/submit_globus.php';
include 'lib/submit_gfac.php';

// Create the payload manager and restore the data
$payload = new Payload_2DSA( $_SESSION );
$payload->restore();

// Create the HPC analysis agent and file writer
$HPC       = new HPC_2DSA();
$file      = new File_2DSA();
$filenames = array();
$HPCAnalysisRequestID = 0;

$files_ok  = true;  // Let's also make sure there weren't any problems writing the files
if ( $_SESSION['separate_datasets'] )
{
  $dataset_count = $payload->get( 'datasetCount' );
  for ( $i = 0; $i < $dataset_count; $i++ )
  {
    $single = $payload->get_dataset( $i );
    $HPCAnalysisRequestID = $HPC->writeDB( $single );
    $filenames[ $i ] = $file->write( $single, $HPCAnalysisRequestID );
    if ( $filenames[ $i ] === false )
      $files_ok = false;

    else
    {
      // Write the xml file content to the db
      $xml_content = mysql_real_escape_string( file_get_contents( $filenames[ $i ] ) );
      $edit_filename = $single['dataset'][0]['edit'];
      $query  = "UPDATE HPCAnalysisRequest " .
                "SET requestXMLfile = '$xml_content', " .
                "editXMLFilename = '$edit_filename' " .
                "WHERE HPCAnalysisRequestID = $HPCAnalysisRequestID ";
      mysql_query( $query )
            or die("Query failed : $query<br />\n" . mysql_error());
      
    }
  }
}

else
{
  $globalfit = $payload->get();
  $HPCAnalysisRequestID = $HPC->writeDB( $globalfit );
  $filenames[ 0 ] = $file->write( $globalfit, $HPCAnalysisRequestID );
  if ( $filenames[ 0 ] === false )
    $files_ok = false;

  else
  {
    // Write the xml file content to the db
    $xml_content = mysql_real_escape_string( file_get_contents( $filenames[ 0 ] ) );
    $edit_filename = $globalfit['dataset'][0]['edit'];
    $query  = "UPDATE HPCAnalysisRequest " .
              "SET requestXMLfile = '$xml_content', " .
              "editXMLFilename = '$edit_filename' " .
              "WHERE HPCAnalysisRequestID = $HPCAnalysisRequestID ";
    mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());
    
  }
}

if ( $files_ok )
{
  $output_msg = <<<HTML
  <pre>
  Thank you, your job was accepted to bcf and is currently processing, an
  email will be sent to {$_SESSION['submitter_email']} when the job is
  completed.

HTML;

  // EXEC COMMAND FOR TIGRE 
  if ( isset($_SESSION['cluster']) )
  {
    $cluster = $_SESSION['cluster']['shortname'];
    unset( $_SESSION['cluster'] );

    // For the moment we are supporting two submission methods.
    switch ( $cluster )
    {
       case 'queenbee' :
          $job = new submit_gfac();
          break;
    
       default :
          $job = new submit_globus();
          break;
    }
   
    $save_cwd = getcwd();         // So we can come back to the current 
                                  // working directory later

    foreach ( $filenames as $filename )
    {
      chdir( dirname( $filename ) );

      $job-> clear();
      $job-> parse_input( basename( $filename ) );
      if ( ! DEBUG ) $job-> submit();
      $retval = $job->get_messages();

      if ( ! empty( $retval ) )
      {
        $output_msg .= "<br /><span class='message'>Message from the queue...</span><br />\n" .
                        print_r( $retval, true ) . " <br />\n";
      }
    }

    chdir( $save_cwd );
  }
  $output_msg .= "</pre>\n";
}

else
{
  $output_msg = <<<HTML
  Thank you, there have been one or more problems writing the various files necessary
  for job submission. Please contact your system administrator.

HTML;

}

// Start displaying page
$page_title = '2DSA Analysis Submitted';
include 'top.php';
include 'links.php';

$message = ( isset( $message ) ) ? "<p class='message'>$message</p>" : "";
$show = $payload->show( $HPCAnalysisRequestID, $filenames );  // debugging info, if enabled

echo <<<HTML
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">$page_title</h1>
  <!-- Place page content here -->

  $message
  <p>$output_msg</p>

  <p><a href="queue_setup_1.php">Submit another request</a></p>

  $show

</div>

HTML;

include 'bottom.php';
exit();

?>
