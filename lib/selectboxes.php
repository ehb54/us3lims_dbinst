<?php
/*
 * selectboxes.php
 * arrays of things for this group, to be included in other files
 *
 */

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


