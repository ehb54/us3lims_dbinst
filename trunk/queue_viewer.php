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

$sort_order = 'submitTime';
if ( isset( $_SESSION['queue_viewer_sort_order'] ) )
  $sort_order = $_SESSION['queue_viewer_sort_order'];

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
  global $clusters, $uses_airavata;             // From utility.php
//  var_dump($uses_airavata);
  $cluster  = $_POST['cluster'];
  $gfacID   = $_POST['gfacID'];
  $jobEmail = $_POST['jobEmail'];
  // Find out which cluster we're deleting from
  $found = false;
  foreach ( $clusters as $info )
  {
    if ( $cluster == $info->name )
    {
      $shortname = $info->short_name;
      $found     = true;
      break;
    }
  }

  if ( ! $found ) return;

  // We need to find out if it's a GFAC or local job
  $hex = "[0-9a-fA-F]";
  if ( ! preg_match( "/^US3-Experiment/", $gfacID ) &&
       ! preg_match( "/^US3-$hex{8}-$hex{4}-$hex{4}-$hex{4}-$hex{12}$/", $gfacID ) &&
       ! preg_match( "/^US3-AIRA/", $gfacID ) )
     $shortname .= '-local';   // Not a GFAC ID

  switch ( $shortname )
  {
    case 'bcf-local'   :
    case 'alamo-local' :
      $status = cancelLocalJob( $shortname, $gfacID );
      break;
    case 'juropa'    :
      $status = cancelJob( $gfacID );
      break;
	
    case 'stampede'  :
    case 'lonestar'  :
    case 'trestles'  :
    case 'gordon'    :
    case 'alamo'     :
    case 'bcf'       :
      if ( $uses_airavata === true )
      {
        $status = cancelAiravataJob( $gfacID );
        if ( $status )
        {
          // Let's update what user sees until canceled
          updateLimsStatus( $gfacID, 'aborted',  $lastMessage );
          updateGFACStatus( $gfacID, 'CANCELED', $lastMessage );
        }
      }
      else
        $status = cancelJob( $gfacID );
      break;

    default          :
      break;
  }
}

// Function to cancel a local job
function cancelLocalJob( $cluster, $gfacID )
{
   $system = "$cluster.uthscsa.edu";
   $system = preg_replace( "/\-local/", "", $system );
   $cmd    = "/usr/bin/ssh -x us3@$system qstat -a $gfacID 2>&1";

   $result = exec( $cmd );

   if ( $result == ""  ||  preg_match( "/^qstat: Unknown/", $result ) )
   {
     // Let's try to update the lastMessage field so the user sees
     updateLimsStatus( $gfacID, 'aborted', "Job was not found in the status queue" );
     updateGFACStatus( $gfacID, 'CANCELED', "Job was not found in the status queue" );
     return;
   }

   $values = preg_split( "/\s+/", $result );
   switch ( $values[ 9 ] )
   {
      case "E" :                      // Job is exiting after having run
        $lastMessage = "Job is exiting";
        break;

      case "C" :                      // Job has completed
        $lastMessage = "Job has already completed";
        break;

      case "W" :                      // Waiting for execution time to be reached
      case "R" :                      // Still running
      case "T" :                      // Job is being moved
      case "H" :                      // Held
      case "Q" :                      // Queued
      default  :                      // This should not occur
        // If we're here we should delete the job
        // We could add logic to discover if any of the nodes I'm using are down
        // 'pbsnodes -l' tells us which nodes are down, if any. If that's empty
        // we can go ahead. Otherwise we can use qstat -f $gfacID to determine
        // which nodes are in use for our job. If any of our nodes is down
        // we should use qdel -r $gfacID instead of the following.
        $parts = explode( ".", $gfacID );
        $jobID = $parts[ 0 ];
        $cmd    = "/usr/bin/ssh -x us3@$system qdel $jobID 2>&1";
        $result = exec( $cmd );

        $lastMessage = "This job has been canceled";
        break;
   }


   // Let's update what user sees until canceled
   updateLimsStatus( $gfacID, 'aborted', $lastMessage );
   updateGFACStatus( $gfacID, 'CANCELED', $lastMessage );

   return;

}

