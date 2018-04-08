<?php
/*
 * check_db.php
 *
 * Check relationships in the DB
 *
 */
include_once 'checkinstance.php';

if ( ( $_SESSION['userlevel'] != 3 ) &&
     ( $_SESSION['userlevel'] != 4 ) &&
     ( $_SESSION['userlevel'] != 5 ) )  // admin and superadmin only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
// ini_set('display_errors', 'On');


// Start displaying page
$page_title = 'Database Consistency Check';
include 'header.php';

echo "<div id='content'>\n";
echo "<pre>\n\n";

$query = "SELECT personID, personGUID, lname FROM people";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

echo "\nPeople:\n";

while ( list( $personID, $personGUID, $lname ) = mysqli_fetch_array( $result ) )
{
   echo "$personID $personGUID $lname\n";
}

echo "\nRaw Data:\n";
$query = "SELECT rawDataID, rawDataGUID, filename, lastUpdated FROM rawData ORDER BY rawDataID";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

while ( list( $rawDataID, $rawDataGUID, $fn, $time ) = mysqli_fetch_array( $result ) )
{
   echo "$rawDataID $rawDataGUID $fn $time\n";
}

echo "\nEdited Data:\n";
$query = "SELECT editedDataID, rawDataID, editGUID, filename FROM editedData";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

while ( list( $editedDataID, $rawDataID, $editGUID, $filename ) = mysqli_fetch_array( $result ) )
{
   echo "$editedDataID $rawDataID $editGUID $filename\n";
}

echo "\nModels: modelID, editedDataID, modelGUID\n";
$query = "SELECT modelID, editedDataID, modelGUID FROM model";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

while ( list( $modelID, $editedDataID, $modelGUID ) = mysqli_fetch_array( $result ) )
{
   echo "$modelID $editedDataID $modelGUID\n";
}

echo "\nmodelPerson: modelID personID\n";
$query = "SELECT modelID, personID FROM modelPerson";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

while ( list( $modelID, $personID )
     = mysqli_fetch_array( $result ) )
{
   echo "$modelID $personID\n";
}


echo "\nNoise: noiseID editedDataID noiseGUID modelID modelGUID\n";
$query = "SELECT noiseID, editedDataID, noiseGUID, modelID, modelGUID FROM noise";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

while ( list( $noiseID, $editedDataID, $noiseGUID, $modelID, $modelGUID )
     = mysqli_fetch_array( $result ) )
{
   echo "$noiseID $editedDataID $noiseGUID $modelID $modelGUID\n";
}

echo "\nHPCAnalysisResultData: AnalysisResultDataID AnalysisResultID resultType resultID\n";
$query = "SELECT HPCAnalysisResultDataID, HPCAnalysisResultID, HPCAnalysisResultType, resultID " .
         "FROM HPCAnalysisResultData";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

while ( list( $dataID, $resultDataID, $type, $resultID )
     = mysqli_fetch_array( $result ) )
{
   echo " $dataID $resultDataID $type $resultID\n";
}

echo "\nHPCAnalysisRequest: HPCAnalysisRequestID HPCAnalysisRequestGUID method\n";
$query = "SELECT HPCAnalysisRequestID, HPCAnalysisRequestGUID, method " .
         "FROM HPCAnalysisRequest";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

while ( list( $id, $guid, $method )
     = mysqli_fetch_array( $result ) )
{
   echo " $id $guid\n";
}


echo "\nHPCAnalysisResult: HPCAnalysisResultID HPCAnalysisRequestID gfacID queueStatus updateTime\n";
$query = "SELECT HPCAnalysisResultID, HPCAnalysisRequestID, gfacID, queueStatus, updateTime " .
         "FROM HPCAnalysisResult";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

while ( list( $id, $reqID, $guid, $status, $time )
     = mysqli_fetch_array( $result ) )
{
   echo " $id $reqID $guid $status $time\n";
}






echo "</pre></div>\n";
include 'footer.php';
exit();
?>
