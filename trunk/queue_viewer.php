<?php
/*
 * queue_viewer.php
 *
 * Displays the queue viewer
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

define( 'DEBUG', true );

include 'config.php';
include 'db.php';

// Start displaying page
$page_title = "Queue Viewer";
$js = 'js/template.js';
$css = 'css/queue_viewer.css';
include 'top.php';
include 'links.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Queue Viewer</h1>
  <!-- Place page content here -->
  <?php echo page_content3();
        echo page_content2();  ?>

</div>

<?php
include 'bottom.php';
exit();

// A function to generate the page content using the limsv3 database
function page_content3()
{
  $content = "<h3>LIMS v3 Queue</h3>\n";

  $query  = "SELECT startTime, queueStatus, lastMessage, updateTime, " .
            "investigatorGUID, submitterGUID, clusterName, method, runID " .
            "FROM HPCAnalysisResult r, HPCAnalysisRequest q, experiment " .
            "WHERE ( ( queueStatus = 'queued' ) || " .
            "        ( queueStatus = 'running' ) ) " .
            "AND r.HPCAnalysisRequestID = q.HPCAnalysisRequestID " .
            "AND q.experimentID = experiment.experimentID " .
            "ORDER BY startTime ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />" . mysql_error());

  if ( mysql_num_rows( $result ) == 0 )
    $content .= "<p>No jobs are currently queued</p>\n";

  else
  {
    $table  = "<table>\n";
	  $table .= "<tr><td colspan='5' class='decoration'><hr/></td></tr>\n";

    while( $row = mysql_fetch_array( $result ) )
    {
      foreach ( $row as $key => $value )
      {
        $$key = (empty($value)) ? "" : html_entity_decode(stripslashes( nl2br($value)) );
      }

      $query2  = "SELECT email FROM people " .
                 "WHERE personGUID = '$investigatorGUID' ";
      $result2 = mysql_query( $query2 )
                 or die( "Query failed : $query2<br />" . mysql_error());
      list( $email ) = mysql_fetch_array( $result2 );

      if ( $investigatorGUID != $submitterGUID )
      {
        $query2  = "SELECT email FROM people " .
                   "WHERE personGUID = '$submitterGUID' ";
        $result2 = mysql_query( $query2 )
                   or die( "Query failed : $query2<br />" . mysql_error());
        list( $submitterEmail ) = mysql_fetch_array( $result2 );
        $email .= " ($submitterEmail)";
      }

      $table .= "<tr><th>Run ID:</th>\n" .
                "<td colspan='3'>$runID</td>\n" .
                "<td rowspan='6'>\n";
                display_buttons();
                "</td></tr>\n";

      $table .= <<<HTML
      <tr><th>Owner:</th>
          <td colspan='3'>$email</td></tr>

      <tr><th>Last message:</th>
          <td colspan='3'>$lastMessage</td></tr>

      <tr><th>Status:</th>
          <td class='$queueStatus'>$queueStatus</td>
          <th>Analysis Type:</th>
          <td>$method</td></tr>

      <tr><th>Started on:</th>
          <td>$startTime</td>
          <th rowspan='2'>Running on:</th>
          <td rowspan='2'>$clusterName</td></tr>

      <tr><th>Last Updated:</th>
          <td>$updateTime</td></tr>

	    <tr><td colspan='5' class='decoration'><hr/></td></tr>
HTML;
    }

    $table .= "</table>\n";
  }

  $content .= $table;

  return $content;
}

// A function to optionally generate delete buttons
function display_buttons()
{
  $buttons = "";

  return $buttons;
}

// A function to generate page content using lims2 methods
function page_content2()
{
  $content = "<h3>LIMS v2 Queue</h2>\n";

    //$content .= "<p>No jobs are currently queued</p>\n";
    $content .= "<p>Not implemented yet</p>\n";

  return $content;
}
?>
