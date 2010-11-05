<?php
/*
 * queue_setup_2.php
 *
 * Display experiments and save queue setup for a supercomputer analysis
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

if ( isset( $_POST['save'] ) )
{
  // Verify we have all the info
  get_setup_2();

  if ( isset( $_POST['noiseIDs'] ) )
  {
    foreach ( $_POST['noiseIDs'] as $rawDataID => $noiseIDs )
      $_SESSION['cells'][$rawDataID]['noiseIDs'] = $noiseIDs;
  }

  // Now translate into a data structure more useful for sequencing datasets
  $_SESSION['request'] = array();
  $count = 0;
  foreach( $_SESSION['cells'] as $rawDataID => $cell )
  {
    $_SESSION['request'][$count]['rawDataID']    = $rawDataID;
    $_SESSION['request'][$count]['path']         = $cell['path'];
    $_SESSION['request'][$count]['filename']     = $cell['filename'];
    $_SESSION['request'][$count]['editedDataID'] = $cell['editedDataID'];
    $_SESSION['request'][$count]['editFilename'] = $cell['editFilename'];
//    $_SESSION['request'][$count]['modelID']      = $cell['modelID'];
    $_SESSION['request'][$count]['noiseIDs']     = $cell['noiseIDs'];

    $count++;
  }

  header( "Location: queue_setup_3.php" );
  exit();
}

// Are we gathering info from previous screen?
if ( isset( $_SESSION['new_submitter'] ) )
  get_setup_1();

else // no, gathering info from here
  get_setup_2();

// Start displaying page
$page_title = "Queue Setup (part 2)";
$css = 'css/queue_setup.css';
include 'top.php';
include 'links.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Queue Setup (part 2)</h1>

<?php

$out_text = "";
foreach ( $_SESSION['cells'] as $rawDataID => $cell )
{
  $editedData_text  = get_editedData( $rawDataID, $cell['editedDataID'] );
/*
  $model_text       = get_model( $rawDataID, $cell['editedDataID'], 
                                 $cell['modelID'] );
*/
  $noise_text       = get_noise( $rawDataID, $cell['editedDataID'], 
                                 $cell['noiseIDs'] );

  $out_text .= <<<HTML
  <fieldset>
    <legend style='font-size:110%;font-weight:bold;'>{$cell['filename']}</legend>

    <table cellpadding='3' cellspacing='0'>
    <tr><th>Edit Profile</th>
        <td>$editedData_text</td></tr>
    <!--tr><th>Model</th>
        <td>$model_text</td></tr-->
    <tr><th>Noise</th>
        <td>$noise_text</td></tr>
    </table>

  </fieldset>

HTML;

}

echo <<<HTML
  <!--h4>Select the edit profile, model and noise files for each cell</h4-->
  <h4>Select the edit profile each cell</h4>

  <div>
  <form action="{$_SERVER[PHP_SELF]}" method="post">

    $out_text

  <input type="submit" name='save' value="Save Queue Information"/>
  </form></div>

HTML;

?>

</div>

<?php
include 'bottom.php';
exit();

// Get information from queue_setup_1.php
function get_setup_1()
{
  if ( isset( $_SESSION['new_submitter'] ) )
  {
    $new_submitter = trim( $_SESSION['new_submitter'] );

    // Replace blanks with commas, in case user added others
    $new_submitter = preg_replace ( "/,/", ',', $new_submitter);
    $new_submitter = preg_replace ( "/\s+/", ',', $new_submitter);
    $new_submitter = preg_replace ( "/,+/", ',', $new_submitter);
    $new_submitter = preg_replace ( "/^,/", '', $new_submitter);
  }

  else if ( isset( $_SESSION['submitter_email'] ) )
    $new_submitter = $_SESSION['submitter_email'];

  if ( empty( $new_submitter ) )
  {
    // Must have, or can't proceed
    $_SESSION['message1'] = "*   Email address was missing";
  }

  if ( isset($_SESSION['add_owner']) && $_SESSION['add_owner'] == 1 )
  {
    $query  = "SELECT email FROM people " .
              "WHERE personID = {$_SESSION['id']} ";
    $result = mysql_query($query)
              or die("Query failed : $query<br />" . mysql_error());
    list($owner_email) = mysql_fetch_array($result);

    // Let's double check that the owner email isn't already there
    $pos = strpos($new_submitter, $owner_email);
    if ($pos === false)       // have to use === to find the string in position 0 too
      $new_submitter .= ",$owner_email";
  }

  // Get experiment ID
  $new_experimentID = 0;
  if ( isset( $_SESSION['new_expID'] ) )
  {
    $new_experimentID = $_SESSION['new_expID'];
  }

  if ( $new_experimentID == 0 )
  {
    // Another must have
    $_SESSION['message2'] = "**  You must choose an experiment before proceeding";
  }

  // Now check for cells (at least one should be selected)
  $new_cell_array = array();
  if ( isset( $_SESSION['new_cells'] ) )
  {
    $new_cell_array = $_SESSION['new_cells'];
  }

  if ( count( $new_cell_array ) < 1 )
  {
    $_SESSION['message3'] = "*** You must select at least one cell";
  }

  if ( isset( $_SESSION['message1'] ) || 
       isset( $_SESSION['message2'] ) ||
       isset( $_SESSION['message3'] ) )
  {
    header("Location: queue_setup_1.php");
    exit();
  }

  // Ok, input parameters are here, so we can proceed
  unset( $_SESSION['new_submitter'] );
  unset( $_SESSION['add_owner'] );
  unset( $_SESSION['new_expID'] );
  unset( $_SESSION['new_cells'] );

  $_SESSION['submitter_email'] = $new_submitter;
  $_SESSION['experimentID']    = $new_experimentID;

  // Set up a temporary data structure
  unset( $_SESSION['cells'] );
  $cells = array();
  foreach ( $new_cell_array as $rawDataID => $filename )
  {
    $cells[$rawDataID] = array();
    $cells[$rawDataID]['path']         = dirname ( $filename );
    $cells[$rawDataID]['filename']     = basename( $filename );
    $cells[$rawDataID]['editedDataID'] = 0;
    $cells[$rawDataID]['editFilename'] = '';
//    $cells[$rawDataID]['modelID']      = 0;
    $cells[$rawDataID]['noiseIDs']     = array();
  }

  $_SESSION['cells'] = $cells;
}

