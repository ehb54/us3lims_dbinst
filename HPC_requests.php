<?php
/*
 * HPC_requests.php
 *
 * Check HPC Request Data
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( ( $_SESSION['userlevel'] != 4 ) &&
     ( $_SESSION['userlevel'] != 5 ) )  // super, admin and superadmin only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';

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
         $link = "<a href='{$_SERVER[ PHP_SELF ]}?RequestID=$reqID'>$reqID</a>";

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

   $len_stdout = strlen( $row[ 'stdout' ] );
   $len_stderr = strlen( $row[ 'stderr' ] );
   
   $link = "<a href='{$_SERVER[ PHP_SELF ]}?RequestID=$reqID&stderr=t'>Length stderr</a>";
   echo "Length stdout           : $len_stdout\n";
   echo "$link           : $len_stderr\n";
   
   if ( isset( $_GET['stderr'] ) ) 
     echo "\nstderr:\n\n{$row[ 'stderr' ]}\n";

   // If gfacID fits the right format for a GFAC job, a status request link:
   $link2 = "<a href=\"{$_SERVER['PHP_SELF']}?RequestID=$reqID&jobstatus=t\">Show GFAC Status</a>";
   $link3 = "<a href=\"{$_SERVER['PHP_SELF']}?RequestID=$reqID\">Hide GFAC Status</a>";

   if ( isset( $_GET['jobstatus'] ) )
   {
      echo "$link3\n";
      echo getJobstatus( $row['gfacID'] );
   }

   else
      echo "$link2\n";

}

echo "</pre>\n";
echo "</div>\n";
include 'footer.php';
exit();

?>
