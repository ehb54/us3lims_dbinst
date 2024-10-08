<?php
/*
 * edit_projects.php
 *
 * A place to edit/update the project table
 *
 */
include_once 'checkinstance.php';

if ( $_SESSION['userlevel'] < 1 )
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';
// ini_set('display_errors', 'On');


// Are we being directed here from a push button?
if (isset($_POST['prior']))
{
  do_prior( $link );
  exit();
}

else if (isset($_POST['next']))
{
  do_next( $link );
  exit();
}

else if (isset($_POST['new']))
{
  do_new( $link );
  exit();
}

// Are we being directed here from a push button?
if (isset($_POST['update']))
{
  do_update( $link );
  exit();
}

// Start displaying page
$page_title = 'My Projects';
$js = 'js/edit_projects.js';
include 'header.php';
include 'lib/selectboxes.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Edit My Projects</h1>
  <!-- Place page content here -->


<?php
// Edit or display a record
if ( isset($_POST['edit']) || isset($_GET['edit']) )
  edit_record( $link );

else
  display_record( $link );

?>
</div>

<?php
include 'footer.php';
exit();

// Function to redirect to prior record
function do_prior( $link )
{
  $ID = $_SESSION['id'];
  $projectID = $_POST['projectID'];
  // language=MariaDB
  $query  = "SELECT j.projectID " .
            "FROM project j, projectPerson p " .
            "WHERE p.personID = ? " .
            "AND p.projectID = j.projectID " .
            "ORDER BY description ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $ID );
  $stmt->execute()
        or die ("Query failed : $query<br/>" . $stmt->error);
  $result = $stmt->get_result()
          or die ("Query failed : $query<br/>" . $stmt->error);

  // Find prior record
  $current = null;
  list( $current ) = mysqli_fetch_array( $result );
  while ( $current != null  &&  $projectID != $current )
  {
    $prior = $current;
    list( $current ) = mysqli_fetch_array( $result );
  }
  $result->close();
  $stmt->close();

  $redirect = ($prior == null) ? "" : "?ID=$prior";
  header("Location: $_SERVER[PHP_SELF]$redirect");
  exit();
}

// Function to redirect to next record
function do_next( $link )
{
  $ID = $_SESSION['id'];
  $projectID = $_POST['projectID'];
  // language=MariaDB
  $query  = "SELECT j.projectID " .
            "FROM project j, projectPerson p " .
            "WHERE p.personID = ? " .
            "AND p.projectID = j.projectID " .
            "ORDER BY description ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $ID );
  $stmt->execute()
        or die ("Query failed : $query<br/>" . $stmt->error);
  $result = $stmt->get_result()
          or die ("Query failed : $query<br/>" . $stmt->error);

  // Find next record
  $next = null;
  while ($projectID != $next)
    list($next) = mysqli_fetch_array( $result );
  list($next) = mysqli_fetch_array( $result );
  $result->close();
  $stmt->close();

  $redirect = ($next == null) ? "?ID=$projectID" : "?ID=$next";
  header("Location: $_SERVER[PHP_SELF]$redirect");
  exit();
}

// Function to create a new record
function do_new( $link )
{
  $ID = $_SESSION['id'];
  $uuid = uuid();

  // Insert an ID
  $query = "INSERT INTO project ( projectGUID ) " .
           "VALUES ( '$uuid' ) ";
  mysqli_query( $link, $query )
    or die("Query failed : $query<br />\n" . mysqli_error($link));
  $new = mysqli_insert_id( $link );

  // Add the ownership record
  // language=MariaDB
  $query  = "INSERT INTO projectPerson SET " .
            "projectID = ?, " .
            "personID  = ? ";
  $args = [ $new, $ID ];
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'ii', ...$args );
  $stmt->execute()
        or die ("Query failed : $query<br/>" . $stmt->error);
  $stmt->close();

  header("Location: $_SERVER[PHP_SELF]?edit=$new");
  exit();
}

