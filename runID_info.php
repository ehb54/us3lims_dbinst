<?php
/*
 * runID_info.php
 *
 * All the linkages for a particular runID
 *
 */
include_once 'checkinstance.php';

if ( ($_SESSION['userlevel'] != 2) &&   // data analyst can see own runID's
     ($_SESSION['userlevel'] != 3) &&   // admin and super admin can see all
     ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) )
{
  header('Location: index.php');
  exit();
} 

include 'config.php';
include 'db.php';
include 'lib/utility.php';
global $class_dir, $link;
include_once $class_dir . 'experiment_status.php';
// ini_set('display_errors', 'On');

// Start displaying page
$page_title = "Info by Run ID";
$css = 'css/admin.css';
include 'header.php';

global $uses_thrift;

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Info by Run ID</h1>
  <!-- Place page content here -->

<?php
  if ( isset( $_POST['experimentID'] ) )
  {
    $text  = experiment_select( $link, 'experimentID', $_POST['experimentID'] );
    if ( $_POST['experimentID'] != -1 )               // -1 is Please select...
       $text .= runID_info( $link, $_POST['experimentID'] );
  }

  else if ( isset( $_GET['RequestID'] ) )
    $text = HPCDetail( $link, $_GET['RequestID'] );

  else
    $text  = experiment_select( $link, 'experimentID' );

  echo $text;

?>
</div>

<?php
include 'footer.php';
exit();

// Function to create a dropdown for available runIDs
function experiment_select( $link, $select_name, $current_ID = NULL )
{
  $myID = $_SESSION['id'];

  $users_clause = ( $_SESSION['userlevel'] > 2 ) ? "" : "AND people.personID = $myID ";

  $query  = "SELECT experimentID, runID, lname " .
            "FROM experiment, projectPerson, people " .
            "WHERE experiment.projectID = projectPerson.projectID " .
            "AND projectPerson.personID = people.personID " .
            $users_clause .
            "ORDER BY lname, runID ";
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />" . mysqli_error($link) );

  if ( mysqli_num_rows( $result ) == 0 ) return "";

  $text = "<form action='{$_SERVER['PHP_SELF']}' method='post'>\n" .
          "  <select name='$select_name' size='1' onchange='form.submit();'>\n" .
          "    <option value=-1>Please select...</option>\n";
  while ( list( $experimentID, $runID, $lname ) = mysqli_fetch_array( $result ) )
  {
    $selected = ( $current_ID == $experimentID ) ? " selected='selected'" : "";
    $text .= "    <option value='$experimentID'$selected>$lname: $runID</option>\n";
  }

  $text .= "  </select>\n" .
           "</form>\n";

  return $text;
}

