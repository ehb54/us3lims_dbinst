<?php
/*
 * queue_setup_1.php
 *
 * A place to begin queue setup for a supercomputer analysis
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
$page_title = "Queue Setup (part 1)";
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

  <h1 class="title">Queue Setup (part 1)</h1>

<?php
// Verify that submission is ok now
motd_block();

// Reset multiple dataset processing for later
unset( $_SESSION['separate_datasets'] );

// Reset submitter email address, in case previous experiment had different owner, etc.
$_SESSION['submitter_email'] = $_SESSION['email'];

// Reset other variables from previous experiment
unset( $_SESSION['experimentID'] );

// Check if current user is the data owner
if ( $_SESSION['loginID'] != $_SESSION['id'] )
{
  $copy_owner = "<input type='checkbox' name='add_owner' />\n" .
                  "  <label for='add_owner'>Add e-mail address of data owner?</legend></p>\n";
}

// If we are here, either userlevel is 4 or it's not blocked

// Get a list of experiments
$query  = "SELECT experimentID, runID, label " .
          "FROM   projectPerson, project, experiment " .
          "WHERE  projectPerson.personID = {$_SESSION['id']} " .
          "AND    project.projectID = projectPerson.projectID " .
          "AND    experiment.projectID = project.projectID ";
$result = mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());

$experiment_list = "<select name='experiments'>\n" .
                   "  <option value='null'>Select an experiment...</option>\n";
while ( list( $experimentID, $runID, $label ) = mysql_fetch_array( $result ) )
  $experiment_list .= "  <option value='$experimentID'>$runID $label</option>\n";

$experiment_list .= "</select>\n";

$msg1  = "";
$msg1a = "";
if ( isset( $_SESSION['message1'] ) )
{
  $msg1  = "<p class='message'>{$_SESSION['message1']}</p>";
  $msg1a = "<span class='message'>**</span>";
  unset( $_SESSION['message1'] );
}

$msg2  = "";
$msg2a = "";
if ( isset( $_SESSION['message2'] ) )
{
  $msg2  = "<p class='message'>{$_SESSION['message2']}</p>";
  $msg2a = "<span class='message'>**</span>";
  unset( $_SESSION['message2'] );
}

echo <<<HTML
  <form action="queue_setup_2.php" method="post">
  <fieldset>
    <legend>Initial Queue Setup</legend>
      <p>Please enter the following information so
      we can track your queue.<p>

      <p>Enter the email address you would like notifications sent to:</p>
      <p>$msg1a<input type="text" name="submitter_email"
                value="{$_SESSION['submitter_email']}"/>
         $copy_owner
      </p>
      $msg1
  </fieldset>

  <fieldset>
    <legend>Select UltraScan Experiment</legend>
    <p>Select the experiment you would like to add to the Analysis Queue.</p>
    <p>$msg2a $experiment_list</p>
    $msg2
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
