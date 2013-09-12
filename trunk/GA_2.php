<?php
/*
 * GA_2.php
 *
 * Solute processing, final database update and submission for the GA analysis
 *
 */
include_once 'checkinstance.php';

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
$globalFileName = '';

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
    if ( $cluster_shortname == 'alamo' )
    {
      if ( isset($_SESSION['separate_datasets']) )
      {
         if ( $_SESSION['separate_datasets'] == 0 )
        {
          $queue = 'ngenseq';
        }
      }
    }
    $_SESSION['cluster']['queue']     = $queue;
  }

  // Check to see if the file is too big
  if ( $advanceLevel == 0 )
    ; //    check_filesize();

  // Restore payload, add buckets to it and then save it
  $payload->restore();
  $sol_count = $_POST['solute-value'];
  $payload->getBuckets( $sol_count, $buckets );

  // Save buckets inside the job parameters section
  $job_parameters = $payload->get( 'job_parameters' );
  $job_parameters['bucket_fixed'] = $_POST['z-fixed'];
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
  $max_buckets = ( $mc_iterations == 1 ) ? 50 : 15;

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
include 'header.php';

$message = ( isset( $message ) ) ? "<p class='message'>$message</p>" : "";
$soluteFile = ( !empty($uploadFileName) ) 
            ? " (Current: $uploadFileName)" : "";

// Create a list of files that were selected
$file_info = '';
$num_datasets = sizeof( $_SESSION['request'] );
foreach ( $_SESSION['request'] as $id => $request )
{
  // Get edited data profile 
  $parts = explode( ".", $request['editFilename'] );
  $edit_text = $parts[1];

  $file_info .= "Dataset " . ($id + 1) . ": " .
                "{$request['filename']}; " .
                "Edit profile: $edit_text<br />\n"; 
}

echo <<<HTML
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">$page_title</h1>
  <!-- Place page content here -->

  $message

  <fieldset>
    <legend>Initialize S-Value Range, Upload file or Specify Manually</legend>

    <p>Selected File(s):<br />
       $file_info</p>

    <form enctype="multipart/form-data" action="{$_SERVER['PHP_SELF']}" method="post">
      <fieldset style="background: #eeeeee">
        <legend>Select File to Upload$soluteFile</legend>
        <input type="file" name="file-upload" size="30"/>
        <input type="submit" name="upload_submit" value="Load Values"/>
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

include 'footer.php';
exit();

// Function to display a varying number of solutes
function solute_setup( $buckets, $count )
{
  global $zfixed;

  $solute_text = <<<HTML
    <fieldset>
    <legend>Setup solutes</legend>
HTML;

  $xtype = 's';
  $ytype = 'ff0';
  if ( isset( $buckets[1]['s_min'] ) )
    $xtype = 's';
  else if ( isset( $buckets[1]['D_min'] ) )
    $xtype = 'D';
  else if ( isset( $buckets[1]['mw_min'] ) )
    $xtype = 'mw';
  if ( isset( $buckets[1]['ff0_min'] ) )
    $ytype = 'ff0';
  if ( isset( $buckets[1]['f/f0_min'] ) )
    $ytype = 'ff0';
  else if ( isset( $buckets[1]['vbar_min'] ) )
    $ytype = 'vbar';
  else if ( isset( $buckets[1]['f_min'] ) )
    $ytype = 'f';
  $xlo = $xtype . '_min';
  $xhi = $xtype . '_max';
  $ylo = $ytype . '_min';
  $yhi = $ytype . '_max';


  for ( $i = 1; $i <= $count; $i++ )
  {
    $x_min = ( isset( $buckets[$i][$xlo] ) ) ? $buckets[$i][$xlo] : '';
    $x_max = ( isset( $buckets[$i][$xhi] ) ) ? $buckets[$i][$xhi] : '';
    $y_min = ( isset( $buckets[$i][$ylo] ) ) ? $buckets[$i][$ylo] : 1;
    $y_max = ( isset( $buckets[$i][$yhi] ) ) ? $buckets[$i][$yhi] : 4;

    $solute_text .= <<<HTML
      <div id='solutes{$i}'>
        Solute $i: $xlo   <input type='text' name='{$i}_xmin' id='{$i}_xmin' 
                                   size='8' value='$x_min' />
                   $xhi   <input type='text' name='{$i}_xmax' id='{$i}_xmax'
                                   size='8' value='$x_max' />
                   $ylo   <input type='text' name='{$i}_ymin' id='{$i}_ymin'
                                   size='5' value='$y_min' />
                   $yhi   <input type='text' name='{$i}_ymax' id='{$i}_ymax'
                                   size='5' value='$y_max' />
      </div>
      <br/><br/>
HTML;
  }

  $solute_text .= <<<HTML
    <input class='submit' type='button'
           onclick="window.location='GA_1.php'" value='Setup GA Control'/>
    <input type='hidden' name='solute-value' value="$count"/>
    <input type='hidden' name='x-type' value="$xtype"/>
    <input type='hidden' name='y-type' value="$ytype"/>
    <input type='hidden' name='z-fixed' value="$zfixed"/>
    </fieldset>
HTML;

  return $solute_text;
}

