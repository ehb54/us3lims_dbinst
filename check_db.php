<?php
/*
 * check_db.php
 *
 * Check relationships in the DB
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( ( $_SESSION['userlevel'] != 3 ) &&
     ( $_SESSION['userlevel'] != 4 ) &&
     ( $_SESSION['userlevel'] != 5 ) )  // admin and superadmin only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';

// Start displaying page
$page_title = 'Database Consistency Check';
include 'header.php';

echo "<div id='content'>\n";
echo "<pre>\n\n";

$query = "SELECT personID, personGUID, lname FROM people";
$result = mysql_query( $query )
      or die( "Query failed : $query<br />\n" . mysql_error());

echo "People:\n";

while ( list( $personID, $personGUID, $lname ) = mysql_fetch_array( $result ) )
{
   echo "$personID $personGUID $lname\n";
}

echo "\nRaw Data:\n";
$query = "SELECT rawDataID, rawDataGUID, filename, lastUpdated FROM rawData ORDER BY rawDataID";
$result = mysql_query( $query )
      or die( "Query failed : $query<br />\n" . mysql_error());

while ( list( $rawDataID, $rawDataGUID, $fn, $time ) = mysql_fetch_array( $result ) )
{
   echo "$rawDataID $rawDataGUID $fn $time\n";
}

echo "\nEdited Data:\n";
$query = "SELECT editedDataID, rawDataID, editGUID, filename FROM editedData";
$result = mysql_query( $query )
      or die( "Query failed : $query<br />\n" . mysql_error());

while ( list( $editedDataID, $rawDataID, $editGUID, $filename ) = mysql_fetch_array( $result ) )
{
   echo "$editedDataID $rawDataID $editGUID $filename\n";
}

echo "\nModels: modelID, editedDataID, modelGUID\n";
$query = "SELECT modelID, editedDataID, modelGUID FROM model";
$result = mysql_query( $query )
      or die( "Query failed : $query<br />\n" . mysql_error());

while ( list( $modelID, $editedDataID, $modelGUID ) = mysql_fetch_array( $result ) )
{
   echo "$modelID $editedDataID $modelGUID\n";
}

echo "\nmodelPerson: modelID personID\n";
$query = "SELECT modelID, personID FROM modelPerson";
$result = mysql_query( $query )
      or die( "Query failed : $query<br />\n" . mysql_error());

while ( list( $modelID, $personID ) 
     = mysql_fetch_array( $result ) )
{
   echo "$modelID $personID\n";
}


echo "\nNoise: noiseID editedDataID noiseGUID modelID modelGUID\n";
$query = "SELECT noiseID, editedDataID, noiseGUID, modelID, modelGUID FROM noise";
$result = mysql_query( $query )
      or die( "Query failed : $query<br />\n" . mysql_error());

while ( list( $noiseID, $editedDataID, $noiseGUID, $modelID, $modelGUID ) 
     = mysql_fetch_array( $result ) )
{
   echo "$noiseID $editedDataID $noiseGUID $modelID $modelGUID\n";
}

echo "\nHPCAnalysisResultData: AnalysisResultDataID AnalysisResultID resultType resultID\n";
$query = "SELECT HPCAnalysisResultDataID, HPCAnalysisResultID, HPCAnalysisResultType, resultID " .
         "FROM HPCAnalysisResultData";
$result = mysql_query( $query )
      or die( "Query failed : $query<br />\n" . mysql_error());

while ( list( $dataID, $resultDataID, $type, $resultID ) 
     = mysql_fetch_array( $result ) )
{
   echo " $dataID $resultDataID $type $resultID\n";
}

echo "\nHPCAnalysisRequest: HPCAnalysisRequestID HPCAnalysisRequestGUID method\n";
$query = "SELECT HPCAnalysisRequestID, HPCAnalysisRequestGUID, method " .
         "FROM HPCAnalysisRequest";
$result = mysql_query( $query )
      or die( "Query failed : $query<br />\n" . mysql_error());

while ( list( $id, $guid, $method ) 
     = mysql_fetch_array( $result ) )
{
   echo " $id $guid\n";
}


echo "\nHPCAnalysisResult: HPCAnalysisResultID HPCAnalysisRequestID gfacID queueStatus updateTime\n";
$query = "SELECT HPCAnalysisResultID, HPCAnalysisRequestID, gfacID, queueStatus, updateTime " .
         "FROM HPCAnalysisResult";
$result = mysql_query( $query )
      or die( "Query failed : $query<br />\n" . mysql_error());

while ( list( $id, $reqID, $guid, $status, $time ) 
     = mysql_fetch_array( $result ) )
{
   echo " $id $reqID $guid $status $time\n";
}






echo "</pre></div>\n";
include 'footer.php';
exit();
?>