// Build information from current page
function get_setup_2()
{
  if ( isset( $_POST['editedDataID'] ) )
  {
    foreach ( $_POST['editedDataID'] as $rawDataID => $editedDataID )
    {
      $_SESSION['cells'][$rawDataID]['editedDataID'] = $editedDataID;

      // Get filename too
      $query  = "SELECT filename FROM editedData " .
                "WHERE editedDataID = $editedDataID ";
      $result = mysql_query( $query )
              or die("Query failed : $query<br />\n" . mysql_error());
      list( $editFilename ) = mysql_fetch_array( $result );
      $_SESSION['cells'][$rawDataID]['editFilename'] = $editFilename;
    }
  }

/*
  if ( isset( $_POST['modelID'] ) )
  {
    foreach ( $_POST['modelID'] as $rawDataID => $modelID )
      $_SESSION['cells'][$rawDataID]['modelID'] = $modelID;
  }
*/
}

// Get edit profiles
function get_editedData( $rawDataID, $editedDataID = 0 )
{
  $query  = "SELECT editedDataID, label " .
            "FROM editedData " .
            "WHERE rawDataID = $rawDataID ";
  $result = mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());

  $profile    = "<select name='editedDataID[$rawDataID]'" .
                "  onchange='this.form.submit();'>\n" .
                "  <option value='null'>Select edit profile...</option>\n";
  while ( list( $eID, $label ) = mysql_fetch_array( $result ) )
  {
    $selected = ( $editedDataID == $eID ) ? " selected='selected'" : "";
    $profile .= "  <option value='$eID'$selected>[$eID] $label</option>\n";
  }

  $profile   .= "</select>\n";

  return( $profile );
}

/*
// Get the models
function get_model( $rawDataID, $editedDataID, $modelID = 0 )
{
  $myID = $_SESSION['id'];

  $query  = "SELECT model.modelID, description " .
            "FROM   modelPerson, model " .
            "WHERE  modelPerson.personID = $myID " .
            "AND    modelPerson.modelID = model.modelID " .
            "AND    ( editedDataID = $editedDataID || " .
            "         editedDataID = 1 )";

  $result = mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());

  $model      = "<select name='modelID[$rawDataID]'" .
                "  onchange='this.form.submit();'>\n" .
                "  <option value='null'>Select model...</option>\n";
  while ( list( $mID, $descr ) = mysql_fetch_array( $result ) )
  {
    $selected = ( $modelID == $mID ) ? " selected='selected'" : "";
    $model .= "  <option value='$mID'$selected>[$mID] $descr</option>\n";
  }

  $model   .= "</select>\n";

  return( $model );
}
*/

// Get the noise files
function get_noise( $rawDataID, $editedDataID, $noiseIDs )
{
  // For now
  $noise      = "<select name='noiseIDs[$rawDataID][]' multiple='multiple' size='3'>\n" .
                "  <option value='null'>Select noise (disabled)...</option>\n";
  $noise   .= "</select>\n";

  // For later
/*
  $query  = "SELECT noiseID, modelID, noiseType " .
            "FROM noise " .
            "WHERE editedDataID = $editedDataID ";

  $result = mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());

  $noise      = "<select name='noiseIDs[$rawDataID][]' multiple='multiple' size='3'>\n" .
                "  <option value='null'>Select noise...</option>\n";
  while ( list( $nID, $modelID, $noiseType ) = mysql_fetch_array( $result ) )
  {
    $selected = ( in_array( $nID, $noiseIDs ) ) ? " selected='selected'" : "";
    $noise .= "  <option value='$nID'$selected>[$nID($modelID)] $noiseType</option>\n";
  }

  $noise   .= "</select>\n";
*/
  return( $noise );
}
?>