// Function to update the current record
function do_update( $link )
{
  $ID        = $_SESSION['id'];
  $projectID = htmlentities($_POST['projectID']);

  // Since we always send out emails here, and the user could press the
  //  Update button even though nothing has changed, let's check
  // language=MariaDB
  $query  = "SELECT goals, molecules, purity, expense, " .
            "bufferComponents, saltInformation, AUC_questions, " .
            "expDesign, notes, description " .
            "FROM project " .
            "WHERE projectID = ? ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $projectID );
  $stmt->execute()
        or die ("Query failed : $query<br/>" . $stmt->error);
  $result = $stmt->get_result()
          or die ("Query failed : $query<br/>" . $stmt->error);

  $row    = mysqli_fetch_array( $result, MYSQLI_ASSOC );
  $result->close();
  $stmt->close();

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
       $description      == $_POST['description']      )
  {
    header("Location: $_SERVER[PHP_SELF]?ID=$projectID");
    exit();
  }


  // Ok, we're still here, so something has changed. Let's create a diff
  $diff = '';
  $diff .= get_xdiff( $goals,            $_POST['goals'],            "Goals"             );
  $diff .= get_xdiff( $molecules,        $_POST['molecules'],        "Molecules"         );
  $diff .= get_xdiff( $purity,           $_POST['purity'],           "Purity"            );
  $diff .= get_xdiff( $expense,          $_POST['expense'],          "Expense"           );
  $diff .= get_xdiff( $bufferComponents, $_POST['bufferComponents'], "Buffer Components" );
  $diff .= get_xdiff( $AUC_questions,    $_POST['AUC_questions'],    "AUC Questions"     );
  $diff .= get_xdiff( $expDesign,        $_POST['expDesign'],        "Experiment Design" );
  $diff .= get_xdiff( $notes,            $_POST['notes'],            "Notes"             );
  $diff .= get_xdiff( $description,      $_POST['description'],      "Description"       );

  $diff_text = '';
  if ( ! empty( $diff ) )
  {
     $diff_text = <<<HTML
 
 Differences are as follows:
  $diff
HTML;
  }

  // Now update the database with the new information
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
  // language=MariaDB
  $query = "UPDATE project " .
           "SET goals  = ?, " .
           "molecules  = ?, " .
           "purity  = ?, " .
           "expense  = ?, " .
           "bufferComponents  = ?, " .
           "saltInformation  = ?, " .
           "AUC_questions  = ?, " .
           "expDesign = ?, " .
           "notes  = ?, " .
           "description  = ? " .
           "WHERE projectID = ? ";
  $args = [ $goals, $molecules, $purity, $expense, $bufferComponents,
            $saltInformation, $AUC_questions, $expDesign, $notes, $description, $projectID ];
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'ssssssssssi', ...$args );
  $stmt->execute()
        or die("Query failed : $query<br />\n" . $stmt->error);
  $stmt->close();


  // The project is new or has changed, so let's mail the user
  global $org_name, $org_site, $dbname, $admin_email;
  $db_abbrev = substr( $dbname, strrpos( $dbname, "uslims3_" ) + 8 );
 
  $fname   = $_SESSION['firstname'];
  $lname   = $_SESSION['lastname'];
  $email   = $_SESSION['email'] . ",$admin_email";

  $subject = "$lname project ($db_abbrev): {$_POST['description']}";
  $subject = ( strlen( $subject ) > 50 )
           ? ( substr( $subject, 0, 50 ) . '...' )
           : ( $subject );
  $message = "Dear $fname $lname,
  You have entered a new project in your $org_name account at $org_site.
  $diff_text
 
  The complete/new project information is:
 
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

  Sample Handling:
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

// Function to make getting xdiff information a little easier
function get_xdiff( $old, $new, $label )
{
  $a1      = array();
  $a2      = array();
  $a1[ 0 ] = $old;
  $a2[ 0 ] = $new;
  $sdiff   = array_diff( $a1, $a2 );
  if ( empty( $sdiff ) )
     return '';

  $diff    = "- " . $old . "\n+ " . $new . "\n";

  return "$label:\n$diff";
}

