<?php
/*
 * admin_view_projects.php
 *
 * Display all the projects for an arbitrary user, allowing individual records to be edited
 *
 */
include_once 'checkinstance.php';

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
// ini_set('display_errors', 'On');


// Are we being directed here from a push button?
if (isset($_POST['new']))
{
  do_new($link);
  exit();
}

// Start displaying page
$page_title = 'View User projects';
$js  = 'js/export.js,js/sorttable.js';
include 'header.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class='title'><?php echo $page_title; ?></h1>
  <!-- Place page content here -->

<?php
  if ( isset( $_POST['personID'] ) )
  {
    $_SESSION['currentID'] = $_POST['personID'];

    $text  = person_project_select( $link, 'personID', $_POST['personID'] );
    $text .= project_info( $link, $_POST['personID'] );
  }

  else if ( isset( $_SESSION['currentID'] ) )
  {
    $text  = person_project_select( $link, 'personID', $_SESSION['currentID'] );
    $text .= project_info( $link, $_SESSION['currentID'] );
  }

  else
    $text  = person_project_select( $link, 'personID' );


  // Display what we have
  echo $text;

  $_SESSION['print_title'] = "LIMS v3 Projects";
  $_SESSION['print_text']  = $text;

?>
</div>

<?php
include 'footer.php';
exit();

// Function to create a new record
function do_new($link)
{
  $ID   = $_SESSION['currentID'];
  $uuid = uuid();

  // Insert an ID
  $query  = "INSERT INTO project ( projectGUID ) " .
            "VALUES ( '$uuid' ) ";
  mysqli_query( $link, $query )
        or die( "Query failed : $query<br />\n" . mysqli_error($link) );
  $new = mysqli_insert_id($link);

  // Add the ownership record
  $query  = "INSERT INTO projectPerson SET " .
            "projectID = $new, " .
            "personID  = $ID ";
  mysqli_query( $link, $query )
        or die( "Query failed : $query<br />\n" . mysqli_error($link) );

  header( "Location: admin_edit_projects.php?edit=$new" );
  exit();
}

// Function to create a dropdown for available people
// Only people with projects should show up
function person_project_select( $link, $select_name, $current_ID = NULL )
{
  // Account for people selecting the "Please select" choice
  $current_ID = ( $current_ID == -1 ) ? NULL : $current_ID;

  $query  = "SELECT DISTINCT people.personID, lname, fname " .
            "FROM projectPerson, people " .
            "WHERE projectPerson.personID = people.personID " .
            "ORDER BY lname, fname ";
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />" . mysqli_error($link) );

  if ( mysqli_num_rows( $result ) == 0 ) return "";

  $text = "<form action='{$_SERVER['PHP_SELF']}' method='post'>\n" .
          "  <select name='$select_name' size='1' class='onchange-form-submit' >\n" .
          "    <option value='-1'>Please select...</option>\n";
  while ( list( $personID, $lname, $fname ) = mysqli_fetch_array( $result ) )
  {
    $selected = ( $current_ID == $personID ) ? " selected='selected'" : "";
    $text .= "    <option value='$personID'$selected>$lname, $fname</option>\n";
  }

  $text .= "  </select>\n" .
           "</form>\n";

  return $text;
}

// Function to display a table of all records
function project_info( $link, $personID )
{
  $query  = "SELECT p.projectID, description, goals, status, lastUpdated " .
            "FROM projectPerson p, project j " .
            "WHERE p.personID = ? " .
            "AND p.projectID = j.projectID " .
            "ORDER BY lastUpdated DESC ";

  /* This code was replaced by the prepared statement
  $query  = "SELECT p.projectID, description, goals, status, lastUpdated " .
            "FROM projectPerson p, project j " .
            "WHERE p.personID = $personID " .
            "AND p.projectID = j.projectID " .
            "ORDER BY description ";
  $result = mysqli_query($link, $query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));
  */

  $table = <<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post" >
  <table cellspacing='0' cellpadding='7' class='style1 sortable width_c_95_p__s_' id='fixed' >
    <thead>
      <tr>
          <th class='width_c__15_p__s_'>Description</th>
          <th class='width_c__70_p__s_'>Goals</th>
          <th class='width_c__11_p__s_'>Status</th>
          <th class='width_c__12_p__s_'>Last Updated</th>
      </tr>
    </thead>

    <tfoot>
      <tr><td colspan='5'><input type='submit' name='new' value = 'New' />
                          <input type='button' value='Print Version'
                                 class='onclick-print-version' /></td></tr>
    </tfoot>

    <tbody>
HTML;

  /* This code was replaced by the prepared statement
  while ( $row = mysqli_fetch_array($result) )
  {
    foreach ($row as $key => $value)
    {
      $$key = (empty($value)) ? "&nbsp;" : stripslashes( nl2br($value) );
    }}
  */

  // Prepared statement
  if ($stmt = mysqli_prepare($link, $query)) {
   $stmt->bind_param('i',$personID);
   $stmt->execute();
   $stmt->store_result();
   $num_of_rows = $stmt->num_rows;

   $stmt->bind_result($p_projectID, $description, $goals, $status, $lastUpdated);

   while($stmt->fetch()){
    $description = empty($description) ? "Unnamed Project" : $description;
    $table .= <<<HTML
          <tr>
              <td><a href='admin_edit_projects.php?ID=$p_projectID'>$description</a></td>
              <td>$goals</td>
              <td>$status</td>
              <td>$lastUpdated</td>
          </tr>
HTML;
   }

   $stmt->free_result();
   $stmt->close();
 }

  $table .= <<<HTML
    </tbody>
  </table>
  </form>
HTML;

  return $table;
}

?>