// Function to process the uploading of a solute file
function upload_file( &$buckets, $upload_dir )
{
  global $solute_count, $max_buckets;
  global $uploadFileName;
  global $zfixed;

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

  $nums  = explode(" ", $lines[0] );
  $solute_count = (int) $nums[0];  // First line total solutes
  $zfixed       = ( sizeof( $nums ) > 3 ) ?  (double)trim( $nums[ 3 ] ) : 0.0;
  $xnum  = ( sizeof( $nums ) > 1 ) ?  (int)trim( $nums[ 1 ] ) : 1;
  $ynum  = ( sizeof( $nums ) > 2 ) ?  (int)trim( $nums[ 2 ] ) : 4;
  $xtype = 's';
  $ytype = 'ff0';

  switch ($xnum) 
  {
     case 0 :
        $xtype = 'mw';
        break;
     case 1 :
     default  :
        $xtype = 's';
        break;
     case 2 :
        $xtype = 'D';
        break;
  }
  switch ($ynum) 
  {
     case 3 :
        $ytype = 'f';
        break;
     case 4 :
     default :
        $ytype = 'ff0';
        break;
     case 5 :
        $ytype = 'vbar';
        break;
  }
  
  // Check that the solute count is in range
  if ( ($solute_count < 1 ) || ($solute_count > $max_buckets) )
  {
    $msg = "Error. The count in the first line of " .
           "$uploadFile ($solute_count) is out of range. " .
           "Acceptable values: " .
           "Minimum: 1 ~ Maximum: $max_buckets.";

    if ( $max_buckets == 50 || $max_buckets == 15 )
    {
      $msg = "Your initialization includes more than 50 solutes " .
             "(15 for Monte Carlo iter. > 1). The GA analysis is " .
             "not appropriate for such heterogeneous samples. Either " .
             "reduce the number of solutes in your initialization " .
             "or proceed by 1/2DSA Monte Carlo analysis.";
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
  $xtlo  = $xtype . '_min';
  $xthi  = $xtype . '_max';
  $ytlo  = $ytype . '_min';
  $ythi  = $ytype . '_max';
  $error = false;
  for ($i = 1; $i <= $solute_count; $i++ )
  {
    $nums = explode(",", $lines[$i] );
    
    for ($j = 0; $j < 4; $j++ )
    {
      $num = trim( $nums[$j] );
      if ( preg_match( '/^[-+]?[0-9]*\.?[0-9]*$/', $num ) )
        settype( $num, 'float' );

      else
      {
        $error   = true;
        $num     = '';
      }

      switch ($j) 
      {
        case 0 :
           $buckets[$i][$xtlo] = $num;
           break;

        case 1 :
           $buckets[$i][$xthi] = $num;
           break;

        case 2 :
           $buckets[$i][$ytlo] = $num;
           break;

        case 3 :
           $buckets[$i][$ythi] = $num;
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