// Function to display and navigate records
function display_record( $link )
{
  // Find a record to display
  $projectID = htmlentities( get_id( $link ) );
  if ($projectID === false)
    return;

  // Anything other than a number here is a security risk
  if (!(is_numeric($projectID)))
    return;

  $query  = "SELECT projectGUID, goals, molecules, purity, expense, " .
            "bufferComponents, saltInformation, AUC_questions, expDesign, notes, description, status " .
            "FROM project " .
            "WHERE projectID = ? ";

  // Prepared statement
  if ($stmt = mysqli_prepare( $link, $query ) )
  {
    $stmt->bind_param( 'i', $projectID );
    $stmt->execute();
    $stmt->store_result();
    $num_of_rows = $stmt->num_rows;
    $stmt->bind_result( $projectGUID, $goals, $molecules, $purity, $expense,
                        $bufferComponents, $saltInformation, $AUC_questions,
                        $expDesign, $notes, $description, $status );
    $stmt->fetch();

    $stmt->free_result();
    $stmt->close();
  }

  /* This code was replace by the prepared statement above
  $result = mysqli_query($link,$query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));

  $row    = mysqli_fetch_array($result, MYSQLI_ASSOC);

  // Create local variables; make sure IE displays empty cells properly
  foreach ($row as $key => $value)
  {
    $$key = (empty($value)) ? "&nbsp;" : htmlentities( stripslashes( nl2br($value) ) );
  }
  */

  // $status = $row['status'];
  global $project_status;               // From lib/selectboxes.php
  $status = $project_status[ $status ];

  $ID = $_SESSION['id'];

  // Populate a list box to allow user to jump to another record
  $nav_listbox =  "<select name='nav_box' id='nav_box' " .
                  "        onchange='get_project(this);' >" .
                  "  <option value='null'>None selected...</option>\n";
  $query  = "SELECT j.projectID, description " .
            "FROM project j, projectPerson p " .
            "WHERE p.personID = $ID " .
            "AND p.projectID = j.projectID " .
            "ORDER BY lastUpdated DESC ";
  $result = mysqli_query( $link, $query )
            or die("Query failed : $query<br />\n" . mysqli_error($link));
  while ( list($t_id, $t_description) = mysqli_fetch_array( $result ) )
  {
    $selected = ($projectID == $t_id) ? " selected='selected'" : "";
    $nav_listbox .= "  <option$selected value='$t_id'>$t_description</option>\n";
  }
  $nav_listbox .= "</select>\n";

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'id='fixed'>
    <thead>
      <tr>
        <th style="width: 16%;"></th>
        <th>Edit My Project</th>
      </tr>
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
      <tr><th>Sample Handling:</th>
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
function get_id( $link )
{
  // See if we are being directed to a particular record
  if (isset($_GET['ID']))
    return( $_GET['ID'] );

  $ID = $_SESSION['id'];

  // We don't know which record, so just find the first one
    // language=MariaDB
  $query  = "SELECT projectID " .
            "FROM project j, projectPerson p " .
            "WHERE p.personID = ? " .
            "AND p.projectID = j.projectID " .
            "ORDER BY description " .
            "LIMIT 1 ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $ID );
  $stmt->execute() or die ("Query failed : $query<br/>" . $stmt->error);
  $result = $stmt->get_result() or die ("Query failed : $query<br/>" . $stmt->error);


  if ( mysqli_num_rows( $result ) == 1 )
  {
    list($projectID) = mysqli_fetch_array( $result );
    $result->close();
    $stmt->close();
    return( $projectID );
  }
  $result->close();
    $stmt->close();

  // If we're here, there aren't any records
echo<<<HTML
  <form action='{$_SERVER[PHP_SELF]}' method='post'>
  <table cellspacing='0' cellpadding='0' class='style1'>
    <thead>
      <tr><th colspan='2'>Edit My Projects</th></tr>
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
function edit_record( $link )
{
  // Get the record we need to edit
  if ( isset( $_POST['edit'] ) )
    $projectID = htmlentities($_POST['projectID']);

  else if ( isset( $_GET['edit'] ) )
    $projectID = htmlentities($_GET['edit']);

  else
  {
    // How did we get here?
    echo "<p>There was a problem with the edit request.</p>\n";
    return;
  }

  // Anything other than a number here is a security risk
  if (!(is_numeric($projectID)))
    return;

  $query  = "SELECT goals, molecules, purity, expense, bufferComponents, " .
            "saltInformation, AUC_questions, expDesign, notes, description, status, lastUpdated  " .
            "FROM project " .
            "WHERE projectID = ? ";

  // Prepared statement
  if ( $stmt = mysqli_prepare( $link, $query ) )
  {
    $stmt->bind_param( 'i', $projectID );
    $stmt->execute();
    $stmt->store_result();
    $num_of_rows = $stmt->num_rows;
    $stmt->bind_result( $goals, $molecules, $purity, $expense, $bufferComponents,
                        $saltInformation, $AUC_questions, $expDesign, $notes,
                        $description, $status, $lastUpdated );
    $stmt->fetch();

    $stmt->free_result();
    $stmt->close();
  }

  /* This code was replace by the prepared statement above
  $result = mysqli_query($link,$query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));

  $row = mysqli_fetch_array($result);

  $goals               = htmlentities( stripslashes( $row['goals'] ) );
  $molecules           = htmlentities( stripslashes( $row['molecules'] ) );
  $purity              = htmlentities( stripslashes( $row['purity'] ) );
  $expense             = htmlentities( stripslashes( $row['expense'] ) );
  $bufferComponents    = htmlentities( stripslashes( $row['bufferComponents'] ) );
  $saltInformation     = htmlentities( stripslashes( $row['saltInformation'] ) );
  $AUC_questions       = htmlentities( stripslashes( $row['AUC_questions'] ) );
  $expDesign           = htmlentities( stripslashes( $row['expDesign'] ) );
  $notes               = htmlentities( stripslashes( $row['notes'] ) );
  $description         = htmlentities( stripslashes( $row['description'] ) );

  $status = $row['status'];
  $lastUpdated = $row['lastUpdated'];
  */

  global $project_status;               // From lib/selectboxes.php
  $status = $project_status[ $status ];

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

    <tr><th>Please enter a brief title for your project. Later, you will be
            able to retrieve your project by this description:</th></tr>
    <tr><td><textarea name='description' rows='6' cols='65'
                      wrap='virtual'>$description</textarea></td></tr>
    <tr><th>Please provide a detailed description of your research. Include an
            abstract and explain the goals of your research. We will use this
            this information to optimally design your experiment: </th></tr>
    <tr><td><textarea name='goals' rows='6' cols='65'
                      wrap='virtual'>$goals</textarea></td></tr>
    <tr><th>For each analyte to be measured, please provide a name, appropriate
            molar mass, partial specific volume (if it was measured), and,
            for proteins, the sequence in single-letter code. For each submitted
            sample, please enter a detailed sample description key for the label
            used on each tube: </th></tr>
    <tr><td><textarea name='molecules' rows='6' cols='65'
                      wrap='virtual'>$molecules</textarea></td></tr>
    <tr><th>Please indicate the approximate purity of your sample(s).
            You can express it in percent:</th></tr>
    <tr><td><input type='text' name='purity' size='40'
                   maxlength='10' value='$purity' /></td></tr>
    <tr><th>How much material (volume, concentration) is available for your
            research? Please identify concentration in absorbance units,
            wavelength, or type of fluorescense label and its molar
            concentration. For samples to be measured with UV/visible absorbance
            optics, provide an absorbance scan against buffer from 215-700 nm
            for each sample: </th></tr>
    <tr><td><textarea name='expense' rows='6' cols='65'
                      wrap='virtual'>$expense</textarea></td></tr>
    <tr><th>Please list the molar concentrations of each component in your
            buffer, including reductants and nucleotides. Provide a buffer scan
            against ddH2O from 215-700 nm. To minimize absorbance, we prefer to
            use phosphate or optically pure TRIS. To avoid hydrodynamic
            non-ideality, a minimum salt concentration of 20 mM is desired.
            Please explain if this is not possible. If reductants are required,
            please use TCEP which can be measured at wavelengths > 260 nm:
           </th></tr>
    <tr><td><textarea name='bufferComponents' rows='6' cols='65'
                      wrap='virtual'>$bufferComponents</textarea></td></tr>
    <tr><th>Please indicate the ideal storage conditions (room temperature, 4,
            -20, and/or -80 degrees Celcius), the shelf life of your sample,
            any health/safety concerns (is it toxic if ingested or injected,
            etc.) and, if so, proper care to be taken when working with and
            disposing of sample. State if you would like to have the remaining
            used or unused sample returned to you (you will need to pay for
            return shipment) and indicate how to properly dispose of the sample:
            </th></tr>
    <tr><td><textarea name='saltInformation' rows='6' cols='65'
                      wrap='virtual'>$saltInformation</textarea></td></tr>
    <tr><th>What questions are you trying to answer with AUC? How do you propose
            to approach the research with AUC experiments?</th></tr>
    <tr><td><textarea name='AUC_questions' rows='6' cols='65'
                      wrap='virtual'>$AUC_questions</textarea></td></tr>
    <tr><th>The AUC experimental design:</th></tr>
    <tr><td><textarea name='expDesign' rows='6' cols='65'
                      wrap='virtual'>$expDesign</textarea></td></tr>
    <tr><th>Special instructions, questions, and notes (optional):</th></tr>
    <tr><td><textarea name='notes' rows='6' cols='65'
                      wrap='virtual'>$notes</textarea></td></tr>
    <tr><th>Status:</th></tr>
    <tr><td>$status &nbsp;&nbsp;&nbsp;(last updated: $lastUpdated)</td></tr>


    </tbody>
  </table>
  </form>

HTML;
}

?>
