<?php
/*
 * checkuser.php
 *
 * Verify user credentials
 *
 */
include 'checkinstance.php';
include 'db.php';
include 'lib/utility.php';

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
  $_SESSION['firstname']    = $fname;
  $_SESSION['lastname']     = $lname;
  $_SESSION['phone']        = $phone;
  $_SESSION['email']        = $email;
  $_SESSION['submitter_email'] = $email;
  $_SESSION['userlevel']    = $userlevel;
  $_SESSION['instance']     = $dbname;
  $_SESSION['user_id' ]     = $fname . "_" . $lname . "_" . $personGUID;
  $_SESSION['advancelevel'] = $advancelevel;

  // Set cluster authorizations
  $clusterAuth = array();
  $clusterAuth = explode(":", $clusterAuthorizations );
  $_SESSION['clusterAuth'] = $clusterAuth;

  // Set GateWay host ID
  $gwhostids = array();
  $gwhostids[ 'uslims3.uthscsa.edu' ]       = 'uslims3.uthscsa.edu_917092f2-7ca3-4bad-8b99-aa83d951bfca'; 
  $gwhostids[ 'uslims3.fz-juelich.de' ]     = 'uslims3.fz-juelich.de_ae70148f-3909-419f-b016-68ab3ff86dc9';
  $gwhostids[ 'uslims3.latrobe.edu.au' ]    = 'uslims3.latrobe.edu.au_ddf7fd58-845d-4408-bcfc-80dba25440c9';
  $gwhostids[ 'uslims3.mbu.iisc.ernet.in' ] = 'uslims3.mbu.iisc.ernet.in_612ab140-978a-4313-bc31-5cea75c5a4fe';
  $gwhostids[ 'gw143.iu.xsede.org'        ] = 'gw143.iu.xsede.org_3bce3fc7-25ed-41eb-97fb-c0930569ceeb';
  $gwhost    = dirname( $org_site );

  if ( !isset( $gwhostids[ $gwhost ] ) )
    $gwhost    = 'uslims3.uthscsa.edu';

  $_SESSION[ 'gwhostid' ] = $gwhostids[ $gwhost ];

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
  $message =  "Error: The account for <i>\"$email\"</i> has not been " .
              "correctly set up. <br/>Please set up a new account first " .
              "or correctly type the email address.";
  include 'login.php';
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
