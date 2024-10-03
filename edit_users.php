<?php
/*
 * edit_users.php
 *
 * A place to edit/update the people table
 *
 */
include_once 'checkinstance.php';

if ( !isset($_SESSION['userlevel']) ||
     ( ($_SESSION['userlevel'] != 0) &&
       ($_SESSION['userlevel'] != 4) &&
       ($_SESSION['userlevel'] != 5) ) )   // Super user, admin and super admin only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';
include 'lib/selectboxes.php';
include 'lib/grant_integrity.php';
global $link;
// ini_set('display_errors', 'On');

if ( !isset( $enable_GMP ) ) {
  $enable_GMP = false;
}

if ( !isset( $enable_PAM ) ) {
  $enable_PAM = false;
}

// Are we being directed here from a push button?
if (isset($_POST['prior']))
{
  do_prior($link);
  exit();
}

else if (isset($_POST['next']))
{
  do_next($link);
  exit();
}

else if (isset($_POST['delete']))
{
  do_delete($link);
  exit();
}

else if (isset($_POST['update']))
{
  do_update($link);
  exit();
}

else if (isset($_POST['create']))
{
  do_create($link);
  exit();
}

// Start displaying page
$page_title = 'Edit Users';
$js = 'js/edit_users.js';
include 'header.php';
?>
<div id='content'>

  <h1 class="title">Edit Users</h1>
  <?php if ( isset($_SESSION['message']) )
        {
          echo "<p class='message'>{$_SESSION['message']}</p>\n";
          unset($_SESSION['message']);
        } ?>

<?php
// Edit or display a record
if (isset($_POST['edit']))
  edit_record($link);

else if (isset($_POST['new']))
  do_new($link);

else
  display_record($link);

?>

</div>

<?php
include 'footer.php';
exit();

// Function to redirect to prior record
function do_prior($link)
{
  $personID = $_POST['personID'];

  $querywhere = "";
  if ( $_SESSION['userlevel'] == 0 ) {
      $querywhere = "WHERE userlevel <= 3 ";
  }

  $query  = "SELECT personID FROM people $querywhere" .
            "ORDER BY lname, fname ";
  $result = mysqli_query($link, $query)
      or die("Query failed : $query<br />\n" . mysqli_error($link));

  // Find prior record
  list($current) = mysqli_fetch_array($result);
  $prior = null;
  while ($current != NULL && $personID != $current)
  {
    $prior = $current;
    list($current) = mysqli_fetch_array($result);
  }

  $redirect = ($prior == null) ? "" : "?personID=$prior";
  header("Location: {$_SERVER['PHP_SELF']}$redirect");
}

// Function to redirect to next record
function do_next($link)
{
  $personID = $_POST['personID'];

  $querywhere = "";
  if ( $_SESSION['userlevel'] == 0 ) {
      $querywhere = "WHERE userlevel <= 3 ";
  }

  $query  = "SELECT personID FROM people $querywhere" .
            "ORDER BY lname, fname ";
  $result = mysqli_query($link, $query)
      or die("Query failed : $query<br />\n" . mysqli_error($link));

  // Find next record
  $current = null;
  while ($personID != $current)
    list($current) = mysqli_fetch_array($result);
  list($next) = mysqli_fetch_array($result);

  $redirect = ($next == null) ? "?personID=$personID" : "?personID=$next";
  header("Location: {$_SERVER['PHP_SELF']}$redirect");
}

// Function to delete the current record
function do_delete($link)
{
  global $admin_list;      // To protect our admin entries
  global $enable_PAM;

  $admins = implode( "','", $admin_list );

  $personID = $_POST['personID'];

  if ( $enable_PAM ) {
      $query = "SELECT userNamePAM FROM people " .
          "WHERE personID = ? " .
          "AND email NOT IN ( '$admins' ) ";
      $stmt = $link->prepare( $query );
      $stmt->bind_param( 'i', $personID );
      $stmt->execute()
            or die( "Query failed : $query<br />\n" . $stmt->error );
      $result = $stmt->get_result()
              or die( "Query failed : $query<br />\n" . $stmt->error );

      $row  = mysqli_fetch_array($result, MYSQLI_ASSOC);
      $userNamePAM = $row['userNamePAM'];
      $result->close();
      $stmt->close();
  }

  $query = "DELETE FROM people " .
           "WHERE personID = ? " .
           "AND email NOT IN ( '$admins' ) ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $personID );
  $stmt->execute()
        or die( "Query failed : $query<br />\n" . $stmt->error );
  $stmt->close();

  if ( $enable_PAM ) {
    $_SESSION['message'] = grant_integrity( $userNamePAM, false, 0 );
  }       

  header("Location: {$_SERVER['PHP_SELF']}");
}

