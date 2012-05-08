<?php
/*
 * recover_password.php
 *
 * Creates a new password and sends it to registered email address
 *
 */

include 'config.php';
include 'db.php';
include 'lib/utility.php';

$email_address = stripslashes( $_POST['email_address'] );

if ( ! $email_address )
{
  $message =  "Error: E-mail address is missing!";
  include 'lost_password.php';
  exit();
}

// Quick check to see if record exists  

$query = "SELECT personID, activated FROM people " .
         "WHERE email='$email_address'";

$result    = mysql_query($query);
$row_count = mysql_num_rows($result);
$row       = mysql_fetch_row($result);

if ( $row_count == 0 )
{
  $message = "No records were found matching your email address<br/>";
  include 'lost_password.php';
  exit();
}

list($personID, $activated) = $row;

// Sometimes users come here before account is activated, and a new
//   password will break the activation, so...
if ( $activated == 0 )
{
  $message = "Error: This account has not been activated yet. " .
  "Please activate your account first. " .
  "The activation code was sent to your e-mail address: $email_address.";
  include 'login.php';
  exit();
}

// Everything looks ok, generate password, update it and send it!
    
$random_password = makeRandomPassword();

$db_password = md5($random_password);

$query = "UPDATE people " .
         "SET password='$db_password' " .
         "WHERE personID='$personID'";
mysql_query($query) 
      or die("Query failed : $query" . mysql_error());

$subject = "System Password";
$message = "We have reset your password at your request.
    
New Password: $random_password
    
http://$org_site

Please save this message for your reference.
Thanks!
The $org_name Admins.
   
This is an automated response, do not reply!";

LIMS_mailer( $email_address, $subject, $message );

$message =  "Your password has been sent to you via email. " . 
            "Please check your email for your new password.";

include 'login.php';

?>
