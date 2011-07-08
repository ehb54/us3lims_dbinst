<?php
/*
 * edit_instruments.php
 *
 * A place to edit/update the instrument table
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( ( $_SESSION['userlevel'] != 3 ) &&
     ( $_SESSION['userlevel'] != 4 ) &&
     ( $_SESSION['userlevel'] != 5 ) )  // super, admin and superadmin only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';

// Are we being directed here from a push button?
if (isset($_POST['prior']))
{
  do_prior();
  exit();
}

else if (isset($_POST['next']))
{
  do_next();
  exit();
}

else if (isset($_POST['delete']))
{
  do_delete();
  exit();
}

else if (isset($_POST['update']))
{
  do_update();
  exit();
}

else if (isset($_POST['create']))
{
  do_create();
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
  edit_record();

else if ( isset($_POST['new']) )
  do_new();

else
  display_record();

?>
</div>

<?php
include 'footer.php';
exit();

// Function to redirect to prior record
function do_prior()
{
  $instrumentID = $_POST['instrumentID'];

  $query  = "SELECT instrumentID FROM instrument " .
            "ORDER BY name ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  // Find prior record
  $current = null;
  list($current) = mysql_fetch_array($result);
  while ($current != null && $instrumentID != $current)
  {
    $prior = $current;
    list($current) = mysql_fetch_array($result);
  }

  $redirect = ($prior == null) ? "" : "?ID=$prior";
  header("Location: $_SERVER[PHP_SELF]$redirect");
  exit();
}

// Function to redirect to next record
function do_next()
{
  $instrumentID = $_POST['instrumentID'];

  $query  = "SELECT instrumentID FROM instrument " .
            "ORDER BY name ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  // Find next record
  $next = null;
  while ($instrumentID != $next)
    list($next) = mysql_fetch_array($result);
  list($next) = mysql_fetch_array($result);

  $redirect = ($next == null) ? "?ID=$instrumentID" : "?ID=$next";
  header("Location: $_SERVER[PHP_SELF]$redirect");
  exit();
}

// Function to delete the current record
function do_delete()
{
  $instrumentID = $_POST['instrumentID'];

  // Add checks here to prevent deleting records that can't be deleted

  $query  = "SELECT COUNT(*) FROM experiment " .
            "WHERE instrumentID = $instrumentID ";
  $result = mysql_query($query)
            or die("Query failed : $query<br />\n" . mysql_error());
  list( $count ) = mysql_fetch_array( $result );
  if ( $count == 0 )
  {
    // Ok to delete
    $query  = "DELETE FROM permits " .
              "WHERE instrumentID = $instrumentID ";
    mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

    $query = "DELETE FROM instrument " .
             "WHERE instrumentID = $instrumentID ";
    mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());
  }

  header("Location: $_SERVER[PHP_SELF]");
}

// Function to update the current record
function do_update()
{
  $ID = $_SESSION['id'];

  $instrumentID        =                                $_POST['instrumentID'];
  $labID               =                                $_POST['labID'];
  $name                =        addslashes(htmlentities($_POST['name']));
  $serialNumber        =        addslashes(htmlentities($_POST['serialNumber']));
  $query = "UPDATE instrument " .
           "SET labID  = $labID, " .
           "name  = '$name', " .
           "serialNumber  = '$serialNumber', " .
           "dateUpdated  = NOW() " .
           "WHERE instrumentID = $instrumentID ";

  mysql_query($query)
    or die("Query failed : $query<br />\n" . mysql_error());

  header("Location: $_SERVER[PHP_SELF]?ID=$instrumentID");
  exit();
}

// Function to create a new record
function do_create()
{

  $labID               =                                $_POST['labID'];
  $name                =        addslashes(htmlentities($_POST['name']));
  $serialNumber        =        addslashes(htmlentities($_POST['serialNumber']));
  $query = "INSERT INTO instrument " .
           "SET labID  = $labID, " .
           "name  = '$name', " .
           "serialNumber  = '$serialNumber', " .
           "dateUpdated  = NOW() ";

  mysql_query($query)
        or die("Query failed : $query<br />\n" . mysql_error());
  $new = mysql_insert_id();

  header("Location: {$_SERVER['PHP_SELF']}?ID=$new");
}

// Function to display and navigate records
function display_record()
{
  // Find a record to display
  $instrumentID = get_id();
  if ($instrumentID === false)
    return;

  $query  = "SELECT i.labID, i.name AS name, serialNumber, " .
            "lab.name AS labName " .
            "FROM instrument i, lab " .
            "WHERE instrumentID = $instrumentID " .
            "AND i.labID = lab.labID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row    = mysql_fetch_array($result, MYSQL_ASSOC);

  // Create local variables; make sure IE displays empty cells properly
  foreach ($row as $key => $value)
  {
    $$key = (empty($value)) ? "&nbsp;" : html_entity_decode( stripslashes( nl2br($value) ) );
  }

  // Populate a list box to allow user to jump to another record
  $nav_listbox =  "<select name='nav_box' id='nav_box' " .
                  "        onchange='get_lab(this);' >" .
                  "  <option value='null'>None selected...</option>\n";
  $query  = "SELECT instrumentID, name FROM instrument ORDER BY name";
  $result = mysql_query($query)
            or die("Query failed : $query<br />\n" . mysql_error());
  while (list($t_id, $t_name) = mysql_fetch_array($result))
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
          <td>$labName</td></tr>
      <tr><th>Instrument Name:</th>
          <td>$name</td></tr>
      <tr><th>Serial Number:</th>
          <td>$serialNumber</td></tr>
    </tbody>
  </table>
  </form>

HTML;
}

// Function to figure out which record to display
function get_id()
{
  // See if we are being directed to a particular record
  if (isset($_GET['ID']))
    return( $_GET['ID'] );

  // We don't know which record, so just find the first one
  $query  = "SELECT instrumentID FROM instrument " .
            "ORDER BY name " .
            "LIMIT 1 ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  if (mysql_num_rows($result) == 1)
  {
    list($instrumentID) = mysql_fetch_array($result);
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
function edit_record()
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

  $query  = "SELECT labID, name, serialNumber  " .
            "FROM instrument " .
            "WHERE instrumentID = $instrumentID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row = mysql_fetch_array($result);

  $labID               =                                   $row['labID'];
  $name                = html_entity_decode( stripslashes( $row['name'] ) );
  $serialNumber        = html_entity_decode( stripslashes( $row['serialNumber'] ) );

  $lab_text = lab_select( "labID", $labID );

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
function do_new()
{

  $lab_text = lab_select( "labID" );

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
