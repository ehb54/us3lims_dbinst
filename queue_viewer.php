<?php
/*
 * queue_viewer.php
 *
 * Displays the queue viewer
 *
 */
include_once 'checkinstance.php';

if ( $_SESSION['userlevel'] < 2 )
{
  header('Location: index.php');
  exit();
} 

if ( isset( $_POST['sort_order'] ) )
{
  $_SESSION['queue_viewer_sort_order'] = $_POST['sort_order'];

  header( "Location: {$_SERVER['PHP_SELF']}" );
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';       // Information about the clusters
include_once $class_dir . 'cancel_result.php';   // CANCEL_* outcomes, used by do_delete()

if ( isset( $_POST['delete'] ) )
{
  do_delete();
  header( "Location: {$_SERVER['PHP_SELF']}" );
  exit();
}

// define( 'DEBUG', true );
// Get sort order from session or default to submitTime
$sort_order = $_SESSION['queue_viewer_sort_order'] ?? 'submitTime';

// Start displaying page
$page_title = "Queue Viewer";
$js     = 'js/queue_viewer.js';
$onload = "onload='update_queue_content();'";
$css    = 'css/queue_viewer.css';
include 'header.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Queue Viewer</h1>
  <!-- Place page content here -->

  <h3>LIMS v3 Queue</h3>

  <table>
  <tr><th>Sort order</th>
      <td><?php echo order_select( $sort_order ); ?></td>
  </table>

  <div id='queue_content'></div>

</div>

<?php
include 'footer.php';
exit();

// Function to create a dropdown for sort order
function order_select( $current_order = NULL )
{
  // A list of ways to sort the queue viewer
  $sortorder = array();

  $sortorder['submitTime']   = 'Time submitted';
  $sortorder['runID']        = 'Run ID';
  $sortorder['queueStatus']  = 'Status';
  $sortorder['method']       = 'Analysis type';
  $sortorder['updateTime']   = 'Date last updated';
  $sortorder['clusterName']  = 'Cluster';

  $text  = "<form action='{$_SERVER['PHP_SELF']}' method='post'>\n";
  $text .= "<select name='sort_order' size='1'
                    onchange='this.form.submit();' >\n";
  foreach ( $sortorder as $order => $display )
  {
    $selected = ( $current_order == $order ) ? " selected='selected'" : "";
    $text .= "  <option value='$order'$selected>$display</option>\n";
  }

  $text .= "</select>\n" .
           "</form>\n";

  return $text;
}

// A function to delete the selected job
function do_delete()
{
  $authorized_gfacIDs = get_gfacIDs_authorized();
  if ( isset( $_POST['gfacIDs'] ) && is_array( $_POST['gfacIDs'] ) )
  {
      foreach ( $_POST['gfacIDs'] as $gfacID )
      {
          if ( !in_array($gfacID, $authorized_gfacIDs, true)) {
              continue;
          }
          delete_single_job( $gfacID );
      }
      return;
  }
  if ( isset( $_POST['gfacID'] )) {
      $gfacID   = $_POST['gfacID'];
      if ( !in_array($gfacID, $authorized_gfacIDs, true)) {
          return;
      }
      delete_single_job( $gfacID );
  }
}

function delete_single_job( $gfacID )
{
  global $global_cluster_details;
  global $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname;

  // We need cluster and other info for this gfacID
  $gLink = mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname );
  if ( ! $gLink ) return;

  $query = "SELECT cluster, metaschedulerClusterExecuting FROM analysis WHERE gfacID = ?";
  $stmt = mysqli_prepare( $gLink, $query );
  if ( ! $stmt )
  {
      // Failed to prepare statement; close connection and abort deletion for this job
      mysqli_close( $gLink );
      return;
  }
  mysqli_stmt_bind_param( $stmt, 's', $gfacID );
  mysqli_stmt_execute( $stmt );
  $result = mysqli_stmt_get_result( $stmt );
  mysqli_stmt_close( $stmt );
  if ( $row = mysqli_fetch_assoc( $result ) )
  {
  $cluster = $row['cluster'];
  if ( !empty( $row['metaschedulerClusterExecuting'] ) )
  {
  $cluster = $row['metaschedulerClusterExecuting'];
  }

  $cancel = cancelLocalJob( $gfacID, $cluster );

  if ( cancel_outcome_is_settled( $cancel[ 'outcome' ] ) )
  {
    ## The job is off the cluster, so the LIMS may say so.
    updateLimsStatus( $gfacID, 'aborted',  $cancel[ 'message' ] );
    updateGFACStatus( $gfacID, 'CANCELED', $cancel[ 'message' ] );
  }
  else
  {
    ## We could not confirm the job is gone. Record why, and leave the status
    ## alone: claiming 'aborted' here is what would hide a still-running job.
    updateLimsStatus( $gfacID, null, $cancel[ 'message' ] );
    updateGFACStatus( $gfacID, null, $cancel[ 'message' ] );
  }
  }
  mysqli_close( $gLink );
}

