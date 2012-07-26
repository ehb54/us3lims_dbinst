<?php
/*
 * admin_edit_projects.php
 *
 * A place for the admin to edit/update user projects
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( ( $_SESSION['userlevel'] != 3 ) &&
     ( $_SESSION['userlevel'] != 4 ) &&
     ( $_SESSION['userlevel'] != 5 ) )  // super, admin and superadmin only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';

// Are we being directed here from a push button?
if (isset($_POST['prior']))
{
  do_prior();
  exit();
}

else if (isset($_POST['next']))
{
  do_next();
  exit();
}

else if (isset($_POST['new']))
{
  do_new();
  exit();
}

else if (isset($_POST['update']))
{
  do_update();
  exit();
}

// Start displaying page
$page_title = 'Edit User Projects';
$js = 'js/admin_edit_projects.js';
include 'header.php';
include 'lib/selectboxes.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Edit User Projects</h1>
  <!-- Place page content here -->

<?php
  if ( isset( $_POST['personID'] ) )
  {
    $_SESSION['currentID'] = $_POST['personID'];

    $text  = person_select( 'personID', $_POST['personID'] );
  }

  else if ( isset( $_SESSION['currentID'] ) )
    $text  = person_select( 'personID', $_SESSION['currentID'] );

  else
    $text  = person_select( 'personID' );

  // Display what we have
  echo $text;

  // Edit or display a record
  if ( isset($_POST['edit']) || isset($_GET['edit']) )
    edit_record();

  else
    display_record();

?>
</div>

<?php
include 'footer.php';
exit();

// Function to redirect to prior record
function do_prior()
{
  $ID = $_SESSION['currentID'];
  $projectID = $_POST['projectID'];

  $query  = "SELECT j.projectID " .
            "FROM project j, projectPerson p " .
            "WHERE p.personID = $ID " .
            "AND p.projectID = j.projectID " .
            "ORDER BY description ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  // Find prior record
  $current = null;
  list($current) = mysql_fetch_array($result);
  while ($current != null && $projectID != $current)
  {
    $prior = $current;
    list($current) = mysql_fetch_array($result);
  }

  $redirect = ($prior == null) ? "" : "?ID=$prior";
  header("Location: $_SERVER[PHP_SELF]$redirect");
  exit();
}

// Function to redirect to next record
function do_next()
{
  $ID = $_SESSION['currentID'];
  $projectID = $_POST['projectID'];

  $query  = "SELECT j.projectID " .
            "FROM project j, projectPerson p " .
            "WHERE p.personID = $ID " .
            "AND p.projectID = j.projectID " .
            "ORDER BY description ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  // Find next record
  $next = null;
  while ($projectID != $next)
    list($next) = mysql_fetch_array($result);
  list($next) = mysql_fetch_array($result);

  $redirect = ($next == null) ? "?ID=$projectID" : "?ID=$next";
  header("Location: $_SERVER[PHP_SELF]$redirect");
  exit();
}

// Function to create a new record
function do_new()
{
  $ID = $_SESSION['currentID'];
  $uuid = uuid();

  // Insert an ID
  $query = "INSERT INTO project ( projectGUID ) " .
           "VALUES (' $uuid' ) ";
  mysql_query($query)
    or die("Query failed : $query<br />\n" . mysql_error());
  $new = mysql_insert_id();

  // Add the ownership record
  $query  = "INSERT INTO projectPerson SET " .
            "projectID = $new, " .
            "personID  = $ID ";
  mysql_query( $query )
        or die( "Query failed : $query<br />\n" . mysql_error() );

  header("Location: $_SERVER[PHP_SELF]?edit=$new");
  exit();
}

// Function to update the current record
function do_update()
{
  $projectID = $_POST['projectID'];

  // Since we always send out emails here, and the user could press the 
  //  Update button even though nothing has changed, let's check
  $query  = "SELECT goals, molecules, purity, expense, " .
            "bufferComponents, saltInformation, AUC_questions, " .
            "expDesign, notes, description, status " .
            "FROM project " .
            "WHERE projectID = $projectID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row    = mysql_fetch_array($result, MYSQL_ASSOC);

  // Create local variables
  foreach ($row as $key => $value)
  {
    $$key = (empty($value)) ? "" : $value ;
  }

  if ( $goals            == $_POST['goals']            &&
       $molecules        == $_POST['molecules']        &&
       $purity           == $_POST['purity']           &&
       $expense          == $_POST['expense']          &&
       $bufferComponents == $_POST['bufferComponents'] &&
       $saltInformation  == $_POST['saltInformation']  &&
       $AUC_questions    == $_POST['AUC_questions']    &&
       $expDesign        == $_POST['expDesign']        &&
       $notes            == $_POST['notes']            &&
       $description      == $_POST['description']      &&
       $status           == $_POST['status']           )
  {
    header("Location: $_SERVER[PHP_SELF]?ID=$projectID");
    exit();
  }

  // Ok, we're still here, so something has changed
  $goals               =        addslashes(htmlentities($_POST['goals']));
  $molecules           =        addslashes(htmlentities($_POST['molecules']));
  $purity              = substr(addslashes(htmlentities($_POST['purity'])), 0,10);
  $expense             =        addslashes(htmlentities($_POST['expense']));
  $bufferComponents    =        addslashes(htmlentities($_POST['bufferComponents']));
  $saltInformation     =        addslashes(htmlentities($_POST['saltInformation']));
  $AUC_questions       =        addslashes(htmlentities($_POST['AUC_questions']));
  $expDesign           =        addslashes(htmlentities($_POST['expDesign']));
  $notes               =        addslashes(htmlentities($_POST['notes']));
  $description         =        addslashes(htmlentities($_POST['description']));
  $status              =                                $_POST['status'];

  $query = "UPDATE project " .
           "SET goals  = '$goals', " .
           "molecules  = '$molecules', " .
           "purity  = '$purity', " .
           "expense  = '$expense', " .
           "bufferComponents  = '$bufferComponents', " .
           "saltInformation  = '$saltInformation', " .
           "AUC_questions  = '$AUC_questions', " .
           "expDesign = '$expDesign', " .
           "notes  = '$notes', " .
           "description  = '$description', " .
           "status  = '$status' " .
           "WHERE projectID = $projectID ";

  mysql_query($query)
    or die("Query failed : $query<br />\n" . mysql_error());

  // The project is new or has changed, so let's mail the user
  global $org_name, $org_site, $admin_email;
  $site_abbrev = substr( $org_site, strrpos( $org_site, "uslims3_" ) + 8 );

  $subject = "Your $site_abbrev project";

  // We have to get info about the project owner, not ourselves here
  $query  = "SELECT lname, fname, email " .
            "FROM projectPerson, people " .
            "WHERE projectID = $projectID " .
            "AND projectPerson.personID = people.personID ";
  $result = mysql_query( $query )
            or die("Query failed : $query<br />\n" . mysql_error());
  list($lname, $fname, $email) = mysql_fetch_array($result);
  $email   .= ",$admin_email";

  $message = "Dear $fname $lname,
  You have entered/updated a project in your $org_name account at $org_site.
  The new project information is:

  Goals:
  {$_POST['goals']}

  Molecules:
  {$_POST['molecules']}

  Purity
  {$_POST['purity']}

  Expense:
  {$_POST['expense']}

  Buffer Components:
  {$_POST['bufferComponents']}

  Salt Information:
  {$_POST['saltInformation']}

  AUC Questions:
  {$_POST['AUC_questions']}

  Experiment Design:
  {$_POST['expDesign']}

  Notes:
  {$_POST['notes']}

  Description:
  {$_POST['description']}

  Please save this message for your reference.
  Thanks!
  The $org_name Admins.

  This is an automated response, do not reply!";

  LIMS_mailer($email, $subject, $message);

  header("Location: $_SERVER[PHP_SELF]?ID=$projectID");
  exit();
}

// Function to display and navigate records
function display_record()
{
  // Find a record to display
  $projectID = get_id();
  if ($projectID === false)
    return;

  $query  = "SELECT projectGUID, goals, molecules, purity, expense, " .
            "bufferComponents, saltInformation, AUC_questions, expDesign, notes, description, status " .
            "FROM project " .
            "WHERE projectID = $projectID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row    = mysql_fetch_array($result, MYSQL_ASSOC);

  // Create local variables; make sure IE displays empty cells properly
  foreach ($row as $key => $value)
  {
    $$key = (empty($value)) ? "&nbsp;" : html_entity_decode( stripslashes( nl2br($value) ) );
  }

  global $project_status;               // From lib/selectboxes.php
  $status = $project_status[ $status ];

  $ID = $_SESSION['currentID'];

  // Populate a list box to allow user to jump to another record
  $nav_listbox =  "<select name='nav_box' id='nav_box' " .
                  "        onchange='get_project(this);' >" .
                  "  <option value='null'>None selected...</option>\n";
  $query  = "SELECT j.projectID, description " .
            "FROM project j, projectPerson p " .
            "WHERE p.personID = $ID " .
            "AND p.projectID = j.projectID " .
            "ORDER BY description ";
  $result = mysql_query($query)
            or die("Query failed : $query<br />\n" . mysql_error());
  while (list($t_id, $t_description) = mysql_fetch_array($result))
  {
    $selected = ($projectID == $t_id) ? " selected='selected'" : "";
    $nav_listbox .= "  <option$selected value='$t_id'>$t_description</option>\n";
  }
  $nav_listbox .= "</select>\n";

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Edit User Projects</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'>Jump to: $nav_listbox
                          <input type='submit' name='prior' value='&lt;' />
                          <input type='submit' name='next' value='&gt;' />
                          <input type='submit' name='new' value='New' />
                          <input type='submit' name='edit' value='Edit' />
                          <input type='hidden' name='projectID' value='$projectID' />
          </td></tr>
    </tfoot>
    <tbody>
      <tr><th>Project Description:</th>
          <td>$description</td></tr>
      <tr><th>Project GUID:</th>
          <td>$projectGUID</td></tr>
      <tr><th>Goals:</th>
          <td>$goals</td></tr>
      <tr><th>Molecules:</th>
          <td>$molecules</td></tr>
      <tr><th>Purity:</th>
          <td>$purity</td></tr>
      <tr><th>Expense:</th>
          <td>$expense</td></tr>
      <tr><th>Buffer Components:</th>
          <td>$bufferComponents</td></tr>
      <tr><th>Salt Information:</th>
          <td>$saltInformation</td></tr>
      <tr><th>AUC Questions:</th>
          <td>$AUC_questions</td></tr>
      <tr><th>Experimental Design:</th>
          <td>$expDesign</td></tr>
      <tr><th>Notes:</th>
          <td>$notes</td></tr>
      <tr><th>Status:</th>
          <td>$status</td></tr>
    </tbody>
  </table>
  </form>

HTML;
}

// Function to figure out which record to display
function get_id()
{
  // See if we are being directed to a particular record
  if (isset($_GET['ID']))
    return( $_GET['ID'] );

  $ID = $_SESSION['currentID'];

  // We don't know which record, so just find the first one
  $query  = "SELECT p.projectID " .
            "FROM project j, projectPerson p " .
            "WHERE p.personID = $ID " .
            "AND p.projectID = j.projectID " .
            "ORDER BY description " .
            "LIMIT 1 ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  if (mysql_num_rows($result) == 1)
  {
    list($projectID) = mysql_fetch_array($result);
    return( $projectID );
  }

  // If we're here, there aren't any records
echo<<<HTML
  <form action='{$_SERVER[PHP_SELF]}' method='post'>
  <table cellspacing='0' cellpadding='0' class='style1'>
    <thead>
      <tr><th colspan='2'>Edit User Projects</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='new' value='New' />
          </td></tr>
    </tfoot>
    <tbody>
      <tr><th>Status:</th>
          <td>There are no records to display</td></tr>
    </tbody>
  </table>
  </form>

HTML;

  return( false );
}

// Function to edit a record
function edit_record()
{
  // Get the record we need to edit
  if ( isset( $_POST['edit'] ) )
    $projectID = $_POST['projectID'];

  else if ( isset( $_GET['edit'] ) )
    $projectID = $_GET['edit'];

  else
  {
    // How did we get here?
    echo "<p>There was a problem with the edit request.</p>\n";
    return;
  }

  $query  = "SELECT goals, molecules, purity, expense, bufferComponents, " .
            "saltInformation, AUC_questions, expDesign, notes, description, status  " .
            "FROM project " .
            "WHERE projectID = $projectID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row = mysql_fetch_array($result);

  $goals               = html_entity_decode( stripslashes( $row['goals'] ) );
  $molecules           = html_entity_decode( stripslashes( $row['molecules'] ) );
  $purity              = html_entity_decode( stripslashes( $row['purity'] ) );
  $expense             = html_entity_decode( stripslashes( $row['expense'] ) );
  $bufferComponents    = html_entity_decode( stripslashes( $row['bufferComponents'] ) );
  $saltInformation     = html_entity_decode( stripslashes( $row['saltInformation'] ) );
  $AUC_questions       = html_entity_decode( stripslashes( $row['AUC_questions'] ) );
  $expDesign           = html_entity_decode( stripslashes( $row['expDesign'] ) );
  $notes               = html_entity_decode( stripslashes( $row['notes'] ) );
  $description         = html_entity_decode( stripslashes( $row['description'] ) );

  $status      = $row['status'];
  $status_text = project_status_select( 'status', $status );

  // Let's see if we're coming from the New Record or the Update button
  $rec_status = ( isset( $_GET['edit'] ) )
              ? "<input type='hidden' name='rec_status' value='new' />"
              : "<input type='hidden' name='rec_status' value='update' />";

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post">
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Edit $description</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='update' value='Update' />
                          <input type='hidden' name='projectID' value='$projectID' />
                          $rec_status
                          <input type='reset' /></td></tr>
    </tfoot>
    <tbody>


    <tr><th>Please enter a title for your project. Later, you will be able to 
            retrieve your project by this description:</th></tr>
    <tr><td><textarea name='description' rows='6' cols='65' 
                      wrap='virtual'>$description</textarea></td></tr>
    <tr><th>Please provide a detailed description of your research. Include an 
            introduction to your research project and explain the goals of your 
            research. We will use this information to optimally design your 
            experiment. Please provide enough background so we can assess the 
            biological significance of this research:</th></tr>
    <tr><td><textarea name='goals' rows='6' cols='65' 
                      wrap='virtual'>$goals</textarea></td></tr>
    <tr><th>What proteins/DNA molecules are involved in the research and what 
            are their approximate molecular weights?</th></tr>
    <tr><td><textarea name='molecules' rows='6' cols='65' 
                      wrap='virtual'>$molecules</textarea></td></tr>
    <tr><th>Please indicate the approximate purity of your sample(s). You can 
            express it in percent:</th></tr>
    <tr><td><input type='text' name='purity' size='40'
                   maxlength='10' value='$purity' /></td></tr>
    <tr><th>Would the expense of providing 5 ml at 1 OD 280 concentration of 
            your sample be acceptable? If not, what amount would you feel 
            comfortable with? Elaborate if necessary:</th></tr>
    <tr><td><textarea name='expense' rows='6' cols='65' 
                      wrap='virtual'>$expense</textarea></td></tr>
    <tr><th>What buffers do you plan to use? Is phosphate or MOPS buffer an option?<br />
            To minimize absorbance we prefer to run phosphate or MOPS buffers in 
            low concentration (~ 5-10 mM). Salts also absorb and should be kept 
            to a minimum, although a certain ionic strength (25-50 mM) is desired 
            to aid with the hydrodynamic ideality behavior.<br /><br />

            Do you need to have drugs in your sample, such as reductants and 
            nucleotide analogs?<br />
            Please list all components in your buffer. If reductants are required 
            it is essential that you use TCEP, which can be used at 280 nm, but 
            not lower wavelengths.<br /><br />

            Please list all buffer components:</th></tr>
    <tr><td><textarea name='bufferComponents' rows='6' cols='65' 
                      wrap='virtual'>$bufferComponents</textarea></td></tr>
    <tr><th>Is a salt concentration between 20-50 mM for your experiment 
            acceptable? If not, please explain why not.</th></tr>
    <tr><td><textarea name='saltInformation' rows='6' cols='65' 
                      wrap='virtual'>$saltInformation</textarea></td></tr>
    <tr><th>What questions are you trying to answer with AUC? How do you propose 
            to approach the research with AUC experiments?</th></tr>
    <tr><td><textarea name='AUC_questions' rows='6' cols='65' 
                      wrap='virtual'>$AUC_questions</textarea></td></tr>
    <tr><th>Please enter any notes about the experiment design.</th></tr>
    <tr><td><textarea name='expDesign' rows='6' cols='65' 
                      wrap='virtual'>$expDesign</textarea></td></tr>
    <tr><th>Special instructions, questions, and notes (optional):</th></tr>
    <tr><td><textarea name='notes' rows='6' cols='65' 
                      wrap='virtual'>$notes</textarea></td></tr>
    <tr><th>Status:</th></tr>
    <tr><td>$status_text</td></tr>


    </tbody>
  </table>
  </form>

HTML;
}

?>
