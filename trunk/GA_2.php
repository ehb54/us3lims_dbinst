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
$mc_iterations  = $job_parameters['mc_iterations'];

// Figure out $max_buckets and $solute_count
$max_buckets = 100;

if ( $advanceLevel == 0 )
  $max_buckets = ( $mc_iterations == 1 ) ? 25 : 10;

// Process changes in the number of solutes
// $solute_count can be between 1 and $max_buckets
$solute_count = 5;
if ( isset($_GET['count']) )
{
  if ( $_GET['count'] < 1 ) $solute_count = 1;

  else if ( $_GET['count'] > $max_buckets ) $solute_count = $max_buckets;

  else $solute_count = $_GET['count'];
}
  
// Process initial bucket file upload, if present
if ( isset( $_FILES['file-upload'] ) )
  $message = upload_file( $buckets, $data_dir ); // $data_dir from config.php

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

    <form enctype="multipart/form-data" action="{$_SERVER['PHP_SELF']}" method="post">
      <fieldset style="background: #eeeeee">
        <legend>Select File to Upload</legend>
        <input type="file" name="file-upload" size="30"/>
        <input type="submit" name="upload_submit" value="Submit"/>
      </fieldset>
    </form>
    <br/><br/>

  <form name="SoluteValue" action=''>
    <fieldset>
      <legend>Set Number of Solutes</legend>
      <br/>
      Value: <input type='text' name='sol' id='sol'
                    onchange='javascript:get_solute_count(this);' 
                    value="$solute_count" size='10'/>
                    Range: (Minimum:1 ~ Maximum:$max_buckets) 
    </fieldset>
  </form>

HTML;

  echo "<form name='Solutes' action='GA_2.php' method='post' " .
       "      onsubmit='return validate_solutes( $solute_count );'>\n";

  echo solute_setup( $buckets, $solute_count );

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

// Function to display a varying number of solutes
function solute_setup( $buckets, $count )
{
  $solute_text = <<<HTML
    <fieldset>
    <legend>Setup solutes</legend>
HTML;

  for ( $i = 1; $i <= $count; $i++ )
  {
    $s_min = ( isset( $buckets[$i]['s_min'] ) ) ? $buckets[$i]['s_min'] : '';
    $s_max = ( isset( $buckets[$i]['s_max'] ) ) ? $buckets[$i]['s_max'] : '';
    $f_min = ( isset( $buckets[$i]['f_min'] ) ) ? $buckets[$i]['f_min'] : 1;
    $f_max = ( isset( $buckets[$i]['f_max'] ) ) ? $buckets[$i]['f_max'] : 4;

    $solute_text .= <<<HTML
      <div id='solutes{$i}'>
        Solute $i: s-min    <input type='text' name='{$i}_min' id='{$i}_min' 
                                   size='8' value='$s_min' />
                   s-max    <input type='text' name='{$i}_max' id='{$i}_max'
                                   size='8' value='$s_max' />
                   f/f0-min <input type='text' name='{$i}_ff0_min' id='{$i}_ff0_min'
                                   size='5' value='$f_min' />
                   f/f0-max <input type='text' name='{$i}_ff0_max' id='{$i}_ff0_max'
                                   size='5' value='$f_max' />
      </div>
      <br/><br/>
HTML;
  }

  $solute_text .= <<<HTML
    <input class='submit' type='button'
           onclick="window.location='GA_1.php'" value='Setup GA Control'/>
    <input type='hidden' name='solute-value' value="$count"/>
    </fieldset>
HTML;

  return $solute_text;
}

// Function to process the uploading of a solute file
function upload_file( &$buckets, $upload_dir )
{
  global $solute_count, $max_buckets;

  $buckets = array();
  
  if ( ( ! isset( $_FILES['file-upload'] ) )   || 
       ( $_FILES['file-upload']['size'] == 0 ) )
    return 'No file was uploaded';

  $uploadFileName=$_FILES['file-upload']['name'];
  $uploadFile = $upload_dir . "/" . $uploadFileName;

  if ( ! move_uploaded_file( $_FILES['file-upload']['tmp_name'], $uploadFile) ) 
    return 'Uploaded file could not be moved to data directory';
  
  if ( ! ( $lines = file( $uploadFile, FILE_IGNORE_NEW_LINES ) ) )
    return 'Uploaded file could not be read';

  $solute_count = (int) $lines[0];  // First line total solutes
  
  // Check that the solute count is in range
  if ( ($solute_count < 1 ) || ($solute_count > $max_buckets) )
  {
    $msg = "Error. The count in the first line of " .
           "$uploadFile ($solute_count) is out of range. " .
           "Acceptable values: " .
           "Minimum: 1 ~ Maximum: $max_buckets.";

    if ( $max_buckets == 25 )
    {
      $msg = "If your analysis includes more than the maximum buckets " .
             "then the system is likely not appropriate for GA analysis. " .
             "Heterogeneous samples and continuous distributions are " .
             "only to be analyzed by the 1/2DSA analysis.";
    }

    return $msg;
  }
  
  $count_lines = count($lines) - 1;

  // Check that the file has the right number of lines.
  if ( $count_lines != $solute_count  ||  $count_lines < 1 )
  {
    $msg = "Error.  Count in first line of $uploadFile ($solute_count) " .
           "does not match the number of lines of data ($count_lines) " .
           "or is invalid.";

    return $msg; 
  }

  // Get the values, checking for floating numbers too
  $error = false;
  for ($i = 1; $i <= $solute_count; $i++ )
  {
    $nums = explode(",", $lines[$i] );
    
    for ($j = 0; $j < 4; $j++ )
    {
      $num = trim( $nums[$j] );
      if ( ereg( '^[-+]?[0-9]*\.?[0-9]*$', $num ) )
        settype( $num, 'float' );

      else
      {
        $error   = true;
        $num     = '';
      }

      switch ($j) 
      {
        case 0 :
           $buckets[$i]['s_min'] = $num;
           break;

        case 1 :
           $buckets[$i]['s_max'] = $num;
           break;

        case 2 :
           $buckets[$i]['f_min'] = $num;
           break;

        case 3 :
           $buckets[$i]['f_max'] = $num;
           break;

      }
    }
  }

  if ( $error )
  {
    $msg = "One or more input values from the data file is not a " .
           "floating-point number. It (They) have been replaced with " .
           "empty values.";
    return $msg;
  }

  return '';
}