// Function to update the current record
function do_update($link)
{
  include 'get_user_info.php';

  global $enable_PAM;

  $personID        = $_POST['personID'];
  $activated       = ( $_POST['activated'] == 'on' ) ? 1 : 0;
  $userlevel       = $_POST['userlevel'];
  $advancelevel    = $_POST['advancelevel'];
  if ( isset( $enable_GMP ) && $enable_GMP ) {
    $gmpReviewerRole = $_POST['gmpReviewerRole'];
  }
  else {
    $gmpReviewerRole = '';
  }
  if ( isset( $enable_PAM ) && $enable_PAM ) {
      $authenticatePAM = ( isset( $_POST['authenticatePAM'] ) && $_POST['authenticatePAM'] == 'on' ) ? 1 : 0;
      $userNamePAM     = $_POST['userNamePAM'];
      if ( empty( $userNamePAM ) ) {
         $userNamePAM = $_POST['email'];
      }
  }

  if ( $enable_PAM ) {
      $query  = "SELECT userNamePAM " .
          "FROM people " .
          "WHERE personID = ? ";
      $stmt = $link->prepare( $query );
      $stmt->bind_param( 'i', $personID );
      $stmt->execute()
            or die( "Query failed : $query<br />\n" . $stmt->error );
      $result = $stmt->get_result()
              or die( "Query failed : $query<br />\n" . $stmt->error );
      
      $row  = mysqli_fetch_array($result, MYSQLI_ASSOC);
      $last_userNamePAM = $row['userNamePAM'];
      $result->close();
      $stmt->close();
  }

  // Get cluster information
  global $clusters;
  $userClusterAuth = array();
  foreach ( $clusters as $cluster )
  {
    if ( isset($_POST[$cluster->short_name]) == 'on' )
      $userClusterAuth[] = $cluster->short_name;
  }

  $clusterAuth = implode( ":", $userClusterAuth );

  // Get operator permissions
  $instrumentIDs = array();
  foreach( $_POST as $ndx => $value )
  {
    $exploded = explode( "_", $ndx );
    if ( count( $exploded ) > 1 ) {
      $prefix       = $exploded[ 0 ];
      $instrumentID = $exploded[ 1 ];
      if ( $prefix == 'inst' && $value == 'on' ) {
        $instrumentIDs[] = $instrumentID;
      }
    }
  }

  if ( empty($message) )
  {
    // language=MariaDB
    $query = "UPDATE people " .
             "SET lname             = ?,   " .
             "fname                 = ?,   " .
             "organization          = ?,   " .
             "address               = ?,   " .
             "city                  = ?,   " .
             "state                 = ?,   " .
             "zip                   = ?,   " .
             "country               = ?,   " .
             "phone                 = ?,   " .
             "email                 = ?,   " .
             "activated             = ?,   " .
             "userlevel             = ?,   " .
             "advancelevel          = ?,   " .
             "clusterAuthorizations = ?    ";
    $args = [ $lname, $fname, $organization, $address, $city, $state, $zip, $country, $phone, $email, $activated,
        $userlevel, $advancelevel, $clusterAuth ];
    $arg_types = 'ssssssssssiiis';
    if ( isset( $enable_GMP ) && $enable_GMP ) {
      $query .= ", gmpReviewerRole = ? ";
      $args[] = $gmpReviewerRole;
      $arg_types .= 's';
    }
    if ( isset( $enable_PAM ) && $enable_PAM ) {
        $query .= ", authenticatePAM = ?, userNamePAM = ? ";
        $args[] = $authenticatePAM;
        $args[] = $userNamePAM;
        $arg_types .= 'is';
    }
    $query .= "WHERE personID = ? ";
    $arg_types .= 'i';
    $args[] = $personID;
    $stmt = $link->prepare( $query );
    $stmt->bind_param( $arg_types, ...$args );
    $stmt->execute()
          or die( "Query failed : $query<br />\n" . $stmt->error );
    $stmt->close();

    // Now delete operator permissions, because we're going to redo it
    $query  = "DELETE FROM permits " .
              "WHERE personID = ? ";
    $stmt = $link->prepare( $query );
    $stmt->bind_param( 'i', $personID );
    $stmt->execute()
          or die( "Query failed : $query<br />\n" . $stmt->error );
    $stmt->close();

    $query  = "INSERT INTO permits " .
        "SET instrumentID = ?, " .
        "personID         = ? ";
    $stmt = $link->prepare( $query );


    // Now add the new ones
    foreach ( $instrumentIDs as $instrumentID )
    {
      $stmt->bind_param( 'ii', $instrumentID, $personID );
      $stmt->execute()
            or die( "Query failed : $query<br />\n" . $stmt->error );
    }

    if ( $enable_PAM ) {
      if ( $last_userNamePAM != $userNamePAM ) {
        $_SESSION['message'] = 
            grant_integrity( $last_userNamePAM, false, 0 )
            . "<br>"
            . grant_integrity( $userNamePAM, $authenticatePAM, $userlevel )
            ;
      } else {
        $_SESSION['message'] = 
            grant_integrity( $userNamePAM, $authenticatePAM, $userlevel )
            ;
      }
    }       
  }

  else
    $_SESSION['message'] = "The following errors were noted:<br />" .
                           $message .
                           "Changes were not recorded.";

  header("Location: {$_SERVER['PHP_SELF']}?personID=$personID");
}

