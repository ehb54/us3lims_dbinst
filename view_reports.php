<?php
/*
 * view_reports.php
 *
 * View the report information that was stored in the DB by UltraScan III
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

$myID = $_SESSION['id'];

include 'config.php';
include 'db.php';
include 'lib/utility.php';

// Start displaying page
$page_title = "View Reports";
$css = 'css/reports.css';
$js  = 'js/reports.js';
include 'header.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">View Reports</h1>
  <!-- Place page content here -->

<?php
  if ( isset( $_POST['reportID'] ) )
    $text  = report_select( 'reportID', $_POST['reportID'] );

  else if ( isset( $_GET['triple'] ) )
    $text = tripleDetail( $_GET['triple'] );

  else
    $text  = report_select( 'reportID' );

  echo $text;

?>
</div>

<?php
include 'footer.php';
exit();

// Function to create a dropdown for available runIDs
function report_select( $select_name, $current_ID = NULL )
{
  global $myID;

  // Account for user selecting the Please select... choice
  $current_ID = ( $current_ID == -1 ) ? NULL : $current_ID;

  $query  = "SELECT report.reportID, runID " .
            "FROM reportPerson, report " .
            "WHERE reportPerson.personID = $myID " .
            "AND reportPerson.reportID = report.reportID " .
            "ORDER BY runID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />" . mysql_error() );

  if ( mysql_num_rows( $result ) == 0 ) return "";

  $text  = "<h3>Run ID:</h3>\n";
  $text .= "<form action='{$_SERVER['PHP_SELF']}' method='post'>\n" .
           "<p>\n" .
           "  <select name='$select_name' size='1' onchange='form.submit();'>\n" .
           "    <option value='-1'>Please select...</option>\n";
  while ( list( $reportID, $runID ) = mysql_fetch_array( $result ) )
  {
    $selected = ( $current_ID == $reportID ) ? " selected='selected'" : "";
    $text .= "    <option value='$reportID'$selected>$runID</option>\n";
  }

  $text .= "  </select>\n" .
           "</p>\n" .
           "</form>\n";

  if ( isset( $current_ID ) )
  {
    // We have a legit reportID, so let's get a list of triples
    //  associated with the report
    $text .= "<h3>Cell:</h3>\n";

    $query  = "SELECT reportTripleID, triple, dataDescription " .
              "FROM reportTriple " .
              "WHERE reportID = $current_ID " .
              "ORDER BY triple ";
    $result = mysql_query( $query )
              or die("Query failed : $query<br />\n" . mysql_error());

    $self = $_SERVER['PHP_SELF'];
    $text .= "<ul>\n";
    while ( list( $tripleID, $tripleDesc, $dataDesc ) = mysql_fetch_array( $result ) )
    {
      list( $cell, $channel, $wl ) = explode( "/", $tripleDesc );
      $description = ( empty($dataDesc) ) ? "" : "; Descr: $dataDesc";
      $display = "Cell: $cell; Channel: $channel; Wavelength: $wl$description";
      $text .= "  <li><a href='$self?triple=$tripleID'>$display</a></li>\n";
    }

    $text .= "</ul><br /><br />\n";
  }
  
  return $text;
}

// A function to retrieve the reportTriple detail
function tripleDetail( $tripleID )
{
  // Let's start with header information
  $query  = "SELECT report.reportID, runID, triple " .
            "FROM reportTriple, report " .
            "WHERE reportTripleID = $tripleID " .
            "AND reportTriple.reportID = report.reportID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );
  list ( $reportID, $runID, $tripleDesc ) = mysql_fetch_array( $result );
  list ( $cell, $channel, $wl ) = explode( "/", $tripleDesc );
  $text = "<h3>Run ID: $runID</h3>\n" .
          "<h4>Cell: $cell; Channel: $channel; Wavelength: $wl</h4>\n";

  // Now create a list of available analysis types
  $atypes = array();
  $query  = "SELECT DISTINCT analysis, label " .
            "FROM documentLink, reportDocument " .
            "WHERE documentLink.reportTripleID = $tripleID " .
            "AND documentLink.reportDocumentID = reportDocument.reportDocumentID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );
  while ( list( $atype, $label ) = mysql_fetch_array( $result ) )
  {
    $parts = explode( ":", $label );
    $atypes[$atype] = $parts[0];      // The analysis part of the label
  }

  foreach ( $atypes as $atype => $alabel )
  {
    $query  = "SELECT reportDocument.reportDocumentID, label " .
              "FROM documentLink, reportDocument " .
              "WHERE documentLink.reportTripleID = $tripleID " .
              "AND documentLink.reportDocumentID = reportDocument.reportDocumentID " .
              "AND analysis = '$atype' " .
              "ORDER BY subAnalysis ";
    $result = mysql_query( $query )
              or die( "Query failed : $query<br />\n" . mysql_error() );

    $text .= "<p class='reporthead'><a name='$atype'></a>$alabel</p>\n" .
             "<ul>\n";
    while ( list( $docID, $label ) = mysql_fetch_array( $result ) )
    {
      list( $anal, $subanal, $doctype ) = explode( ":", $label );
      $text .= "  <li><a href='#$atype' onclick='show_report_detail( $docID );'>$subanal ($doctype)</a></li>\n";
    }

    $text .= "</ul>\n";
  }

  // Let's add a back link to make things easier to get to the list of triples
  $self = $_SERVER['PHP_SELF'];
  $text .= <<<HTML
  <form action='$self' method='post'>
    <p><input type='hidden' name='reportID' value='$reportID' />
       <input type='submit' value='Select another cell?' /></p>
  </form>

HTML;
  return $text;
}
?>
