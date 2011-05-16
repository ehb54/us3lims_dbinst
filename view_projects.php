<?php
/*
 * view_projects.php
 *
 * Display the entire project table, allowing individual records to be edited
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

// Not sure which userlevels yet
/*
if ( ( $_SESSION['userlevel'] != 3 ) &&
     ( $_SESSION['userlevel'] != 4 ) &&
     ( $_SESSION['userlevel'] != 5 ) )  // super, admin and superadmin only
*/
if ( $_SESSION['userlevel'] < 1 )
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';

// Are we being directed here from a push button?
if (isset($_POST['new']))
{
  do_new();
  exit();
}

// Start displaying page
$page_title = 'View My Projects';
$js  = 'js/export.js,js/sorttable.js';
include 'top.php';
include 'links.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class='title'><?php echo $page_title; ?></h1>
  <!-- Place page content here -->

<?php
// Display a table
$table = create_table();
echo $table;

$_SESSION['print_title'] = "LIMS v3 Projects";
$_SESSION['print_text']  = $table;

?>
</div>

<?php
include 'bottom.php';
exit();

// Function to create a new record 
function do_new()
{
  $ID   = $_SESSION['id'];
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

  header( "Location: edit_projects.php?edit=$new" );
  exit();
}

// Function to display a table of all records
function create_table()
{
  $ID   = $_SESSION['id'];

  $query  = "SELECT p.projectID, description, goals, status " .
            "FROM projectPerson p, project j " .
            "WHERE p.personID = $ID " .
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
          <td><a href='edit_projects.php?edit=$projectID'>$description</a></td>
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