// Function to create a new record
function do_create($link)
{
  global $enable_PAM;
  global $enable_GMP;

  include 'get_user_info.php';

  $guid = uuid();

  if ( empty($message) )
  {
    $userlevel = 1; // default for new users

    $query = "INSERT INTO people " .
             "SET lname        = ?, " .
             "fname            = ?, " .
             "personGUID       = ?, " .
             "organization     = ?, " .
             "address          = ?, " .
             "city             = ?, " .
             "state            = ?, " .
             "zip              = ?, " .
             "country          = ?, " .
             "phone            = ?, " .
             "email            = ?, " .
             "userlevel        = ?, " .
             "advancelevel     = 0, " .
             "activated        = 1, " .
             "password         = '__invalid__',      " .
             "signup           = NOW()  ";    // use the default cluster auths
    $args = [ $lname, $fname, $guid, $organization, $address, $city, $state, $zip, $country, $phone, $email,
        $userlevel ];
    $args_type = 'sssssssssssi';
    if ( isset( $enable_GMP ) && $enable_GMP ) {
      $query .= ", gmpReviewerRole = ? ";
      $args[] = $gmpReviewerRole;
      $args_type .= 's';
    }
    if ( isset( $enable_PAM ) && $enable_PAM ) {
        $query .= ", authenticatePAM = ? , userNamePAM = ? ";
        $args[] = $authenticatePAM;
        $args[] = $userNamePAM;
        $args_type .= 'is';
    }
    $stmt = $link->prepare( $query );
    $stmt->bind_param( $args_type, ...$args );
    $stmt->execute()
          or die("Query failed : $query<br />\n" . $stmt->error);
    $new = $stmt->insert_id;
    $stmt->close();

    if ( $enable_PAM ) {
       $_SESSION['message'] = grant_integrity( $userNamePAM, $authenticatePAM, $userlevel );
    }       

    header("Location: {$_SERVER['PHP_SELF']}?personID=$new");
    return;
  }

  else
    $_SESSION['message'] = "The following errors were noted:<br />" .
                           $message .
                           "New user was not created!";

  header("Location: {$_SERVER['PHP_SELF']}");
}

