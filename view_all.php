<?php
/*
 * view_participants.php
 *
 * Display the entire people table
 *
 */
include_once 'checkinstance.php';

if ( !isset($_SESSION['userlevel']) ||
     ( ($_SESSION['userlevel'] != 0) &&
       ($_SESSION['userlevel'] != 3) &&
       ($_SESSION['userlevel'] != 4) &&
       ($_SESSION['userlevel'] != 5) ) )   // Super user, admin and super admin only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
// ini_set('display_errors', 'On');


// Start displaying page
$page_title = 'View Users';
$js = 'js/export.js,js/sorttable.js';
include 'header.php';
?>
<div id='content'>

  <h1 class='title'>View Users</h1>

<?php
// Display a table
$table = create_table($link);
echo $table;

$_SESSION['print_title'] = "LIMS Users";
$_SESSION['print_text']  = $table;

?>
  </div>
<?php
include 'footer.php';
exit();

// Function to create a table of all records
function create_table($link)
{
  $querywhere = "";
  if ( $_SESSION['userlevel'] == 0 ) {
      $querywhere = "WHERE userlevel <= 3 ";
  }

  $query  = "SELECT personID, lname, fname, " .
            "organization, address, city, state, zip, country, " .
            "phone, email " .
            "FROM people " .
            $querywhere .
            "ORDER BY lname, fname ";
  $result = mysqli_query($link, $query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));

  $table = <<<HTML
  <table cellspacing='0' cellpadding='7' class='style1 sortable width_c_95_p__s_' >
    <thead>
      <tr>
          <th>Name</th>
          <th>Organization</th>
          <th>Phone</th>
          <th>Email</th>
      </tr>
    </thead>
    <tfoot>
      <tr><td colspan='5'><input type='button' value='Print Version'
                                 data-test-1='xyz'
                                 class='onclick-print-version' /></td></tr>
    </tfoot>

    <tbody>
HTML;

  while ( $row = mysqli_fetch_array($result) )
  {
    foreach ($row as $key => $value)
    {
      $$key = (empty($value)) ? "" : html_entity_decode(stripslashes( nl2br($value)) );
    }

    $table .= <<<HTML
      <tr>
          <td><a href='view_users.php?personID=$personID'>$lname, $fname</a></td>
          <td>$organization<br />
              $address<br />
              $city, $state $zip<br />
              $country</td>
          <td>$phone</td>
          <td>$email</td>
      </tr>
HTML;

  }

  $table .= <<<HTML
    </tbody>
  </table>

HTML;

  return $table;
}

?>
