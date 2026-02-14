<?php
/*
 * edit_instruments.php
 *
 * A place to edit/update the instrument table
 *
 */
include_once 'checkinstance.php';

if ( ( $_SESSION['userlevel'] != 3 ) &&
     ( $_SESSION['userlevel'] != 4 ) &&
     ( $_SESSION['userlevel'] != 5 ) )  // super, admin and superadmin only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
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

// Start displaying page
$page_title = 'Edit Instruments';
$js = 'js/edit_instruments.js';
include 'header.php';
include 'lib/selectboxes.php';


?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Edit Instruments</h1>
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
  $instrumentID = $_POST['instrumentID'];

  $query  = "SELECT instrumentID FROM instrument " .
            "ORDER BY name ";
  $result = mysqli_query($link, $query)
      or die("Query failed : $query<br />\n" . mysqli_error($link));

  // Find prior record
  $current = null;
  list($current) = mysqli_fetch_array($result);
  while ($current != null && $instrumentID != $current)
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
  $instrumentID = $_POST['instrumentID'];

  $query  = "SELECT instrumentID FROM instrument " .
            "ORDER BY name ";
  $result = mysqli_query($link, $query)
      or die("Query failed : $query<br />\n" . mysqli_error($link));

  // Find next record
  $next = null;
  while ($instrumentID != $next)
    list($next) = mysqli_fetch_array($result);
  list($next) = mysqli_fetch_array($result);

  $redirect = ($next == null) ? "?ID=$instrumentID" : "?ID=$next";
  header("Location: $_SERVER[PHP_SELF]$redirect");
  exit();
}

// Function to delete the current record
function do_delete($link)
{
  $query  = "SELECT COUNT(*) FROM instrument";
  $result = mysqli_query($link, $query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));
  list( $count ) = mysqli_fetch_array( $result );
  if ( $count < 2 )
  {  // Skip deleting if no or only 1 instrument row exists
    echo "<p>Cannot delete the last remaining instrument record.</p>\n";
    //header("Location: $_SERVER[PHP_SELF]");
    //exit();
    return;
  }

  $instrumentID = $_POST['instrumentID'];

  // Add checks here to prevent deleting records that can't be deleted

  $query  = "SELECT COUNT(*) FROM experiment " .
            "WHERE instrumentID = ? ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $instrumentID );
  $stmt->execute()
        or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result()
        or die( "Query failed : $query<br />\n" . $stmt->error );
  list( $count ) = mysqli_fetch_array( $result );
  $result->close();
  $stmt->close();
  if ( $count == 0 )
  {
    // Ok to delete
    $query  = "DELETE FROM permits " .
              "WHERE instrumentID = ? ";
    $stmt = $link->prepare( $query );
    $stmt->bind_param( 'i', $instrumentID );
    $stmt->execute()
          or die( "Query failed : $query<br />\n" . $stmt->error );
    $stmt->close();

    $query = "DELETE FROM instrument " .
             "WHERE instrumentID = ? ";
    $stmt = $link->prepare( $query );
    $stmt->bind_param( 'i', $instrumentID );
    $stmt->execute()
          or die( "Query failed : $query<br />\n" . $stmt->error );
    $stmt->close();
  }

  header("Location: $_SERVER[PHP_SELF]");
}

// Function to update the current record
function do_update($link)
{
  $ID = $_SESSION['id'];

  $instrumentID        =                                $_POST['instrumentID'];
  $labID               =                                $_POST['labID'];
  $name                =        addslashes(htmlentities($_POST['name']));
  $serialNumber        =        addslashes(htmlentities($_POST['serialNumber']));
  // language=MariaDB
  $query = "UPDATE instrument " .
           "SET labID  = ?, " .
           "name  = ?, " .
           "serialNumber  = ?, " .
           "dateUpdated  = NOW() " .
           "WHERE instrumentID = ? ";
  $args = [ $labID, $name, $serialNumber, $instrumentID ];
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'issi', ...$args );
  $stmt->execute()
        or die("Query failed : $query<br />\n" . $stmt->error);
  $stmt->close();


  header("Location: $_SERVER[PHP_SELF]?ID=$instrumentID");
  exit();
}

