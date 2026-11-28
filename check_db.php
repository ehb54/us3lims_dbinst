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

echo "<div id='content' class='diagnostic-page'>\n";
echo "<h1 class='title'>Database Consistency Check</h1><pre>\n\n";

$query = "SELECT personID, personGUID, lname FROM people";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

echo "\nPeople (" . mysqli_num_rows( $result ) . " records)\n";

while ( list( $personID, $personGUID, $lname ) = mysqli_fetch_array( $result ) )
{
   echo "$personID $personGUID $lname\n";
}

$query = "SELECT rawDataID, rawDataGUID, filename, lastUpdated FROM rawData ORDER BY rawDataID";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

echo "\nRaw Data: (" . mysqli_num_rows( $result ) . " records)\n";

while ( list( $rawDataID, $rawDataGUID, $fn, $time ) = mysqli_fetch_array( $result ) )
{
   echo "$rawDataID $rawDataGUID $fn $time\n";
}

$query = "SELECT editedDataID, rawDataID, editGUID, filename FROM editedData";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

echo "\nEdited Data: (" . mysqli_num_rows( $result ) . " records)\n";

while ( list( $editedDataID, $rawDataID, $editGUID, $filename ) = mysqli_fetch_array( $result ) )
{
   echo "$editedDataID $rawDataID $editGUID $filename\n";
}

$query = "SELECT modelID, editedDataID, modelGUID FROM model";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

echo "\nModels: modelID, editedDataID, modelGUID (" . mysqli_num_rows( $result ) . " records)\n";

while ( list( $modelID, $editedDataID, $modelGUID ) = mysqli_fetch_array( $result ) )
{
   echo "$modelID $editedDataID $modelGUID\n";
}

$query = "SELECT modelID, personID FROM modelPerson";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

echo "\nmodelPerson: modelID personID (" . mysqli_num_rows( $result ) . " records)\n";

while ( list( $modelID, $personID )
     = mysqli_fetch_array( $result ) )
{
   echo "$modelID $personID\n";
}


$query = "SELECT noiseID, editedDataID, noiseGUID, modelID, modelGUID FROM noise";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

echo "\nNoise: noiseID editedDataID noiseGUID modelID modelGUID (" . mysqli_num_rows( $result ) . " records)\n";

while ( list( $noiseID, $editedDataID, $noiseGUID, $modelID, $modelGUID )
     = mysqli_fetch_array( $result ) )
{
   echo "$noiseID $editedDataID $noiseGUID $modelID $modelGUID\n";
}

$query = "SELECT HPCAnalysisResultDataID, HPCAnalysisResultID, HPCAnalysisResultType, resultID " .
         "FROM HPCAnalysisResultData";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

echo "\nHPCAnalysisResultData: AnalysisResultDataID AnalysisResultID resultType resultID (" . mysqli_num_rows( $result ) . " records)\n";

while ( list( $dataID, $resultDataID, $type, $resultID )
     = mysqli_fetch_array( $result ) )
{
   echo " $dataID $resultDataID $type $resultID\n";
}

$query = "SELECT HPCAnalysisRequestID, HPCAnalysisRequestGUID, method " .
         "FROM HPCAnalysisRequest";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

echo "\nHPCAnalysisRequest: HPCAnalysisRequestID HPCAnalysisRequestGUID method (" . mysqli_num_rows( $result ) . " records)\n";

while ( list( $id, $guid, $method )
     = mysqli_fetch_array( $result ) )
{
   echo " $id $guid\n";
}


$query = "SELECT HPCAnalysisResultID, HPCAnalysisRequestID, gfacID, queueStatus, updateTime " .
         "FROM HPCAnalysisResult";
$result = mysqli_query( $link, $query )
      or die( "Query failed : $query<br />\n" . mysqli_error($link));

echo "\nHPCAnalysisResult: HPCAnalysisResultID HPCAnalysisRequestID gfacID queueStatus updateTime (" . mysqli_num_rows( $result ) . " records)\n";

while ( list( $id, $reqID, $guid, $status, $time )
     = mysqli_fetch_array( $result ) )
{
   echo " $id $reqID $guid $status $time\n";
}






echo "</pre></div>\n";
include $common_dir . 'footer.php';
exit();
?>