// A function to retrieve information about that runID
function runID_info( $link, $experimentID )
{
  // language=MariaDB
  $query  = "SELECT experiment.experimentID, people.personID, personGUID, lname, fname, email " .
            "FROM experiment, projectPerson, people " .
            "WHERE experiment.experimentID = ? and people.personID = ? " .
            "AND experiment.projectID = projectPerson.projectID " .
            "AND projectPerson.personID = people.personID ";

  // Prepared statement
  $stmt = mysqli_prepare($link, $query);
  $stmt->bind_param('ii', $experimentID, $_SESSION['id']);
  $stmt->execute();
  $stmt->store_result();
  $num_of_rows = $stmt->num_rows;
  if ($num_of_rows == 0)
      return "No data found for this experiment";
  $stmt->bind_result($experiementID,$ID, $GUID, $lname, $fname, $email);
  $stmt->fetch();

  $stmt->free_result();
  $stmt->close();



  /* This code was replace by the prepared statement above
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );
  list( $ID, $GUID, $lname, $fname, $email ) = mysqli_fetch_array( $result );
  */
  $toc = <<<HTML
  <h2 id='top'>Content</h2>
  <ul>
HTML;

  $toc_end = "\n</ul>\n";

  $text = <<<HTML
  <div id='Investigator'>
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Investigator Information&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
  <tr><th>ID:</th>
      <td>$ID</td></tr>

  <tr><th>GUID:</th>
      <td>$GUID</td></tr>

  <tr><th>Name:</th>
      <td>$fname $lname</td></tr>

  <tr><th>Email:</th>
      <td>$email</td></tr>

  </table>
  </div>
HTML;
  $toc .= "<li><a href='#Investigator'>Investigator Information</a></li>\n";

  $query  = "SELECT experimentGUID, coeff1, coeff2, type, runType " .
            "FROM experiment, rotorCalibration " .
            "WHERE experimentID = ? " .
            "AND experiment.rotorCalibrationID = rotorCalibration.rotorCalibrationID ";

  // Prepared statement
  if ($stmt = mysqli_prepare($link, $query)) {
   $stmt->bind_param('i', $experimentID);
   $stmt->execute();
   $stmt->store_result();
   $num_of_rows = $stmt->num_rows;

   $stmt->bind_result($experimentGUID, $coeff1, $coeff2, $type, $runType);
   $stmt->fetch();

   $stmt->free_result();
   $stmt->close();


  /* This code was replace by the prepared statement above
  $result = mysqli_query( $link, $query )
            or die( "Query failed : ".htmlentities($query)."<br />\n" . mysqli_error($link) );
  list( $GUID, $coeff1, $coeff2, $type, $runType ) = mysqli_fetch_array( $result );
  */
    if ($num_of_rows == 0) {
        return $toc . $toc_end . $text;
    }
  $text .= <<<HTML
<div id='Runinfo'>
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Run Information&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
  <tr><th>GUID:</th>
      <td>$GUID</td></tr>

  <tr><th>Rotor stretch coeff 1:</th>
      <td>$coeff1</td></tr>

  <tr><th>Rotor stretch coeff 2:</th>
      <td>$coeff2</td></tr>

  <tr><th>Experiment type:</th>
      <td>$type</td></tr>

  <tr><th>Run Type:</th>
      <td>$runType</td></tr>

  </table>
</div>
HTML;
    $toc .= <<<HTML
<li><a href='#Runinfo'>Run Information</a></li>
HTML;
  }

  $query  = "SELECT rawDataID, rawDataGUID, filename, solutionID " .
            "FROM rawData " .
            "WHERE experimentID = ? " .
            "ORDER BY filename ";

  // Prepared statement
  $rawIDs      = array();
  $solutionIDs = array();
  if ($stmt = mysqli_prepare($link, $query)) {
   $stmt->bind_param('i', $experimentID);
   $stmt->execute();
   $stmt->store_result();
   $num_of_rows = $stmt->num_rows;
   $stmt->bind_result($rawDataID, $rawDataGUID, $filename, $solutionID);

  /* This code was replace by the prepared statement above
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );
  */

  if ( $num_of_rows == 0 )
    return $toc . $toc_end . $text;


  $text .= <<<HTML
  <div id='Rawdata'>
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Raw Data&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
  <thead>
    <tr><th>ID</th>
        <th>GUID</th>
        <th>Filename</th>
        <th>Solution</th>
    </tr>
  </thead>

  <tbody>
HTML;

  while ($stmt->fetch())
  {
    $rawIDs[] = $rawDataID;
    $solutionIDs[] = $solutionID;

    $text .= <<<HTML
    <tr><td>$rawDataID</td>
        <td>$rawDataGUID</td>
        <td>$filename</td>
        <td>$solutionID</td>
    </tr>

HTML;

  }

  $stmt->free_result();
  $stmt->close();
  $text .= "</tbody>\n\n</table>\n</div>\n";
  $toc .= "<li><a href='#Rawdata'>Raw Data</a></li>\n";
 }



  $rawIDs_csv = implode( ", ", $rawIDs );
  $query  = "SELECT editedDataID, rawDataID, editGUID, filename " .
            "FROM editedData " .
            "WHERE rawDataID IN ( $rawIDs_csv ) " .
            "ORDER BY editedDataID, filename ";
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );

  if ( mysqli_num_rows( $result ) == 0 )
    return $toc . $toc_end . $text;

  $text .= <<<HTML