// Function to create a new record
function do_create($link)
{

  $labID               =                                $_POST['labID'];
  $name                =        addslashes(htmlentities($_POST['name']));
  $serialNumber        =        addslashes(htmlentities($_POST['serialNumber']));
  $query = "INSERT INTO instrument " .
           "SET labID  = ?, " .
           "name  = ?, " .
           "serialNumber  = ?, " .
           "dateUpdated  = NOW() ";
  $args = [ $labID, $name, $serialNumber ];
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'iss', ...$args );
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
  $instrumentID = htmlentities(get_id($link));
  if ($instrumentID === false)
    return;

  // Anything other than a number here is a security risk
  if (!(is_numeric($instrumentID)))
    return;
  // language=MariaDB
  $query  = "SELECT i.labID, i.name, lab.name, i.serialNumber " .
            "FROM instrument i, lab " .
            "WHERE instrumentID = ? " .
            "AND i.labID = lab.labID ";

  // Prepared statement
  if ($stmt = mysqli_prepare($link, $query)) {
   $stmt->bind_param('i', $instrumentID);
   $stmt->execute();
   $stmt->store_result();
   $num_of_rows = $stmt->num_rows;
   $stmt->bind_result( $i_labID, $i_name, $lab_name, $i_serial );
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
                  "        class='onchange-get-lab' >" .
                  "  <option value='null'>None selected...</option>\n";
  $query  = "SELECT instrumentID, name FROM instrument ORDER BY name";
  $result = mysqli_query($link, $query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));
  while (list($t_id, $t_name) = mysqli_fetch_array($result))
  {
    $selected = ($instrumentID == $t_id) ? " selected='selected'" : "";
    $nav_listbox .= "  <option$selected value='$t_id'>$t_name</option>\n";
  }
  $nav_listbox .= "</select>\n";

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Edit Instruments</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'>Jump to: $nav_listbox
                          <input type='submit' name='prior' value='&lt;' />
                          <input type='submit' name='next' value='&gt;' />
                          <input type='submit' name='new' value='New' />
                          <input type='submit' name='edit' value='Edit' />
                          <input type='submit' name='delete' value='Delete' />
                          <input type='hidden' name='instrumentID' value='$instrumentID' />
          </td></tr>
    </tfoot>
    <tbody>
      <tr><th>Lab:</th>
          <td>$lab_name</td></tr>
      <tr><th>Instrument Name:</th>
          <td>$i_name</td></tr>
      <tr><th>Serial Number:</th>
          <td>$i_serial</td></tr>
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
  $query  = "SELECT instrumentID FROM instrument " .
            "ORDER BY name " .
            "LIMIT 1 ";
  $result = mysqli_query($link, $query)
      or die("Query failed : $query<br />\n" . mysqli_error($link));

  if (mysqli_num_rows($result) == 1)
  {
    list($instrumentID) = mysqli_fetch_array($result);
    return( $instrumentID );
  }

  // If we're here, there aren't any records
echo<<<HTML
  <form action='{$_SERVER[PHP_SELF]}' method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='2'>Edit Instruments</th></tr>
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
    $instrumentID = $_POST['instrumentID'];

  else if ( isset( $_GET['edit'] ) )
    $instrumentID = $_GET['edit'];

  else
  {
    // How did we get here?
    echo "<p>There was a problem with the edit request.</p>\n";
    return;
  }
  // language=MariaDB
  $query  = "SELECT labID, name, serialNumber  " .
            "FROM instrument " .
            "WHERE instrumentID = ? ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $instrumentID );
  $stmt->execute()
        or die("Query failed : $query<br />\n" . $stmt->error);
  $result = $stmt->get_result()
        or die("Query failed : $query<br />\n" . $stmt->error);

  $row = mysqli_fetch_array($result);
  $result->close();
  $stmt->close();

  $labID               =                                   $row['labID'];
  $name                = html_entity_decode( stripslashes( $row['name'] ) );
  $serialNumber        = html_entity_decode( stripslashes( $row['serialNumber'] ) );

  $lab_text = lab_select( $link, "labID", $labID );

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post">
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Edit Instruments</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='update' value='Update' />
                          <input type='hidden' name='instrumentID' value='$instrumentID' />
                          <input type='reset' /></td></tr>
    </tfoot>
    <tbody>


    <tr><th>Lab:</th>
        <td>$lab_text</td></tr>
    <tr><th>Instrument Name:</th>
        <td><textarea name='name' rows='6' cols='65'
                      wrap='virtual'>$name</textarea></td></tr>
    <tr><th>Serial Number:</th>
        <td><textarea name='serialNumber' rows='6' cols='65'
                      wrap='virtual'>$serialNumber</textarea></td></tr>

    </tbody>
  </table>
  </form>

HTML;
}

// Function to create a new record
function do_new($link)
{

  $lab_text = lab_select( $link, "labID" );

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post">
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Edit Instruments</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='create' value='Create' />
                          <input type='reset' /></td></tr>
    </tfoot>
    <tbody>


    <tr><th>Lab:</th>
        <td>$lab_text</td></tr>
    <tr><th>Instrument Name:</th>
        <td><textarea name='name' rows='6' cols='65'
                      wrap='virtual'></textarea></td></tr>
    <tr><th>Serial Number:</th>
        <td><textarea name='serialNumber' rows='6' cols='65'
                      wrap='virtual'></textarea></td></tr>

    </tbody>
  </table>
  </form>

HTML;
}

?>
