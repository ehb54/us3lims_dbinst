<?php
/*
 * activate.php
 *
 * Activates a new account from the email verification
 *
 */

include 'config.php';
include 'db.php';
global $link;
global $admin, $admin_email, $admin_phone;

// These variables should be in the URL string
if (! isset($_GET['id']) || ! isset($_GET['code'] ) )
{
  $message = "Your account cannot be activated without the information that " .
             "was sent to you in the email. If you have any questions, please " .
             "contact $admin at $admin_email or $admin_phone\n";
  include 'index.php';
}

$userid = $_GET['id'];
settype( $userid, 'int' );       // Removes any remaining characters in URL
$code   = $_GET['code'];

$query = "UPDATE people SET activated = 1 " .
         "WHERE personID=? AND password=?";
$args = [$userid, $code];
$stmt = $link->prepare( $query );
$stmt->bind_param( 'is', ...$args );
$stmt->execute()
      or die ("Query failed : $query<br/>" . $stmt->error);
$stmt->close();

$query = "SELECT count(*) FROM people " .
         "WHERE personID=? "    .
         "AND password=? AND activated = 1";
$args = [$userid, $code];
$stmt = $link->prepare( $query );
$stmt->bind_param( 'is', ...$args );
$stmt->execute()
      or die ("Query failed : $query<br/>" . $stmt->error);
$result = $stmt->get_result()
          or die ( "Query failed : $query<br />" . $stmt->error );
list( $doublecheck ) = $result->fetch_row( );
$result->close();
$stmt->close();
if ( $doublecheck == 0 )
{
    $message = "Your account could not be activated.";
} 
else
{
    $message = "Your account has been activated.  You may login below.";
}

include 'login.php';
?>
