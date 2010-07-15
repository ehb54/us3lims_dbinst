<?php
/*
 * queue_setup.php
 *
 * A place to set up the queue for a supercomputer analysis
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

include 'config.php';
include 'db.php';

// Start displaying page
$page_title = "Queue Setup";
$js = 'js/queue_setup.js';
$css = 'css/queue_setup.css';
include 'top.php';
include 'links.php';
include 'lib/payload_manager.php';
include 'lib/motd.php';

$payload = new payload_manager( $_SESSION );

// Clear the payload
$payload->clear();

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Queue Setup</h1>

<?php
// Verify that submission is ok now
motd_block();

// Reset submitter email address, in case previous experiment had different owner, etc.
$_SESSION['submitter_email'] = $_SESSION['email'];

// Check if current user is the data owner
if ( $_SESSION['loginID'] != $_SESSION['id'] )
{
  $copy_owner = "<input type='checkbox' name='add_owner' />\n" .
                  "  <label for='add_owner'>Add e-mail address of data owner?</legend></p>\n";
}

// If we are here, either userlevel is 4 or it's not blocked

// Get a list of experiments
$query  = "SELECT editedData.editedDataID, editedData.label " .
          "FROM projectPerson, project, experiment, rawData, editedData " .
          "WHERE projectPerson.personID = {$_SESSION['id']} " .
          "AND   project.projectID = projectPerson.projectID " .
          "AND   experiment.projectID = project.projectID " .
          "AND   rawData.experimentID = experiment.experimentID " .
          "AND   editedData.rawDataID = rawData.rawDataID ";
$result = mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());

$experiment_list = "<select name='experiments'>\n" .
                   "  <option value='null'>Select an experiment...</option>\n";
while ( list( $editedDataID, $label ) = mysql_fetch_array( $result ) )
  $experiment_list .= "  <option value='$editedDataID'>$label</option>\n";

$experiment_list .= "</select>\n";

echo <<<HTML
  <form action="queue_setup_2.php" method="post">
  <fieldset>
    <legend>Initial Queue Setup</legend>
      <p>Please enter the following information so
      we can track your queue.<p>

      <p>Enter the email address you would like notifications sent to:</p>
      <p><input type="text" name="submitter_email"
                value="{$_SESSION['submitter_email']}"/>
         $copy_owner;
  </fieldset>

  <fieldset>
    <legend>Select UltraScan Experiment</legend>
    <p>Select the experiment you would like to add to the Analysis Queue.</p>
      <p>$experiment_list</p>
  </fieldset>
  <p><input type="submit" value="Next"/></p>

  </form>

HTML;

motd_submit(); 

?>

</div>

<?php
include 'bottom.php';
exit();
?>