/**
 * Ask the cluster to cancel a job.
 *
 * Returns what cancel_result.php's cancel_outcome_from_result() returns:
 * array( 'outcome' => one of the CANCEL_* constants, 'message' => a sentence
 * fit to show the user ).
 *
 * WHY THIS IS NOT A BOOLEAN. It used to be, and it was hardcoded to true: the
 * old code ran a bare exec( "ssh ... scancel" ), ignored the result, and
 * returned true whether or not scancel had ever run. The caller then wrote
 * queueStatus = 'aborted'. During an outage that is the original Expanse bug
 * pointed the other way -- a transport failure becoming a statement about the
 * job -- and it is worse here, because the job really is still running on the
 * cluster while the LIMS shows it as cancelled and nobody goes looking.
 *
 * The old retry loop was also dead code: it matched ssh_exchange_identification
 * in $result, then slept 2 + 4 + 8 seconds without ever re-running the command,
 * so $result could not change and the one error it claimed to handle was
 * retried zero times.
 *
 * Everything remote now goes through remote_exec, which supplies the connect
 * timeout, BatchMode, exit-code classification, transport-only retry, and the
 * circuit breaker. scancel is idempotent, so retrying a transport fault is
 * safe. The budget is deliberately tighter than the batch paths use: a person
 * is sitting in front of this waiting for the page to come back.
 */
function cancelLocalJob( $gfacID, $cluster )
{
   global $global_cluster_details;
   global $class_dir;

   $self = "queue_viewer.php::cancelLocalJob";

   if ( ! class_exists( 'remote_exec' ) )
      require_once $class_dir . 'remote_exec.php';

   require_once $class_dir . 'cancel_result.php';

   $rx = new remote_exec( $cluster, $global_cluster_details, 'elog' );

   if ( ! $rx->is_configured() )
   {
      elog( "$self cluster $cluster missing or has no 'name' in global_config.php \$global_cluster_details" );

      return array(
         'outcome' => CANCEL_UNCONFIGURED,
         'message' => "Cannot cancel: cluster $cluster is not configured on this LIMS."
      );
   }

   elog( "$self gfacID $gfacID cluster $cluster" );

   ## remote_exec quotes the command for the local ssh invocation, but the
   ## login node's shell parses it again, so the id is escaped here too. It is
   ## already checked against get_gfacIDs_authorized(); this is the second lock.
   $res = $rx->run( 'scancel ' . escapeshellarg( $gfacID ), array(
      'label'      => 'scancel',
      'timeout'    => 15,
      'retries'    => 1,
      'retry_wait' => 2,
   ) );

   $cancel = cancel_outcome_from_result( $res, $cluster );

   elog( "$self gfacID $gfacID {$cancel['outcome']} after {$res['attempts']} attempt(s)"
         . " [{$res['class']} exit {$res['exit_code']}]: {$cancel['message']}" );

   return $cancel;
}

// Function to update the status on an arbitrary lims database
function updateLimsStatus( $gfacID, $status, $message )
{

  //include 'config.php';
  global $globaldbhost;
  global $globaldbuser;
  global $globaldbpasswd;
  global $globaldbname;
  global $configs;
  global $dbhost;

  // Connect to the global GFAC database
  $gLink = mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname );
  if ( ! $gLink )
    return;

  // Get database name
  $query  = "SELECT us3_db FROM analysis " .
            "WHERE gfacID = ?";
  $stmt = mysqli_prepare( $gLink, $query );
  $stmt->bind_param( 's', $gfacID );
  $stmt->execute();
  $result = $stmt->get_result();
  if ( ! $result ) return;
  if ( mysqli_num_rows( $result ) == 0 ) return;
  list( $db ) = mysqli_fetch_array( $result );
  $stmt->close();
  $result->close();
  mysqli_close( $gLink );

  // Using credentials that will work for all databases
  $upasswd = $configs[ 'us3php' ][ 'password' ];
  $us3link = mysqli_connect( $dbhost, 'us3php', $upasswd, $db );
  if ( ! $us3link ) return false;

  ## A null $status means "say what happened without claiming the job changed
  ## state". Used when a cancel could not be confirmed: the user needs the
  ## reason, but queueStatus must keep reflecting the job, not the click.
  if ( $status === null )
  {
    $query = "UPDATE HPCAnalysisResult SET lastMessage = ? WHERE gfacID = ? ";
    $args  = [ $message, $gfacID ];
    $types = 'ss';
  }
  else
  {
    $query = "UPDATE HPCAnalysisResult SET " .
             "queueStatus = ?, " .
             "lastMessage = ? " .
             "WHERE gfacID = ? ";
    $args  = [ $status, $message, $gfacID ];
    $types = 'sss';
  }

  $stmt = mysqli_prepare( $us3link, $query );
  $stmt->bind_param( $types, ...$args );
  $stmt->execute()
        or die( "Query failed : $query<br />\n" . $stmt->error );
  $stmt->close();

  mysqli_close( $us3link );
}

