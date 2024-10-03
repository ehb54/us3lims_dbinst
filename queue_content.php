<?php
/*
 * queue_content.php
 *
 * Creates the content for the LIMS3 queue viewer. Updated by Ajax.
 *
 */
include_once 'checkinstance.php';

if ( $_SESSION['userlevel'] < 2 )
{
  header('Location: index.php');
  exit();
}

$start_time = dt_now();

include_once 'config.php';
include_once 'lib/utility.php';

// Start by getting info from global db
$globaldb = mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname )
    or die( "Connect failed :  $globaldbhost  $globaldbuser $globaldbpasswd  $globaldbname " );

if ( ! $globaldb )
{
  echo "<p>Cannot open global database on $globaldbhost  mysqli_error($globaldb)</p>\n";
  return;
}

$is_uiab = ( $ipaddr === '127.0.0.1' ) ? 1 : 0;

$query  = "SELECT gfacID, us3_db, cluster, status FROM analysis ";
if ( $_SESSION['userlevel'] < 4 ) {
  $query .= "WHERE us3_db = '$dbname' ";
}

if ( $_SESSION['userlevel'] == 2 ) {
    $submitterGUID = preg_replace( '/^.*_/', '', $_SESSION["user_id"] );
    $query =
        "SELECT analysis.gfacID, analysis.us3_db, analysis.cluster, analysis.status"
        . " FROM gfac.analysis"
        . " INNER JOIN $dbname.HPCAnalysisResult ON $dbname.HPCAnalysisResult.gfacID = analysis.gfacID"
        . " INNER JOIN $dbname.HPCAnalysisRequest ON $dbname.HPCAnalysisResult.HPCAnalysisRequestID = $dbname.HPCAnalysisRequest.HPCAnalysisRequestID"
        . " WHERE analysis.us3_db = '$dbname' AND $dbname.HPCAnalysisRequest.submitterGUID = '$submitterGUID'"
        . " "
        ;
}

$query .= "ORDER BY time ";
$result = mysqli_query( $globaldb, $query )
          or die( "Query failed : $query<br />" . mysqli_error($globaldb));
if ( mysqli_num_rows( $result ) == 0 )
{
    if ( $_SESSION['userlevel'] < 4 ) {
        if ( $_SESSION['userlevel'] == 2 ) {
            echo "<p>You have no <b>$dbname</b> jobs currently queued, running or completing.</p>\n";
        } else {
            echo "<p>No <b>$dbname</b> jobs are currently queued, running or completing.</p>\n";
        }            
    } else {
        echo "<p>No jobs are currently queued, running or completing.</p>\n";
    }
  return;
}

// Get all the info from the global db at once to avoid switching more than necessary
$global_info = array();
$global_clusters = [];
while ( list( $gfacID, $us3_db, $cluster, $status ) = mysqli_fetch_array( $result ) ) {
    $global_info[ $gfacID ]        = $us3_db;
    $global_clusters[ $gfacID ]    = $cluster;
    $global_gfac_status[ $gfacID ] = $status;
}

// Now get the info we need from each of the local databases
$display_info = array();

foreach ( $global_info as $l_gfacID => $db )
{
  $info = get_status( $l_gfacID, $db );
  #elog( "info:\n" . json_encode( $info, JSON_PRETTY_PRINT ) );
  if ( $info !== false )
    $display_info[$l_gfacID] = $info;
}
mysqli_close( $globaldb );

// Sort $display_info according to preferred sort_order or default to submitTime
$sort_order = $_SESSION['queue_viewer_sort_order'] ?? 'submitTime';
uasort( $display_info, 'cmp' );

$content = "<div class='queue_content'>\n";

$count_jobs = count( $display_info );
$is_are     = "are";
$strjob     = "jobs";
if ( $count_jobs == 1 )
{
  $is_are     = "is";
  $strjob     = "job";
}

