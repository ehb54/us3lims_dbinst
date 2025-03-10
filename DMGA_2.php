<?php
/*
 * DMGA_2.php
 *
 * Final database update and submission for the DMGA analysis
 *
 */
include_once 'checkinstance.php';
elogrs( __FILE__ );

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
include $class_dir . 'submit_local.php';
include $class_dir . 'submit_gfac.php';
include $class_dir . 'submit_airavata.php';
include_once $class_dir . 'priority.php';

// Make sure the advancement level is set
$advanceLevel = $_SESSION['advancelevel'] ?? 0;

// Create the payload manager and restore the data
$payload = new Payload_DMGA( $_SESSION );
$payload->restore();

// Create the HPC analysis agent and file writer
$HPC       = new HPC_DMGA();
$file      = new File_DMGA();
$filenames = array();
$HPCAnalysisRequestID = 0;

$files_ok  = true;  // Let's also make sure there weren't any problems writing the files

if ( $_SESSION[ 'separate_datasets' ] )
{
//echo "Payload\n<pre>";
//print_r( $payload->get() );

  $dataset_count = $payload->get( 'datasetCount' );
  priority( "DMGA", $dataset_count, $payload->get( 'job_parameters' ) );

  for ( $ii = 0; $ii < $dataset_count; $ii++ )
  {
    $single               = $payload->get_dataset( $ii );
    $HPCAnalysisRequestID = $HPC->writeDB( $single );
    $filenames[ $ii ]     = $file->write( $single, $HPCAnalysisRequestID );

    if ( $filenames[ $ii ] === false )
      $files_ok = false;

    else
    {
      // Write the xml file content to the db
      $xml_content = mysqli_real_escape_string( $link, file_get_contents( $filenames[ $ii ] ) );
      $edit_filename = $single[ 'dataset' ][ 0 ][ 'edit' ];
      $experimentID  = $_SESSION['request'][$ii]['experimentID'];
      // Update the HPCAnalysisRequest record with the xml content
      // language=MariaDB
      $query  = "UPDATE HPCAnalysisRequest " .
                "SET requestXMLfile = ?, " .
                "experimentID = ?, " .
                "editXMLFilename = ? " .
                "WHERE HPCAnalysisRequestID = ? ";
      $args = [ $xml_content, $experimentID, $edit_filename, $HPCAnalysisRequestID ];
      $stmt = $link->prepare( $query );
      $stmt->bind_param( 'sisi', ...$args );
      $stmt->execute()
            or die("Query failed : $query<br />\n" . $stmt->error);
      $stmt->close();
    }
  }
}

else
{
//echo "not separate\n"; exit();
  $globalfit = $payload->get();
  priority( "DMGA-GF", $payload->get( 'datasetCount' ), $payload->get( 'job_parameters' ) );
  $HPCAnalysisRequestID = $HPC->writeDB( $globalfit );
  $filenames[ 0 ] = $file->write( $globalfit, $HPCAnalysisRequestID );
  
  if ( $filenames[ 0 ] === false )
    $files_ok = false;
  
  else
  {
    // Write the xml file content to the db
    $xml_content = mysqli_real_escape_string( $link, file_get_contents( $filenames[ 0 ] ) );
    $edit_filename = $globalfit['dataset'][0]['edit'];
    // Update the HPCAnalysisRequest record
    // language=MariaDB
    $query  = "UPDATE HPCAnalysisRequest " .
              "SET requestXMLfile = ?, " .
              "editXMLFilename = ? " .
              "WHERE HPCAnalysisRequestID = ? ";
    $stmt = mysqli_prepare( $link, $query );
    $args = [ $xml_content, $edit_filename, $HPCAnalysisRequestID ];
    $stmt->bind_param( 'ssi', ...$args );
    $stmt->execute()
          or die("Query failed : $query<br />\n" . $stmt->error);
    $stmt->close();
  }
}

if ( $files_ok )
{
  $output_msg = <<<HTML
  <pre>
  Thank you, your job was accepted and is currently processing. An
  email will be sent to {$_SESSION[ 'submitter_email' ]} when the job is
  completed.

HTML;

  // EXEC COMMAND FOR TIGRE 
  if ( isset( $_SESSION[ 'cluster' ] ) )
  {
    $cluster     = $_SESSION[ 'cluster' ][ 'shortname' ];
    unset( $_SESSION[ 'cluster' ] );

    if ( isset( $global_cluster_details )
         && is_array( $global_cluster_details )
         && array_key_exists( $cluster, $global_cluster_details ) 
         && array_key_exists( 'airavata', $global_cluster_details[$cluster] ) ) {
           if ( $global_cluster_details[$cluster]['airavata' ] ) {
               $job = new submit_airavata();
           } else {
               $job = new submit_local();
           }
    } else {
        error_log( "$cluster not properly setup\n" );
        $msg = "<br /><span class='message'>Configuration error: Unsupported cluster $cluster</span><br />\n";
        echo $msg;
        exit;
    }

    $save_cwd = getcwd();         // So we can come back to the current 
                                  // working directory later

//print_r( $filenames );echo "</pre>";
//exit();

    foreach ( $filenames as $filename )
    {
      chdir( dirname( $filename ) );

      $job-> clear();
      $job-> parse_input( basename( $filename ) );

      if ( ! DEBUG ) $job->submit();
      
      $retval = $job->get_messages();

      if ( ! empty( $retval ) )
      {
        $output_msg .= "<br /><span class='message'>Message from the queue...</span><br />\n" .
                        print_r( $retval, true ) . " <br />\n";
      }
    }

    $job->close_transport();
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
$page_title = 'Discrete Model GA Analysis Submitted';
include 'header.php';

$message = ( isset( $message ) ) ? "<p class='message'>$message</p>" : "";
$show = $payload->show( $HPCAnalysisRequestID, $filenames );  // debugging info, if enabled
$payload->save();

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

include 'footer.php';
exit();

?>