// Function to cancel a job
function cancelJob( $gfacID )
{
  global $gfac_serviceURL;

  $hex = "[0-9a-fA-F]";
  if ( ! preg_match( "/^US3-Experiment/", $gfacID ) &&
       ! preg_match( "/^US3-$hex{8}-$hex{4}-$hex{4}-$hex{4}-$hex{12}$/", $gfacID ) )
     return "Not a GFAC ID";

  $url = "$gfac_serviceURL/canceljob/$gfacID";

  $r = new HttpRequest( $url, HttpRequest::METH_GET );

  $time   = date( "F d, Y H:i:s", time() );

  try
  {
     $result = $r->send();
     $xml    = $result->getBody();
  }
  catch ( HttpException $e )
  {
    // Let's try to update the lastMessage field so the user sees
    updateLimsStatus( $gfacID, 'aborted', "Error ($e) attempting to delete job" );
    updateGFACStatus( $gfacID, 'CANCELED', "Error ($e) attempting to delete job" );
    return;

  }

  $status = parse_response( $xml, $message );
  $updateok = true;

  switch ( $status )
  {
    case 'CANCELED':
    case 'CANCELLED':
    case 'Success':
      $lastMessage = 'This job has been canceled.';
      break;

    case 'NOTALLOWED':
      $lastMessage = 'This job has been canceled already, or has completed.';
      break;

    case 'UNKNOWN':
    case 'ERROR':
      $lastMessage = 'GFAC cannot find this job.';
      break;

    default :
      $updateok = false;
      break;
  }

  if ( $updateok )
  {
    // Let's update what user sees until canceled
    updateLimsStatus( $gfacID, 'aborted', $lastMessage );
    updateGFACStatus( $gfacID, 'CANCELED', $lastMessage );
  }
}

function parse_response( $xml, &$msg )
{
   $status  = "";
   $msg = "";

   $parser = new XMLReader();
   $parser->xml( $xml );

   while( $parser->read() )
   {
      $type = $parser->nodeType;

      if ( $type == XMLReader::ELEMENT )
         $name = $parser->name;

      else if ( $type == XMLReader::TEXT )
      {
         if ( $name == "status" )
            $status  = $parser->value;
         else
            $msg = $parser->value;
      }
   }

   $parser->close();
   return $status;
}

// Function to update the status on an arbitrary lims database
function updateLimsStatus( $gfacID, $status, $message )
{
  global $globaldbhost;
  global $globaldbuser;
  global $globaldbpasswd;
  global $globaldbname;

  // Connect to the global GFAC database
  $gLink = mysql_connect( $globaldbhost, $globaldbuser, $globaldbpasswd );
  if ( ! mysql_select_db( $globaldbname, $gLink ) )
    return;

  // Get database name
  $query  = "SELECT us3_db FROM analysis " .
            "WHERE gfacID = '$gfacID'";
  $result = mysql_query( $query, $gLink );
  if ( ! $result ) return;
  if ( mysql_num_rows( $result ) == 0 ) return;
  list( $db ) = mysql_fetch_array( $result );
  mysql_close( $gLink );

  // Using credentials that will work for all databases
  $us3link = mysql_connect( 'localhost', 'us3php', 'us3' );
  if ( ! mysql_select_db($db, $us3link) ) return false;

  $query  = "UPDATE HPCAnalysisResult SET " .
            "queueStatus = '$status', " .
            "lastMessage = '" . mysql_real_escape_string( $message ) . "' " .
            "WHERE gfacID = '$gfacID' ";
  mysql_query( $query, $us3link );

  mysql_close( $us3link );
}

// Function to update the GFAC status, mostly because job is canceled
function updateGFACStatus( $gfacID, $status, $message )
{
  global $globaldbhost;
  global $globaldbuser;
  global $globaldbpasswd;
  global $globaldbname;

  // Connect to the global GFAC database
  $gLink = mysql_connect( $globaldbhost, $globaldbuser, $globaldbpasswd );
  if ( ! mysql_select_db( $globaldbname, $gLink ) )
    return;

  $status = strtoupper( $status );

  // Update gfac status
  $query  = "UPDATE analysis " .
            "SET status = '$status', " .
            "queue_msg = '$message' " .
            "WHERE gfacID = '$gfacID' ";
  mysql_query( $query, $gLink );
  mysql_close( $gLink );
}

// A function to generate page content using lims2 methods
function page_content2()
{
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
