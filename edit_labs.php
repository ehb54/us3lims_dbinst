<?php
/*
 * edit_labs.php
 *
 * A place to edit/update the lab table
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
// ini_set('display_errors', 'On');


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

else if (isset($_POST['create']))
{
  do_create($link);
  exit();
}

// Start displaying page
$page_title = 'Edit Labs';
$js = 'js/edit_labs.js';
include 'header.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Edit Labs</h1>
  <!-- Place page content here -->
  <?php if ( isset($_SESSION['message']) )
        {
          echo "<p class='message'>{$_SESSION['message']}</p>\n";
          unset($_SESSION['message']);
        } ?>

<?php
// Edit or display a record
if ( isset($_POST['edit']) )
  edit_record($link);

else if ( isset($_POST['new']) )
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
  $labID = $_POST['labID'];

  $query  = "SELECT labID FROM lab " .
            "ORDER BY name ";
  $result = mysqli_query($link, $query)
      or die("Query failed : $query<br />\n" . mysqli_error($link));

  // Find prior record
  $current = null;
  list($current) = mysqli_fetch_array($result);
  while ($current != null && $labID != $current)
  {
    $prior = $current;
    list($current) = mysqli_fetch_array($result);
  }

  $redirect = ($prior == null) ? "" : "?ID=$prior";
  header("Location: $_SERVER[PHP_SELF]$redirect");
  exit();
}

// Function to redirect to next record
function do_next($link)
{
  $labID = $_POST['labID'];

  $query  = "SELECT labID FROM lab " .
            "ORDER BY name ";
  $result = mysqli_query($link, $query)
      or die("Query failed : $query<br />\n" . mysqli_error($link));

  // Find next record
  $next = null;
  while ($labID != $next)
    list($next) = mysqli_fetch_array($result);
  list($next) = mysqli_fetch_array($result);

  $redirect = ($next == null) ? "?ID=$labID" : "?ID=$next";
  header("Location: $_SERVER[PHP_SELF]$redirect");
  exit();
}

// Function to delete the current record
function do_delete($link)
{
  $labID = $_POST['labID'];

  // Tests to see if the current lab can be deleted
  $found  = false;
  $query  = "SELECT COUNT(*) FROM instrument " .
            "WHERE labID = ? ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $labID );
  $stmt->execute()
        or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result()
        or die( "Query failed : $query<br />\n" . $stmt->error );

  list( $count ) = mysqli_fetch_array( $result );
  $result->close();
  $stmt->close();
  if ( $count > 0 ) $found = true;

  $query  = "SELECT COUNT(*) FROM experiment " .
            "WHERE labID = ? ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $labID );
  $stmt->execute()
        or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result()
          or die( "Query failed : $query<br />\n" . $stmt->error );
  list( $count ) = mysqli_fetch_array( $result );
  $result->close();
  $stmt->close();
  if ( $count > 0 ) $found = true;

  $query  = "SELECT COUNT(*) FROM rotor " .
            "WHERE labID = ? ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $labID );
  $stmt->execute()
         or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result()
            or die( "Query failed : $query<br />\n" . $stmt->error );
  list( $count ) = mysqli_fetch_array( $result );
  $result->close();
  $stmt->close();
  if ( $count > 0 ) $found = true;

  if ( ! $found )
  {
    $query  = "DELETE FROM lab " .
              "WHERE labID = ? ";
    $stmt = $link->prepare( $query );
    $stmt->bind_param( 'i', $labID );
    $stmt->execute()
           or die( "Query failed : $query<br />\n" . $stmt->error );
    $stmt->close();
  }

  else
    $_SESSION['message'] = "This lab cannot be deleted while instruments, " .
                           "rotors, or labs are associated with it.\n";

  header("Location: $_SERVER[PHP_SELF]");
}

// Function to update the current record
function do_update($link)
{
  $labID       =                         $_POST['labID'];
  $name        = addslashes(htmlentities($_POST['name']));
  $contact     = addslashes(htmlentities($_POST['contact']));
  // language=MariaDB
  $query = "UPDATE lab " .
           "SET name  = ?, " .
           "building  = ?, " .
           "dateUpdated  = NOW() " .
           "WHERE labID = ? ";
  $args = [ $name, $contact, $labID ];
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'ssi', ...$args );
  $stmt->execute()
        or die("Query failed : $query<br />\n" . $stmt->error);
  $stmt->close();

  header("Location: $_SERVER[PHP_SELF]?ID=$labID");
  exit();
}

// Function to create a new record
function do_create($link)
{
  $guid = uuid();

  $name        = addslashes(htmlentities($_POST['name']));
  $contact     = addslashes(htmlentities($_POST['contact']));
  $query = "INSERT INTO lab " .
           "SET labGUID = ?, " .
           "name  = ?, " .
           "building  = ?, " .
           "dateUpdated  = NOW() ";
  $args = [ $guid, $name, $contact ];
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'sss', ...$args );
  $stmt->execute()
        or die("Query failed : $query<br />\n" . $stmt->error);
  $new = $stmt->insert_id;
  $stmt->close();

  header("Location: {$_SERVER['PHP_SELF']}?ID=$new");
}

// Function to display and navigate records
function display_record($link)
{
  // Find a record to display
  $labID = htmlentities(get_id($link));
  if ($labID === false)
    return;

  // Anything other than a number here is a security risk
  if (!(is_numeric($labID)))
    return;
  // language=MariaDB
  $query  = "SELECT name, building AS contact " .
            "FROM lab " .
            "WHERE labID = ? ";

  // Prepared statement
  if ($stmt = mysqli_prepare($link, $query)) {
   $stmt->bind_param('i', $labID);
   $stmt->execute();
   $stmt->store_result();
   $num_of_rows = $stmt->num_rows;
   $stmt->bind_result($name, $contact);
   $stmt->fetch();

   $stmt->free_result();
   $stmt->close();
  }

  /* This code was replace by the prepared statement above
  $result = mysqli_query($link, $query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));

  $row    = mysqli_fetch_array($result, MYSQLI_ASSOC);

  // Create local variables; make sure IE displays empty cells properly
  foreach ($row as $key => $value)
  {
    $$key = (empty($value)) ? "&nbsp;" : html_entity_decode( stripslashes( nl2br($value) ) );
  }
  */

  // Populate a list box to allow user to jump to another record
  $nav_listbox =  "<select name='nav_box' id='nav_box' " .
                  "        onchange='get_lab(this);' >" .
                  "  <option value='null'>None selected...</option>\n";
  $query  = "SELECT labID, name FROM lab ORDER BY name";
  $result = mysqli_query($link, $query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));
  while (list($t_id, $t_name) = mysqli_fetch_array($result))
  {
    $selected = ($labID == $t_id) ? " selected='selected'" : "";
    $nav_listbox .= "  <option$selected value='$t_id'>$t_name</option>\n";
  }
  $nav_listbox .= "</select>\n";

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Edit Labs</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'>Jump to: $nav_listbox
                          <input type='submit' name='prior' value='&lt;' />
                          <input type='submit' name='next' value='&gt;' />
                          <input type='submit' name='new' value='New' />
                          <input type='submit' name='edit' value='Edit' />
                          <input type='submit' name='delete' value='Delete' />
                          <input type='hidden' name='labID' value='$labID' />
          </td></tr>
    </tfoot>
    <tbody>
      <tr><th>Facility Name:</th>
          <td>$name</td></tr>
      <tr><th>Contact Information:</th>
          <td>$contact</td></tr>
    </tbody>
  </table>
  </form>

