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
include 'lib/analysis.php';
include 'lib/HPC_analysis.php';
include 'lib/file_writer.php';

// Create the payload manager and restore the data
$payload = new payload_manager( $_SESSION );
$payload->restore();

// Create the HPC analysis agent and file writer
$HPC       = new HPC_analysis();
$file      = new file_writer();
$filenames = array();

$files_ok  = true;  // Let's also make sure there weren't any problems writing the files
if ( $_SESSION['separate_datasets'] )
{
  $dataset_count = $payload->get( 'datasetCount' );
  for ( $i = 0; $i < $dataset_count; $i++ )
  {
    $HPCAnalysisRequestID = $HPC->writeDB( $payload->get_dataset( $i ) );
    $filenames[ $i ] = $file->write( $payload->get_dataset( $i ), $HPCAnalysisRequestID );
    if ( $filenames[ $i ] === false )
      $files_ok = false;

    else
    {
      // Write the xml file content to the db
      $xml_content = mysql_real_escape_string( file_get_contents( $filenames[ $i ] ) );
      $query  = "UPDATE HPCAnalysisRequest " .
                "SET requestXMLfile = '$xml_content' " .
                "WHERE HPCAnalysisRequestID = $HPCAnalysisRequestID ";
      mysql_query( $query )
            or die("Query failed : $query<br />\n" . mysql_error());
      
    }
  }
}

else
{
  $HPCAnalysisRequestID = $HPC->writeDB( $payload->get() );
  $filenames[ 0 ] = $file->write( $payload->get(), $HPCAnalysisRequestID );
  if ( $filenames[ 0 ] === false )
    $files_ok = false;

  else
  {
    // Write the xml file content to the db
    $xml_content = mysql_real_escape_string( file_get_contents( $filenames[ 0 ] ) );
    $query  = "UPDATE HPCAnalysisRequest " .
              "SET requestXMLfile = '$xml_content' " .
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
    global $submit_dir;

    $cluster = $_SESSION['cluster']['name'];
    unset( $_SESSION['cluster'] );

    $save_cwd = getcwd();         // So we can come back to the current 
                                  // working directory later

    foreach ( $filenames as $filename )
    {
      chdir( dirname( $filename ) );

      $submit = "php {$submit_dir}submit3.dz.php " . basename( $filename );
      exec($submit, $retval);

      if ( ! empty( $retval ) )
        $output_msg .= "<br /><span class='message'>Message from the queue...</span><br />\n" .
                        print_r( $retval, TRUE ) . " <br />\n";
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
$page_title = "2DSA Analysis Submitted";
include 'top.php';
include 'links.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">2DSA Analysis Submitted</h1>
  <!-- Place page content here -->

<?php 
  if ( isset($message) ) echo "<p class='message'>$message</p>\n";

  echo "<p>$output_msg</p>\n";
 ?>
  
  <p><a href="queue_setup_1.php">Submit another request</a></p>

<?php show_mem(); ?>

</div>

<?php
include 'bottom.php';
exit();

// Function to display some debugging info
function show_mem()
{
  if ( DEBUG )
  {
    global $HPCAnalysisRequestID, $payload, $filenames;

    echo "<pre>SessionID = " . session_id() . "\n";
    echo "From 2DSA_2.php\n";
    echo "Time() = " . time() . "\n</pre>\n";
    echo "<pre>\n" .
         "HPCAnalysisRequestID = $HPCAnalysisRequestID\n\n" .
         "Payload... "; 
    if ( $_SESSION['separate_datasets'] )
    {
      $dataset_count = $payload->get( 'datasetCount' );
      for ( $i = 0; $i < $dataset_count; $i++ )
      {
        echo "Payload dataset $i ...\n";
        print_r( $payload->get_dataset( $i ) );
      }
    }

    else
      print_r( $payload->get() );

    echo "Session variables...";
    print_r( $_SESSION );
    echo "</pre>\n";

    echo "<pre>Filenames:\n";
    foreach ( $filenames as $filename )
      echo "* $filename\n";
    echo "</pre>\n";
  }
}
?>
