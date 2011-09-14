<?php
/*
 * selectboxes.php
 * arrays of things for this group, to be included in other files
 *
 */

include_once 'lib/utility.php';

// A list of project status possibilities
$project_status = array();

$project_status['submitted'] = 'Submitted';
$project_status['designed']  = 'Designed';
$project_status['scheduled'] = 'Scheduled';
$project_status['uploaded']  = 'Uploaded';
$project_status['anlyzed']   = 'Analyzed';     // Check spelling in db
$project_status['invoiced']  = 'Invoiced';
$project_status['paid']      = 'Paid';
$project_status['other']     = 'Other';

// Function to create a dropdown for project status
function project_status_select( $select_name, $current_status = NULL )
{
  global $project_status;

  $text = "<select name='$select_name' size='1'>\n" .
                  "  <option value='None'>Please select...</option>\n";
  foreach ( $project_status as $status => $display )
  {
    $selected = ( $current_status == $status ) ? " selected='selected'" : "";
    $text .= "  <option value='$status'$selected>$display</option>\n";
  }

  $text .= "</select>\n";

  return $text;
}

// Function to create a dropdown of user levels
function userlevel_select( $userlevel = 0 )
{
  // Create userlevel drop down
  $myUserlevel = $_SESSION['userlevel'];
  $ulimit = ( $myUserlevel >= 3 ) ? $myUserlevel : 0;

  if ( $userlevel > $myUserlevel ) 
  {
     $text = "<input type='hidden' name='userlevel' value='$userlevel' /> " .
             "<input type='text' value='$userlevel' disabled='disabled' />\n";
     return $text;
  }

  $text = "<select name='userlevel'>\n";
  for ( $x = 0; $x <= $ulimit; $x++ )
  {
    $selected = ( $userlevel == $x ) ? " selected='selected'" : "";
    $text    .= "  <option value='$x'$selected>$x</option>\n";
  }
  $text .= "</select>\n";

  return $text;
}

// Function to display list box of AdvanceLevels
function advancelevel_select( $level = 0 )
{
  $text = "<select name='advancelevel'>\n";
  for ( $x = 0; $x <= 1; $x++ )
  {
    $selected = ( $level == $x ) ? " selected='selected'" : "";
    $text    .= "  <option value='$x'$selected>$x</option>\n";
  }
  $text .= "</select>\n";

  return $text;
}

// Function to create a dropdown for available labs
function lab_select( $select_name, $current_lab = NULL )
{
  $query  = "SELECT labID, name " .
            "FROM lab " .
            "ORDER BY name ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />" . mysql_error() );

  if ( mysql_num_rows( $result ) == 0 ) return "";

  $text = "<select name='$select_name' size='1'>\n";
  while ( list( $labID, $name ) = mysql_fetch_array( $result ) )
  {
    $selected = ( $current_lab == $labID ) ? " selected='selected'" : "";
    $text .= "  <option value='$labID'$selected>$name</option>\n";
  }

  $text .= "</select>\n";

  return $text;
}