HTML;
}

// Function to figure out which record to display
function get_id($link)
{
  // See if we are being directed to a particular record
  if (isset($_GET['ID']))
    return( $_GET['ID'] );

  // We don't know which record, so just find the first one
  $query  = "SELECT labID FROM lab " .
            "ORDER BY name " .
            "LIMIT 1 ";
  $result = mysqli_query($link, $query)
      or die("Query failed : $query<br />\n" . mysqli_error($link));

  if (mysqli_num_rows($result) == 1)
  {
    list($labID) = mysqli_fetch_array($result);
    return( $labID );
  }

  // If we're here, there aren't any records
echo<<<HTML
  <form action='{$_SERVER[PHP_SELF]}' method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='2'>Edit Labs</th></tr>
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
  // Get the record we need to edit
  if ( isset( $_POST['edit'] ) )
    $labID = $_POST['labID'];

  else if ( isset( $_GET['edit'] ) )
    $labID = $_GET['edit'];

  else
  {
    // How did we get here?
    echo "<p>There was a problem with the edit request.</p>\n";
    return;
  }
  // language=MariaDB
  $query  = "SELECT name, building AS contact " .
            "FROM lab " .
            "WHERE labID = ? ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $labID );
  $stmt->execute()
          or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result()
          or die( "Query failed : $query<br />\n" . $stmt->error );

  $row = mysqli_fetch_array($result);
  $result->close();
  $stmt->close();


  $name    = html_entity_decode( stripslashes( $row['name'] ) );
  $contact = html_entity_decode( stripslashes( $row['contact'] ) );

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post">
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Edit Labs</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='update' value='Update' />
                          <input type='hidden' name='labID' value='$labID' />
                          <input type='reset' /></td></tr>
    </tfoot>
    <tbody>


    <tr><th>Facility Name:</th>
        <td><input type='text' name='name' size='40'
                   maxlength='255' value='$name' /></td></tr>
    <tr><th>Contact Information:</th>
        <td><textarea name='contact' rows='6' cols='65'
                      wrap='virtual'>$contact</textarea></td></tr>

    </tbody>
  </table>
  </form>

HTML;
}

// Function to create a new record
function do_new($link)
{

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post">
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Edit Labs</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='create' value='Create' />
                          <input type='reset' /></td></tr>
    </tfoot>
    <tbody>


    <tr><th>Name:</th>
        <td><input type='text' name='name' size='40'
                   maxlength='256' /></td></tr>
    <tr><th>Contact Information:</th>
        <td><textarea name='contact' rows='6' cols='65'
                      wrap='virtual'></textarea></td></tr>


    </tbody>
  </table>
  </form>

HTML;
}

?>