// Function to update the GFAC status, mostly because job is canceled
function updateGFACStatus( $gfacID, $status, $message )
{
  global $globaldbhost;
  global $globaldbuser;
  global $globaldbpasswd;
  global $globaldbname;

  // Connect to the global GFAC database
  $gLink = mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname );
  if ( ! $gLink )
    return;

  // A null $status updates the message only; see updateLimsStatus(). It also
  // keeps us out of analysis.status, which is an ENUM with no member meaning
  // "we could not reach the cluster" -- writing one would be a truncation.
  // language=MariaDB
  if ( $status === null )
  {
    $query = "UPDATE analysis SET queue_msg = ? WHERE gfacID = ? ";
    $args  = [ $message, $gfacID ];
    $types = 'ss';
  }
  else
  {
    $query = "UPDATE analysis " .
             "SET status = ?, " .
             "queue_msg = ? " .
             "WHERE gfacID = ? ";
    $args  = [ strtoupper( $status ), $message, $gfacID ];
    $types = 'sss';
  }

  $stmt = mysqli_prepare( $gLink, $query );
  $stmt->bind_param( $types, ...$args );
  $stmt->execute()
        or die( "Query failed : $query<br />\n" . $stmt->error );
  $stmt->close();
  mysqli_close( $gLink );
}

function get_gfacIDs_authorized()
{
  global $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname;
  global $ipaddr, $dbname;
    // Start by getting info from global db. See the note in queue_content.php:
    // the credentials must not reach the response, and both a thrown
    // mysqli_sql_exception and a false return have to be handled.
    $globaldb       = false;
    $globaldb_error = '';

    try
    {
        $globaldb = mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname );
        if ( ! $globaldb )
            $globaldb_error = mysqli_connect_error();
    }
    catch ( mysqli_sql_exception $e )
    {
        $globaldb_error = $e->getMessage();
    }

    if ( ! $globaldb )
    {
        error_log( "queue_viewer.php: cannot connect to global database "
                   . "$globaldbname on $globaldbhost as $globaldbuser: $globaldb_error" );
        echo "<p>Cannot open the global database. See the server error log.</p>\n";
        return array();
    }

    ## Deployment-level, not per-cluster -- see lib/utility.php
    $is_local_deploy = is_single_tenant_deployment();

    $submitterGUID = preg_replace( '/^.*_/', '', $_SESSION["user_id"] );
    $query =
            "SELECT analysis.gfacID as gfacID, analysis.us3_db, analysis.cluster, analysis.status"
            . " FROM gfac.analysis";

    if ( $is_local_deploy  ||  $_SESSION['userlevel'] < 4 ) {

        $query .= " INNER JOIN $dbname.HPCAnalysisResult ON $dbname.HPCAnalysisResult.gfacID = analysis.gfacID"
        . " INNER JOIN $dbname.HPCAnalysisRequest ON $dbname.HPCAnalysisResult.HPCAnalysisRequestID = $dbname.HPCAnalysisRequest.HPCAnalysisRequestID"
        . " WHERE analysis.us3_db = '$dbname'";
    }

    if ( $_SESSION['userlevel'] == 2 ){
        $query .= " AND ($dbname.HPCAnalysisRequest.submitterGUID = '$submitterGUID' or $dbname.HPCAnalysisRequest.investigatorGUID = '$submitterGUID')";
    }

    $query .= " ORDER BY time ";
    $result = mysqli_query( $globaldb, $query )
    or die( "Query failed : $query<br />");
    $authorized_gfacIDs = array();
    if ( mysqli_num_rows( $result ) == 0 )
    {
        return $authorized_gfacIDs;
    }

    while ( $row = mysqli_fetch_assoc( $result ) )
    {
        $authorized_gfacIDs[] = $row['gfacID'];
    }
    mysqli_close( $globaldb );
    return $authorized_gfacIDs;
}

?>