// Function to display and navigate records
function display_record($link)
{
  global $enable_PAM;
  global $enable_GMP;

  // Find a record to display
  $personID = get_id($link);
  if ($personID === false)
    return;

  $query  = "SELECT lname, fname, organization, " .
            "address, city, state, zip, country, phone, email, " .
            "activated, userlevel, advancelevel, clusterAuthorizations ";
  if ( $enable_GMP ) {
      $query .= ", gmpReviewerRole, ";
  }
  if ( $enable_PAM ) {
      $query .= ", authenticatePAM, userNamePAM ";
  }
  $query .= "FROM people WHERE personID = ? ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $personID );
  $stmt->execute()
  or die("Query failed : $query<br />\n" . $stmt->error);
  $result = $stmt->get_result()
  or die("Query failed : $query<br />\n" . $stmt->error);

  $row    = mysqli_fetch_array($result, MYSQLI_ASSOC);

  foreach ($row as $key => $value)
  {
    $$key = (empty($value)) ? "" : html_entity_decode(stripslashes( $value ));
  }
  $result->close();
  $stmt->close();

  $userlevel             = $row['userlevel'];    // 0 translates to null
  $advancelevel          = $row['advancelevel']; // 0 translates to null
  $gmpReviewerRole       = $row['gmpReviewerRole'] ?? 'NONE';
  $authenticatePAM       = $row['authenticatePAM'] ?? 0;
  $userNamePAM           = $row['userNamePAM'] ?? '';
  $activated             = ( $row['activated'] == 1 ) ? "yes" : "no";
  $clusterAuth           = explode( ":", $row['clusterAuthorizations'] );
  $clusterAuthorizations = implode( ", ", $clusterAuth );

  // Operator permissions
  $query  = "SELECT name " .
            "FROM permits, instrument " .
            "WHERE permits.personID = ? " .
            "AND permits.instrumentID = instrument.instrumentID ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $personID );
  $stmt->execute()
        or die("Query failed : $query<br />\n" . $stmt->error);
  $result = $stmt->get_result()
          or die("Query failed : $query<br />\n" . $stmt->error);

  $instruments = array();
  while ( list( $instName ) = mysqli_fetch_array( $result ) )
    $instruments[] = $instName;
  $instruments_text = implode( ", ", $instruments );
  $result->close();
  $stmt->close();

  // Populate a list box to allow user to jump to another record
  $nav_listbox =  "<select name='nav_box' id='nav_box' " .
                  "        onchange='get_person(this);' >" .
                  "  <option value='null'>None selected...</option>\n";
  $querywhere = "";
  if ( $_SESSION['userlevel'] == 0 ) {
      $querywhere = "WHERE userlevel <= 3 ";
  }
  $query  = "SELECT personID, lname, fname FROM people $querywhere" .
            "ORDER BY lname, fname ";
  $result = mysqli_query($link, $query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));
  while (list($t_id, $t_last, $t_first) = mysqli_fetch_array($result))
  {
    $t_last   = html_entity_decode( stripslashes($t_last)  );
    $t_first  = html_entity_decode( stripslashes($t_first) );
    $selected = ($personID == $t_id) ? " selected='selected'" : "";
    $nav_listbox .= "  <option$selected value='$t_id'>$t_last, $t_first</option>\n";
  }
  $nav_listbox .= "</select>\n";

  $extrasGMP =
    $enable_GMP
    ? "<tr><th>GMP Reviewer Role:</th>"
      . "<td>$gmpReviewerRole</td></tr>"
    : ""
    ;

  $extrasPAM =
    $enable_PAM
    ? "<tr><th>Authenticate via PAM:</th>"
      . "<td>" . ( $authenticatePAM ? "yes" : "no" ) . "</td></tr>"
      . "<tr><th>User name (PAM):</th>"
      . " <td>$userNamePAM</td></tr>"
    : ""
    ;

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Edit Profile</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'>Jump to: $nav_listbox
                          <input type='submit' name='prior' value='&lt;' />
                          <input type='submit' name='next' value='&gt;' />
                          <input type='submit' name='new' value='New' />
                          <input type='submit' name='edit' value='Edit' />
                          <input type='submit' name='delete' value='Delete' />
                          <input type='hidden' name='personID' value='$personID' />
          </td></tr>
    </tfoot>
    <tbody>
      <tr><th>First Name:</th>
          <td>$fname</td></tr>
      <tr><th>Last Name:</th>
          <td>$lname</td></tr>
      <tr><th>Organization:</th>
          <td>$organization</td></tr>
      <tr><th>Address:</th>
          <td>$address</td></tr>
      <tr><th>City:</th>
          <td>$city</td></tr>
      <tr><th>State (Province):</th>
          <td>$state</td></tr>
      <tr><th>Postal Code (Zip):</th>
          <td>$zip</td></tr>
      <tr><th>Country:</th>
          <td>$country</td></tr>
      <tr><th>Phone:</th>
          <td>$phone</td></tr>
      <tr><th>Email:</th>
          <td>$email</td></tr>
      <tr><th>Activated:</th>
          <td>$activated</td></tr>
      <tr><th>Userlevel:</th>
          <td>$userlevel</td></tr>
      <tr><th>Advance Level:</th>
          <td>$advancelevel</td></tr>
      <tr><th>Cluster Authorizations:</th>
          <td>$clusterAuthorizations</td></tr>
      <tr><th>Instrument Permissions:</th>
          <td>$instruments_text</td></tr>
      $extrasGMP
      $extrasPAM
    </tbody>
  </table>
  </form>

