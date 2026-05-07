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

  if ( cancelLocalJob( $gfacID, $cluster ) ) {
   ## Let's update what user sees until canceled
     updateLimsStatus( $gfacID, 'aborted',  "Job has been canceled"  );
  updateGFACStatus( $gfacID, 'CANCELED', "Job has been canceled"  );
  }
  }
  mysqli_close( $gLink );
}

// Function to cancel a local job
function cancelLocalJob( $gfacID, $cluster )
{
   # elog( "cancel_local_job( $gfacID )" );
   global $global_cluster_details;

   $self = "queue_viewer.php::cancelLocalJob";
   $ruser     = "us3"; 

   if ( !array_key_exists( $cluster, $global_cluster_details ) ) {
       elog( "$self cluster $cluster missing from global_config.php \$global_cluster_details" );
       return false;
   }
       
   if ( !array_key_exists( 'name', $global_cluster_details[$cluster] ) ) {
       elog( "$self 'name' key missing from global_config.php \$global_cluster_details[$cluster]" );
       return false;
   }

   $login = $global_cluster_details[$cluster]['name'];

   if ( array_key_exists( 'login', $global_cluster_details[$cluster] ) ) {
       $login = $global_cluster_details[$cluster]['login'];
   }

   $cmd_prefix = "ssh -x $login ";

   if ( array_key_exists( 'localhost', $global_cluster_details[$cluster] ) 
        && $global_cluster_details[$cluster]['localhost'] ) {
       $cmd_prefix = "";
   }

   $cmd    = "$cmd_prefix scancel $gfacID 2>&1";

   elog( "$self gfacID $gfacID cluster $cluster" );

   $result = exec( $cmd );
   elog( "$self locstat: cmd=$cmd  result=$result" );

   $secwait    = 2;
   $num_try    = 0;
   ## Sleep and retry up to 3 times if ssh has "ssh_exchange_identification" error
   while ( preg_match( "/ssh_exchange_id/", $result )  &&  $num_try < 3 )
   {
      sleep( $secwait );
      $num_try++;
      $secwait   *= 2;
      elog( "$self  num_try=$num_try  secwait=$secwait" );
   }

   ## should likely verify if canceled, perhaps via a call to get_local_status()
   return true;
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
  $us3link = mysqli_connect( '127.0.0.1', 'us3php', $upasswd, $db );
  if ( ! $us3link ) return false;

  $query  = "UPDATE HPCAnalysisResult SET " .
            "queueStatus = ?, " .
            "lastMessage = ? " .
            "WHERE gfacID = ? ";
  $args = [$status, mysqli_real_escape_string($us3link,$message), $gfacID];
  $stmt = mysqli_prepare( $us3link, $query );
  $stmt->bind_param( 'sss', ...$args );
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

  $status = strtoupper( $status );

  // Update gfac status
  // language=MariaDB
  $query  = "UPDATE analysis " .
            "SET status = ?, " .
            "queue_msg = ? " .
            "WHERE gfacID = ? ";
  $args = [ $status, $message, $gfacID ];
  $stmt = mysqli_prepare( $gLink, $query );
  $stmt->bind_param( 'sss', ...$args );
  $stmt->execute()
        or die( "Query failed : $query<br />\n" . $stmt->error );
  $stmt->close();
  mysqli_close( $gLink );
}

function get_gfacIDs_authorized()
{
  global $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname;
  global $ipaddr, $dbname;
    // Start by getting info from global db
    $globaldb = mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname )
    or die( "Connect failed :  $globaldbhost  $globaldbuser $globaldbpasswd  $globaldbname " );

    if ( ! $globaldb )
    {
        echo "<p>Cannot open global database on $globaldbhost  mysqli_error($globaldb)</p>\n";
        return array();
    }

    ## Phase 4: use localhost config flag instead of IP address detection
    ## If any active cluster is localhost, this is a single-tenant USiaB deployment
    $is_local_deploy = false;
    if ( isset( $global_cluster_details ) && is_array( $global_cluster_details ) ) {
        foreach ( $global_cluster_details as $cd ) {
            if ( !empty( $cd['localhost'] ) ) {
                $is_local_deploy = true;
                break;
            }
        }
    }

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
