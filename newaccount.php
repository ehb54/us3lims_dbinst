<?php
/*
 * newaccount.php
 *
 * Allows a user to create a new account
 *
 */

include 'checkinstance.php';

include_once 'config.php';
include_once 'db.php';
include_once 'lib/utility.php';

$page_title = 'New Account';
$js = 'js/edit_users.js';
include 'header.php';
?>

<div id='content'>

<h1 class="title">New Account Signup Form</h1>

<?php
// Are we being directed to enter the data?
if ( isset( $_POST['enter_request'] ) )
{
  // Check if they match
  if ( $_POST['captcha'] == $_SESSION['captcha'] )
    enter_record();

  else
    do_captcha( "Entered text doesn&rsquo;t match." );
}

else if ( isset( $_SESSION['message'] ) )
{
  // Then we are being redirected back here from register.php
  $message = $_SESSION['message'];
  unset( $_SESSION['message'] );

  enter_record( $message );
}

else                  // Just display the captcha
  do_captcha();

?>

</div>

<?php

include 'footer.php';
exit();

function enter_record( $message = '' )
{
  if ( isset($message) )
  {
    // Get posted information from register.php
    $lname        = $_SESSION['POST']['lname'];
    $fname        = $_SESSION['POST']['fname'];
    $organization = $_SESSION['POST']['organization'];
    $address      = $_SESSION['POST']['address'];
    $city         = $_SESSION['POST']['city'];
    $state        = $_SESSION['POST']['state'];
    $zip          = $_SESSION['POST']['zip'];
    $country      = $_SESSION['POST']['country'];
    $phone        = $_SESSION['POST']['phone'];

    // email might have been unset
    if ( isset( $_SESSION['POST']['email'] ) )
      $email = $_SESSION['POST']['email'];

    unset( $_SESSION['POST'] );

    echo "<p class='message'>$message</p>\n";
  }

  echo<<<HTML

    <p>Please enter your contact information. All fields
       are required.</p>

    <p class='message'>When entering your address, PLEASE MAKE SURE
       it is a valid mailing address, since this will be used to
       mail information to you.</p>

    <form action="register.php" method="post"
          onsubmit='return validate( this );'>
    <table cellspacing='0' cellpadding='10'>
      <thead>
        <tr><th colspan='2'>Personal Information</th></tr>
      </thead>

      <tfoot>
        <tr><td colspan='2'><input type='submit' name='create'
                             value='Create New Account' /></td></tr>
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
      <tr><th>Email:</th>
          <td><input type='text' name='email' size='40'
                     maxlength='64' value='$email' /></td></tr>
      </tbody>
    </table>
    </form>

HTML;

}

// Function to display a captcha and request human input
function do_captcha( $msg = "" )
{
  $message = ( empty( $msg ) ) ? "" : "<p style='color:red;'>$msg</p>";
  $act     = htmlentities($_SERVER['PHP_SELF']);
  // Let's just use the random password function we already have
  $pw      = makeRandomPassword();
  $_SESSION['captcha'] = $pw;

echo<<<HTML
  <div id='captcha'>

  $message

  <img src='create_captcha.php' alt='Captcha image' />

  <form action="$act" method="post">
    <h3>Please enter the code above to proceed to new account request</h3>

    <p><input type='text' name='captcha' size='40' maxlength='10' /></p>

    <p><input type='submit' name='enter_request' value='Enter Request' />
       <input type='reset' /></p>

  </form>

  </div>

HTML;
}
?>
