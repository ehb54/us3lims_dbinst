<?php
/*
 * orphans.php
 *
 * Display information about missing link records
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( ($_SESSION['userlevel'] != 3) &&
     ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) )    // admin and super admin only
{
  header('Location: index.php');
  exit();
} 

include 'config.php';
include 'db.php';

// Start displaying page
$page_title = "Orphans";
$css = 'css/admin.css';
include 'header.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Orphans</h1>
  <!-- Place page content here -->

<?php
  $text .= orphan_info();

  echo $text;

?>
</div>

<?php
include 'footer.php';
exit();

// A function to retrieve orphan information
function orphan_info()
{
  // Assemble tables
  $text  = orphan_projects();
  $text .= orphan_experiments1();
  $text .= orphan_experiments2();
  $text .= orphan_experiments3();
  $text .= orphan_rawdata();
  $text .= orphan_solutionBuffer();
  $text .= orphan_solutionAnalyte();
  $text .= orphan_editedData();
  $text .= orphan_models1();
  $text .= orphan_models2();
  $text .= orphan_noise1();
  $text .= orphan_noise2();
  $text .= orphan_HPCRequests();
  $text .= orphan_HPCResults();

  return $text;
}

function orphan_projects()
{
  // Projects that don't belong to anybody
  $query  = "SELECT p.projectID, description, status, pp.personID " .
            "FROM project p " .
            "LEFT JOIN projectPerson pp ON ( p.projectID = pp.projectID ) " .
            "LEFT JOIN people ON ( pp.personID = people.personID ) " .
            "WHERE ( pp.projectID IS NULL ) " .
            "||    ( people.personID IS NULL ) " .
            "ORDER BY p.projectID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Projects with Missing Ownership</caption>
  <thead>
    <tr><th>ID</th>
        <th>Description</th>
        <th>Status</th>
        <th>Person ID</th>
    </tr>
  </thead>
  
  <tbody>
HTML;

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='4'>No orphan projects found</td></tr>\n";

  else
  {
    while ( list( $ID, $description, $status, $personID ) = mysql_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$ID</td>
          <td>$description</td>
          <td>$status</td>
          <td>$personID</td>
      </tr>
  
HTML;
    }
  }

  $text .= "</tbody>\n\n" .
           "</table>\n";

  return $text;
}

function orphan_experiments1()
{
  // Experiments with missing projects
  $query  = "SELECT experimentID, experimentGUID, runID, type, e.projectID, dateUpdated " .
            "FROM experiment e LEFT JOIN project " .
            "ON ( e.projectID = project.projectID ) " .
            "WHERE project.projectID IS NULL " .
            "ORDER BY experimentID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Runs (experiments) with Missing Projects</caption>
  <thead>
    <tr><th>ID</th>
        <th>GUID</th>
        <th>Run ID</th>
        <th>Type</th>
        <th>ProjectID</th>
        <th>Updated</th>
    </tr>
  </thead>
  
  <tbody>
HTML;

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='4'>No orphan raw data found</td></tr>\n";

  else
  {
    while ( list( $ID, $GUID, $runID, $type, $projectID, $updated ) = mysql_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$ID</td>
          <td>$GUID</td>
          <td>$runID</td>
          <td>$type</td>
          <td>$projectID</td>
          <td>$updated</td>
      </tr>
  
HTML;
    }
  }

  $text .= "</tbody>\n\n" .
           "</table>\n";

  return $text;
}

function orphan_experiments2()
{
  // Experiments that don't belong to anybody
  $query  = "SELECT e.experimentID, runID, dateUpdated, ep.personID " .
            "FROM experiment e " .
            "LEFT JOIN experimentPerson ep ON ( e.experimentID = ep.experimentID ) " .
            "LEFT JOIN people ON ( ep.personID = people.personID ) " .
            "WHERE ( ep.experimentID IS NULL ) " .
            "||    ( people.personID IS NULL ) " .
            "ORDER BY e.experimentID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Runs ( experiments ) with Missing Ownership</caption>
  <thead>
    <tr><th>ID</th>
        <th>Run ID</th>
        <th>Updated</th>
        <th>Person ID</th>
    </tr>
  </thead>
  
  <tbody>
HTML;

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='4'>No orphan runs found</td></tr>\n";

  else
  {
    while ( list( $ID, $runID, $updated, $personID ) = mysql_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$ID</td>
          <td>$runID</td>
          <td>$updated</td>
          <td>$personID</td>
      </tr>
  
HTML;
    }
  }

  $text .= "</tbody>\n\n" .
           "</table>\n";

  return $text;
}

function orphan_experiments3()
{
  // Experiments that refer to a nonexistent solution
  $query  = "SELECT e.experimentID, runID, dateUpdated, esc.solutionID " .
            "FROM experiment e " .
            "LEFT JOIN experimentSolutionChannel esc ON ( e.experimentID = esc.experimentID ) " .
            "LEFT JOIN solution ON ( esc.solutionID = solution.solutionID ) " .
            "WHERE ( esc.experimentID IS NULL ) " .
            "||    ( solution.solutionID IS NULL ) " .
            "ORDER BY e.experimentID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Runs ( experiments ) with Missing Solutions</caption>
  <thead>
    <tr><th>ID</th>
        <th>Run ID</th>
        <th>Updated</th>
        <th>Solution ID</th>
    </tr>
  </thead>
  
  <tbody>
HTML;

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='4'>No orphan runs found</td></tr>\n";

  else
  {
    while ( list( $ID, $runID, $updated, $solutionID ) = mysql_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$ID</td>
          <td>$runID</td>
          <td>$updated</td>
          <td>$solutionID</td>
      </tr>
  
HTML;
    }
  }

  $text .= "</tbody>\n\n" .
           "</table>\n";

  return $text;
}

function orphan_rawdata()
{
  $query  = "SELECT rawDataID, rawDataGUID, filename, rawData.experimentID " .
            "FROM rawData LEFT JOIN experiment " .
            "ON (rawData.experimentID = experiment.experimentID ) " .
            "WHERE experiment.experimentID IS NULL " .
            "ORDER BY filename ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Raw Data with Missing Run Records</caption>
  <thead>
    <tr><th>ID</th>
        <th>GUID</th>
        <th>Filename</th>
        <th>Experiment ID</th>
    </tr>
  </thead>
  
  <tbody>
HTML;

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='4'>No orphan raw data found</td></tr>\n";

  else
  {
    while ( list( $ID, $GUID, $filename, $experimentID ) = mysql_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$ID</td>
          <td>$GUID</td>
          <td>$filename</td>
          <td>$experimentID</td>
      </tr>
  
HTML;
    }
  }

  $text .= "</tbody>\n\n" .
           "</table>\n";

  return $text;
}

function orphan_solutionBuffer()
{
  $query  = "SELECT s.solutionID, solutionGUID, s.description, sb.bufferID " .
            "FROM solution s " .
            "LEFT JOIN solutionBuffer sb ON ( s.solutionID = sb.solutionID ) " .
            "LEFT JOIN buffer ON ( sb.bufferID = buffer.bufferID ) " .
            "WHERE ( sb.solutionID IS NULL ) " .
            "||    ( buffer.bufferID IS NULL ) " .
            "ORDER BY s.solutionID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Solutions with Missing Buffers</caption>
  <thead>
    <tr><th>ID</th>
        <th>GUID</th>
        <th>Description</th>
        <th>Buffer ID</th>
    </tr>
  </thead>
  
  <tbody>
HTML;

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='4'>No orphan solution-buffer links found</td></tr>\n";

  else
  {
    while ( list( $ID, $GUID, $description, $bufferID ) = mysql_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$ID</td>
          <td>$GUID</td>
          <td>$description</td>
          <td>$bufferID</td>
      </tr>
  
HTML;
    }
  }

  $text .= "</tbody>\n\n" .
           "</table>\n";

  return $text;
}

function orphan_solutionAnalyte()
{
  $query  = "SELECT s.solutionID, solutionGUID, s.description, sa.analyteID " .
            "FROM solution s " .
            "LEFT JOIN solutionAnalyte sa ON ( s.solutionID = sa.solutionID ) " .
            "LEFT JOIN analyte ON ( sa.analyteID = analyte.analyteID ) " .
            "WHERE ( sa.solutionID IS NULL ) " .
            "||    ( analyte.analyteID IS NULL ) " .
            "ORDER BY s.solutionID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Solutions with Missing Analytes</caption>
  <thead>
    <tr><th>ID</th>
        <th>GUID</th>
        <th>Description</th>
        <th>Analyte ID</th>
    </tr>
  </thead>
  
  <tbody>
HTML;

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='4'>No orphan solution-analyte links found</td></tr>\n";

  else
  {
    while ( list( $ID, $GUID, $description, $analyteID ) = mysql_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$ID</td>
          <td>$GUID</td>
          <td>$description</td>
          <td>$analyteID</td>
      </tr>
  
HTML;
    }
  }

  $text .= "</tbody>\n\n" .
           "</table>\n";

  return $text;
}

function orphan_editedData()
{
  $query  = "SELECT editedDataID, editedData.rawDataID, editGUID, editedData.filename " .
            "FROM editedData LEFT JOIN rawData " .
            "ON (editedData.rawDataID = rawData.rawDataID ) " .
            "WHERE rawData.rawDataID IS NULL " .
            "AND editedDataID != 1 " .                      // the special "unassigned" ID
            "ORDER BY editedDataID, filename ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Edit Profiles with Missing Raw Data</caption>
  <thead>
    <tr><th>ID</th>
        <th>GUID</th>
        <th>Filename</th>
        <th>Raw ID</th>
    </tr>
  </thead>
  
  <tbody>
  
HTML;

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='4'>No orphan edit profiles found</td></tr>\n";

  else
  {
    while ( list ( $editID, $rawID, $GUID, $filename ) = mysql_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$editID</td>
          <td>$GUID</td>
          <td>$filename</td>
          <td>$rawID</td>
      </tr>

HTML;
    }
  }

  $text .= "</tbody>\n\n" .
           "</table>\n";


  return $text;
}

function orphan_models1()
{
  // Models linked to missing edit profiles
  $query  = "SELECT modelID, model.editedDataID, modelGUID, variance, meniscus " .
            "FROM model LEFT JOIN editedData " .
            "ON ( model.editedDataID = editedData.editedDataID ) " .
            "WHERE editedData.editedDataID IS NULL " .
            "ORDER BY modelID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Models with missing Edit Profiles</caption>
  <thead>
    <tr><th>ID</th>
        <th>GUID</th>
        <th>Variance</th>
        <th>Meniscus</th>
        <th>Edit ID</th>
    </tr>
  </thead>

  <tbody>

HTML;

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='5'>No orphan models found</td></tr>\n";

  else
  {
    while ( list ( $modelID, $editID, $GUID, $variance, $meniscus ) = mysql_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$modelID</td>
          <td>$GUID</td>
          <td>$variance</td>
          <td>$meniscus</td>
          <td>$editID</td>
      </tr>

HTML;
    }
  }

  $text .= "</tbody>\n\n" .
           "</table>\n";


  return $text;
}

function orphan_models2()
{
  // models that don't belong to anybody
  $query  = "SELECT m.modelID, editedDataID, modelGUID, variance, meniscus, mp.personID " .
            "FROM model m " .
            "LEFT JOIN modelPerson mp ON ( m.modelID = mp.modelID ) " .
            "LEFT JOIN people ON ( mp.personID = people.personID ) " .
            "WHERE ( mp.personID IS NULL ) " .
            "||    ( people.personID IS NULL ) " .
            "ORDER BY m.modelID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Models with missing Ownership</caption>
  <thead>
    <tr><th>ID</th>
        <th>GUID</th>
        <th>Variance</th>
        <th>Meniscus</th>
        <th>Edit ID</th>
        <th>Person ID</th>
    </tr>
  </thead>

  <tbody>

HTML;

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='5'>No orphan models found</td></tr>\n";

  else
  {
    while ( list ( $modelID, $editID, $GUID, $variance, $meniscus, $personID ) = mysql_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$modelID</td>
          <td>$GUID</td>
          <td>$variance</td>
          <td>$meniscus</td>
          <td>$editID</td>
          <td>$personID</td>
      </tr>

HTML;
    }
  }

  $text .= "</tbody>\n\n" .
           "</table>\n";


  return $text;
}

function orphan_noise1()
{
  // Orphan noise ( relating to models )
  $query  = "SELECT noiseID, noiseGUID, noise.editedDataID, noise.modelID, noise.modelGUID, noiseType " .
            "FROM noise LEFT JOIN model " .
               "ON ( noise.modelID = model.modelID ) " .
            "WHERE model.modelID IS NULL " .
            "ORDER BY noiseID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Noise Linked to Missing Models</caption>
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

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='5'>No orphan noise records found</td></tr>\n";

  else
  {
    while ( list ( $noiseID, $GUID, $editID, $modelID, $modelGUID, $type ) = mysql_fetch_array( $result ) )
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
  }

  $text .= "</tbody>\n\n" .
           "</table>\n";


  return $text;
}

function orphan_noise2()
{
  // Orphan noise files ( relating to edit profiles )
  $query  = "SELECT noiseID, noiseGUID, noise.editedDataID, modelID, modelGUID, noiseType " .
            "FROM noise LEFT JOIN editedData " .
            "ON ( noise.editedDataID = editedData.editedDataID ) " .
            "WHERE editedData.editedDataID IS NULL " .
            "ORDER BY noiseID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>Noise Linked to Missing Edit Profiles</caption>
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

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='6'>No orphan noise records found</td></tr>\n";

  else
  {
    while ( list ( $noiseID, $GUID, $editID, $modelID, $modelGUID, $type ) = mysql_fetch_array( $result ) )
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
  }

  $text .= "</tbody>\n\n" .
           "</table>\n";


  return $text;
}

function orphan_HPCRequests()
{
  $query  = "SELECT HPCAnalysisRequestID, HPCAnalysisRequestGUID, editXMLFilename, " .
            "submitTime, clusterName, method, r.experimentID " .
            "FROM HPCAnalysisRequest r LEFT JOIN experiment " .
            "ON ( r.experimentID = experiment.experimentID ) " .
            "WHERE experiment.experimentID IS NULL " .
            "ORDER BY HPCAnalysisRequestID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>HPC Requests</caption>
  <thead>
    <tr><th>ID</th>
        <th>GUID</th>
        <th>XML Filename</th>
        <th>Submit</th>
        <th>Cluster</th>
        <th>Method</th>
        <th>Experiment ID</th>
    </tr>
  </thead>

  <tbody>
HTML;

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='6'>No orphan HPC requests found</td></tr>\n";

  else
  {
    while ( list( $ID, $GUID, $filename, $submit, $cluster, $method, $expID ) = mysql_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$ID</td>
          <td>$GUID</td>
          <td>$filename</td>
          <td>$submit</td>
          <td>$cluster</td>
          <td>$method</td>
          <td>$expID</td>
      </tr>

HTML;
    }
  }
  
  $text .= "</tbody>\n\n" .
           "</table>\n";


  return $text;
}

function orphan_HPCResults()
{
  $query  = "SELECT HPCAnalysisResultID, r.HPCAnalysisRequestID, gfacID, queueStatus, updateTime " .
            "FROM HPCAnalysisResult r LEFT JOIN HPCAnalysisRequest q " .
            "ON ( r.HPCAnalysisRequestID = q.HPCAnalysisRequestID ) " .
            "WHERE q.HPCAnalysisRequestID IS NULL " .
            "ORDER BY HPCAnalysisResultID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $text = <<<HTML
  <table cellspacing='0' cellpadding='0' class='admin'>
  <caption>HPC Results</caption>
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

  if ( mysql_num_rows( $result ) == 0 )
    $text .= "    <tr><td colspan='5'>No orphan HPC results found</td></tr>\n";

  else
  {
    while ( list( $ID, $requestID, $gfacID, $status, $updated ) = mysql_fetch_array( $result ) )
    {
      $text .= <<<HTML
      <tr><td>$ID</td>
          <td>$requestID</td>
          <td>$gfacID</td>
          <td>$status</td>
          <td>$updated</td>
      </tr>

HTML;
    }
  }
  
  $text .= "</tbody>\n\n" .
           "</table>\n";

  return $text;
}
?>
