<?php
/*
 * profile.php
 *
 * A place for a user to edit/update his own info
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

include 'config.php';
include 'db.php';
include 'lib/utility.php';

// Are we being directed here from a push button?
if (isset($_POST['update']))
{
  do_update();
  exit();
}

// Start displaying page
$page_title = 'My Profile';
$js = 'js/edit_users.js';
include 'header.php';
?>
<div id='content'>

  <h1 class="title">My Profile</h1>

  <?php if ( isset($_SESSION['message']) )
        {
          echo "<p class='message'>{$_SESSION['message']}</p>\n";
          unset($_SESSION['message']);
        } ?>

<?php
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

// Function to update the current record
function do_update()
{
  $ID  = $_SESSION['id'];
  include 'get_user_info.php';
  $pw1 = trim(substr(addslashes(htmlentities($_POST['pw1'])), 0,128));
  $pw2 = trim(substr(addslashes(htmlentities($_POST['pw2'])), 0,128));

  if ( $pw1 != $pw2 )
    $message .= "--passwords do not match.";

  if ( empty($message) )
  {
    $query = "UPDATE people " .
             "SET lname      = '$lname',       " .
             "fname          = '$fname',      " .
             "organization   = '$organization',   " .
             "address        = '$address',        " .
             "city           = '$city',           " .
             "state          = '$state',          " .
             "zip            = '$zip',            " .
             "country        = '$country',        " .
             "phone          = '$phone',          " .
             "email          = '$email'           ";

    // See if password has changed
    if ( $pw1 )
    {
      $pw = md5($pw1);
      $query .= ", password = '$pw' ";
    }

    $query .= "WHERE personID = $ID ";

    mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

    // Now update the session variables 
    $_SESSION['firstname']    = $fname;
    $_SESSION['lastname']     = $lname;
    $_SESSION['phone']        = $phone;
    $_SESSION['email']        = $email;

    $_SESSION['message'] = "Personal information updated.";
  }

  else
    $_SESSION['message'] = "The following errors were noted:<br />" .
                           $message .
                           "Changes were not recorded.";

  header("Location: {$_SERVER['PHP_SELF']}");
  exit();
}

// Function to display record
function display_record()
{
  $ID = $_SESSION['id'];

  $query  = "SELECT lname, fname, organization, " .
            "address, city, state, zip, country, phone, email " .
            "FROM people " .
            "WHERE personID = $ID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row    = mysql_fetch_array($result, MYSQL_ASSOC);

  foreach ($row as $key => $value)
  {
    $$key = (empty($value)) ? "" : html_entity_decode(stripslashes( $value ));
  }

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Edit My Information</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'>
                          <input type='submit' name='edit' value='Edit' />
                          <input type='hidden' name='personID' value='$ID' />
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

HTML;
}

// Function to edit a record
function edit_record()
{
  $ID = $_SESSION['id'];

  $query  = "SELECT lname, fname, organization, " .
            "address, city, state, zip, country, phone, email " .
            "FROM people " .
            "WHERE personID = $ID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row = mysql_fetch_array($result);

  $lname           = html_entity_decode(stripslashes($row['lname']));
  $fname           = html_entity_decode(stripslashes($row['fname']));
  $organization    = html_entity_decode(stripslashes($row['organization']));
  $address         = html_entity_decode(stripslashes($row['address']));
  $city            = html_entity_decode(stripslashes($row['city']));
  $state           = html_entity_decode(stripslashes($row['state']));
  $zip             = html_entity_decode(stripslashes($row['zip']));
  $country         = html_entity_decode(stripslashes($row['country']));
  $phone           =                                 $row['phone'];
  $email           =                    stripslashes($row['email']);

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post"
        onsubmit="return validate(this);">
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Edit My Information</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='update' value='Update' />
                          <input type='reset' /></td></tr>
    </tfoot>
    <tbody>

    <tr><th>First Name:</th>
        <td><input type='text' name='fname' size='40'
                   maxlength='64' value='$fname' /></td></tr>
    <tr><th>Last Name:</th>
        <td><input type='text' name='lname' size='40'
                   maxlength='64' value='$lname' /></td></tr>
    <tr><th>Organization:</th>
        <td><input type='text' name='organization' size='40'
                   maxlength='128' value='$organization' /></td></tr>
    <tr><th>Address:</th>
        <td><input type='text' name='address' size='40'
                   maxlength='128' value='$address' /></td></tr>
    <tr><th>City:</th>
        <td><input type='text' name='city' size='40'
                   maxlength='64' value='$city' /></td></tr>
    <tr><th>State (Province):</th>
        <td><input type='text' name='state' size='40'
                   maxlength='64' value='$state' /></td></tr>
    <tr><th>Postal Code (Zip):</th>
        <td><input type='text' name='zip' size='40'
                   maxlength='16' value='$zip' /></td></tr>
    <tr><th>Country:</th>
        <td><input type='text' name='country' size='40'
                   maxlength='64' value='$country' /></td></tr>
    <tr><th>Phone:</th>
        <td><input type='text' name='phone' size='40'
                   maxlength='64' value='$phone' /></td></tr>
    <tr><th>Email (required):</th>
        <td><input type='text' name='email' size='40'
                   maxlength='64' value='$email' /></td></tr>
    <tr><th>New Password (or leave blank to leave password unchanged):</th>
        <td><input type='password' name='pw1' size='40'
                   maxlength='128' /></td></tr>
    <tr><th>New Password Again (must match):</th>
        <td><input type='password' name='pw2' size='40'
                   maxlength='128' /></td></tr>

    </tbody>
  </table>
  </form>

HTML;
}
?>