<div id='Edits'>
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Edit Profiles&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
  <thead>
    <tr><th>ID</th>
        <th>GUID</th>
        <th>Filename</th>
        <th>Raw ID</th>
    </tr>
  </thead>

  <tbody>

HTML;

  $editIDs = array();
  while ( list ( $editID, $rawID, $GUID, $filename ) = mysqli_fetch_array( $result ) )
  {
    $editIDs[] = $editID;

    $text .= <<<HTML
    <tr><td>$editID</td>
        <td>$GUID</td>
        <td>$filename</td>
        <td>$rawID</td>
    </tr>

HTML;
  }

  $text .= "</tbody>\n\n" .
           "</table>\n</div>\n";
  $toc .= "<li><a href='#Edits'>Edit Profiles</a></li>\n";


  $editIDs_csv = implode( ", ", $editIDs );
  $query  = "SELECT model.modelID, editedDataID, modelGUID, variance, meniscus, personID " .
            "FROM model LEFT JOIN modelPerson " .
            "ON ( model.modelID = modelPerson.modelID ) " .
            "WHERE editedDataID IN ( $editIDs_csv ) " .
            "ORDER BY modelID ";
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );
  $modelIDs = array();
  if ( mysqli_num_rows( $result ) != 0 )
  {
    $text .= <<<HTML
    <div id='Models'>
    <table cellspacing='0' cellpadding='0' class='admin'>
    <caption>Models&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
    <thead>
      <tr><th>ID</th>
          <th>GUID</th>
          <th>Edit ID</th>
          <th>Variance</th>
          <th>Meniscus</th>
          <th>Owner ID</th>
      </tr>
    </thead>

    <tbody>

HTML;


    while ( list ( $modelID, $editID, $GUID, $variance, $meniscus, $personID ) = mysqli_fetch_array( $result ) )
    {
      $modelIDs[] = $modelID;

      $text .= <<<HTML
      <tr><td>$modelID</td>
          <td>$GUID</td>
          <td>$editID</td>
          <td>$variance</td>
          <td>$meniscus</td>
          <td>$personID</td>
      </tr>

HTML;
    }

    $text .= "</tbody>\n\n" .
             "</table>\n</div>\n";
    $toc .= "<li><a href='#Models'>Models</a></li>\n";

  }

  if ( count( $modelIDs ) > 0 )
  {
    $modelIDs_csv = implode( ", ", $modelIDs );
    $query  = "SELECT noiseID, noiseGUID, editedDataID, modelID, modelGUID, noiseType " .
              "FROM noise " .
              "WHERE modelID IN ( $modelIDs_csv ) " .
              "ORDER BY noiseID ";
    $result = mysqli_query( $link, $query )
              or die( "Query failed : $query<br />\n" . mysqli_error($link) );

    if ( mysqli_num_rows( $result ) != 0 )
    {
      $text .= <<<HTML
    <div id='Noiselinksmodel'>
      <table cellspacing='0' cellpadding='0' class='admin'>
      <caption>Noise Linked to Models&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
      <thead>
        <tr><th>ID</th>
            <th>GUID</th>
            <th>Edit ID</th>
            <th>Model ID</th>
            <th>Model GUID</th>
            <th>Type</th>
        </tr>
      </thead>
    
      <tbody>

HTML;

      while ( list ( $noiseID, $GUID, $editID, $modelID, $modelGUID, $type ) = mysqli_fetch_array( $result ) )
      {
        $text .= <<<HTML
        <tr><td>$noiseID</td>
            <td>$GUID</td>
            <td>$editID</td>
            <td>$modelID</td>
            <td>$modelGUID</td>
            <td>$type</td>
        </tr>

HTML;
      }

      $text .= "</tbody>\n\n" .
               "</table>\n</div>\n";
      $toc .= "<li><a href='#Noiselinksmodel'>Noise Linked to Models</a></li>\n";

    }
  }

  $query  = "SELECT noiseID, noiseGUID, editedDataID, modelID, modelGUID, noiseType " .
            "FROM noise " .
            "WHERE editedDataID IN ( $editIDs_csv ) " .
            "ORDER BY noiseID ";
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );

  if ( mysqli_num_rows( $result ) != 0 )
  {
    $text .= <<<HTML
    <div id='Noiselinksedit'>
    <table cellspacing='0' cellpadding='0' class='admin'>
    <caption>Noise Linked to Edit Profiles&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
    <thead>
      <tr><th>ID</th>
          <th>GUID</th>
          <th>Edit ID</th>
          <th>Model ID</th>
          <th>Model GUID</th>
          <th>Type</th>
      </tr>
    </thead>
  
    <tbody>

HTML;

    while ( list ( $noiseID, $GUID, $editID, $modelID, $modelGUID, $type ) = mysqli_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$noiseID</td>
          <td>$GUID</td>
          <td>$editID</td>
          <td>$modelID</td>
          <td>$modelGUID</td>
          <td>$type</td>
      </tr>

HTML;
    }

    $text .= "</tbody>\n\n" .
             "</table>\n</div>\n";
    $toc .= "<li><a href='#Noiselinksedit'>Noise Linked to Edit Profiles</a></li>\n";

  }

  $reportIDs = array();
  $query  = "SELECT reportID, reportGUID, title " .
            "FROM report " .
            "WHERE experimentID = $experimentID " .
            "ORDER BY reportID ";

  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );

  if ( mysqli_num_rows( $result ) != 0 )
  {
    $text .= <<<HTML
    <div id='Reportexperiment'>
    <table cellspacing='0' cellpadding='0' class='admin'>
    <caption>Reports Related to This Experiment&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
    <thead>
      <tr><th>ID</th>
          <th>GUID</th>
          <th>Title</th>
      </tr>
    </thead>
  
    <tbody>

HTML;

    while ( list ( $reportID, $GUID, $title ) = mysqli_fetch_array( $result ) )
    {
      $reportIDs[] = $reportID;
      $text .= <<<HTML
      <tr><td>$reportID</td>
          <td>$GUID</td>
          <td>$title</td>
      </tr>

HTML;
    }

    $text .= "</tbody>\n\n" .
             "</table>\n</div>\n";
    $toc .= "<li><a href='#Reportexperiment'>Reports Related to This Experiment</a></li>\n";

  }

  $reportTripleIDs = array();
  $requestIDs = array();
  if ( ! empty( $reportIDs ) )
  {
    $reportIDs_csv = implode( ",", $reportIDs );
    $query  = "SELECT reportTripleID, reportTripleGUID, resultID, triple, dataDescription, reportID " .
              "FROM reportTriple " .
              "WHERE reportID IN ( $reportIDs_csv ) " .
              "ORDER BY reportID, reportTripleID ";
  
    $result = mysqli_query( $link, $query )
              or die( "Query failed : $query<br />\n" . mysqli_error($link) );

    if ( mysqli_num_rows( $result ) != 0 )
    {
      $text .= <<<HTML
      <div id='Reporttriples'>
      <table cellspacing='0' cellpadding='0' class='admin'>
      <caption>Report Triples Related to Reports&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
      <thead>
        <tr><th>ID</th>
            <th>GUID</th>
            <th>Result ID</th>
            <th>Triple</th>
            <th>Description</th>
            <th>Report ID</th>
        </tr>
      </thead>
    
      <tbody>
  
HTML;
  
      while ( list ( $reportTripleID, $GUID, $resultID, $triple, $dataDesc, $rptID ) 
                   = mysqli_fetch_array( $result ) )
      {
        $reportTripleIDs[] = $reportTripleID;
        $text .= <<<HTML
        <tr><td>$reportTripleID</td>
            <td>$GUID</td>
            <td>$resultID</td>
            <td>$triple</td>
            <td>$dataDesc</td>
            <td>$rptID</td>
        </tr>
  
HTML;
      }
  
      $text .= "</tbody>\n\n" .
               "</table>\n</div>\n";
      $toc .= "<li><a href='#Reporttriples'>Report Triples Related to Reports</a></li>\n";

    }
  }

  if ( ! empty( $reportTripleIDs ) )
  {
    $reportTripleIDs_csv = implode( ",", $reportTripleIDs );
    $query  = "SELECT d.reportDocumentID, reportDocumentGUID, editedDataID, label, " .
              "filename, analysis, subAnalysis, documentType, l.reportTripleID " .
              "FROM documentLink l, reportDocument d " .
              "WHERE reportTripleID IN ( $reportTripleIDs_csv ) " .
              "AND l.reportDocumentID = d.reportDocumentID " .
              "ORDER BY reportTripleID, reportDocumentID ";
  
    $result = mysqli_query( $link, $query )
              or die( "Query failed : $query<br />\n" . mysqli_error($link) );

    if ( mysqli_num_rows( $result ) != 0 )
    {
      $text .= <<<HTML
      <div id='Reports'>
      <table cellspacing='0' cellpadding='0' class='admin'>
      <caption>Report Documents Related to Triples&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
      <thead>
        <tr><th>ID</th>
            <th>GUID</th>
            <th>Edit ID</th>
            <th>Label/Filename</th>
            <th>Anal/Sub/DocType</th>
            <th>Trip ID</th>
        </tr>
      </thead>
    
      <tbody>
  
HTML;
  
      while ( list ( $reportDocumentID, $GUID, $editID, $label, $filename, 
                     $analysis, $subAnal, $docType, $tripID ) 
                   = mysqli_fetch_array( $result ) )
      {
        $text .= <<<HTML
        <tr><td>$reportDocumentID</td>
            <td>$GUID</td>
            <td>$editID</td>
            <td>$label/$filename</td>
            <td>$analysis/$subAnal/$docType</td>
            <td>$tripID</td>
        </tr>
  
HTML;
      }
  
      $text .= "</tbody>\n\n" .
               "</table>\n</div>\n";
        $toc .= "<li><a href='#Reports'>Report Documents Related to Triples</a></li>\n";

    }
  }

  $query  = "SELECT HPCAnalysisRequestID, HPCAnalysisRequestGUID, editXMLFilename, " .
            "submitTime, clusterName, method " .
            "FROM HPCAnalysisRequest " .
            "WHERE experimentID = $experimentID " .
            "ORDER BY HPCAnalysisRequestID ";
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );

  if ( mysqli_num_rows( $result ) == 0 )
    return $toc . $toc_end . $text;

  $text .= <<<HTML
  <div id='HPCrequests'>
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>HPC Requests&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
  <thead>
    <tr><th>ID</th>
        <th>GUID</th>
        <th>XML Filename</th>
        <th>Submit</th>
        <th>Cluster</th>
        <th>Method</th>
    </tr>
  </thead>

  <tbody>
