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

if ( isset( $_GET['reset'] ) )
{
  unset( $_SESSION['new_submitter'] );
  unset( $_SESSION['add_owner'] );
  unset( $_SESSION['new_expID'] );
  unset( $_SESSION['new_cells'] );

  header( "Location: {$_SERVER[PHP_SELF]}" );
  exit();
}

include 'config.php';
include 'db.php';

// Reset multiple dataset processing for later
unset( $_SESSION['separate_datasets'] );

// Get posted information, if any
// Reset submitter email address, in case previous experiment had different owner, etc.
$submitter_email = $_SESSION['email'];
if ( isset( $_POST['submitter_email'] ) )
{
  $submitter_email = $_POST['submitter_email'];
}

else if ( isset( $_SESSION['new_submitter'] ) )
{
  $submitter_email = $_SESSION['new_submitter'];
}

$add_owner = ( isset( $_POST['add_owner'] ) ) ? 1 : 0;

$experimentID = 0;
if ( isset( $_POST['expID'] ) )
{
  $experimentID = $_POST['expID'];
}

else if ( isset( $_SESSION['new_expID'] ) )
{
  $experimentID = $_SESSION['new_expID'];
}

// Let's see if we should go to the next page
if ( isset( $_POST['next'] ) )
{
  $_SESSION['new_submitter'] = $submitter_email;
  $_SESSION['add_owner']     = $add_owner;
  $_SESSION['new_expID']     = $experimentID;

  // Extract rawDataID and filename from cells[]
  $_SESSION['new_cells'] = array();
  foreach( $_POST['cells'] as $cell )
  {
    list( $rawDataID, $filename ) = explode( ":", $cell );
    $_SESSION['new_cells'][$rawDataID] = $filename;
  }

  header( "Location: queue_setup_2.php" );
  exit();
}

// Start displaying page
$page_title = "Queue Setup (part 1)";
$css = 'css/queue_setup.css';
include 'top.php';
include 'links.php';
include 'lib/motd.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Queue Setup (part 1)</h1>

<?php
// Verify that submission is ok now
motd_block();

// If we are here, either userlevel is 4 or it's not blocked

$email_text      = get_email_text();
$experiment_text = get_experiment_text();
$cell_text       = get_cell_text();
$submit_text     = "<p style='padding-bottom:3em;'></p>\n";  // a spacer
if ( $experimentID != 0 )
{
  $submit_text = <<<HTML
  <p><input type='button' value='Select Different Experiment'
            onclick='window.location="{$_SERVER[PHP_SELF]}?reset=true";' /> 
     <input type='submit' name='next' value='Add to Queue'/>
  </p>

HTML;
}

echo <<<HTML
  <form action="{$_SERVER[PHP_SELF]}" method="post">
    <fieldset>
      <legend>Initial Queue Setup</legend>

      $email_text
      $experiment_text
      $cell_text

    </fieldset>

    $submit_text

  </form>

HTML;

motd_submit(); 

?>

</div>

<?php
include 'bottom.php';
exit();

function get_email_text()
{
  global $submitter_email, $add_owner;

  $msg1  = "";
  $msg1a = "";
  if ( isset( $_SESSION['message1'] ) )
  {
    $msg1  = "<p class='message'>{$_SESSION['message1']}</p>";
    $msg1a = "<span class='message'>*</span>";
    unset( $_SESSION['message1'] );
  }

  // Check if current user is the data owner
  $checked = ( $add_owner == 1 ) ? " checked='checked'" : "";
  if ( $_SESSION['loginID'] != $_SESSION['id'] )
  {
    $copy_owner = "<input type='checkbox' name='add_owner'$checked />\n" .
                  "  <label for='add_owner'>Add e-mail address of data owner?</label>\n";
  }

  $text = <<<HTML
        <p>Please enter the following information so
        we can track your queue.<p>

        <p>Enter the email address you would like notifications sent to:</p>
        <p>$msg1a<input type="text" name="submitter_email"
                  value="$submitter_email"/>
           $copy_owner
        </p>
        $msg1

HTML;

  return( $text );
}

function get_experiment_text()
{
  global $experimentID;

  // Get a list of experiments
  $query  = "SELECT experimentID, runID " .
            "FROM   projectPerson, project, experiment " .
            "WHERE  projectPerson.personID = {$_SESSION['id']} " .
            "AND    project.projectID = projectPerson.projectID " .
            "AND    experiment.projectID = project.projectID ";
  $result = mysql_query( $query )
            or die("Query failed : $query<br />\n" . mysql_error());

  $experiment_list = "<select id='expID' name='expID'" .
                     "  onchange='this.form.submit();'>\n" .
                     "  <option value='null'>Select the run ID...</option>\n";
  while ( list( $expID, $runID ) = mysql_fetch_array( $result ) )
  {
    $selected = ( $expID == $experimentID )
              ? " selected='selected'"
              : "";
    $experiment_list .= "  <option value='$expID'$selected>$runID</option>\n";
  }

  $experiment_list .= "</select>\n";

  $msg2  = "";
  $msg2a = "";
  if ( isset( $_SESSION['message2'] ) )
  {
    $msg2  = "<p class='message'>{$_SESSION['message2']}</p>";
    $msg2a = "<span class='message'>**</span>";
    unset( $_SESSION['message2'] );
  }

  $text = <<<HTML
      <p>Select the UltraScan experiment (run ID) you would like to add to the Analysis Queue.</p>
      <p>$msg2a $experiment_list</p>
      $msg2

HTML;

  return( $text );
}

function get_cell_text()
{
  global $experimentID;

  if ( $experimentID == 0 )
  {
    $rawData_list = "<select name='cells[]' multiple='multiple'>\n" .
                       "  <option value='null'>Select runID first...</option>\n";
    $rawData_list .= "</select>\n";
  }

  else
  {
    // We have a legit experimentID, so let's get a list of cells 
    //  (auc files) in experiment
    $query  = "SELECT rawDataID, runID, filename " .
              "FROM   rawData, experiment " .
              "WHERE  rawData.experimentID = $experimentID " .
              "AND    rawData.experimentID = experiment.experimentID ";
    $result = mysql_query( $query )
              or die("Query failed : $query<br />\n" . mysql_error());
  
    $rawData_list = "<select name='cells[]' multiple='multiple'>\n" .
                       "  <option value='null'>Select cells...</option>\n";
    while ( list( $rawDataID, $runID, $filename ) = mysql_fetch_array( $result ) )
      $rawData_list .= "  <option value='$rawDataID:$filename'>$runID $filename</option>\n";
  
    $rawData_list .= "</select>\n";
  }

  $msg3  = "";
  $msg3a = "";
  if ( isset( $_SESSION['message3'] ) )
  {
    $msg3  = "<p class='message'>{$_SESSION['message3']}</p>";
    $msg3a = "<span class='message'>***</span>";
    unset( $_SESSION['message3'] );
  }

  $text = <<<HTML
      <p>Select the cells you wish to process.<br />
      <em>You can select multiple cells at once.</em></p>

      <p>$msg3a $rawData_list</p>
      $msg3

HTML;

  return( $text );
}
?>
