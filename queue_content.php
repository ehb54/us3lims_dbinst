<?php
/*
 * queue_content.php
 *
 * Creates the content for the LIMS3 queue viewer. Updated by Ajax.
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( $_SESSION['userlevel'] < 2 )
{
  header('Location: index.php');
  exit();
} 

include_once 'config.php';

// Start by getting info from global db
$globaldb = mysql_connect( $globaldbhost, $globaldbuser, $globaldbpasswd );

if ( ! $globaldb )
{
  echo "<p>Cannot open global database on $globaldbhost</p>\n";
  return;
}

if ( ! mysql_select_db( $globaldbname, $globaldb ) ) 
{
  echo "<p>Cannot change to global database $globaldbname</p>\n";
  return;
}

$query  = "SELECT gfacID, us3_db FROM analysis " .
          "ORDER BY time ";
$result = mysql_query( $query )
          or die( "Query failed : $query<br />" . mysql_error());
if ( mysql_num_rows( $result ) == 0 )
{
  echo "<p>No jobs are currently queued.</p>\n";
  return;
}

// Get all the info from the global db at once to avoid switching more than necessary
$global_info = array();
while ( list( $gfacID, $us3_db ) = mysql_fetch_array( $result ) )
  $global_info[ $gfacID ] = $us3_db;

// Now get the info we need from each of the local databases
$display_info = array();
foreach ( $global_info as $l_gfacID => $db )
{
  $info = get_status( $l_gfacID, $db );
  if ( $info !== false )
    $display_info[] = $info;
}

// Sort $display_info according to preferred sort_order
$sort_order = 'submitTime';
if ( isset( $_SESSION['queue_viewer_sort_order'] ) )
  $sort_order = $_SESSION['queue_viewer_sort_order'];
uasort( $display_info, 'cmp' );

$content = "<div class='queue_content'>\n";

$count_jobs = count( $display_info );
$content .= "<p>There are $count_jobs job(s) queued.</p>\n";

$content .= "<table>\n";
$content .= "<tr><td colspan='5' class='decoration'><hr/></td></tr>\n";

foreach( $display_info as $display )
{
  foreach ( $display as $key => $value )
  {
    $$key = (empty($value)) ? "" : html_entity_decode(stripslashes( nl2br($value)) );
  }

  $db_info = ( $_SESSION['userlevel'] >= 2 ) ? "$database (ID: $HPCAnalysisRequestID)" : "";

  $content .= "<tr><th>Run ID:</th>\n" .
            "<td colspan='3'>$runID $triple $db_info</td>\n" .
            "<td rowspan='6'>\n" .
            display_buttons( $database, $clusterName, $gfacID, $jobEmail ) .
            "</td></tr>\n";

  $content .= <<<HTML
  <tr><th>Owner:</th>
      <td colspan='3'>$jobEmail</td></tr>

  <tr><th>Last message:</th>
      <td colspan='3'>$lastMessage</td></tr>

  <tr><th>Status:</th>
      <td class='$queueStatus'>$queueStatus</td>
      <th>Analysis Type:</th>
      <td>$method</td></tr>

  <tr><th>Submitted on:</th>
      <td>$submitTime</td>
      <th rowspan='2'>Running on:</th>
      <td rowspan='2'>$clusterName</td></tr>

  <tr><th>Last Status Update:</th>
      <td>$updateTime</td></tr>

  <tr><td colspan='5' class='decoration'><hr/></td></tr>
HTML;
}

$content .= "</table>\n";

$content .= "</div>\n";

echo $content;
exit();

// Function to get the information we need from an individual database
function get_status( $gfacID, $us3_db )
{
  // Using credentials that will work for all databases
  $link = mysql_connect( 'uslims3.uthscsa.edu', 'us3php', 'us3' );
  if ( ! $link ) return false;

  $result = mysql_select_db($us3_db, $link);
  if ( ! $result ) return false;

  // Ok, now get what we can from the HPC tables
  $query  = "SELECT r.HPCAnalysisRequestID, queueStatus, lastMessage, updateTime, editXMLFilename, " .
            "investigatorGUID, submitterGUID, submitTime, clusterName, method, runID " .
            "FROM HPCAnalysisResult r, HPCAnalysisRequest q, experiment " .
            "WHERE r.gfacID = '$gfacID' " .                                 // limit to 1 record right off
            "AND r.HPCAnalysisRequestID = q.HPCAnalysisRequestID " .
            "AND q.experimentID = experiment.experimentID ";
  $result = mysql_query( $query );
  if ( ! $result || mysql_num_rows( $result ) == 0 )
    return false;

  $status = mysql_fetch_array( $result, MYSQL_ASSOC );
  
  // Make a few helpful changes
  $triple = '';
  if ( ! empty( $status['editXMLFilename'] ) )
  {
    $xmlparts = array();
    $xmlparts = explode( '.', $status['editXMLFilename'] );
    $triple   =                  '( ' .
                $xmlparts[ 3 ] . '/' .
                $xmlparts[ 4 ] . '/' .
                $xmlparts[ 5 ] . ' )';
  }
  unset( $status['editXMLFilename'] );
  $status['triple'] = $triple;

  $email = '';
  $query  = "SELECT email FROM people " .
            "WHERE personGUID = '{$status['investigatorGUID']}' ";
  $result = mysql_query( $query );
  if ( $result && mysql_num_rows( $result ) == 1 )
    list( $jobEmail ) = mysql_fetch_array( $result );

  if ( $status['investigatorGUID'] != $status['submitterGUID'] )
  {
    $query  = "SELECT email FROM people " .
              "WHERE personGUID = '{$status['submitterGUID']}' ";
    $result = mysql_query( $query );
    if ( $result && mysql_num_rows( $result ) == 1 )
    {
      list( $submitterEmail ) = mysql_fetch_array( $result );
      $jobEmail .= " ($submitterEmail)";
    }
  }
  $status['jobEmail'] = $jobEmail;

  $status['database'] = $us3_db;
  $status['gfacID']   = $gfacID;

  return $status;
}

// A function to compare to items
function cmp( $a, $b )
{
  global $sort_order;

  if ( $a[ $sort_order ] == $b[ $sort_order ] )
    return 0;

  return ( $a[ $sort_order ] < $b[ $sort_order ] ) ? -1 : 1;
}

// If current user is authorized to delete this job, display
//  a delete button
function display_buttons( $current_db, $cluster, $gfacID, $jobEmail )
{
  $buttons = "";

  // For now, since the GFAC cancel_job method is not working
  return "";

  // Let's see if the current job has already been deleted
  $query  = "SELECT COUNT( queueStatus ) FROM HPCAnalysisResult " .
            "WHERE gfacID = '$gfacID' " .
            "AND queueStatus = 'aborted' ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />" . mysql_error());
  list( $count ) = mysql_fetch_array( $result );
  if ( $count > 0 )
    return "";

  if ( is_authorized( $current_db, $jobEmail ) )
  {
    // Button to delete current job from the queue
    $buttons = "<form action='queue_viewer.php' method='post'>\n" .
               "  <input type='hidden' name='cluster' value='$cluster' />\n" .
               "  <input type='hidden' name='gfacID' value='$gfacID' />\n" .
               "  <input type='hidden' name='jobEmail' value='$jobEmail' />\n" .
               "  <input type='submit' name='delete' value='Delete' />\n" .
               "</form>\n";

    // Button to resubmit current job to a different cluster
    /*
    $buttons .= "<form action='resubmit_analysis.php' method='post'>\n" .
                "  <input type='hidden' name='cluster' value='$cluster' />\n" .
                "  <input type='hidden' name='gfacID' value='$gfacID' />\n" .
                "  <input type='hidden' name='jobEmail' value='$jobEmail' />\n" .
               ?? "  <input type='hidden' name='HPCID' value='$HPCAnalysisID' />\n" .
                "  <input type='submit' name='resubmit' value='Resubmit' />\n" .
                "</form>\n";
    */

  }

  return $buttons;
}

// Figure out if current user is authorized to delete this job
function is_authorized( $current_db, $jobEmail )
{
  global $dbname;             // The database we're logged into
  $authorized = false;
  
  // $jobEmail could have multiple emails in it
  $pos = strpos( $jobEmail, $_SESSION['email'] );

  // Userlevel 5 is always authorized
  if ($_SESSION['userlevel'] >= 5)
    $authorized = true;

  // Userlevel 4 is authorized within his own database
  else if ( ($_SESSION['userlevel'] == 4) &&
            ($current_db == $dbname)      )
    $authorized = true;

  // Userlevels 2, 3 are authorized for their own jobs
  else if ( ($_SESSION['userlevel'] >= 2) &&
            ($pos !== false)              )
    $authorized = true;
   
  return ($authorized);
}


?>
