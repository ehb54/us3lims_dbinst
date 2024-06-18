<?php
/*
 * get_user_info.php
 *
 * Include file to get and process some common user information
 *  This is used in three files --- register.php, edit_user.php,
 *   and profile.php
 *
 */

$lname              = trim(substr(addslashes(htmlentities($_POST['lname'])), 0,64));
$fname              = trim(substr(addslashes(htmlentities($_POST['fname'])), 0,64));
$organization       = trim(substr(addslashes(htmlentities($_POST['organization'])), 0,128));
$address            = trim(substr(addslashes(htmlentities($_POST['address'])), 0,128));
$city               = trim(substr(addslashes(htmlentities($_POST['city'])), 0,64));
$state              = trim(substr(addslashes(htmlentities($_POST['state'])), 0,64));
$zip                = trim(substr(addslashes(htmlentities($_POST['zip'])), 0,16));
$country            = trim(substr(addslashes(htmlentities($_POST['country'])), 0,64));
$phone              = trim(substr(addslashes(htmlentities($_POST['phone'])), 0,64));
$email              = trim(substr(addslashes(htmlentities($_POST['email'])), 0,64));
$gmpReviewerRole    = trim(substr(addslashes(htmlentities($_POST['gmpReviewerRole'])), 0, 12));
if ( isset( $enable_PAM ) && $enable_PAM ) {
    $authenticatePAM = trim(substr(addslashes(htmlentities($_POST['authenticatePAM'])), 0, 4)) == "on" ? 1 : 0;
    $userNamePAM     = trim(substr(addslashes(htmlentities($_POST['userNamePAM'])), 0,64));
    if ( !$authenticatePAM && empty( $userNamePAM ) ) {
      $userNamePAM = $email;
    }
}
  
// Let's do some error checking first of all
// -- most fields are required
$message = "";
if ( empty($fname) )
  $message .= "--first name is missing<br />";

if ( empty($lname) )
  $message .= "--last name is missing<br />";

if ( empty($organization) )
  $message .= "--organization is missing<br />";

if ( empty($address) )
  $message .= "--address is missing<br />";

if ( empty($city) )
  $message .= "--city is missing<br />";

if ( empty($state) )
  $message .= "--state or province is missing<br />";

if ( empty($zip) )
  $message .= "--postal code or zip is missing<br />";

if ( empty($country) )
  $message .= "--country is missing<br />";

if ( empty($phone) )
  $message .= "--phone is missing<br />";

if ( empty($email) )
  $message .= "--email address is missing<br />";

if (! emailsyntax_is_valid($email) )
  $message .= "--$email is not a valid email address<br />";

if ( isset( $enable_PAM ) && $enable_PAM && empty( $userNamePAM ) ) {
  $message .= "--user name (PAM) is missing <br>";
}

if ( !isset( $enable_PAM ) || !$enable_PAM ) {
  $userNamePAM     = $email;
  $authenticatePAM = 0;
}
