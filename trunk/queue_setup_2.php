<?php
/*
 * queue_setup_2.php
 *
 * Display experiments and save queue setup for a supercomputer analysis
 *
 */
include_once 'checkinstance.php';

if ( ($_SESSION['userlevel'] != 2) &&
     ($_SESSION['userlevel'] != 3) &&
     ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) )    // only data analyst and up
{
  header('Location: index.php');
  exit();
} 

// Verify that job submission is ok now
include 'lib/motd.php';
if ( motd_isblocked() && ($_SESSION['userlevel'] < 4) )
{
  header("Location: index.php");
  exit();
}

include 'config.php';
include 'db.php';

$data_missing = false;
if ( isset( $_POST['save'] ) )
{
  // Verify we have all the info
  get_setup_2();

  // Now translate into a data structure more useful for sequencing datasets
  $_SESSION['request'] = array();
  $count = 0;
  foreach( $_SESSION['cells'] as $rawDataID => $cell )
  {
    // Check to see if we have all the editedDataID's
    if ( !isset($cell['editedDataID']) ||
         $cell['editedDataID'] == 0    ||
         empty ($cell['editFilename']) )
       $data_missing = true;

    $_SESSION['request'][$count]['rawDataID']    = $rawDataID;
    $_SESSION['request'][$count]['path']         = $cell['path'];
    $_SESSION['request'][$count]['filename']     = $cell['filename'];
    $_SESSION['request'][$count]['editedDataID'] = $cell['editedDataID'];
    $_SESSION['request'][$count]['editFilename'] = $cell['editFilename'];
    $_SESSION['request'][$count]['noiseIDs']     = $cell['noiseIDs'];
    $_SESSION['request'][$count]['editMeniscus'] = $cell['editMeniscus'];
    $_SESSION['request'][$count]['dataLeft']     = $cell['dataLeft'];
    $_SESSION['request'][$count]['dataRight']    = $cell['dataRight'];

    $count++;
  }

  if ( !$data_missing )
  {
     header( "Location: queue_setup_3.php" );
     exit();
  }
}

else if ( isset( $_SESSION['new_submitter'] ) )   // Are we gathering info from previous screen?
{
  get_setup_1();
  unset( $_SESSION['anchor_count'] );
}

else   // no, gathering info from here
{
  get_setup_2();
}

// Start displaying page
$page_title = "Queue Setup (part 2)";
$css = 'css/queue_setup.css';
include 'header.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Queue Setup (part 2)</h1>

<?php

$out_text = "";
$count_anchors = 0;        // just for creating some named anchors
foreach ( $_SESSION['cells'] as $rawDataID => $cell )
{
  $editedData_text  = get_editedData( $rawDataID, $cell['editedDataID'] );

  $noise_text       = get_noise( $rawDataID, $cell['editedDataID'], 
                                 $cell['noiseIDs'] );

  $missing1 = "";
  $missing2 = "";
  
  if ( ( !isset($cell['editedDataID'])   ||
         $cell['editedDataID'] == 0      ||
         empty ($cell['editFilename']) ) &&
       $data_missing                     )
  {
    // If $data_missing is true, then the user has pressed the save button 
    // without selecting all the edit profiles
    $missing1 = "<span class='message'>***</span>";
    $missing2 = "<p class='message'>*** You must select an edit profile for each cell</p>\n";
  }

  $out_text .= <<<HTML

  <a name='anchor_$count_anchors'></a>
  <fieldset>
    <legend style='font-size:110%;font-weight:bold;'>{$cell['filename']}</legend>

    <table cellpadding='3' cellspacing='0'>
    <tr><th>Edit Profile</th>
        <td>$editedData_text</td>
        <td>$missing1</td></tr>
    <tr><th>Noise</th>
        <td colspan='2'>$noise_text</td></tr>
    </table>

    $missing2

  </fieldset>

HTML;

   $count_anchors++;

}
$_SESSION['max_anchor'] = $count_anchors - 1;

// calculate anchor to jump to
$anchor_no = 0;
if ( !isset( $_SESSION['anchor_count'] ) )
  $anchor_count = -1;
else
  $anchor_count = $_SESSION['anchor_count'];

$anchor_count++;
$_SESSION['anchor_count'] = $anchor_count;
$anchor_no  = (int)( $anchor_count / 2 );
$max_anchor = $_SESSION['max_anchor'];
if ( $anchor_no > $max_anchor )
  $anchor_no = $max_anchor;
