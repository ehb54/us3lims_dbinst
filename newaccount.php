<?php
/*
 * newaccount.php
 *
 * Allows a user to create a new account
 *
 */

include_once 'config.php';
include_once 'db.php';

$page_title = 'New Account';
$js = 'js/edit_users.js';
include 'top.php';
include 'links.php';
?>

<div id='content'>

  <h1 class="title">New Account</h1>

<?php

if ( isset($message) )
   echo "<p class='message'>$message</p>\n";

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

</div>

HTML;

include 'bottom.php';
?>
