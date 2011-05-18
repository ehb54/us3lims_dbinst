<?php
/*
 * sendcomments.php
 *
 * A page that processes comments sent from contactus.php
 *
 */
session_start();

include 'config.php';
include 'lib/utility.php';

$page_title = "Contact Reply";
include 'top.php';
include 'links.php';
?>
<div id='content'>

  <h1 class="title">Send Comments</h1>
<?php

// Get the submitted information
$lname    = trim(substr(addslashes(htmlentities($_POST['lname'])), 0,64));
$fname    = trim(substr(addslashes(htmlentities($_POST['fname'])), 0,64));
$phone    = trim(substr(addslashes(htmlentities($_POST['phone'])), 0,64));
$email    = trim(substr(addslashes(htmlentities($_POST['email'])), 0,64));
$comments = trim(addslashes(htmlentities($_POST['comments'])) );

// Let's do some error checking first of all
// -- all fields are required
$message = "";
if ( empty($fname) )
  $message .= "--first name is missing<br />";

if ( empty($lname) )
  $message .= "--last name is missing<br />";

if ( empty($phone) )
  $message .= "--phone is missing<br />";

if ( empty($email) )
  $message .= "--email address is missing<br />";

if (! emailsyntax_is_valid($email) )
  $message .= "--$email is not a valid email address<br />";

if ( empty($comments) )
  $message .= "--your comments are missing<br />";

$msg  = "Message from the $org_name Contact Us form:\n\n";
$msg .= "Name: $fname $lname\n";
$msg .= "Phone: $phone\n";
$msg .= "Email: $email\n\n";

$msg .= "Comments:\n";
$msg .= wordwrap($comments);

// Show user information he submitted
if ( empty($message) )
{
  echo "<p>Thank you, $fname, for filling out the comment form.</p>\n";
  echo "<p>Your comments are important. Here's the information you sent:</p>\n";
  echo "<pre>$msg</pre>";

  // Now send the mail
  $now   = time();
  $name  = "$fname $lname";
  $headers = "From: $name <$email>"           . "\n";

  // Set the reply address
  $headers .= "Reply-To: $name <$email>"      . "\n";
  $headers .= "Return-Path: $name <$email>"   . "\n";
  $headers .= "Bcc: demeler@biochem.uthscsa.edu" . "\n";

  // Try to avoid spam filters
  $headers .= "Message-ID: <" . $now . "info@" . $_SERVER['SERVER_NAME'] . ">\n";
  $headers .= "X-Mailer: PHP v" . phpversion()         . "\n";
  $headers .= "MIME-Version: 1.0"                      . "\n";
  $headers .= "Content-Transfer-Encoding: 8bit"        . "\n";

  $subject = "$org_name Send Comments submission";

  mail($admin_email, $subject, $msg, $headers);
}

else
{
  // Show user the information submitted
  echo "<p>We're sorry, but we cannot accept your comments.\n";
  echo "Here's the information you sent:</p>\n";
  echo "<pre>$msg</pre>";
  echo "<p>However, the following errors were noted:<br />\n" .
       $message .
       "Your comments were not sent.</p>\n";
}

?>
  <p><a href="contactus.php">Back to contact form. </a></p>

</div>

<?php
include 'bottom.php';
?>