if ( $_SESSION['userlevel'] < 4 ) {
    if ( $_SESSION['userlevel'] == 2 ) {
        $content .= "<p>You have $count_jobs <b>$dbname</b> $strjob" .
            " queued, running or completing.</p>\n";
    } else {
        $content .= "<p>There $is_are $count_jobs <b>$dbname</b> $strjob" .
            " queued, running or completing.</p>\n";
    }
} else {
  $content .= "<p>There $is_are $count_jobs $strjob" .
      " queued, running or completing.</p>\n";
}

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
            display_buttons( $database, $cluster, $gfacID, $jobEmail ) .
            "</td></tr>\n";

  $content .= <<<HTML
  <tr><th>Owner:</th>
      <td colspan='3'>$jobEmail</td></tr>

  <tr><th>Last message:</th>
      <td colspan='3'>$lastMessage</td></tr>

  <tr><th>Status:</th>
      <td class='$queueStatus'>$queueStatus</td>
      <th>Analysis Type:</th>
      <td>$analType</td></tr>

  <tr><th>Submitted on:</th>
      <td>$submitTime</td>
      <th rowspan='2'>Running on:</th>
      <td rowspan='2'>$cluster<br>($clusterName)</td></tr>

  <tr><th>Last Status Update:</th>
      <td>$updateTime</td></tr>

  <tr><td colspan='5' class='decoration'><hr/></td></tr>
HTML;
}

$content .= "</table>\n";

$content .= "</div>\n";

elog( sprintf( "queue_content run time %.2fs", dt_duration_seconds( $start_time, dt_now() ) ) );
echo $content;
exit();

// Function to get the information we need from an individual database
function get_status( $gfacID, $us3_db )
{
  global $globaldbhost, $configs;
  global $global_clusters;
  global $global_gfac_status;
  global $globaldb;
  // Using credentials that will work for all databases
  $upasswd = $configs[ 'us3php' ][ 'password' ];

  // Ok, now get what we can from the HPC tables
  $query  = "SELECT r.HPCAnalysisRequestID, queueStatus, lastMessage, updateTime, editXMLFilename, " .
            "investigatorGUID, submitterGUID, submitTime, clusterName, method, runID, analType " .
            "FROM $us3_db.HPCAnalysisResult r, $us3_db.HPCAnalysisRequest q, $us3_db.experiment " .
            "WHERE r.gfacID = '$gfacID' " .                                 // limit to 1 record right off
            "AND r.HPCAnalysisRequestID = q.HPCAnalysisRequestID " .
            "AND q.experimentID = experiment.experimentID " .
            "ORDER BY HPCAnalysisResultID DESC LIMIT 1";
  $result = mysqli_query( $globaldb, $query );
  if ( ! $result || mysqli_num_rows( $result ) == 0 )
    return false;

  $status = mysqli_fetch_array( $result, MYSQLI_ASSOC );

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

  $status['cluster']              = $global_clusters[ $gfacID ];
  $status['gfac_analysis_status'] = $global_gfac_status[ $gfacID ];

  if ( !strncmp( $global_gfac_status[ $gfacID ], 'CANCEL', 6 ) ) {
      $status['queueStatus'] = "canceled";
  }

  #elog( "get_status\n" . json_encode( $status, JSON_PRETTY_PRINT ) );

  $email = '';
  $query  = "SELECT email FROM $us3_db.people " .
            "WHERE personGUID = '{$status['investigatorGUID']}' ";
  $result = mysqli_query( $globaldb, $query );
  if ( $result && mysqli_num_rows( $result ) == 1 )
    list( $jobEmail ) = mysqli_fetch_array( $result );

  if ( $status['investigatorGUID'] != $status['submitterGUID'] )
  {
    $query  = "SELECT email FROM $us3_db.people " .
              "WHERE personGUID = '{$status['submitterGUID']}' ";
    $result = mysqli_query( $globaldb, $query );
    if ( $result && mysqli_num_rows( $result ) == 1 )
    {
      list( $submitterEmail ) = mysqli_fetch_array( $result );
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
  global $display_info;

  $buttons = "";

  // Let's see if the current job has already been deleted
  if ( $display_info[$gfacID]['queueStatus'] == 'aborted' 
       || $display_info[$gfacID]['queueStatus'] == 'canceled' )
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

  // Userlevel >= 4 is always authorized
  if ($_SESSION['userlevel'] >= 4)
    $authorized = true;

  // Userlevel 3 is authorized within his own database
  else if ( ($_SESSION['userlevel'] == 3) &&
            ($current_db == $dbname)      )
    $authorized = true;

  // Userlevel 2 is authorized for their own jobs
  else if ( ($_SESSION['userlevel'] >= 2) &&
            ($pos !== false)              )
    $authorized = true;

  return ($authorized);
}


?>