HTML;

  while ( list( $ID, $GUID, $filename, $submit, $cluster, $method ) = mysqli_fetch_array( $result ) )
  {
    $requestIDs[]  = $ID;

    $text .= <<<HTML
    <tr><td><a href='{$_SERVER['PHP_SELF']}?RequestID=$ID'>$ID</a></td>
        <td>$GUID</td>
        <td>$filename</td>
        <td>$submit</td>
        <td>$cluster</td>
        <td>$method</td>
    </tr>

HTML;

  }

  $text .= "</tbody>\n\n" .
           "</table>\n</div>\n";
  $toc .= "<li><a href='#HPCrequests'>HPC Requests</a></li>\n";

  $requestIDs_csv = implode( ", ", $requestIDs );
  $query  = "SELECT HPCAnalysisResultID, HPCAnalysisRequestID, gfacID, queueStatus, updateTime " .
            "FROM HPCAnalysisResult " .
            "WHERE HPCAnalysisRequestID IN ( $requestIDs_csv ) " .
            "ORDER BY HPCAnalysisResultID ";
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );
  $incomplete = array();
  if ( mysqli_num_rows( $result ) != 0 )
  {
    $text .= <<<HTML
    <div id='HPCresults'>
    <table cellspacing='0' cellpadding='0' class='admin'>
    <caption>HPC Results&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
    <thead>
      <tr><th>ID</th>
          <th>Request ID</th>
          <th>gfac ID</th>
          <th>Status</th>
          <th>Updated</th>
      </tr>
    </thead>

    <tbody>
HTML;


    while ( list( $ID, $requestID, $gfacID, $status, $updated ) = mysqli_fetch_array( $result ) )
    {
      if ( $status != 'completed' )
        $incomplete[] = $gfacID;

      $text .= <<<HTML
      <tr><td>$ID</td>
          <td>$requestID</td>
          <td>$gfacID</td>
          <td>$status</td>
          <td>$updated</td>
      </tr>

HTML;

    }

  $text .= "</tbody>\n\n" .
           "</table>\n</div>\n";

  $toc .= "<li><a href='#HPCresults'>HPC Results</a></li>";
  }

  if ( empty( $incomplete ) )
    return $toc . $toc_end . $text;

  // Now switch over to the global db
  global $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname;

  $globaldb = mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname );

  if ( ! $globaldb )
  {
    $text .= "<p>Cannot open global database on $globaldbhost $globaldbname mysqli_error($globaldb) </p>\n";
    return $toc.$toc_end.$text;
  }

  $text .= <<<HTML
  <div id='GFAC'>
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>GFAC Status&emsp;&emsp;&emsp;<a href='#top'>Top</a></caption>
  <thead>
    <tr><th>gfacID</th>
        <th>Cluster</th>
        <th>DB</th>
        <th>Status</th>
        <th>Message</th>
        <th>Updated</th>
    </tr>
  </thead>

  <tbody>
