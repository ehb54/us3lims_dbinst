<?php
/*
 * admin_view_projects.php
 *
 * Display all the projects for an arbitrary user, allowing individual records to be edited
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
if (isset($_POST['new']))
{
  do_new();
  exit();
}

// Start displaying page
$page_title = 'View User Projects';
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

    $text  = person_project_select( 'personID', $_POST['personID'] );
    $text .= project_info( $_POST['personID'] );
  }

  else if ( isset( $_SESSION['currentID'] ) )
  {
    $text  = person_project_select( 'personID', $_SESSION['currentID'] );
    $text .= project_info( $_SESSION['currentID'] );
  }

  else
    $text  = person_project_select( 'personID' );


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
function do_new()
{
  $ID   = $_SESSION['currentID'];
  $uuid = uuid();

  // Insert an ID
  $query  = "INSERT INTO project ( projectGUID ) " .
            "VALUES ( '$uuid' ) ";
  mysql_query( $query )
        or die( "Query failed : $query<br />\n" . mysql_error() );
  $new = mysql_insert_id();

  // Add the ownership record
  $query  = "INSERT INTO projectPerson SET " .
            "projectID = $new, " .
            "personID  = $ID ";
  mysql_query( $query )
        or die( "Query failed : $query<br />\n" . mysql_error() );

  header( "Location: admin_edit_projects.php?edit=$new" );
  exit();
}

// Function to create a dropdown for available people
// Only people with projects should show up
function person_project_select( $select_name, $current_ID = NULL )
{
  $query  = "SELECT DISTINCT people.personID, lname, fname " .
            "FROM projectPerson, people " .
            "WHERE projectPerson.personID = people.personID " .
            "ORDER BY lname, fname ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />" . mysql_error() );

  if ( mysql_num_rows( $result ) == 0 ) return "";

  $text = "<form action='{$_SERVER['PHP_SELF']}' method='post'>\n" .
          "  <select name='$select_name' size='1' onchange='form.submit();'>\n";
  while ( list( $personID, $lname, $fname ) = mysql_fetch_array( $result ) )
  {
    $selected = ( $current_ID == $personID ) ? " selected='selected'" : "";
    $text .= "    <option value='$personID'$selected>$lname, $fname</option>\n";
  }

  $text .= "  </select>\n" .
           "</form>\n";

  return $text;
}

// Function to display a table of all records
function project_info( $personID )
{
  $query  = "SELECT p.projectID, description, goals, status " .
            "FROM projectPerson p, project j " .
            "WHERE p.personID = $personID " .
            "AND p.projectID = j.projectID " .
            "ORDER BY description ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $table = <<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post" >
  <table cellspacing='0' cellpadding='7' class='style1 sortable' style='width:95%;'>
    <thead>
      <tr>
          <th>Description</th>
          <th>Goals</th>
          <th>Status</th>
      </tr>
    </thead>

    <tfoot>
      <tr><td colspan='5'><input type='submit' name='new' value = 'New' />
                          <input type='button' value='Print Version' 
                                 onclick='print_version();' /></td></tr>
    </tfoot>

    <tbody>
HTML;

  while ( $row = mysql_fetch_array($result) )
  {
    foreach ($row as $key => $value)
    {
      $$key = (empty($value)) ? "&nbsp;" : stripslashes( nl2br($value) );
    }

    $description = ( $description == "&nbsp;" ) ? "Unnamed Project" : $description;

$table .= <<<HTML
      <tr>
          <td><a href='admin_edit_projects.php?edit=$projectID'>$description</a></td>
          <td>$goals</td>
          <td>$status</td>
      </tr>
HTML;

  }

  $table .= <<<HTML
    </tbody>
  </table>
  </form>

HTML;

  return $table;
}

?>
