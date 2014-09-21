<?php
/*
 * HPC_requests.php
 *
 * Check HPC Request Data
 *
 */
include_once 'checkinstance.php';

if ( ( $_SESSION['userlevel'] != 3 ) &&
     ( $_SESSION['userlevel'] != 4 ) &&
     ( $_SESSION['userlevel'] != 5 ) )  // super, admin and superadmin only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';
include $class_dir . 'experiment_status.php';
include $class_dir . 'experiment_errors.php';

// Start displaying page
$page_title = 'HPC Request Data';
include 'header.php';

echo "<div id='content'>\n";
echo "<pre>\n\n";
echo "</pre>\n\n";

if ( ! isset( $_GET[ 'RequestID' ] ) )
{
   $query = "SELECT HPCAnalysisRequestID, submitTime, clusterName, method FROM HPCAnalysisRequest";
   $result = mysql_query( $query )
         or die( "Query failed : $query<br />\n" . mysql_error());

   $numrow = mysql_num_rows( $result );
   if ( $numrow > 2000 )
   {
      $start_hpcr = $numrow - 1000;
      $query = "SELECT HPCAnalysisRequestID, submitTime, clusterName, method FROM HPCAnalysisRequest" .
               " where HPCAnalysisRequestID>" . $start_hpcr;
      $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error());
   }

   if ( mysql_num_rows( $result ) > 0 )
   {
      $table = <<<HTML
      <table>
        <thead>
          <tr>
            <th>Request ID</th>
            <th>Submit Time</th>
            <th>Cluster</th>
            <th>Method</th>
          </tr>
        </thead>
        <tbody>
HTML;

      while ( list( $reqID, $time, $cluster, $method ) = mysql_fetch_array( $result ) )
      {
         $link = "<a href='{$_SERVER['PHP_SELF']}?RequestID=$reqID'>$reqID</a>";

         $table .= "<tr><td>$link</td><td>$time</td><td>$cluster</td><td>$method</td></tr>\n";
      }

      $table .= "</tbody></table>\n";
      echo $table;
   }
   else
      echo "<p>No Requests</p>\n";
}
else
{
   // Get details about the request
   $reqID = $_GET[ 'RequestID' ];

   $query = "SELECT * FROM HPCAnalysisRequest WHERE HPCAnalysisRequestID=$reqID";
   $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error());
           
   $row = mysql_fetch_assoc( $result );
   $clusterName = $row[ 'clusterName' ];

   echo "<pre>\n\n";
   echo "HPCAnalysisRequestID  : $reqID\n";
   echo "HPCAnalysisRequestGUID: {$row[ 'HPCAnalysisRequestGUID' ]}\n";
   echo "investigatorGUID      : {$row[ 'investigatorGUID' ]}\n";
   echo "submitterGUID         : {$row[ 'submitterGUID' ]}\n";
   echo "email                 : {$row[ 'email' ]}\n";
   echo "Experiment ID         : {$row[ 'experimentID' ]}\n";
   echo "Edit XML Filename     : {$row[ 'editXMLFilename' ]}\n";
   echo "Submit Time           : {$row[ 'submitTime' ]}\n";
   echo "Cluster Name          : {$row[ 'clusterName' ]}\n";
   echo "Method                : {$row[ 'method' ]}\n";
   $xmlFile = htmlentities( $row[ 'requestXMLFile' ] );
   echo "Request XML File      : \n$xmlFile\n";
   
   // Save for later
   $requestGUID  = $row['HPCAnalysisRequestGUID'];

   $query = "SELECT * FROM HPCAnalysisResult WHERE HPCAnalysisRequestID=$reqID";
   $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error());
           
   $row = mysql_fetch_assoc( $result );
   
   echo "HPCAnalysisResult\n\n";

   echo "HPCAnalysisResultID     : {$row[ 'HPCAnalysisResultID' ]}\n";
   echo "HPCAnalysisRequestID    : $reqID\n";
   echo "Start Time              : {$row[ 'startTime' ]}\n";
   echo "End Time                : {$row[ 'endTime' ]}\n";
   echo "Queue Status            : {$row[ 'queueStatus' ]}\n";
   echo "Last Message            : {$row[ 'lastMessage' ]}\n";
   echo "Update Time             : {$row[ 'updateTime' ]}\n";
   echo "GFAC ID                 : {$row[ 'gfacID' ]}\n";
   echo "Wall Time               : {$row[ 'wallTime' ]}\n";
   echo "CPU Time                : {$row[ 'CPUTime' ]}\n";
   echo "CPU Count               : {$row[ 'CPUCount' ]}\n";
   echo "Max Memory              : {$row[ 'max_rss' ]}\n";
   echo "Calculated Data (Unused): {$row[ 'calculatedData' ]}\n";

   // Get queue messages from disk directory, if it still exists
   global $submit_dir;
   global $dbname;
   global $uses_airavata;
 
   $msg_filename = "$submit_dir$requestGUID/$dbname-$reqID-messages.txt";
   $queue_msgs = false;
   if ( file_exists( $msg_filename ) )
   {
     $queue_msgs   = file_get_contents( $msg_filename );
     $len_msgs     = strlen( $queue_msgs );
     $queue_msgs   = '<pre>' . $queue_msgs . '</pre>';
   }

   if ( $queue_msgs !== false )
   {
     $linkmsg1 = "<a href='{$_SERVER[ 'PHP_SELF' ]}?RequestID=$reqID&msgs=t#runDetail'>Length Messages</a>";
     $linkmsg2 = "<a href='{$_SERVER[ 'PHP_SELF' ]}?RequestID=$reqID'>Hide Messages</a>";
   
     if ( isset( $_GET[ 'msgs' ] ) )
     {
       echo "$linkmsg2\n";
       echo "$queue_msgs\n";
     }

     else
       echo "$linkmsg1         : $len_msgs\n";
   }

   // If gfacID fits the right format for a GFAC job, a status request link:
   $link2 = "<a href=\"{$_SERVER['PHP_SELF']}?RequestID=$reqID&jobstatus=t\">Show GFAC Status</a>";
   $link3 = "<a href=\"{$_SERVER['PHP_SELF']}?RequestID=$reqID\">Hide GFAC Status</a>";

   if ( isset( $_GET['jobstatus'] ) )
   {
      $gfacID  = $row[ 'gfacID' ];
      echo "$link3\n";

      if ( $uses_airavata === true && $clusterName != 'juropa.fz-juelich.de')
      {
         echo getExperimentStatus( $gfacID );
         echo " -- ";
         echo getExperimentErrors( $gfacID );
      }
      else
      {
         echo getJobstatus( $gfacID );
      }
   }

   else
      echo "$link2\n";

}

echo "</pre>\n";
echo "</div>\n";
include 'footer.php';
exit();

?>
