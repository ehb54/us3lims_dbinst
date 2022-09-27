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
ini_set('display_errors', 'On');


// Start displaying page
$page_title = 'HPC Request Data';
include 'header.php';

echo "<div id='content'>\n";
echo "<pre>\n\n";
echo "</pre>\n\n";

if ( ! isset( $_GET[ 'RequestID' ] ) )
{
   $query = "SELECT count(*) FROM HPCAnalysisRequest";
   $result = mysqli_query( $link, $query )
         or die( "Query failed : $query<br />\n" . mysqli_error($link));
   list( $numrow ) = mysqli_fetch_array( $result );

   $start_hpcr = 0;

   if ( $numrow > 2000 )
      $start_hpcr = $numrow - 1000;

   // First build array of last messages for each request id
   $last_msgs = array();
   $query = "SELECT HPCAnalysisRequestID, lastMessage FROM HPCAnalysisResult" .
            " where HPCAnalysisRequestID > " . $start_hpcr;
   $result = mysqli_query( $link, $query )
         or die( "Query failed : $query<br />\n" . mysqli_error($link));
   if ( mysqli_num_rows( $result ) > 0 )
   {
      while ( list( $reqID, $lastmsg ) = mysqli_fetch_array( $result ) )
      {
         $last_msgs[ $reqID ] = $lastmsg;
      }
   }

   // Now build lines of the HPC Request list
   $query = "SELECT HPCAnalysisRequestID, submitTime, clusterName, method FROM HPCAnalysisRequest" .
            " WHERE HPCAnalysisRequestID > " . $start_hpcr .
            " ORDER BY HPCAnalysisRequestID DESC";
   $result = mysqli_query( $link, $query )
         or die( "Query failed : $query<br />\n" . mysqli_error($link));

   if ( mysqli_num_rows( $result ) > 0 )
   {
      $table = <<<HTML
      <table>
        <thead>
          <tr>
            <th>Request ID</th>
            <th>Submit Time</th>
            <th>Cluster</th>
            <th>Method</th>
            <th>Last Message</th>
          </tr>
        </thead>
        <tbody>
HTML;

      while ( list( $reqID, $time, $cluster, $method ) = mysqli_fetch_array( $result ) )
      {
         if ( isset( $last_msgs[ $reqID ] ) )
            $lastMessage = $last_msgs[ $reqID ];
         else
            $lastMessage = "????";
         $link = "<a href='{$_SERVER['PHP_SELF']}?RequestID=$reqID'>$reqID</a>";

         $table .= "<tr><td>$link</td><td>$time</td><td>$cluster</td><td>$method</td><td>$lastMessage</td></tr>\n";
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
  $reqID = htmlentities($_GET[ 'RequestID' ]);

  if ( $stmt = mysqli_prepare( $link, "SELECT clusterName, HPCAnalysisRequestGUID, investigatorGUID, submitterGUID, email, experimentID, editXMLFilename, submitTime, clusterName, method, requestXMLFile FROM HPCAnalysisRequest WHERE HPCAnalysisRequestID=?" ) )
  {
    $stmt->bind_param( 'i', $reqID );
    $stmt->execute();
    $stmt->store_result();
    $num_of_rows = $stmt->num_rows;
    $stmt->bind_result( $clusterName, $HPCAnalysisRequestGUID, $investigatorGUID, $submitterGUID,
                        $email, $experimentID, $editXMLFilename, $submitTime, $clusterName,
                        $method, $requestXMLFile );
    $stmt->fetch();
    $stmt->free_result();
    $stmt->close();
  }

   echo "HPCAnalysisRequest\n";

   echo "<pre>\n\n";
   echo "HPCAnalysisRequestID  : $reqID\n";
   echo "HPCAnalysisRequestGUID: $HPCAnalysisRequestGUID\n";
   echo "investigatorGUID      : $investigatorGUID\n";
   echo "submitterGUID         : $submitterGUID\n";
   echo "email                 : $email\n";
   echo "Experiment ID         : $experimentID\n";
   echo "Edit XML Filename     : $editXMLFilename\n";
   echo "Submit Time           : $submitTime\n";
   echo "Cluster Name          : $clusterName\n";
   echo "Method                : $method\n";
   $xmlFile = htmlentities( $requestXMLFile );
   echo "Request XML File      : \n$xmlFile\n";

   // Save for later
   $requestGUID  = $HPCAnalysisRequestGUID;

   if ( $stmt = mysqli_prepare( $link, "SELECT HPCAnalysisResultID, startTime, endTime, queueStatus, lastMessage, updateTime, gfacID, wallTime, CPUTime, CPUCount, max_rss, calculatedData FROM HPCAnalysisResult WHERE HPCAnalysisRequestID=?" ) )
   {
     $stmt->bind_param( 'i', $reqID );
     $stmt->execute();
     $stmt->store_result();
     $num_of_rows = $stmt->num_rows;
     $stmt->bind_result( $HPCAnalysisResultID, $startTime, $endTime, $queueStatus, $lastMessage,
                         $updateTime, $gfacID, $wallTime, $CPUTime, $CPUCount,
                         $max_rss, $calculatedData );
     $stmt->fetch();
     $stmt->free_result();
     $stmt->close();
   }


   echo "HPCAnalysisResult\n\n";

   echo "HPCAnalysisResultID     : $HPCAnalysisResultID\n";
   echo "HPCAnalysisRequestID    : $reqID\n";
   echo "Start Time              : $startTime\n";
   echo "End Time                : $endTime\n";
   echo "Queue Status            : $queueStatus\n";
   echo "Last Message            : $lastMessage\n";
   echo "Update Time             : $updateTime\n";
   echo "GFAC ID                 : $gfacID\n";
   echo "Wall Time               : $wallTime\n";
   echo "CPU Time                : $CPUTime\n";
   echo "CPU Count               : $CPUCount\n";
   echo "Max Memory              : $max_rss\n";
   echo "Calculated Data (Unused): $calculatedData\n";

   // Get queue messages from disk directory, if it still exists
   global $submit_dir;
   global $dbname;
   global $uses_thrift;
   list( $cluster )  = explode( '.', $clusterName );

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

   if ( isset( $gfacID ) ) {
       // If gfacID fits the right format for a GFAC job, a status request link:
       $link2 = "<a href=\"{$_SERVER['PHP_SELF']}?RequestID=$reqID&jobstatus=t\">Show GFAC Status</a>";
       $link3 = "<a href=\"{$_SERVER['PHP_SELF']}?RequestID=$reqID\">Hide GFAC Status</a>";

       if ( isset( $_GET['jobstatus'] ) )
       {
           $clus_thrift    = $uses_thrift;
           if ( in_array( $cluster, $thr_clust_excls ) )
               $clus_thrift    = false;
           if ( in_array( $cluster, $thr_clust_incls ) )
               $clus_thrift    = true;

           $gfacID  = $gfacID;
           echo "$link3\n";

           if ( $clus_thrift )
           {
               $expstat = getExperimentStatus( $gfacID );
               echo $expstat . "\n";
               if ( ! preg_match( "/COMPLETED/", $expstat ) )
               {
                   echo " -- \n";
                   echo getExperimentErrors( $gfacID );
               }
           }
           else
           {
               echo getJobstatus( $gfacID );
           }
       } else {
           echo "$link2\n";
       }
   }
}


echo "</pre>\n";
echo "</div>\n";
include 'footer.php';
exit();

?>
