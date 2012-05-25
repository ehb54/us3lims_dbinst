<?php
/*
 * edit_images.php
 *
 * A place to edit/update the image table
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( $_SESSION['userlevel'] < 1 )
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';

$uploadFilename = '';

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

else if (isset($_POST['new']))
{
  do_new();
  exit();
}

else if (isset($_POST['update']))
{
  do_update();
  exit();
}

// Start displaying page
$page_title = 'Edit Images';
// $css = 'css/edit_images.css';
$js = 'js/edit_images.js';
include 'header.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Edit Images</h1>
  <!-- Place page content here -->
  <?php if ( isset($_SESSION['message']) )
        {
          echo "<p class='message'>{$_SESSION['message']}</p>\n";
          unset($_SESSION['message']);
        } ?>

<?php
// Edit or display a record
if ( isset($_POST['edit']) || isset($_GET['edit']) )
  edit_record();

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
  $ID = $_SESSION['id'];
  $imageID = $_POST['imageID'];

  $query  = "SELECT i.imageID " .
            "FROM image i, imagePerson p " .
            "WHERE p.personID = $ID " .
            "AND p.imageID = i.imageID " .
            "ORDER BY description ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  // Find prior record
  $current = null;
  list($current) = mysql_fetch_array($result);
  while ($current != null && $imageID != $current)
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
  $ID = $_SESSION['id'];
  $imageID = $_POST['imageID'];

  $query  = "SELECT i.imageID " .
            "FROM image i, imagePerson p " .
            "WHERE p.personID = $ID " .
            "AND p.imageID = i.imageID " .
            "ORDER BY description ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  // Find next record
  $next = null;
  while ($imageID != $next)
    list($next) = mysql_fetch_array($result);
  list($next) = mysql_fetch_array($result);

  $redirect = ($next == null) ? "?ID=$imageID" : "?ID=$next";
  header("Location: $_SERVER[PHP_SELF]$redirect");
  exit();
}

// Function to delete the current record
function do_delete()
{
  $imageID = $_POST['imageID'];

  // Delete from tables in an order that will be allowed by
  //   fk constraints
  $query = "DELETE FROM imageAnalyte " .
           "WHERE imageID = $imageID ";
  mysql_query($query)
    or die("Query failed : $query<br />\n" . mysql_error());

  $query = "DELETE FROM imageSolution " .
           "WHERE imageID = $imageID ";
  mysql_query($query)
    or die("Query failed : $query<br />\n" . mysql_error());

  $query = "DELETE FROM imagePerson " .
           "WHERE imageID = $imageID ";
  mysql_query($query)
    or die("Query failed : $query<br />\n" . mysql_error());

  $query = "DELETE FROM image " .
           "WHERE imageID = $imageID ";
  mysql_query($query)
    or die("Query failed : $query<br />\n" . mysql_error());

  header("Location: $_SERVER[PHP_SELF]");
}

// Function to create a new record
function do_new()
{
  $ID = $_SESSION['id'];
  $uuid = uuid();

  $query = "INSERT INTO image " .
           "SET imageGUID  = '$uuid', " .
           "description  = '' " ;
  mysql_query($query)
        or die("Query failed : $query<br />\n" . mysql_error());
  $new = mysql_insert_id();

  // Add the ownership record
  $query  = "INSERT INTO imagePerson SET " .
            "imageID = $new, " .
            "personID  = $ID ";
  mysql_query( $query )
        or die( "Query failed : $query<br />\n" . mysql_error() );

  header("Location: {$_SERVER['PHP_SELF']}?edit=$new");
}

// Function to update the current record
function do_update()
{
  global $data_dir;
  global $uploadFilename;

  $ID = $_SESSION['id'];
  $imageID = $_POST['imageID'];

  $description         = substr(addslashes(htmlentities($_POST['description'])), 0,80);
  $imageType           = $_POST['imageType'];
  $analyteID           = ( isset( $_POST['analyteID']) ) ? $_POST['analyteID'] : -1;
  $solutionID          = ( isset( $_POST['solutionID']) ) ? $_POST['solutionID'] : -1;
  $uploadFilename      = '';
  $gelPicture          = '';

  $query = "UPDATE image " .
           "SET description  = '$description' " .
           "WHERE imageID = $imageID ";
  mysql_query($query)
    or die("Query failed : $query<br />\n" . mysql_error());

  // Process gel image upload, if present
  if ( isset( $_FILES['gelPicture'] ) )
    $message = upload_file( $gelPicture, $data_dir ); // $data_dir from config.php

  if ( $gelPicture !== false )
  {
    // We can't allow uploading of picture without either analyte 
    //   or solution linkage
    if ( (!isset($_POST['analyteID'] )) &&
         (!isset($_POST['solutionID'])) )
    {
       $_SESSION['message'] = "You must associate either an analyte or a solution " .
                              "when uploading an image. File upload was not saved. ";

       header("Location: $_SERVER[PHP_SELF]?ID=$imageID");
       exit();
    }

    $gelPicture  = addslashes( $gelPicture );
    $newFilename = basename( $uploadFilename );

    $query = "UPDATE image " .
             "SET gelPicture = '$gelPicture', " .
             "filename = '$newFilename' " .
             "WHERE imageID = $imageID ";
    mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

    // Now create analyte and solution link table entries
    $query  = "DELETE FROM imageAnalyte " .
              "WHERE imageID = $imageID ";
    mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

    $query  = "DELETE FROM imageSolution " .
              "WHERE imageID = $imageID ";
    mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

    if ( $imageType == 'analyte' )
    {
      $query  = "INSERT INTO imageAnalyte " .
                "SET imageID = $imageID, " .
                "analyteID   = $analyteID ";
      mysql_query($query)
        or die("Query failed : $query<br />\n" . mysql_error());
    }

    else if ( $imageType == 'solution' )
    {
      $query  = "INSERT INTO imageSolution " .
                "SET imageID = $imageID, " .
                "solutionID   = $solutionID ";
      mysql_query($query)
        or die("Query failed : $query<br />\n" . mysql_error());
    }

  }

  header("Location: $_SERVER[PHP_SELF]?ID=$imageID");
  exit();
}

// Function to display and navigate records
function display_record()
{
  // Find a record to display
  $imageID = get_id();
  if ($imageID === false)
    return;

  $query  = "SELECT imageGUID, description, filename, LENGTH(gelPicture) AS imageLength " .
            "FROM image " .
            "WHERE imageID = $imageID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row    = mysql_fetch_array($result, MYSQL_ASSOC);

  // Create local variables; make sure IE displays empty cells properly
  foreach ($row as $key => $value)
  {
    $$key = (empty($value)) ? "&nbsp;" : html_entity_decode( stripslashes( nl2br($value) ) );
  }

  $ID = $_SESSION['id'];
  $imageLength = $row['imageLength'];
  $gelPicture  = ( $imageLength > 0 ) 
               ? "<a href='show_image.php?ID=$imageID'>Show Picture</a> ($filename)"
               : "";

  // Populate a list box to allow user to jump to another record
  $nav_listbox =  "<select name='nav_box' id='nav_box' " .
                  "        onchange='get_image(this);' >" .
                  "  <option value='null'>None selected...</option>\n";
  $query  = "SELECT i.imageID, description " .
            "FROM image i, imagePerson p " .
            "WHERE p.personID = $ID " .
            "AND p.imageID = i.imageID " .
            "ORDER BY description ";
  $result = mysql_query($query)
            or die("Query failed : $query<br />\n" . mysql_error());
  while (list($t_id, $t_description) = mysql_fetch_array($result))
  {
    $selected = ($imageID == $t_id) ? " selected='selected'" : "";
    $nav_listbox .= "  <option$selected value='$t_id'>$t_description</option>\n";
  }
  $nav_listbox .= "</select>\n";

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='2'>Edit Images</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'>Jump to: $nav_listbox
                          <input type='submit' name='prior' value='&lt;' />
                          <input type='submit' name='next' value='&gt;' />
                          <input type='submit' name='new' value='New' />
                          <input type='submit' name='edit' value='Edit' />
                          <input type='submit' name='delete' value='Delete' />
                          <input type='hidden' name='imageID' value='$imageID' />
          </td></tr>
    </tfoot>
    <tbody>
      <tr><th>Description:</th>
          <td>$description</td></tr>
      <tr><th>Image GUID:</th>
          <td>$imageGUID</td></tr>
      <tr><th>Gel Picture:</th>
          <td>$gelPicture</td></tr>
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

  $ID = $_SESSION['id'];

  // We don't know which record, so just find the first one
  $query  = "SELECT i.imageID " .
            "FROM image i, imagePerson p " .
            "WHERE p.personID = $ID " .
            "AND p.imageID = i.imageID " .
            "ORDER BY description " .
            "LIMIT 1 ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  if (mysql_num_rows($result) == 1)
  {
    list($imageID) = mysql_fetch_array($result);
    return( $imageID );
  }

  // If we're here, there aren't any records
echo<<<HTML
  <form action='{$_SERVER[PHP_SELF]}' method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='2'>Edit Images</th></tr>
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
    $imageID = $_POST['imageID'];

  else if ( isset( $_GET['edit'] ) )
    $imageID = $_GET['edit'];

  else
  {
    // How did we get here?
    echo "<p>There was a problem with the edit request.</p>\n";
    return;
  }

  $query  = "SELECT description " .
            "FROM image " .
            "WHERE imageID = $imageID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row = mysql_fetch_array($result);

  $description         = html_entity_decode( stripslashes( $row['description'] ) );

echo<<<HTML
  <form enctype="multipart/form-data" aaction="{$_SERVER['PHP_SELF']}" method="post">
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='2'>Edit Images</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='update' value='Update' />
                          <input type='hidden' name='imageID' value='$imageID' />
                          <input type='reset' /></td></tr>
    </tfoot>
    <tbody>

    <tr><th>Description:</th>
        <td><input type='text' name='description' size='40'
                   maxlength='80' value='$description' /></td></tr>
    <tr><th>Upload Gel Image (jpg, png):</th>
        <td><input type='file' name='gelPicture' size='40' /></td></tr>
    <tr><th>Type of Image</th>
        <td><table cellspacing='0' cellpadding='10' class='noborder'>
              <tr><td><label><input type='radio' name='imageType' id='imageAnalyte'
                                    value='analyte' />
                                    Analyte</label><br />
                      <label><input type='radio' name='imageType' id='imageSolution'
                                    value='solution' />
                                    Solution</label></td>
                  <td><div id='imageLink'>&lt;--- Please select an image type when 
                                          uploading a file</div></td></tr>
            </table></td></tr>

    </tbody>
  </table>
  </form>

HTML;
}

// Function to process the uploading of a gel image
function upload_file( &$image, $upload_dir )
{
  global $uploadFilename;

  $image = false;
  
  if ( ( ! isset( $_FILES['gelPicture'] ) )   || 
       ( $_FILES['gelPicture']['size'] == 0 ) )
    return 'No file was uploaded';

  $uploadFilename=$_FILES['gelPicture']['name'];
  $uploadFile = $upload_dir . "/" . $uploadFilename;

  if ( ! move_uploaded_file( $_FILES['gelPicture']['tmp_name'], $uploadFile) ) 
    return 'Uploaded file could not be moved to data directory';
  
  $fh = fopen( $uploadFile, "r" );
  if ( !$fh )
     return 'Uploaded file could not be opened';

  $image = fread( $fh, filesize($uploadFile) );
  if ( $image === false )
     return 'Error reading uploaded file';

  return '';
}


?>
