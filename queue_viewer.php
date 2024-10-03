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
include $class_dir . 'experiment_cancel.php';

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

  <?php echo page_content2();  ?>

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
  global $global_cluster_details;
  global $clusters, $uses_thrift;               // From utility.php
  global $info;
//  var_dump($uses_thrift);
  $cluster  = $_POST['cluster'];
  $gfacID   = $_POST['gfacID'];
  $jobEmail = $_POST['jobEmail'];

    if ( !array_key_exists( $cluster, $global_cluster_details )
         || !array_key_exists( 'airavata',  $global_cluster_details[ $cluster ] )
         ) {
             ## should message somehow
             elog( "do_delete cluster $cluster not in \$global_config:\$cluster_details or missing keys" );
             return;
    }
    
    if( $global_cluster_details[ $cluster ][ 'airavata' ] ?
        cancelAiravataJob( $gfacID ) :
        cancelLocalJob( $gfacID, $cluster ) ) {
            ## Let's update what user sees until canceled
            updateLimsStatus( $gfacID, 'aborted',  "Job has been canceled"  );
            updateGFACStatus( $gfacID, 'CANCELED', "Job has been canceled"  );
    }

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

// A function to generate page content using lims2 methods
function page_content2()
{
    ## this should never succeed, probably should be removed
    ## page content is now ins queue_content.php

  if ( ! file_exists( '/share/apps64/ultrascan/bin64/mpi_status' ) )
    return;                                             // no lims2 status available

  $content = "<h3>LIMS v2 Queue</h2>\n";

  exec("/share/apps64/ultrascan/bin64/mpi_status", $aData, $iRet );

  // Print queue status timestamp
  $content .= "<h5>$aData[0]:\n" .
              "  <input type='button' value='Refresh'\n" .
              "  onclick='window.location.href=window.location.href;' /></h5>\n";

  // Check if there are any jobs in the queue
  if (sizeof( $aData ) == 3 and $aData[2] == "No jobs are currently queued, running, or completing.")
  {
    $content .= "<p>$aData[2]</p>";
  }

  // Check to see if a Delete button has been pressed
  else if (isset($_POST['delete']))
  {
    $jobid = $_POST['jobid'];
    $jobowner = $_POST['jobowner'];
    $jobtype = $_POST['jobtype'];
    $HPCAnalysisID = $_POST['HPCID'];

    // Double check user authorization
    if (is_authorized($jobowner))
    {
      if ($jobtype == "tigre")
        exec("/share/apps64/ultrascan/bin64/tigre_job_cancel $jobid");
      else if ($jobtype == "mpi")
        exec("/share/apps64/ultrascan/bin64/mpi_job_cancel $jobid");
      else
        ;                                         // unsupported job type

  $content .= <<<HTML
  <p>Your job has now been scheduled for deletion from the queue.
     The HPC data analysis queue will be updated within the next
     couple of minutes, and your job will then be deleted. You will
     receive a message in your e-mail when the job has been cancelled.</p>

  <p>You can now return to the 
     <a href='$_SERVER[PHP_SELF]'>HPC Data Analysis Queue Viewer</a>
     and refresh the view in a couple of minutes to obtain the updated 
     queue.</p>
HTML;

    }
  }

  // No other tasks at hand --- just display the queue
  else
  {
    $content .= "<table>\n";
    $content .= "<tr><td colspan='5' class='decoration'><hr/></td></tr>\n";
    for( $i = 2; $i < sizeof( $aData ); $i++ ) 
    {
      unset( $fields );
      unset( $jobdata );

      $k		= $i - 1;
      $fields = explode( " ", $aData[$i] );
      for ( $j = 0; $j < sizeof( $fields ); $j++ )
      {
        // Eliminate empty fields to get fields into 
        // the proper key numbering
        if ( ($fields[$j] != "") && ($fields[$j] != ":") )
        {
          $jobdata[] = $fields[$j];
        }
      }

      // Calculate MC iterations
      $iterations = "";
      if ( isset($jobdata[15]) )
      {
        $iterations = " (current MC iteration: " . ( $jobdata[15]+1 ) . ")";
      }

      $content .= "<tr><th>Name:</th>\n" .
                  "<td colspan='3'>$jobdata[8]</td>\n" .
                  "<td rowspan='5'>\n" .
                  display_buttons2($jobdata) .
                  "</td></tr>\n";

      $content .= "<tr><th>Owner:</th>" .
                  "<td colspan='3'>$jobdata[7]</td></tr>\n";

      $content .= "<tr><th>Job $k:</th>" .
                  "<td colspan='3'>$jobdata[0]$iterations</td>\n" .
                  "</tr>\n";
      
        if ($jobdata[14] == "Active" ||
      $jobdata[14] == "ACTIVE" )
        {
           $content .= "<tr><th>Status:</th>" .
                       "<td bgcolor='#47ff47'>$jobdata[14]</td>\n" .
                       "<th>Analysis Type:</th>" .
                       "<td>$jobdata[9]</td></tr>\n";
        }
        else if ($jobdata[14] == "Failed" ||
           $jobdata[14] == "FAILED")
        {
           $content .= "<tr><th>Status:</th>" .
                       "<td bgcolor='#ff4747'>$jobdata[14]</td>\n" .
                       "<th>Analysis Type:</th>" .
                       "<td>$jobdata[9]</td></tr>\n";
        }
        else if ($jobdata[14] == "Pending" ||
           $jobdata[14] == "PENDING" )
        {
           $content .= "<tr><th>Status:</th>" .
                       "<td bgcolor='#8888ff'>$jobdata[14]</td>\n" .
                       "<th>Analysis Type:</th>" .
                       "<td>$jobdata[9]</td></tr>\n";
        }
        else if ($jobdata[14] == "Unsubmitted")
        {
           $content .= "<tr><th>Status:</th>" .
                       "<td bgcolor='#ffff47'>$jobdata[14]</td>\n" .
                       "<th>Analysis Type:</th>" .
                       "<td>$jobdata[9]</td></tr>\n";
        }
        else
        {
           $content .= "<tr><th>Status:</th>" .
                       "<td>$jobdata[14]</td>\n" .
                       "<th>Analysis Type:</th>" .
                       "<td>$jobdata[9]</td></tr>\n";
        }
      
      $content .= "<tr><th>Submitted on:</th>" .
                  "<td>$jobdata[4], at $jobdata[5]</td>\n" .
                  "<th>Running on:</th>" .
                  "<td>$jobdata[6]</td></tr>\n";
    
      $content .= "<tr><td colspan='5' class='decoration'><hr/></td></tr>\n";
    }
    $content .= "</table>\n";

  }

  if (sizeof( $aData ) != 3 or $aData[2] != "No jobs are currently queued, running, or completing.")
  {
    // Print queue status timestamp a second time, if there are jobs listed
    $content .= "<h5>$aData[0]:\n" .
                "  <input type='button' value='Refresh'\n" .
                "  onclick='window.location.href=window.location.href;' /></h5>\n";
  }

  return $content;
}

