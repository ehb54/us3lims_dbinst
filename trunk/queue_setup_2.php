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

// Get posted information
if ( isset( $_POST['submitter_email'] ) )
{
  $new_email = trim( $_POST['submitter_email'] );

  // Replace blanks with commas, in case user added others
  $new_email = preg_replace ( "/,/", ',', $new_email);
  $new_email = preg_replace ( "/\s+/", ',', $new_email);
  $new_email = preg_replace ( "/,+/", ',', $new_email);
  $new_email = preg_replace ( "/^,$/", '', $new_email);
}

else if ( isset( $_SESSION['submitter_email'] ) )
  $new_email = $_SESSION['submitter_email'];

if ( empty( $new_email ) )
{
  // Must have, or can't proceed
  $_SESSION['message1'] = "** Email address was missing";
}

// Get experiment ID
$new_expID = 0;
if ( isset( $_POST['experiments'] ) && ( $_POST['experiments'] != null ) )
{
  $new_expID = $_POST['experiments'];
}

else if ( isset( $_SESSION['experiments'] ) )
{
  $new_expID = $_SESSION['experiments'];
}

if ( $new_expID == 0 )
{
  // Another must have
  $_SESSION['message2'] = "** You must choose an experiment before proceeding";
}

if ( isset( $_SESSION['message1'] ) || isset( $_SESSION['message2'] ) )
{
  header("Location: queue_setup_1.php");
  exit();
}

// Ok, input parameters are here, so we can proceed
$_SESSION['submitter_email'] = $new_email;
$_SESSION['experimentID']    = $new_expID;

if ( isset($_POST['add_owner']) )
{
  $query  = "SELECT email FROM people " .
            "WHERE peopleID = {$_SESSION['id']} ";
  $result = mysql_query($query)
            or die("Query failed : $query<br />" . mysql_error());
  list($owner_email) = mysql_fetch_array($result);

  // Let's double check that the owner email isn't already there
  $pos = strpos($_SESSION['submitter_email'], $owner_email);
  if ($pos === false)       // have to use === to find the string in position 0 too
    $_SESSION['submitter_email'] .= ",$owner_email";
}

// Start displaying page
$page_title = "Queue Setup (part 2)";
$css = 'css/queue_setup.css';
include 'top.php';
include 'links.php';
include 'lib/payload_manager.php';

$payload = new payload_manager( $_SESSION );

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Queue Setup (part 2)</h1>

<?php

// Get a list of cells (auc files) in experiment
$query  = "SELECT rawDataID, runID, rawData.label " .
          "FROM   rawData, experiment " .
          "WHERE  rawData.experimentID = {$_SESSION['experimentID']} " .
          "AND    rawData.experimentID = experiment.experimentID ";
$result = mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());

$rawData_list = "<select name='experiments[]' multiple='multiple'>\n" .
                   "  <option value='null'>Select an experiment...</option>\n";
while ( list( $rawDataID, $runID, $label ) = mysql_fetch_array( $result ) )
  $rawData_list .= "  <option value='$rawDataID'>$runID $label</option>\n";

$rawData_list .= "</select>\n";

echo <<<HTML
  <form action="queue_setup_3.php" method="post">
  <fieldset>
    <legend>Select Cells to Process</legend>
    <p>Select the cells you wish to process.<br />
    <em>You can select multiple cells at once.</em></p>

      <p>$rawData_list</p>

      <p><input type="button" value="Select Different Experiment"
             onclick='window.location="queue_setup_1.php";' />
      <input type="submit" value="Add to Queue"/></p>
  </fieldset>

  </form>

HTML;

motd_submit(); 

?>

</div>

<?php
include 'bottom.php';
exit();
?>