HTML;
}

// Function to figure out which record to display
function get_id($link)
{
  // See if we are being directed to a particular record
  if (isset($_GET['personID']))
  {
    $personID = $_GET['personID'];
    settype( $personID, 'int' );       // Removes any remaining characters in URL
    return( $personID );
  }

  // We don't know which record, so just find the first one
  $query  = "SELECT personID FROM people " .
            "ORDER BY lname, fname " .
            "LIMIT 1 ";
  $result = mysqli_query($link, $query)
      or die("Query failed : $query<br />\n" . mysqli_error($link));

  if (mysqli_num_rows($result) == 1)
  {
    list($personID) = mysqli_fetch_array($result);
    return( $personID );
  }

  // If we're here, there aren't any records
echo<<<HTML
  <form action='{$_SERVER['PHP_SELF']}' method='post'>
  <table cellspacing='0' cellpadding='0' class='style1'>
    <thead>
      <tr><th colspan='2'>Edit Profile</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='new' value='New' />
          </td></tr>
    </tfoot>
    <tbody>
      <tr><th>Status:</th>
          <td>There are no records to display</td></tr>
    </tbody>
  </table>
  </form>

HTML;

  return( false );
}

// Function to edit a record
function edit_record($link)
{
  global $enable_GMP;
  global $enable_PAM;

  // Get the record we need to edit
  $personID = $_POST['personID'];


  $query  = "SELECT lname, fname, organization, " .
      "address, city, state, zip, country, phone, email, " .
      "activated, userlevel, advancelevel, clusterAuthorizations ";
  if ( $enable_GMP ) {
      $query .= ", gmpReviewerRole, ";
  }
  if ( $enable_PAM ) {
      $query .= ", authenticatePAM, userNamePAM ";
  }
  $query .= "FROM people WHERE personID = ? ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $personID );

  $stmt->execute()
          or die("Query failed : $query<br />\n" . $stmt->error);
  $result = $stmt->get_result()
          or die("Query failed : $query<br />\n" . $stmt->error);

  $row = mysqli_fetch_array($result);

  $lname           = html_entity_decode(stripslashes($row['lname']));
  $fname           = html_entity_decode(stripslashes($row['fname']));
  $organization    = html_entity_decode(stripslashes($row['organization']));
  $address         = html_entity_decode(stripslashes($row['address']));
  $city            = html_entity_decode(stripslashes($row['city']));
  $state           = html_entity_decode(stripslashes($row['state']));
  $zip             = html_entity_decode(stripslashes($row['zip']));
  $country         = html_entity_decode(stripslashes($row['country']));
  $phone           =                                 $row['phone'];
  $email           =                    stripslashes($row['email']);
  $userlevel       =                                 $row['userlevel'];
  $advancelevel    =                                 $row['advancelevel'];
  $clusterAuth     =                                 $row['clusterAuthorizations'];
  $gmpReviewerRole =                                 $row['gmpReviewerRole'] ?? 'NONE';
  $authenticatePAM =                                 $row['authenticatePAM'] ?? 0;
  $userNamePAM     =                                 $row['userNamePAM'] ?? $email;
  $result->close();
  $stmt->close();

  // Create dropdowns
  $userlevel_text    = userlevel_select( $userlevel );
  $advancelevel_text = advancelevel_select( $advancelevel );
  $activated_chk     = ( $row['activated'] == 1 ) ? " checked='checked'" : "";
  $activated_text    = "<input type='checkbox'name='activated'$activated_chk />";

  // Figure out checks for cluster authorizations
  global $clusters;
  foreach ( $clusters as $cluster )
  {
    // This produces variables like this: $checked_bcf, $checked_alamo, etc.
    $checked_cluster  = "checked_$cluster->short_name";
    $$checked_cluster = ( strpos($clusterAuth, $cluster->short_name) === false ) ? "" : "checked='checked'";
  }

  $cluster_table = "<table cellspacing='0' cellpadding='5' class='noborder'>\n";
  foreach ( $clusters as $cluster )
  {
    $checked_cluster  = "checked_$cluster->short_name";
    $cluster_table   .= "  <tr><td>$cluster->short_name:</td>\n" .
                        "      <td><input type='checkbox' " .
                        "name='$cluster->short_name' {$$checked_cluster} /></td>\n" .
                        "  </tr>\n";
  }
  $cluster_table .= "</table>\n";

  // A list of all the instruments
  $query  = "SELECT instrumentID, name " .
            "FROM instrument ";
  $result = mysqli_query($link, $query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));
  $instruments = array();
  while ( list( $instrumentID, $instName ) = mysqli_fetch_array( $result ) )
    $instruments[ $instrumentID ] = $instName;

  // A list of current user operator permissions
  $query  = "SELECT instrumentID " .
            "FROM permits " .
            "WHERE personID = ? " ;
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $personID );
  $stmt->execute()
        or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result()
          or die( "Query failed : $query<br />\n" . $stmt->error );

  $instrAuth = array();
  while ( list( $instrumentID ) = mysqli_fetch_array( $result ) )
    $instrAuth[] = $instrumentID;
  $stmt->close();
  $result->close();
  $instrAuth_text = implode( ":", $instrAuth );

  foreach ( $instruments as $instrumentID => $instName )
  {
    // This produces variables like this: $checked_1, $checked_2, based on ID's
    $checked_instr  = "checked_$instrumentID";
    $instrID        = "$instrumentID";   // as a string
    $$checked_instr = ( strpos( $instrAuth_text, $instrID ) === false ) ? "" : "checked='checked'";
  }

  $instrument_table = "<table cellspacing='0' cellpadding='5' class='noborder'>\n";
  foreach ( $instruments as $instrumentID => $instName )
  {
    $checked_instrument  = "checked_$instrumentID";
    $instrument_table   .= "  <tr><td>$instName:</td>\n" .
                           "      <td><input type='checkbox' name='inst_$instrumentID' {$$checked_instrument} /></td>\n" .
                           "  </tr>\n";
  }
  $instrument_table .= "</table>\n";

  $authenticatePAM_text =
     "<input type='checkbox' name='authenticatePAM'"
     . ( $authenticatePAM ? " checked" : "" )
     . ">"
     ;

  $extrasGMP =
    $enable_GMP
    ? "<tr><th>GMP Reviewer Role:</th>"
      . "<td>"
      . "<select name='gmpReviewerRole'>"
      . "<option value='NONE'" . ( $gmpReviewerRole == "NONE" ? " selected" : "" ) . ">None</option>"
      . "<option value='REVIEWER'" . ( $gmpReviewerRole == "REVIEWER" ? " selected" : "" ) . ">Reviewer</option>"
      . "<option value='APPROVER'" . ( $gmpReviewerRole == "APPROVER" ? " selected" : "" ) . ">Approver</option>"
      . "</select>"
      . "</td>"
      . "</tr>"
    : ""
    ;

  $extrasPAM =
    $enable_PAM
    ? "<tr><th>Authenticate via PAM:</th>"
      .  "<td>$authenticatePAM_text</td></tr>"
      .  "<tr><th>User name (PAM):</th>"
      .  "<td><input type='text' name='userNamePAM' size='40'"
      .  "          maxlength='64' value='$userNamePAM' /></td></tr>"
    : ""
    ;
    
echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post"
        onsubmit="return validate(this);">
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Edit Profile</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='update' value='Update' />
                          <input type='hidden' name='personID' value='$personID' />
                          <input type='reset' /></td></tr>
    </tfoot>
    <tbody>

    <tr><th>First Name:</th>
        <td><input type='text' name='fname' size='40'
                   maxlength='64' value='$fname' /></td></tr>
    <tr><th>Last Name:</th>
        <td><input type='text' name='lname' size='40'
                   maxlength='64' value='$lname' /></td></tr>
    <tr><th>Organization:</th>
        <td><input type='text' name='organization' size='40'
                   maxlength='128' value='$organization' /></td></tr>
    <tr><th>Address:</th>
        <td><input type='text' name='address' size='40'
                   maxlength='128' value='$address' /></td></tr>
    <tr><th>City:</th>
        <td><input type='text' name='city' size='40'
                   maxlength='64' value='$city' /></td></tr>
    <tr><th>State (Province):</th>
        <td><input type='text' name='state' size='40'
                   maxlength='64' value='$state' /></td></tr>
    <tr><th>Postal Code (Zip):</th>
        <td><input type='text' name='zip' size='40'
                   maxlength='16' value='$zip' /></td></tr>
    <tr><th>Country:</th>
        <td><input type='text' name='country' size='40'
                   maxlength='64' value='$country' /></td></tr>
    <tr><th>Phone:</th>
        <td><input type='text' name='phone' size='40'
                   maxlength='64' value='$phone' /></td></tr>
    <tr><th>Email:</th>
        <td><input type='text' name='email' size='40'
                   maxlength='64' value='$email' /></td></tr>
    <tr><th>Activated:</th>
        <td>$activated_text</td></tr>
    <tr><th>Userlevel:</th>
        <td>$userlevel_text</td></tr>
    <tr><th>Advance Level:</th>
        <td>$advancelevel_text</td></tr>
    <tr><th>Cluster Authorizations:</th>
        <td>$cluster_table</td></tr>
    <tr><th>Instrument Permissions:</th>
        <td>$instrument_table</td></tr>

    $extrasGMP
    $extrasPAM
    </tbody>
  </table>
  </form>