// If current user is authorized to delete this job, display
//  a delete button
function display_buttons2($jobdata)
{
  $jobowner      = $jobdata[7];
  $cluster       = $jobdata[6];
  $jobid         = $jobdata[0];
  $jobtype       = $jobdata[3];
  $HPCAnalysisID = $jobdata[2];
  $gc_file       = $jobdata[10];
  $content       = '';

  $lines = file( "/share/apps64/ultrascan/etc/queue_status_detail" );
  $moreinfo = '';
  foreach ( $lines as $line )
  {
    $detail = explode( ' ', $line );
    if ( $detail[0] == $jobid )
    {
      $moreinfo = substr( $line, strpos( $line, ' ' ) );
      break;
    }
  }

  $moreinfo_box  = "";
  if ( ! empty( $moreinfo ) )
  {
    $moreinfo_box = <<<HTML
      <div id='info$jobid' class='more_info'>
        <div class='moreinfo_hdr'>Job $jobid Info<br />
          <hr /></div>
        $moreinfo
      </div>
HTML;
  }

  if (is_authorized($jobowner))
  {
    // Button to delete current job from the queue
    $content .= "<form action='$_SERVER[PHP_SELF]' method='post'>\n" .
                "  <input type='hidden' name='jobid' value='$jobid' />\n" .
                "  <input type='hidden' name='jobtype' value='$jobtype' />\n" .
                "  <input type='hidden' name='jobowner' value='$jobowner' />\n" .
                "  <input type='hidden' name='HPCID' value='$HPCAnalysisID' />\n" .
                "  <input type='submit' name='delete' value='Delete' />\n" .
                "</form>\n";
  }

  // Button to show more info, if it exists
  if ( !empty($moreinfo_box) )
  {
    $content .= <<<HTML
    $moreinfo_box
    <button id='more_info$jobid' onclick='return show_info( $jobid );'>
            More Info</button>
HTML;
  }

  return $content ;
}

// Figure out if current user is authorized to delete this job
function is_authorized($jobowner)
{
  $authorized = false;

  // $jobowner could have multiple emails in it
  $pos = strpos( $jobowner, $_SESSION['submitter_email'] );

  if ( ($_SESSION['userlevel'] >= 2) &&
       ( $pos !== false ) )
    $authorized = true;

  else if ($_SESSION['userlevel'] == 4)
    $authorized = true;

  return ($authorized);
}

?>