if ( $anchor_no < 1 )
  $anchor = "setup_form";
else
  $anchor = "anchor_$anchor_no";

// Set or reset edit selection type (0=auto, 1=manual)
if ( isset( $_POST['edit_select_type'] ) )
{
  $edit_select_type = $_POST['edit_select_type'] == 'manualedits'
                      ? 1
                      : 0;
}

else if ( isset( $_SESSION['edit_select_type'] ) )
  $edit_select_type = $_SESSION['edit_select_type'];

else
  $edit_select_type = 0;

$_SESSION['edit_select_type'] = $edit_select_type;
if ( $edit_select_type == 0 )
{
  $edauto_checked="  checked='checked'";
  $edmanu_checked="";
}
else
{
  $edauto_checked="";
  $edmanu_checked="  checked='checked'";
}

// Display and set edit selection type radio buttons
echo <<<HTML

 <fieldset>
 <legend style='font-size:110%;font-weight:bold;'>Edit and Noise Selection</legend>
 <ul class='edit_select'>
   <li><form action='$_SERVER[PHP_SELF]' method='post'>
       By default, all the latest edits for chosen cells will be
       selected, along with the latest associated noises.
       Alternatively, you may make individual manual selections.

       <table cellspacing='0' cellpadding='3px'>
       <tr><td><label>
               <input type='radio' name='edit_select_type'
                      value='autoedits'$edauto_checked
                      onclick='this.form.submit();' />
                 Use latest edits and noises
               </label></td></tr>
       <tr><td><label>
               <input type='radio' name='edit_select_type'
                      value='manualedits'$edmanu_checked
                      onclick='this.form.submit();' />
                 Make individual manual selections
               </label></td></tr>
       </table>
       </form>
   </li>
 </ul>
 </fieldset>

HTML;

if ( $edit_select_type == 1 )
{ // If manual selection, present each edit profile
echo <<<HTML

  <!--h4>Select the edit profile and noise files for each cell</h4-->
  <h4>Select the edit profile each cell</h4>

HTML;
}
else
{ // For default auto selection, no edit profiles get shown
  $out_text = "";
}


// Present edits, if need be, and final Save button
echo <<<HTML
  <div>
  <a name='setup_form'></a>
  <form action="{$_SERVER['PHP_SELF']}#$anchor" method="post">

    $out_text

  <input type="submit" name='save' value="Save Queue Information"/>
  </form></div>

HTML;

?>

</div>

<?php
include 'footer.php';
exit();

// Get information from queue_setup_1.php
function get_setup_1()
{
  if ( isset( $_SESSION['new_submitter'] ) )
  {
    $new_submitter = trim( $_SESSION['new_submitter'] );

    // Replace blanks with commas, in case user added others
    $new_submitter = preg_replace ( "/,/", ',', $new_submitter);
    $new_submitter = preg_replace ( "/\s+/", ',', $new_submitter);
    $new_submitter = preg_replace ( "/,+/", ',', $new_submitter);
    $new_submitter = preg_replace ( "/^,/", '', $new_submitter);
  }

  else if ( isset( $_SESSION['submitter_email'] ) )
    $new_submitter = $_SESSION['submitter_email'];

  if ( empty( $new_submitter ) )
  {
    // Must have, or can't proceed
    $_SESSION['message1'] = "*   Email address was missing";
  }

  if ( isset($_SESSION['add_owner']) && $_SESSION['add_owner'] == 1 )
  {
    $query  = "SELECT email FROM people " .
              "WHERE personID = {$_SESSION['id']} ";
    $result = mysql_query($query)
              or die("Query failed : $query<br />" . mysql_error());
    list($owner_email) = mysql_fetch_array($result);

    // Let's double check that the owner email isn't already there
    $pos = strpos($new_submitter, $owner_email);
    if ($pos === false)       // have to use === to find the string in position 0 too
      $new_submitter .= ",$owner_email";
  }

  // Get experiment ID
  $new_experimentID = 0;
  if ( isset( $_SESSION['new_expID'] ) )
  {
    $new_experimentID = $_SESSION['new_expID'];
  }

  if ( $new_experimentID == 0 )
  {
    // Another must have
    $_SESSION['message2'] = "**  You must choose an experiment before proceeding";
  }

  // Now check for cells (at least one should be selected)
  $new_cell_array = array();
  if ( isset( $_SESSION['new_cells'] ) )
  {
    $new_cell_array = $_SESSION['new_cells'];
  }

  if ( count( $new_cell_array ) < 1 )
  {
    $_SESSION['message3'] = "*** You must select at least one cell";
  }

  if ( isset( $_SESSION['message1'] ) || 
       isset( $_SESSION['message2'] ) ||
       isset( $_SESSION['message3'] ) )
  {
    header("Location: queue_setup_1.php");
    exit();
  }

  // Ok, input parameters are here, so we can proceed
  unset( $_SESSION['new_submitter'] );
  unset( $_SESSION['add_owner'] );
  unset( $_SESSION['new_expID'] );
  unset( $_SESSION['new_cells'] );

  $_SESSION['submitter_email'] = $new_submitter;
  $_SESSION['experimentID']    = $new_experimentID;

  // Set up a temporary data structure
  unset( $_SESSION['cells'] );
  $cells = array();
  foreach ( $new_cell_array as $rawDataID => $filename )
  {
    $cells[$rawDataID] = array();
    $cells[$rawDataID]['path']         = dirname ( $filename );
    $cells[$rawDataID]['filename']     = basename( $filename );
    $cells[$rawDataID]['editedDataID'] = 0;
    $cells[$rawDataID]['editFilename'] = '';
//    $cells[$rawDataID]['modelID']      = 0;
    $cells[$rawDataID]['noiseIDs']     = array();
  }

  $_SESSION['cells'] = $cells;
}

