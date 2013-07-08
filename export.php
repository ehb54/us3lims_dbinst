<?php
/*
 * export.php
 *
 * Create a downloadable tab-delimted file for import
 *  into spreadsheets
 *
 */
include_once 'checkinstance.php';

if ( ($_SESSION['userlevel'] != 3) &&
     ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) &&
     ($_SESSION['userlevel'] != 6) )    // Committee, admin and super admin only
{
  header('Location: index.php');
  exit();
}

if ( !isset( $_SESSION['exportfile'] ) )
{
  echo "<pre>No data has been selected</pre>\n";
  exit();
}

$export = $_SESSION['exportfile'];

// Change to "," for csv
$delimiter = "\t";

header("Pragma: public");   // Needed for IE
header("Expires: 0"); 
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=untitled.xls");
// Force browser to download the file rather than use the cached version
header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
header("Content-Transfer-Encoding: binary"); 

// See if there is a page title
if ( isset($_SESSION['exporttitle']) )
{
  $page_title = $_SESSION['exporttitle'];
  echo $page_title . "\r\n\r\n";
}

// Next, column names
$titles     = array_keys($export[0]);
$title_text = implode($delimiter, $titles);
echo $title_text . "\r\n";

// Loop through data and create detail lins
foreach ( $export as $row )
{
  $detail = implode($delimiter, $row);
  echo $detail . "\r\n";
}
?>
