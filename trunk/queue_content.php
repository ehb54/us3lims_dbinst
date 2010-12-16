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
include_once 'db.php';

$sort_order = 'submitTime';
if ( isset( $_SESSION['queue_viewer_sort_order'] ) )
  $sort_order = $_SESSION['queue_viewer_sort_order'];

$content = "<div class='queue_content'>\n";

$query  = "SELECT queueStatus, lastMessage, updateTime, editXMLFilename, " .
          "investigatorGUID, submitterGUID, submitTime, clusterName, method, runID " .
          "FROM HPCAnalysisResult r, HPCAnalysisRequest q, experiment " .
          "WHERE ( ( queueStatus = 'queued' ) || " .
          "        ( queueStatus = 'running' ) ) " .
          "AND r.HPCAnalysisRequestID = q.HPCAnalysisRequestID " .
          "AND q.experimentID = experiment.experimentID " .
          "ORDER BY $sort_order ";
$result = mysql_query( $query )
          or die( "Query failed : $query<br />" . mysql_error());

if ( mysql_num_rows( $result ) == 0 )
  $content .= "<p>No jobs are currently queued</p>\n";

else
{
  $content  = "<table>\n";
  $content .= "<tr><td colspan='5' class='decoration'><hr/></td></tr>\n";

  while( $row = mysql_fetch_array( $result ) )
  {
    foreach ( $row as $key => $value )
    {
      $$key = (empty($value)) ? "" : html_entity_decode(stripslashes( nl2br($value)) );
    }

    $triple = '';
    if ( ! empty( $row['editXMLFilename'] ) )
    {
      $xmlparts = array();
      $xmlparts = explode( '.', $row['editXMLFilename'] );
      $triple   =                  '( ' .
                  $xmlparts[ 3 ] . '/' .
                  $xmlparts[ 4 ] . '/' .
                  $xmlparts[ 5 ] . ' )';
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

    $content .= "<tr><th>Run ID:</th>\n" .
              "<td colspan='3'>$runID $triple</td>\n" .
              "<td rowspan='6'>\n" .
              display_buttons() .
              "</td></tr>\n";

    $content .= <<<HTML
    <tr><th>Owner:</th>
        <td colspan='3'>$email</td></tr>

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

    <tr><th>Last Updated:</th>
        <td>$updateTime</td></tr>

    <tr><td colspan='5' class='decoration'><hr/></td></tr>
HTML;
  }

  $content .= "</table>\n";
}

$content .= "</div>\n";

echo $content;

// A function to optionally generate delete buttons
function display_buttons()
{
  $buttons = "";

  return $buttons;
}
?>