// Build information from current page
function get_setup_2()
{
  $no_posts=true;

  if ( isset( $_POST['editedDataID'] ) )
  {
    $no_posts=false;
    foreach ( $_POST['editedDataID'] as $rawDataID => $editedDataID )
    {
      $_SESSION['cells'][$rawDataID]['editedDataID'] = $editedDataID;

      // Get other things we need too
      $query  = "SELECT filename, data FROM editedData " .
                "WHERE editedDataID = $editedDataID ";
      $result = mysql_query( $query )
              or die("Query failed : $query<br />\n" . mysql_error());
      list( $editFilename, $editXML ) = mysql_fetch_array( $result );
      $_SESSION['cells'][$rawDataID]['editFilename'] = $editFilename;
      getOtherEditInfo( $rawDataID, $editXML );
    }
  }

  if ( isset( $_POST['noiseIDs'] ) )
  {
    $no_posts=false;
    foreach ( $_POST['noiseIDs'] as $rawDataID => $noiseIDs )
    {
      $_SESSION['cells'][$rawDataID]['noiseIDs'] = array();

      // Check if user had the "Select noise..." selected
      if ( ! in_array( 'null', $noiseIDs ) )
         $_SESSION['cells'][$rawDataID]['noiseIDs'] = $noiseIDs;   // each of these is an array
    }
  }
  if ( $no_posts )
  {
    if ( isset( $_SESSION['edit_select_type'] ) )
    {
      if ( $_SESSION['edit_select_type'] == 0 )
      { // Auto-Edit-Select:  get latest edits and noises
        get_latest_edits( );
      }
    }
  }
}

// Get edit profiles
function get_editedData( $rawDataID, $editedDataID = 0 )
{
  $query  = "SELECT editedDataID, label, filename " .
            "FROM editedData " .
            "WHERE rawDataID = $rawDataID ";
  $result = mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());

  $profile    = "<select name='editedDataID[$rawDataID]'" .
                "  onchange='this.form.submit();' size='3'>\n" .
                "  <option value='null'>Select edit profile...</option>\n";
  while ( list( $eID, $label, $fn ) = mysql_fetch_array( $result ) )
  {
    $parts    = explode( ".", $fn ); // runID, editID, runType, c,c,w, xml
    $edit_txt  = $parts[1];
    $selected = ( $editedDataID == $eID ) ? " selected='selected'" : "";
    $profile .= "  <option value='$eID'$selected>$label [$edit_txt]</option>\n";
  }

  $profile   .= "</select>\n";

  return( $profile );
}

