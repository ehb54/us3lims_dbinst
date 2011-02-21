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

// Check if user has elected to remove one of the datasets in the queue
if ( isset($_GET['removeID']) )
{
  $removeID = $_GET['removeID'];

  if ( sizeof( $_SESSION['request'] ) == 1)
    unset( $_SESSION['request'] );

  else
  {
    // More than one, so we can move higher numbered ones into one lower position
    for ($i = $removeID; $i < sizeof( $_SESSION['request']) - 1; $i++ )
      $_SESSION['request'][$i] = $_SESSION['request'][$i+1];

    // Last one is the one to delete
    unset( $_SESSION['request'][$i] );
  }

  // Has to be redirected to avoid another removal from the queue just by
  //  refreshing the screen
  header("Location: $_SERVER[PHP_SELF]");
  exit();
}

// Check if the clear queue button has been pressed
if ( isset($_GET['clear']) )
{
  unset( $_SESSION['request'] );

  header("Location: $_SERVER[PHP_SELF]");
  exit();
}

// Check the status of the separate datasets radio buttons
if ( isset( $_POST['separate_datasets'] ) )
{
  $separate_datasets = $_POST['separate_datasets'] == 'global'
                     ? 0
                     : 1;
}

else if ( isset( $_SESSION['separate_datasets'] ) )
  $separate_datasets = $_SESSION['separate_datasets'];

else
  $separate_datasets = 1;

$_SESSION['separate_datasets'] = $separate_datasets;

// Set up some web stuff
$button_message = ( $separate_datasets )
                ? "Click here to proceed as a global fit"
                : "Click here to proceed as separate jobs";
$separate_text  = ( $separate_datasets )
                ? "proceed as separate jobs"
                : "proceed as a global fit";
if ( $separate_datasets )
{
  $separate_checked = " checked='checked'";
  $global_checked   = "";
}

else
{
  $separate_checked = "";
  $global_checked   = " checked='checked'";
}

include 'config.php';
include 'db.php';

// Start displaying page
$page_title = "Queue Setup (completed)";
$css = 'css/queue_setup.css';
include 'top.php';
include 'links.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Queue Setup (completed)</h1>

<?php

if ( isset( $_SESSION['request'] ) && sizeof( $_SESSION['request'] > 0 ) )
{
  $out_text = "";
  foreach ( $_SESSION['request'] as $removeID => $cellinfo )
  {
    $editedData_text  = get_editedData( $cellinfo['editedDataID'] );
/*    $model_text       = get_model( $cellinfo['modelID'] );

also insert below in $out_text:
      <tr><th>Model</th>
          <td>$model_text</td></tr>
*/

    $noise_text       = get_noise( $cellinfo['noiseIDs'] );

    $out_text .= <<<HTML
    <fieldset>
      <legend style='font-size:110%;font-weight:bold;'>{$cellinfo['filename']}
              <a href='{$_SERVER['PHP_SELF']}?removeID=$removeID'>Remove?</a></legend>

      <table cellpadding='3' cellspacing='0'>
      <tr><th>Edit Profile</th>
          <td>$editedData_text</td></tr>
      <tr><th>Noise</th>
          <td>$noise_text</td></tr>
      </table>

    </fieldset>

HTML;

  }

  // Some controls only support one dataset
  $disabled = ( sizeof( $_SESSION['request'] ) == 1 ) ?
              "" : " disabled='disabled' ";

  $multiset_notes = "";
  if ( $disabled )
  {
    $multiset_notes = <<<HTML
    <fieldset>
    <legend>Multiple dataset notes:</legend>
    <ul class='multi_notes'>
      <li><form action='$_SERVER[PHP_SELF]' method='post'>
          In the case of the 2DSA control and the 2DSA with MW
          constraint control, multiple datasets can be submitted as
          either a global fit to a single model or separated into
          multiple jobs. Currently, you are set up to $separate_text.
          To change, select one of the options below before proceeding.
          Remember, if you select a GA analysis method this setting
          will be ignored.

          <table cellspacing='0' cellpadding='3px'>
          <tr><td><label>
                  <input type='radio' name='separate_datasets'
                         value='separate'$separate_checked
                         onclick='this.form.submit();' />
                         Proceed as separate jobs</label></td></tr>
          <tr><td><label>
                  <input type='radio' name='separate_datasets'
                         value='global'$global_checked
                         onclick='this.form.submit();' />
                         Proceed as a global fit</label></td></tr>
          </table>
          </form></li>
      <li>The nonlinear GA model currently supports only one dataset</li>
    </ul>
    </fieldset>

HTML;
  }

  echo <<<HTML
  <h4>Review submitted edit profiles, models and noise files for each cell</h4>

  <div>
  $multiset_notes

  <form action="queue_setup_2.php" method="post">

    $out_text

  <p><input type='button' value='Select Different Experiment'
            onclick='window.location="queue_setup_1.php";' /> 
     <input type="submit" name='setup_2' value="Edit Profile Information"/></p>

  <p><input type="button" value="Setup 2DSA Control"
            onclick='window.location="2DSA_1.php"' />
     <input type="button" value="Setup 2DSA Control with MW Constraint"
            onclick='window.location="2DSA_MW_1.php"' disabled='disabled' /></p>

  <p><input type="button" value="Setup GA Control"
            onclick='window.location="GA_1.php"' />
     <input type="button" value="Setup GA Control with MW Constraint"
            onclick='window.location="GA_MW_1.php"' disabled='disabled' /></p>

  <p><input type="button" value="Nonlinear Model GA Control"
            onclick='window.location="GA_SC_1.php"' disabled='disabled' $disabled />
     <input type="button" value="Clear Queue"
            onclick='window.location="{$_SERVER['PHP_SELF']}?clear=clear"'/></p>
  </form>
  </div>

  <div>
  <p>Double check the information for each cell, and if it is not correct, 
     please click on one of the buttons to edit it again, or to start over.
     If the queue information is correct, please select the <em>Analysis</em>
     global menu above and choose which type of analysis you would like to
     perform.</p>
  </div>

HTML;

}

else
{
  echo <<<HTML
  <p>Your Queue is currently empty</p>
  <p>Please go back to add one or more experiments into the queue.</p>
  <p><input type='button' value='Select Experiment'
            style='width:12em;' onclick='window.location="queue_setup_1.php";'/>
  </p>
HTML;
}

?>

</div>

<?php

include 'bottom.php';
exit();

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

/*
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
*/

// Get the noise files
function get_noise( $noiseIDs )
{
  if ( empty( $noiseIDs ) )
    return( "" );

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
