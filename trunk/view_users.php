<?php
/*
 * view_users.php
 *
 * A place to view the people table
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( ($_SESSION['userlevel'] != 3) &&
     ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) )    // super, admin and super admin only
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

// Start displaying page
$page_title = 'View Users';
$js = 'js/view_users.js';
include 'top.php';
include 'links.php';
?>
<div id='content'>

  <h1 class="title">View Users</h1>
  <?php if ( isset($_SESSION['message']) )
        {
          echo "<p class='message'>{$_SESSION['message']}</p>\n";
          unset($_SESSION['message']);
        } ?>

<?php
// Display a record
display_record();

?>

</div>

<?php
include 'bottom.php';
exit();

// Function to redirect to prior record
function do_prior()
{
  $personID = $_POST['personID'];

  $query  = "SELECT personID FROM people " .
            "ORDER BY lname, fname ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  // Find prior record
  list($current) = mysql_fetch_array($result);
  $prior = null;
  while ($current != NULL && $personID != $current)
  {
    $prior = $current;
    list($current) = mysql_fetch_array($result);
  }

  $redirect = ($prior == null) ? "" : "?personID=$prior";
  header("Location: $_SERVER[PHP_SELF]$redirect");
}

// Function to redirect to next record
function do_next()
{
  $personID = $_POST['personID'];

  $query  = "SELECT personID FROM people " .
            "ORDER BY lname, fname ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  // Find next record
  $current = null;
  while ($personID != $current)
    list($current) = mysql_fetch_array($result);
  list($next) = mysql_fetch_array($result);

  $redirect = ($next == null) ? "?personID=$personID" : "?personID=$next";
  header("Location: $_SERVER[PHP_SELF]$redirect");
}

// Function to display and navigate records
function display_record()
{
  // Find a record to display
  $personID = get_id();
  if ($personID === false)
    return;

  $query  = "SELECT lname, fname, organization, " .
            "address, city, state, zip, country, phone, email " .
            "FROM people " .
            "WHERE personID = $personID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row    = mysql_fetch_array($result, MYSQL_ASSOC);

  foreach ($row as $key => $value)
  {
    $$key = (empty($value)) ? "" : html_entity_decode(stripslashes( $value ));
  }

  $userlevel = $row['userlevel']; // 0 translates to null

  // Populate a list box to allow user to jump to another record
  $nav_listbox =  "<select name='nav_box' id='nav_box' " .
                  "        onchange='get_person(this);' >" .
                  "  <option value='null'>None selected...</option>\n";
  $query  = "SELECT personID, lname, fname FROM people " .
            "ORDER BY lname, fname ";
  $result = mysql_query($query)
            or die("Query failed : $query<br />\n" . mysql_error());
  while (list($t_id, $t_last, $t_first) = mysql_fetch_array($result))
  {
    $t_last   = html_entity_decode( stripslashes($t_last)  );
    $t_first  = html_entity_decode( stripslashes($t_first) );
    $selected = ($personID == $t_id) ? " selected='selected'" : "";
    $nav_listbox .= "  <option$selected value='$t_id'>$t_last, $t_first</option>\n";
  }
  $nav_listbox .= "</select>\n";

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>View Profile</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'>Jump to: $nav_listbox
                          <input type='submit' name='prior' value='&lt;' />
                          <input type='submit' name='next' value='&gt;' />
                          <input type='hidden' name='personID' value='$personID' />
          </td></tr>
    </tfoot>
    <tbody>
      <tr><th>First Name:</th>
          <td>$fname</td></tr>
      <tr><th>Last Name:</th>
          <td>$lname</td></tr>
      <tr><th>Organization:</th>
          <td>$organization</td></tr>
      <tr><th>Address:</th>
          <td>$address</td></tr>
      <tr><th>City:</th>
          <td>$city</td></tr>
      <tr><th>State (Province):</th>
          <td>$state</td></tr>
      <tr><th>Postal Code (Zip):</th>
          <td>$zip</td></tr>
      <tr><th>Country:</th>
          <td>$country</td></tr>
      <tr><th>Phone:</th>
          <td>$phone</td></tr>
      <tr><th>Email:</th>
          <td>$email</td></tr>
    </tbody>
  </table>
  </form>

  <p><a href='view_all.php'>View All Users</a></p>

HTML;
}

// Function to figure out which record to display
function get_id()
{
  // See if we are being directed to a particular record
  if (isset($_GET['personID']))
  {
    $personID = $_GET['personID'];
    settype( $personID, 'int' );       // Removes any remaining characters in URL
    return( $personID );
  }

  // We don't know which record, so just find the first one
  $query  = "SELECT personID FROM people " .
            "ORDER BY lname, fname " .
            "LIMIT 1 ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  if (mysql_num_rows($result) == 1)
  {
    list($personID) = mysql_fetch_array($result);
    return( $personID );
  }

  // If we're here, there aren't any records
echo<<<HTML
  <form action='{$_SERVER[PHP_SELF]}' method='post'>
  <table cellspacing='0' cellpadding='0' class='style1'>
    <thead>
      <tr><th colspan='2'>View Profile</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'>
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

?>
