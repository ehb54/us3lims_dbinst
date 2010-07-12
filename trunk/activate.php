<?php
/*
 * activate.php
 *
 * Activates a new account from the email verification
 *
 */

include 'config.php';
include 'db.php';

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

$query = "UPDATE people SET status='activated' " .
         "WHERE personID=$userid AND password='$code'";
mysql_query($query) 
      or die ("Query failed : $query<br/>" . mysql_error());

$query = "SELECT count(*) FROM people " .
         "WHERE personID='$userid' "    .
         "AND password='$code' AND status='activated'";
$result = mysql_query( $query ) 
          or die ( "Query failed : $query<br />" . mysql_error() );
list( $doublecheck ) = mysql_fetch_row( $result );

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
