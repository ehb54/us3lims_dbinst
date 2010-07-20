<?php
/*
 * queue_setup_3.php
 *
 * Display all chosen cells and associate edit profiles, models and noise
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

include 'config.php';
include 'db.php';

// Start displaying page
$page_title = "Queue Setup (completed)";
$css = 'css/queue_setup.css';
include 'top.php';
include 'links.php';
include 'lib/payload_manager.php';

$payload = new payload_manager( $_SESSION );

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Queue Setup (completed)</h1>

<?php

$out_text = "";
foreach ( $_SESSION['cells'] as $rawDataID => $cell )
{
  $cell_text        = get_rawData( $rawDataID );
  $editedData_text  = get_editedData( $cell['editedDataID'] );
  $model_text       = get_model( $cell['modelID'] );
  $noise_text       = get_noise( $cell['noiseIDs'] );

  $out_text .= <<<HTML
  <fieldset>
    <legend>$cell_text</legend>

    <table cellpadding='3' cellspacing='0'>
    <tr><th>Edit Profile</th>
        <td>$editedData_text</td></tr>
    <tr><th>Model</th>
        <td>$model_text</td></tr>
    <tr><th>Noise</th>
        <td>$noise_text</td></tr>
    </table>

  </fieldset>

HTML;

}

echo <<<HTML
  <h4>Review submitted edit profiles, models and noise files for each cell</h4>

  <div>
  <form action="queue_setup_2.php" method="post">

    $out_text

  <p><input type='button' value='Select Different Experiment'
            onclick='window.location="queue_setup_1.php";' /> 
     <input type="submit" name='setup_2' value="Edit Profile Information"/>
  </p>
  </form>
  </div>

  <div>
  <p>Double check the information for each cell, and if it is not correct, 
     please click on one of the buttons to edit it again, or to start over.
     If the queue information is correct, please select the <em>Analysis</em>
     global menu above and choose which type of analysis you would like to
     perform.</p>

HTML;

?>

</div>

<?php
include 'bottom.php';
exit();

// Get raw data info
function get_rawData( $rawDataID )
{
  $query  = "SELECT runID, rawData.label " .
            "FROM rawData, experiment " .
            "WHERE rawDataID = $rawDataID " .
            "AND rawData.experimentID = experiment.experimentID ";
  $result = mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());
  list( $runID, $label ) = mysql_fetch_array( $result );

  return( "<span style='font-size:110%;font-weight:bold;'>$runID $label</span>" );
}

// Get edit profiles
function get_editedData( $editedDataID )
{
  $query  = "SELECT label " .
            "FROM editedData " .
            "WHERE editedDataID = $editedDataID ";
  $result = mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());

  list( $label ) = mysql_fetch_array( $result );
  $profile = "<span>[$editedDataID] $label</span>";

  return( $profile );
}

// Get the models
function get_model( $modelID )
{
  $query  = "SELECT description " .
            "FROM model " .
            "WHERE modelID = $modelID ";

  $result = mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());

  list( $descr ) = mysql_fetch_array( $result );
  $model = "<span>[$modelID] $descr</span>";

  return( $model );
}

// Get the noise files
function get_noise( $noiseIDs )
{
  $commaIDs = implode(",", $noiseIDs );
  $query  = "SELECT noiseID, modelID, noiseType " .
            "FROM noise " .
            "WHERE noiseID IN ( $commaIDs ) ";

  $result = mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());

  $noise = "";
  while ( list( $nID, $modelID, $noiseType ) = mysql_fetch_array( $result ) )
    $noise .= "<span>[$nID($modelID)] $noiseType</span><br />\n";

  return( $noise );
}
?>
