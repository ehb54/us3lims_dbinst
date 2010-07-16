<?php
/*
 * checkuser.php
 *
 * Verify user credentials
 *
 */
session_start();

include 'config.php';
include 'db.php';
include 'utility.php';

$email  = trim($_POST['email']);
$passwd = trim($_POST['password']);

if (  ! $email || ! $passwd )
{
  remove_session();
  $message =  "Please enter both email address and password!";
  include 'login.php';
  exit();
}

if ( ! emailsyntax_is_valid($email) )
{
  remove_session();
  $message = "Error: $email is not a valid email address!";
  include 'login.php';
  exit();
}

// Convert password to md5 hash
$md5pass = md5($passwd);

// Find the id of the record with the same e-mail address:

$query  = "SELECT * FROM people WHERE email='$email'";
$result = mysql_query($query)
          or die( "Query failed : $query<br />\n" . mysql_error() );
$row    = mysql_fetch_assoc($result);
$count  = mysql_num_rows($result);

// Register the variables:

if ( $count == 1 )
{
  foreach( $row AS $key => $val )
  {
     $$key = stripslashes( $val );
  }

  $_SESSION['id']           = $personID;
  $_SESSION['loginID']      = $personID;  // This never changes, even if working on behalf of another
  $_SESSION['firstname']    = $fName;
  $_SESSION['lastname']     = $lName;
  $_SESSION['phone']        = $phone;
  $_SESSION['email']        = $email;
  $_SESSION['submitter_email'] = $email;
  $_SESSION['userlevel']    = $userlevel;
}

else if ( $count > 1 )
{
  remove_session();
  $message = "There was a problem with duplicate email addresses.  " .
             "Please contact the administrator: "                    .
             "<a href='mailto:$admin_email'>"                  .
             "&lt;$admin_email&gt;</a>.";
  include 'login.php';
  exit();
}

// There better be one row 

if ( $count < 1 )
{
  remove_session();
  $message =  "Error: The account for $email has not been " .
              "correctly set up.  Please set up a new account first.";
  include 'newaccount.php';
  exit();
}

if ( $row["password"] != $md5pass )
{
  remove_session();
  $message = "Error: Invalid password for $email.";
  include 'login.php';
  exit();
}

if ( $row["activated"] != 1 )
{
  remove_session();
  $message = "Error: This account has not been activated yet. " .
             "Please activate your account first. " .
             "The activation code was sent to your e-mail address: $email.";
  include 'login.php';
  exit();
}

// Update last login time

$query = "UPDATE people SET lastLogin=now() WHERE personID=$personID";
mysql_query($query);

header("Location: http://$org_site/index.php");
exit();

function remove_session()
{
  $_SESSION = array();
  if ( isset($_COOKIE[session_name()]) ) 
      setcookie(session_name(), '', time()-42000, '/');
  session_destroy();            
}
?>
