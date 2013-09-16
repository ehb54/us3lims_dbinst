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
    $text  = person_select( 'personID', $_POST['personID'] );
    $text .= change_person_info( $_POST['personID'] );
  }

  else if ( isset( $_GET['restore'] ) &&
            $_GET['restore'] == 'true' )
  {
    $text  = person_select( 'personID', $_SESSION['loginID'] );
    $text .= restore_info();
  }

  else
    $text  = person_select( 'personID', $_SESSION['id'] );

  echo $text;

  echo "<p><a href='{$_SERVER['PHP_SELF']}?restore=true'>Restore my own info</a></p>\n";

?>
</div>

<?php
include 'footer.php';
exit();

// Function to update user session variables and display info
function change_person_info( $personID )
{
  $query  = "SELECT fname, lname, phone, ClusterAuthorizations " .
            "FROM people " .
            "WHERE personID = $personID ";
  $result = mysql_query($query)
            or die( "Query failed : $query<br />\n" . mysql_error() );
  $row    = mysql_fetch_assoc($result);

  // Register the variables:

  foreach( $row AS $key => $val )
  {
     $$key = stripslashes( $val );
  }

  // The loginID, email and userlevel don't change, even if working 
  // on behalf of another
  $_SESSION['id']           = $personID;
  $_SESSION['firstname']    = $fname;
  $_SESSION['lastname']     = $lname;
  $_SESSION['phone']        = $phone;

  // Set cluster authorizations
  $loginID = $_SESSION['loginID'];
  $query  = "SELECT ClusterAuthorizations " .
            "FROM people " .
            "WHERE personID = $loginID ";
  $result = mysql_query($query)
            or die( "Query failed : $query<br />\n" . mysql_error() );
  $row    = mysql_fetch_assoc($result);
  foreach( $row AS $key => $val )
  {
     $$key = stripslashes( $val );
  }
  $clusterAuth = array();
  $clusterAuth = explode(":", $ClusterAuthorizations );
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
function restore_info()
{
  $personID = $_SESSION['loginID'];

  $query  = "SELECT fname, lname, phone, ClusterAuthorizations " .
            "FROM people " .
            "WHERE personID = $personID ";
  $result = mysql_query($query)
            or die( "Query failed : $query<br />\n" . mysql_error() );
  $row    = mysql_fetch_assoc($result);

  // Register the variables:

  foreach( $row AS $key => $val )
  {
     $$key = stripslashes( $val );
  }

  // The loginID, email and userlevel don't change, even if working 
  // on behalf of another
  $_SESSION['id']           = $personID;
  $_SESSION['firstname']    = $fname;
  $_SESSION['lastname']     = $lname;
  $_SESSION['phone']        = $phone;

  // Set cluster authorizations
  $clusterAuth = array();
  $clusterAuth = explode(":", $ClusterAuthorizations );
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
?>
