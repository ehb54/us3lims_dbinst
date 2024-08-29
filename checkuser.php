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

$loginname  = htmlentities(trim($_POST['email']));
$passwd = trim($_POST['password']);
if ( !isset( $enable_PAM ) ) {
  $enable_PAM = false;
}

if ( ! $loginname || ! $passwd )
{
  remove_session();
  $message =  "Please enter both email address and password!";
  include 'login.php';
  exit();
}

if ( ! ( emailsyntax_is_valid($loginname) ||
     ( $enable_PAM && PAM_name_is_valid( $loginname ) ) )
   ) {
  remove_session();
  $message = $enable_PAM
             ? "Error: $loginname is neither a valid user name nor email address!"
             : "Error: $loginname is not a valid email address!"
             ;
  include 'login.php';
  exit();
}

$pamActive = false;

if ( $enable_PAM && PAM_name_is_valid( $loginname ) ) {
  // for PAM authentication
  $query     = "SELECT * FROM people WHERE userNamePAM=?";
  $args      = [ $loginname ];
  $stmt      = $link->prepare( $query );
  $stmt->bind_param( 's', ...$args );
  $stmt->execute()
        or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result()
          or die( "Query failed : $query<br />\n" . $stmt->error );
  $row    = mysqli_fetch_assoc($result);
  $count  = $result->num_rows;

  if ( $count == 1 && $row['authenticatePAM'] == 1 ) {
     $pamActive = true;
  }
  $result->close();
  $stmt->close();
}

if ( !$pamActive ) {
  // for email authentication

  // Convert password to md5 hash
  $md5pass = md5($passwd);

  // Find the id of the record with the same e-mail address:

  $query  = "SELECT * FROM people WHERE email=?";
  $args   = [ $loginname ];
  $stmt   = $link->prepare( $query );
  $stmt->bind_param( 's', ...$args );
  $stmt->execute()
        or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result()
        or die( "Query failed : $query<br />\n" . $stmt->error );

  $row    = mysqli_fetch_assoc($result);
  $count  = mysqli_num_rows($result);

  $result->close();
  $stmt->close();
}


if ( $enable_PAM && !$pamActive && $row['authenticatePAM'] == 1 ) {
  remove_session();
  $message = "Error: E-Mail address login is not allowed for this user";
  include 'login.php';
  exit();
}

// Register the variables:

if ( $count == 1 )
{
  foreach( $row AS $key => $val )
  {
     $$key = stripslashes( $val );
  }

  $_SESSION['id']               = $personID;
  $_SESSION['loginID']          = $personID;  // This never changes, even if working on behalf of another
  $_SESSION['firstname']        = $fname;
  $_SESSION['lastname']         = $lname;
  $_SESSION['phone']            = $phone;
  $_SESSION['email']            = $email;
  $_SESSION['submitter_email']  = $email;
  $_SESSION['userlevel']        = $userlevel;
  $_SESSION['instance']         = $dbname;
  $_SESSION['user_id' ]         = $fname . "_" . $lname . "_" . $personGUID;
  $_SESSION['advancelevel']     = $advancelevel;
  $_SESSION['userNamePAM']      = $userNamePAM;
  $_SESSION['authenticatePAM']  = $authenticatePAM;

  // Set cluster authorizations
  $clusterAuth = array();
  $clusterAuth = explode(":", $clusterAuthorizations );
  $_SESSION['clusterAuth'] = $clusterAuth;

  // Set GateWay host ID
  $gwhostids = array();
  $gwhostids[ 'uslims3.uthscsa.edu' ]       = 'uslims3.uthscsa.edu_e47e8a2d-9cb7-4489-a84d-38636fb3ed01';
  $gwhostids[ 'uslims3.aucsolutions.com' ]  = 'uslims3.aucsolutions.com_91754ea7-e3be-4895-b501-05f0ca2c0ccd';
  $gwhostids[ 'uslims3.fz-juelich.de' ]     = 'uslims3.fz-juelich.de_283650c2-8815-43b2-8150-907feb6935bb';
  $gwhostids[ 'uslims3.latrobe.edu.au' ]    = 'uslims3.latrobe.edu.au_dea05b5c-5596-49b9-bd10-b0c593713be1';
  $gwhostids[ 'uslims3.mbu.iisc.ernet.in' ] = 'uslims3.mbu.iisc.ernet.in_0ef689dc-5b41-438a-b06d-e2c19b74a920';
  $gwhostids[ 'gw143.iu.xsede.org']         = 'gw143.iu.xsede.org_3bce3fc7-25ed-41eb-97fb-c0930569ceeb';
  $gwhostids[ 'vm1584.kaj.pouta.csc.fi' ]   = 'vm1584.kaj.pouta.csc.fi_35eab34c-7e76-4b3f-a943-c145fde85f36';
  $gwhostids[ 'uslims.uleth.ca' ]           = 'uslims.uleth.ca_82aea4e7-f4a4-4deb-93ac-47e3ad32c868';
  $gwhostids[ 'demeler6.uleth.ca' ]         = 'demeler6.uleth.ca_7b30612e-ab07-4729-81f7-75af7f674e1f';
  $gwhost    = dirname( $org_site );
  if ( preg_match( "/\/uslims3/", $gwhost ) )
     $gwhost    = dirname( $gwhost );
  $gwhostid  = $gwhost;
  if ( isset( $gwhostids[ $gwhost ] ) )
     $gwhostid  = $gwhostids[ $gwhost ];

  $_SESSION[ 'gwhostid' ] = $gwhostid;

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
  $message =  "Error: The account for <i>\"$loginname\"</i> has not been " .
              "correctly set up. <br/>Please set up a new account first " .
              "or correctly type the username or email address.";
  include 'login.php';
  exit();
}

if ( !$pamActive && $row["password"] != $md5pass ) {
  remove_session();
  $message = "Error: Invalid password for $loginname.";
  include 'login.php';
  exit();
}

if ( $pamActive && !pam_auth( $loginname, $passwd, $error ) ) {
  remove_session();
  $message = "Error: Password failed for $loginname.\n$error";
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

$query = "UPDATE people SET lastLogin=now() WHERE personID=?";
$args = [ $personID ];
$stmt = $link->prepare( $query );
$stmt->bind_param( 'i', ...$args );
$stmt->execute()
      or die( "Query failed : $query<br />\n" . $stmt->error );

header("Location: https://$org_site/index.php");
exit();

function remove_session()
{
  $_SESSION = array();
  if ( isset($_COOKIE[session_name()]) )
      setcookie(session_name(), '', time()-42000, '/');
  session_destroy();
}