// Get the noise files
function get_noise( $rawDataID, $editedDataID, $noiseIDs )
{
  $noise  = "<select name='noiseIDs[$rawDataID][]' multiple='multiple'" .
            "  onchange='this.form.submit();' size='8'>\n" .
            "  <option value='null'>Select noise ...</option>\n";

  $query  = "SELECT noiseID, modelID, noiseType, timeEntered " .
            "FROM noise " .
            "WHERE editedDataID = $editedDataID " .
            "ORDER BY timeEntered DESC ";

  $result = mysql_query( $query )
          or die("Query failed : $query<br />\n" . mysql_error());

  while ( list( $nID, $modelID, $noiseType, $time ) = mysql_fetch_array( $result ) )
  {
    $selected = ( in_array( $nID, $noiseIDs ) ) ? " selected='selected'" : "";
    $noise .= "  <option value='$nID'$selected>[$nID] $noiseType - $time</option>\n";
  }

  $noise   .= "</select>\n";

  return( $noise );
}

// Function to get information from the XML to check meniscus value later
function getOtherEditInfo( $rawDataID, $xml )
{
  $parser = new XMLReader();
  $parser->xml( $xml );

  while( $parser->read() )
  {
     $type = $parser->nodeType;

     if ( $type == XMLReader::ELEMENT )
     {
        $name = $parser->name;
  
        if ( $name == "meniscus" )
        {
          $parser->moveToAttribute( 'radius' );
          $_SESSION['cells'][$rawDataID]['editMeniscus'] = $parser->value;
        }

        else if ( $name == 'data_range' )
        {
          $parser->moveToAttribute( 'left' );
          $_SESSION['cells'][$rawDataID]['dataLeft'] = $parser->value;

          $parser->moveToAttribute( 'right' );
          $_SESSION['cells'][$rawDataID]['dataRight'] = $parser->value;
        }
     }
  }

  $parser->close();
}

// Get latest edit and noises information for all cells
function get_latest_edits( )
{
  $_SESSION['request'] = array();
  $count = 0;
  foreach( $_SESSION['cells'] as $rawDataID => $cell )
  {
    $query  = "SELECT editedDataID, label, filename, data, " .
              " lastUpdated " .
              "FROM editedData " .
              "WHERE rawDataID = $rawDataID " .
              "ORDER BY label, lastUpdated DESC";
    $result = mysql_query( $query )
            or die("Query failed : $query<br />\n" . mysql_error());

    list( $editedDataID, $label, $filename, $editXML ) = mysql_fetch_array( $result );
    getOtherEditInfo( $rawDataID, $editXML );

    $noiseIDs = array();
    $query  = "SELECT noiseID, noiseType, timeEntered " .
              "FROM noise " .
              "WHERE editedDataID = $editedDataID " .
              "ORDER BY timeEntered DESC ";

    $result = mysql_query( $query )
            or die("Query failed : $query<br />\n" . mysql_error());

    $knoise = 0;
    $prtype = "";
    $prtime = 0;
    while ( list( $noiseID, $noiseType, $time ) = mysql_fetch_array( $result ) )
    {
      if ( $knoise == 0 )
      {
        $noiseIDs[$knoise] = $noiseID;
        $prtype = $noiseType;
        $prtime = $time;
        $knoise++;
      }
      else if ( $knoise == 1 )
      {
        if ( $prtype == $noiseType )    break;
        if ( ( $time - $prtime ) > 2 )  break;
        $noiseIDs[$knoise] = $noiseID;
        $knoise++;
        break;
      }
    }

    $cell = $_SESSION['cells'][$rawDataID];
    $_SESSION['request'][$count]['rawDataID']    = $rawDataID;
    $_SESSION['request'][$count]['experimentID'] = $cell['experimentID'];
    $_SESSION['request'][$count]['path']         = $cell['path'];
    $_SESSION['request'][$count]['filename']     = $cell['filename'];
    $_SESSION['request'][$count]['editedDataID'] = $editedDataID;
    $_SESSION['request'][$count]['editFilename'] = $filename;
    $_SESSION['request'][$count]['editMeniscus'] = $cell['editMeniscus'];
    $_SESSION['request'][$count]['dataLeft']     = $cell['dataLeft'];
    $_SESSION['request'][$count]['dataRight']    = $cell['dataRight'];
    $_SESSION['request'][$count]['noiseIDs']     = $noiseIDs;
    $_SESSION['cells'][$rawDataID]['editedDataID']  = $editedDataID;
    $_SESSION['cells'][$rawDataID]['editFilename']  = $filename;
    $_SESSION['cells'][$rawDataID]['noiseIDs']      = $noiseIDs;
    $count++;
  }
}

?>