HTML;
}

// Function to create a new record
function do_new($link)
{
   global $enable_GMP;
   global $enable_PAM;

   $extrasGMP =
    $enable_GMP
    ? "<tr><th>GMP Reviewer Role:</th>"
      . "<td>"
      . "<select name='gmpReviewerRole'>"
      . "<option value='NONE'>None</option>"
      . "<option value='REVIEWER'>Reviewer</option>"
      . "<option value='APPROVER'>Approver</option>"
      . "</select>"
      . "</td>"
      . "</tr>"
    : ""
    ;

   $extrasPAM =
    $enable_PAM
    ? "<tr><th>Authenticate via PAM:</th>"
      . "<td><input type='checkbox' name='authenticatePAM' checked>"
      . "</td></tr>"
      . "<tr><th>User name (PAM):</th>"
      . "<td><input type='text' name='userNamePAM' size='40'"
      . "               maxlength='64'></td></tr>"
    : ""
    ;

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post"
        onsubmit="return validate(this);">
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Create a New Profile</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='create' value='Create' />
                          <input type='reset' /></td></tr>
    </tfoot>
    <tbody>

    <tr><th>First Name:</th>
        <td><input type='text' name='fname' size='40'
                   maxlength='64' /></td></tr>
    <tr><th>Last Name:</th>
        <td><input type='text' name='lname' size='40'
                   maxlength='64' /></td></tr>
    <tr><th>Organization:</th>
        <td><input type='text' name='organization' size='40'
                   maxlength='128' /></td></tr>
    <tr><th>Address:</th>
        <td><input type='text' name='address' size='40'
                   maxlength='128' /></td></tr>
    <tr><th>City:</th>
        <td><input type='text' name='city' size='40'
                   maxlength='64' /></td></tr>
    <tr><th>State (Province):</th>
        <td><input type='text' name='state' size='40'
                   maxlength='64' /></td></tr>
    <tr><th>Postal Code (Zip):</th>
        <td><input type='text' name='zip' size='40'
                   maxlength='16' /></td></tr>
    <tr><th>Country:</th>
        <td><input type='text' name='country' size='40'
                   maxlength='64' /></td></tr>
    <tr><th>Phone:</th>
        <td><input type='text' name='phone' size='40'
                   maxlength='64' /></td></tr>
    <tr><th>Email:</th>
        <td><input type='text' name='email' size='40'
                   maxlength='64' /></td></tr>

    $extrasGMP
    $extrasPAM

    </tbody>
  </table>
  </form>

HTML;
}
