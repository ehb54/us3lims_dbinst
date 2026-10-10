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

// A transient DB failure during login must not dump raw SQL/driver text to
// the browser (information disclosure, and a broken-looking page besides);
// show the same friendly message login.php already uses for bad input.
function login_db_unavailable()
{
  $message = "The login service is temporarily unavailable. Please try again in a moment.";
  include 'login.php';
  exit();
}

/**
 * Prepare and execute a parameterized SELECT, routing every failure mode to
 * login_db_unavailable() instead of letting it reach the browser.
 *
 * Previously this held only on PHP 7.2: mysqli's default report mode there
 * is silent failure (prepare()/execute() return false, which the original
 * `if ( ! $stmt->execute() ... )` check caught, though nothing checked
 * prepare() itself, so a false $stmt there still reached bind_param() as a
 * fatal "call to a member function on bool"). PHP 8.1 changed the default
 * to MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT, so on 8.2 a connection
 * problem throws mysqli_sql_exception instead of returning false -- from
 * prepare() as readily as from execute(), uncaught, past every check here,
 * as a fatal 500 with driver text in it.
 *
 * @return array{0: mysqli_stmt, 1: mysqli_result}
 */
function login_db_query( $link, $query, $types, array $args )
{
  try {
    $stmt = $link->prepare( $query );
    if ( ! $stmt ) {
      login_db_unavailable();
    }
    $stmt->bind_param( $types, ...$args );
    if ( ! $stmt->execute() || ! ( $result = $stmt->get_result() ) ) {
      login_db_unavailable();
    }
  } catch ( Throwable $e ) {
    login_db_unavailable();
  }

  return array( $stmt, $result );
}

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
  list( $stmt, $result ) = login_db_query( $link, "SELECT * FROM people WHERE userNamePAM=?", 's', [ $loginname ] );
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

  list( $stmt, $result ) = login_db_query( $link, "SELECT * FROM people WHERE email=?", 's', [ $loginname ] );

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
  $_SESSION['userNamePAM']      = $userNamePAM ?? $email;
  $_SESSION['authenticatePAM']  = $authenticatePAM ?? 0;
  $_SESSION['gmpReviewerRole']  = $gmpReviewerRole ?? 'NONE';

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

// Every branch below that fails before the account's own activated/enabled
// state is checked uses the same generic message, on purpose: telling an
// unauthenticated caller specifically that an account doesn't exist, has a
// duplicate row, or just has the wrong password lets them enumerate valid
// email addresses by the response alone. None of that distinction is needed
// once the real cause is in the server log for an admin to look at.
if ( $count > 1 )
{
  remove_session();
  error_log( "login: duplicate email addresses for $loginname" );
  $message = "Error: Invalid email address or password.";
  include 'login.php';
  exit();
}

// There better be one row

if ( $count < 1 )
{
  remove_session();
  $message = "Error: Invalid email address or password.";
  include 'login.php';
  exit();
}

if ( !$pamActive && $row["password"] != $md5pass ) {
  remove_session();
  $message = "Error: Invalid email address or password.";
  include 'login.php';
  exit();
}

if ( $pamActive && !pam_auth( $loginname, $passwd, $error ) ) {
  remove_session();
  error_log( "login: PAM authentication failed for $loginname: $error" );
  $message = "Error: Invalid email address or password.";
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

if ( $row["account_enabled"] != 1 )
{
  remove_session();
  $message = "Error: This account has been disabled. " .
             "Please contact the administrator: " .
             "<a href='mailto:$admin_email'>" .
             "&lt;$admin_email&gt;</a>.";
  include 'login.php';
  exit();
}

// Update last login time. Credentials are already fully verified at this
// point, so a failure here must not block the login itself -- it would
// otherwise turn a cosmetic bookkeeping write into a reason the user can't
// get in.
// Caught, not routed through login_db_unavailable(): credentials are
// already verified, so on PHP 8.1+ (mysqli throwing instead of returning
// false by default) this must still only log, the same as the 7.2 case
// below already did -- a thrown exception here must not turn a cosmetic
// bookkeeping failure into a login failure either.
try {
  $query = "UPDATE people SET lastLogin=now() WHERE personID=?";
  $args = [ $personID ];
  $stmt = $link->prepare( $query );
  if ( ! $stmt ) {
    error_log( "login: could not prepare lastLogin update for personID=$personID: " . $link->error );
  } else {
    $stmt->bind_param( 'i', ...$args );
    if ( ! $stmt->execute() ) {
      error_log( "login: could not update lastLogin for personID=$personID: " . $stmt->error );
    }
  }
} catch ( Throwable $e ) {
  error_log( "login: could not update lastLogin for personID=$personID: " . $e->getMessage() );
}

// New session id for the newly-authenticated session: nothing an
// unauthenticated request may have already seen (e.g. a session id fixed
// before login) should carry over as a valid, logged-in session.
session_regenerate_id( true );

header("Location: index.php");
exit();

function remove_session()
{
  $_SESSION = array();
  if ( isset($_COOKIE[session_name()]) )
      setcookie(session_name(), '', time()-42000, '/');
  session_destroy();
}
