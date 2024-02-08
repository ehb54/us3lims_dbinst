<?php
/*
 * select_user.php
 *
 * Allows admin users to assume another user's identity for submitting jobs
 *
 */
include_once 'checkinstance.php';

if ( ($_SESSION['userlevel'] < 3) )    // super, admin and super admin only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/selectboxes.php';
// ini_set('display_errors', 'On');


// Start displaying page
$page_title = "Select Another User";
include 'header.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Select Another User</h1>
  <!-- Place page content here -->

<?php
  if ( isset( $_POST['personID'] ) )
  {
    $text  = person_select( $link, 'personID', $_POST['personID'] );
    $text .= change_person_info( $link, $_POST['personID'] );
  }

  else if ( isset( $_GET['restore'] ) &&
            $_GET['restore'] == 'true' )
  {
    $text  = person_select( $link, 'personID', $_SESSION['loginID'] );
    $text .= restore_info($link);
  }

  else
    $text  = person_select( $link, 'personID', $_SESSION['id'] );

  echo $text;

  echo "<p><a href='{$_SERVER['PHP_SELF']}?restore=true'>Restore my own info</a></p>\n";

?>
</div>

<?php
include 'footer.php';
exit();

// Function to clear the queue to prevent modelPerson corruption

function clear_queue() {
  unset( $_SESSION['request'] );
  unset( $_SESSION['cells'] );
  unset( $_SESSION['experimentID'] );
  unset( $_SESSION['new_noise'] );
}    

// Function to update user session variables and display info
function change_person_info( $link, $personID )
{
  clear_queue();
  $query  = "SELECT fname, lname, phone, clusterAuthorizations " .
            "FROM people " .
            "WHERE personID = ? ";

  // Prepared statement
  if ($stmt = mysqli_prepare($link, $query)) {
   $stmt->bind_param('i', $personID);
   $stmt->execute();
   $stmt->store_result();
   $num_of_rows = $stmt->num_rows;
   $stmt->bind_result($fname, $lname, $phone, $clusterAuthorizations);
   $stmt->fetch();

   $stmt->free_result();
   $stmt->close();
  }

  /* This code was replace by the prepared statement above
  $result = mysqli_query($link, $query)
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );
  $row    = mysqli_fetch_assoc($result);

  // Register the variables:

  foreach( $row AS $key => $val )
  {
     $$key = stripslashes( $val );
  }
  */

  // The loginID, email and userlevel don't change, even if working
  // on behalf of another
  $_SESSION['id']           = $personID;
  $_SESSION['firstname']    = $fname;
  $_SESSION['lastname']     = $lname;
  $_SESSION['phone']        = $phone;

  // Set cluster authorizations
  $loginID = $_SESSION['loginID'];
  $query  = "SELECT clusterAuthorizations " .
            "FROM people " .
            "WHERE personID = $loginID ";
  $result = mysqli_query($link, $query)
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );
  $row    = mysqli_fetch_assoc($result);
  foreach( $row AS $key => $val )
  {
     $$key = stripslashes( $val );
  }
  $clusterAuth = array();
  $clusterAuth = explode(":", $clusterAuthorizations );
  $_SESSION['clusterAuth'] = $clusterAuth;

  // Allow for display of this information
  $clusterText = implode( ", ", $clusterAuth );
  $text = <<<HTML
  <pre>
  User identity assumed.

  Name:   $lname, $fname

  Login User Cluster Authorizations: $clusterText
  </pre>
HTML;

  return $text;
}

// Function to restore user session variables and display info to login values
function restore_info($link)
{
  clear_queue();

  $personID = $_SESSION['loginID'];

  $query  = "SELECT fname, lname, phone, clusterAuthorizations " .
            "FROM people " .
            "WHERE personID = ? ";

  // Prepared statement
  if ($stmt = mysqli_prepare($link, $query)) {
   $stmt->bind_param('i', $personID);
   $stmt->execute();
   $stmt->store_result();
   $num_of_rows = $stmt->num_rows;
   $stmt->bind_result($fname, $lname, $phone, $clusterAuthorizations);
   $stmt->fetch();

   $stmt->free_result();
   $stmt->close();
  }

  /* This code was replace by the prepared statement above
  $result = mysqli_query($link, $query)
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );
  $row    = mysqli_fetch_assoc($result);

  // Register the variables:

  foreach( $row AS $key => $val )
  {
     $$key = stripslashes( $val );
  }
  */

  // The loginID, email and userlevel don't change, even if working
  // on behalf of another
  $_SESSION['id']           = $personID;
  $_SESSION['firstname']    = $fname;
  $_SESSION['lastname']     = $lname;
  $_SESSION['phone']        = $phone;

  // Set cluster authorizations
  $clusterAuth = array();
  $clusterAuth = explode(":", $clusterAuthorizations );
  $_SESSION['clusterAuth'] = $clusterAuth;

  // Allow for display of this information
  $clusterText = implode( ", ", $clusterAuth );
  $text = <<<HTML
  <pre>
  User identity restored.

  Name:   $lname, $fname

  Cluster Authorizations: $clusterText
  </pre>
HTML;

  return $text;
}
