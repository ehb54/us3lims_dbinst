<?php
/*
 * queue_setup_2.php
 *
 * Display experiments and save queue setup for a supercomputer analysis
 *
 */
include_once 'checkinstance.php';
elogrsp( __FILE__ );

if ( ($_SESSION['userlevel'] != 2) &&
     ($_SESSION['userlevel'] != 3) &&
     ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) )    // only data analyst and up
{
  header('Location: index.php');
  if ( $is_cli ) {
    $errstr = "ERROR: " . __FILE__ . " user level is insufficient";
    echo "$errstr\n";
    $cli_errors[] = $errstr;
    return;
  }
  exit();
} 

// Verify that job submission is ok now
include_once 'lib/motd.php';
if ( motd_isblocked() && ($_SESSION['userlevel'] < 4) )
{
  if ( $is_cli ) {
    $errstr =  "ERROR: " . __FILE__ . " Job submission is blocked";
    echo "$errstr\n";
    $cli_errors[] = $errstr;
    return;
  }
  header("Location: index.php");
  exit();
}

include 'config.php';
include 'db.php';
include_once 'queue_setup_2_funcs.php';

$advancelevel   = ( isset($_SESSION['advancelevel']) )
                ? $_SESSION['advancelevel'] : 0;

// Save queue information if Save Queue Information posted
$data_missing = false;
if ( isset( $_POST['save'] ) )
{
  // Verify we have all the info
  get_setup_2( $link );
  if ( $is_cli && count( $cli_errors ) ) {
     return;
  }

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
    if ( $is_cli ) {
      $_REQUEST = [];
      $_POST    = [];
      include "queue_setup_3.php";
      return;
    } else {
      header( "Location: queue_setup_3.php" );
      echo __FILE__ . " exiting 3\n";
      exit();
    }
  } else {
     if ( $is_cli ) {
        $errstr = "ERROR: " . __FILE__ . " data missing\n";
        echo "$errstr\n";
        $cli_errors[] = $errstr;
        return;
     }
  }
}

else if ( isset( $_SESSION['new_submitter'] ) )   // Are we gathering info from previous screen?
{
  if ( $is_cli ) {
    echo __FILE__ . " post gathering info from previous screen\n";
  }
  get_setup_1( $link );
  unset( $_SESSION['anchor_count'] );
}

else   // no, gathering info from here
{
  if ( $is_cli ) {
    echo __FILE__ . " post gathering info from here\n";
  }
  get_setup_2( $link );
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
  $editedData_text  = get_editedData( $link, $rawDataID, $cell['editedDataID'] );

  $noise_text       = get_noise( $link, $rawDataID, $cell['editedDataID'], 
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

if ( $advancelevel != 0 )
{
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
}
else
{  // advancelevel==0
echo <<<HTML

 <div><p>
   By default, all the latest edits and noises for chosen cells will
   be selected. To make individual manual edit and noise selections,
   the user must have a non-0 "Advance Level" setting. That option
   is rarely needed.
 </p></div>
HTML;
}

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
if ( $is_cli ) {
    return;
}
exit();

?>
