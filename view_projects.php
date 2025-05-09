<?php
/*
 * view_projects.php
 *
 * Display the entire project table, allowing individual records to be edited
 *
 */
include_once 'checkinstance.php';

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
include 'lib/utility.php';

ini_set('display_errors', 'On');

// Are we being directed here from a push button?
if (isset($_POST['new']))
{
  do_new( $link );
  exit();
}

// Start displaying page
$page_title = 'View My Projects';
$js  = 'js/export.js,js/sorttable.js';
include 'header.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class='title'><?php echo $page_title; ?></h1>
  <!-- Place page content here -->

<?php
// Display a table
$table = create_table( $link );
echo $table;

$_SESSION['print_title'] = "LIMS v3 Projects";
$_SESSION['print_text']  = $table;

?>
</div>

<?php
include 'footer.php';
exit();

// Function to create a new record
function do_new( $mysqli_con )
{
  $ID   = $_SESSION['id'];
  $uuid = uuid();

  // Insert an ID
  $query  = "INSERT INTO project ( projectGUID ) " .
            "VALUES ( '$uuid' ) ";
  mysqli_query( $mysqli_con, $query )
        or die( "Query failed : $query<br />\n" . mysqli_error( $mysqli_con ) );
  $new = mysqli_insert_id( $mysqli_con );

  // Add the ownership record
  $query  = "INSERT INTO projectPerson SET " .
            "projectID = $new, " .
            "personID  = $ID ";
  mysqli_query( $mysqli_con, $query )
        or die( "Query failed : $query<br />\n" . mysqli_error( $mysqli_con ) );

  header( "Location: edit_projects.php?edit=$new" );
  exit();
}

// Function to display a table of all records
function create_table( $mysqli_con )
{
  $ID   = $_SESSION['id'];

  $query  = "SELECT p.projectID, description, goals, status, lastUpdated " .
            "FROM projectPerson p, project j " .
            "WHERE p.personID = $ID " .
            "AND p.projectID = j.projectID " .
            "ORDER BY lastUpdated DESC ";
  $result = mysqli_query( $mysqli_con, $query )
        or die( "Query failed : $query<br />\n" . mysqli_error( $mysqli_con ));

  $table = <<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post" >
  <table cellspacing='0' cellpadding='7' class='style1 sortable w-95p' id='fixed' >
    <thead>
      <tr>
          <th class='w-15p'>Description</th>
          <th class='w-70p'>Goals</th>
          <th class='w-11p'>Status</th>
          <th class='w-12p'>Last Updated</th>
      </tr>
    </thead>

    <tfoot>
      <tr><td colspan='5'><input type='submit' name='new' value = 'New' />
                          <input type='button' value='Print Version'
                                 class='onclick-print-version' /></td></tr>
    </tfoot>

    <tbody>
HTML;

  while ( $row = mysqli_fetch_array( $result ) )
  {
    foreach ($row as $key => $value)
    {
      $$key = (empty($value)) ? "&nbsp;" : stripslashes( nl2br($value) );
    }

    $description = ( $description == "&nbsp;" ) ? "Unnamed Project" : $description;

$table .= <<<HTML
      <tr>
          <td><a href='edit_projects.php?ID=$projectID'>$description</a></td>
          <td>$goals</td>
          <td>$status</td>
          <td>$lastUpdated</td>
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