HTML;

  $in_queue = 0;
  foreach ( $incomplete as $gfacID )
  {
    
    $query  = "SELECT cluster, us3_db, status, queue_msg, time " .
              "FROM analysis " .
              "WHERE gfacID = '$gfacID' ";
    $result = mysqli_query( $globaldb, $query )
              or die( "Query failed : $query<br />\n" . mysqli_error($globaldb) );
  
    if ( mysqli_num_rows( $result ) == 1 )
    {
      $in_queue++;
      list( $cluster, $db, $status, $msg, $time ) = mysqli_fetch_array( $result );
      $text .= <<<HTML
      <tr><td>$gfacID</td>
          <td>$cluster</td>
          <td>$db</td>
          <td>$status</td>
          <td>$msg</td>
          <td>$time</td>
      </tr>

HTML;
    }
  }
  
  if ( $in_queue == 0 )
     $text .= "<tr><td colspan='6'>No local jobs currently in the queue</td></tr>\n";

  $text .= "</tbody>\n\n" .
           "</table>\n</div>\n";
  $toc .= <<<HTML
    <li><a href='#GFAC'>GFAC Status</a></li>
HTML;

  mysqli_close( $globaldb );

  return $toc . $toc_end . $text;
}

function HPCDetail( $link, $requestID )
{
  global $thr_clust_excls, $thr_clust_incls;
  // Check if the user has access to this request by either being the investigator, submitter,
  // an entry in the experimentPerson table, an entry in the projectPerson table, or an user level of greater 2
  // language=MariaDB
  $query = "SELECT * FROM HPCAnalysisRequest hpc 
            LEFT OUTER JOIN experimentPerson ep ON hpc.experimentID = ep.experimentID
            LEFT OUTER JOIN experiment e ON hpc.experimentID = e.experimentID
            LEFT OUTER JOIN projectPerson pp ON e.projectID = pp.projectID
            LEFT OUTER JOIN people p ON p.personGUID = hpc.investigatorGUID
            LEFT OUTER JOIN people p2 ON p2.personGUID = hpc.submitterGUID
            WHERE HPCAnalysisRequestID=? and 
                  (p.personID = ? or 
                   p2.personID = ? or 
                   ep.personID = ? or 
                   pp.personID = ? or
                   ? > 2)";
  $stmt = mysqli_prepare( $link, $query );
  $stmt->bind_param('iiiiii', $requestID, $_SESSION['id'], $_SESSION['id'], $_SESSION['id'], $_SESSION['id'], $_SESSION['userlevel']);
  $stmt->execute() or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result() or die( "Query failed : $query<br />\n" . $stmt->error );
  $stmt->close();
  $row = mysqli_fetch_assoc( $result );
  $result->close();
  $requestID = $row['HPCAnalysisRequestID'];
  $row['requestXMLFile'] = '<pre>' . htmlentities( $row['requestXMLFile'] ) . '</pre>';

  // Save for later
  $requestGUID  = $row['HPCAnalysisRequestGUID'];
  $cluster = $row['clusterName'];
  #var_dump($cluster); 
 $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>HPC Request Detail</caption>

HTML;

  foreach ($row as $key => $value)
  {
    $text .= "  <tr><th>$key</th><td>$value</td></tr>\n";
  }

  $text .= "</table>\n";
  
  $query = "SELECT * FROM HPCAnalysisResult WHERE HPCAnalysisRequestID=$requestID";
  $result = mysqli_query( $link, $query )
           or die( "Query failed : $query<br />\n" . mysqli_error($link));
  $row = mysqli_fetch_assoc( $result );
  $row['jobfile'] = '<pre>' . htmlentities( $row['jobfile'] ) . '</pre>';

  // Get GFAC job status
  global $uses_thrift;
  $clus_thrift   = $uses_thrift;
  if ( in_array( $cluster, $thr_clust_excls ) )
    $clus_thrift   = false;
  if ( in_array( $cluster, $thr_clust_incls ) )
    $clus_thrift   = true;

  if ( $clus_thrift === true )
  {
    $row['gfacStatus'] = nl2br( getExperimentStatus( $row['gfacID'] ) );
  }
  else
  {
    $row['gfacStatus'] = nl2br( getJobstatus( $row['gfacID'] ) );
  }

  // Get queue messages from disk directory, if it still exists
  global $submit_dir;
  global $dbname;

  $msg_filename = "$submit_dir$requestGUID/$dbname-$requestID-messages.txt";
  $queue_msgs = false;
  $len_msgs   = 0;
  if ( file_exists( $msg_filename ) )
  {
    $queue_msgs   = file_get_contents( $msg_filename );
    $len_msgs     = strlen( $queue_msgs );
    $queue_msgs   = '<pre>' . $queue_msgs . '</pre>';
  }

  // Get resulting model and noise information
  if ( ! empty( $resultID ) )
  {
    $resultID = $row['HPCAnalysisResultID'];

    $query  = "SELECT resultID FROM HPCAnalysisResultData " .
              "WHERE HPCAnalysisResultID = $resultID " .
              "AND HPCAnalysisResultType = 'model' ";
    $result = mysqli_query( $link, $query )
             or die( "Query failed : $query<br />\n" . mysqli_error($link));
    $models = mysqli_fetch_row( $result );         // An array with all of them
    if ( $models !== false )
      $row['modelIDs'] = implode( ", ", $models );

    $query  = "SELECT resultID FROM HPCAnalysisResultData " .
              "WHERE HPCAnalysisResultID = $resultID " .
              "AND HPCAnalysisResultType = 'noise' ";
    $result = mysqli_query( $link, $query )
             or die( "Query failed : $query<br />\n" . mysqli_error($link));
    $noise  = mysqli_fetch_row( $result );         // An array with all of them
    if ( $noise !== false )
      $row['noiseIDs'] = implode( ", ", $noise );
  }

  $text .= <<<HTML
  <a name='runDetail'></a>
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>HPC Result Detail</caption>

HTML;

  foreach ($row as $key => $value)
  {
    $text .= "  <tr><th>$key</th><td>$value</td></tr>\n";
  }

  if ( $queue_msgs !== false )
  {
    $linkmsg = "<a href='{$_SERVER[ 'PHP_SELF' ]}?RequestID=$requestID&msgs=t#runDetail'>Length Messages</a>";

    $text .= "  <tr><th>$linkmsg</th><td>$len_msgs</td></tr>\n";
    if ( isset( $_GET[ 'msgs' ] ) ) 
      $text .= "  <tr><th>Queue Messages</th><td>$queue_msgs</td></tr>\n";
  }

  $text .= "</table>\n";

  return $text;
}

?>
