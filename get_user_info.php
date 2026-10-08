<?php
/*
 * get_user_info.php
 *
 * Include file to get and process some common user information
 *  This is used in three files --- register.php, edit_user.php,
 *   and profile.php
 *
 */

// Single source of truth for which profile fields are always required, so
// the validation below and the "*" markers on the edit/create forms
// (edit_users_actions.php) can never drift apart from each other.
if ( ! function_exists( 'profile_required_fields' ) ) {
  function profile_required_fields()
  {
    return array(
      'fname'        => 'first name',
      'lname'        => 'last name',
      'organization' => 'organization',
      'address'      => 'address',
      'city'         => 'city',
      'state'        => 'state or province',
      'zip'          => 'postal code or zip',
      'country'      => 'country',
      'phone'        => 'phone',
      'email'        => 'email address',
    );
  }
}

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
if ( isset( $enable_GMP ) && $enable_GMP ) {
    $gmpReviewerRole = trim(substr(addslashes(htmlentities($_POST['gmpReviewerRole'])), 0, 12));
}
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
foreach ( profile_required_fields() as $field_key => $field_label )
{
  if ( empty( $$field_key ) )
    $message .= "--$field_label is missing<br />";
}

if (! emailsyntax_is_valid($email) )
  $message .= "--$email is not a valid email address<br />";

if ( isset( $enable_PAM ) && $enable_PAM && empty( $userNamePAM ) ) {
  $message .= "--user name (PAM) is missing <br>";
}

if ( !isset( $enable_PAM ) || !$enable_PAM ) {
  $userNamePAM     = $email;
  $authenticatePAM = 0;
}
