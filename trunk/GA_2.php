<?php
/*
 * GA_2.php
 *
 * Solute processing, final database update and submission for the GA analysis
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
include 'lib/controls_GA.php';

// Make sure the advancement level is set
$advanceLevel = ( isset($_SESSION['advancelevel']) )
              ? $_SESSION['advancelevel'] : 0;

// Create the payload manager and place to gather bucket data
$payload = new Payload_GA( $_SESSION );
$buckets = array();

// First, let's see if the "TIGRE" button has been pressed
if ( isset($_POST['TIGRE']) )
{
  // Save cluster information
  if ( isset($_POST['cluster']) )
  {
    list( $cluster_name, $cluster_shortname, $queue ) = explode(":", $_POST['cluster'] );
    $_SESSION['cluster']              = array();
    $_SESSION['cluster']['name']      = $cluster_name;
    $_SESSION['cluster']['shortname'] = $cluster_shortname;
    $_SESSION['cluster']['queue']     = $queue;
  }

  // for now, at home
/*
  else
  {
    $_SESSION['cluster']              = array();
    $_SESSION['cluster']['name']      = 'bcf.uthscsa.edu';
    $_SESSION['cluster']['shortname'] = 'bcf';
  }
*/

  // Check to see if the file is too big
  if ( $advanceLevel == 0 )
    ; //    check_filesize();

  // Restore payload, add buckets to it and then save it
  $payload->restore();
  $sol_count = $_POST['solute-value'];
  $payload->getBuckets( $sol_count, $buckets );

  // Save buckets inside the job parameters section
  $job_parameters = $payload->get( 'job_parameters' );
  $job_parameters['buckets'] = $buckets;
  $payload->add( 'job_parameters', $job_parameters );
  $payload->add( 'cluster', $_SESSION['cluster'] );

  $payload->show();
  $payload->save();

  header("Location: GA_3.php");
  exit();
}

// Get what payload information we need
$payload->restore();
$job_parameters = $payload->get( 'job_parameters' );
$montecarlo  = $job_parameters['mc_iterations'];
$controls  = new Controls_GA();
$controls->initSolutes( $advanceLevel, $montecarlo );

// Process initial bucket file upload, if present
if ( isset( $_FILES ) )
  $message = $controls->upload_file( $buckets, $data_dir ); // $data_dir from config.php

$show = $payload->show( 0, array() );  // debugging info, if enabled
$payload->save();

// Start displaying page
$page_title = "Enter GA Solute Data";
$js = 'js/analysis.js,js/GA.js,js/GA_2.js';
include 'top.php';
include 'links.php';

$message = ( isset( $message ) ) ? "<p class='message'>$message</p>" : "";

echo <<<HTML
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">$page_title</h1>
  <!-- Place page content here -->

  $message

  <fieldset>
    <legend>Initialize S-Value Range, Upload file or Specify Manually</legend>

HTML;

  $controls->solute_file_setup();
  $controls->solute_count_setup();

  echo "<form name='Solutes' action='GA_2.php' method='post' " .
       "      onsubmit='return validate_solutes( $controls->solute_count );'>\n";

  $controls->solute_setup( $buckets );

  echo tigre();

  echo "</form>\n";

echo <<<HTML

  </fieldset>

  <p><a href="queue_setup_1.php">Submit a different request</a><br />
     <a href="GA_1.php">Set up the GA analysis</a></p>

  $show

</div>

HTML;

include 'bottom.php';
exit();

?>
