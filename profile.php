<?php
/*
 * profile.php
 *
 * A place for a user to edit/update his own info
 *
 */
include_once 'checkinstance.php';

include 'db.php';
include 'lib/utility.php';
// ini_set('display_errors', 'On');
if ( !isset( $enable_PAM ) ) {
  $enable_PAM = false;
}

if ( $enable_PAM
     && $_SESSION['authenticatePAM'] == true ) {
  include 'header.php';
  include 'footer.php';
  exit();
}

if ( !isset( $_SESSION['id'] ) ) {
  include 'login.php';
  exit();
}

// Are we being directed here from a push button?
if (isset($_POST['update']))
{
  do_update($link);
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
  edit_record($link);

else
  display_record($link);

?>

</div>

<?php
include 'footer.php';
exit();

// Function to update the current record
function do_update($link)
{
  global $message;
  $ID  = $_SESSION['id'];
  include 'get_user_info.php';

  // Check password length
  if ( !empty($_POST['pw1']) && strlen($_POST['pw1']) > 127 ){
    $message .= "--password must be less than 128 characters long.<br />";
  }
  elseif ( !empty($_POST['pw2']) && strlen($_POST['pw2']) > 127 ){
    $message .= "--password must be less than 128 characters long.<br />";
  }
  // We can't use any sanitization here, because it would alter the password potentially
  $pw1 = $_POST['pw1'];
  $pw2 = $_POST['pw2'];

  if ( $pw1 != $pw2 )
    $message .= "--passwords do not match.";

  if ( empty($message) )
  {
    // language=MariaDB
    $query = "UPDATE people " .
             "SET lname      = ?, " .
             "fname          = ?, " .
             "organization   = ?, " .
             "address        = ?, " .
             "city           = ?, " .
             "state          = ?, " .
             "zip            = ?, " .
             "country        = ?, " .
             "phone          = ?, " .
             "email          = ?  ";
    $args = [ $lname, $fname, $organization, $address, $city, $state,
              $zip, $country, $phone, $email ];
    $args_type = 'ssssssssss';
    // See if password has changed
    if ( $pw1 )
    {
      $pw = md5($pw1);
      $query .= ", password = ? ";
      $args[] = $pw;
      $args_type .= 's';
    }

    $query .= "WHERE personID = ? ";
    $args[] = $ID;
    $args_type .= 'i';
    $stmt = mysqli_prepare($link, $query);
    $stmt->bind_param($args_type, ...$args);
    $stmt->execute()
          or die("Query failed : $query<br />\n" . mysqli_error($link));
    $stmt->close();

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
function display_record($link)
{
  $ID = $_SESSION['id'];

  $query  = "SELECT lname, fname, organization, " .
            "address, city, state, zip, country, phone, email " .
            "FROM people " .
            "WHERE personID = $ID ";
  $result = mysqli_query($link, $query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));

  $row    = mysqli_fetch_array($result, MYSQLI_ASSOC);

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
function edit_record($link)
{
  $ID = $_SESSION['id'];

  $query  = "SELECT lname, fname, organization, " .
            "address, city, state, zip, country, phone, email " .
            "FROM people " .
            "WHERE personID = $ID ";
  $result = mysqli_query($link, $query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));

  $row = mysqli_fetch_array($result);

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
