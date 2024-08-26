<?php
/*
 * reports.php
 *
 * A few common routines used to display controls having to do with reports
 *   that were stored in the DB by UltraScan III
 *
 */

// Function to create a dropdown for people who have given us permission
function people_select( $link, $select_name, $personID = NULL )
{
  // Caller can pass a selected personID, but we need to check permissions
  $myID = $_SESSION['id'];
  if ( $personID == NULL ) $personID = $myID;

  if ( $_SESSION['userlevel'] < 3 )
  {
     // First of all, make an array of all people we are authorized to view
     // language=MariaDB
     $query  = "SELECT people.personID, lname, fname "  .
               "FROM permits, people " .
               "WHERE collaboratorID = ? " .
               "AND permits.personID = people.personID " .
               "ORDER BY lname, fname ";
  }

  else
  {
     // We are admin, so we can view all of them
     // language=MariaDB
     $query  = "SELECT personID, lname, fname "  .
               "FROM people " .
               "WHERE personID != ? " .
               "ORDER BY lname, fname ";
  }
  $stmt = mysqli_prepare( $link, $query );
  $stmt->bind_param( "i", $myID );
  $stmt->execute();
  $result = $stmt->get_result()
            or die( "Query failed : $query<br />" . $stmt->error );
  // Create the list box
  $myName = "{$_SESSION['lastname']}, {$_SESSION['firstname']}";
  $text  = "<h3>Investigator:</h3>\n";
  $text .= "<select name='$select_name' id='$select_name' size='1'>\n" .
           "    <option value='$myID'>$myName</option>\n";
  while ( list( $ID, $lname, $fname ) = mysqli_fetch_array( $result ) )
  {
    $selected = ( $ID == $personID ) ? " selected='selected'" : "";
    $text .= "    <option value='$ID'$selected>$lname, $fname</option>\n";
  }
  $result->close();
  $stmt->close();
  $text .= "  </select>\n";

  return $text;
}

// Function to create a dropdown for available runIDs
function run_select( $link, $select_name, $current_ID = NULL, $personID = NULL )
{
  // Caller can pass a personID to get anybody's report, but we default
  //   to user's own
  $myID = $_SESSION['id'];
  if ( $personID == NULL ) $personID = $myID;

  // Check the permits table to be sure user is authorized to view this report
  if ( ( $personID != $myID ) && ( $_SESSION['userlevel'] < 3 ) )
  {
     // language=MariaDB
     $query  = "SELECT COUNT(*) FROM permits " .
               "WHERE personID = ? " .
               "AND collaboratorID = ? ";
     $stmt = mysqli_prepare( $link, $query );
     $stmt->bind_param( "ii", $personID, $myID );
     $stmt->execute();
     $result = $stmt->get_result()
         or die( "Query failed : $query<br />" . $stmt->error );
     list( $count ) = mysqli_fetch_array( $result );
     $result->close();
     $stmt->close();
     if ( $count == 0 )
     {
        // Ok, user was not authorized
        $personID = $myID;
     }
  }

  // Account for user selecting the Please select... choice
  $current_ID = ( $current_ID == -1 ) ? NULL : $current_ID;
  // language=MariaDB
  $query  = "SELECT report.reportID, runID " .
            "FROM reportPerson, report " .
            "WHERE reportPerson.personID = ? " .
            "AND reportPerson.reportID = report.reportID " .
            "ORDER BY runID ";
  $stmt = mysqli_prepare( $link, $query );
  $stmt->bind_param( "i", $personID );
  $stmt->execute();
  $result = $stmt->get_result()
          or die( "Query failed : $query<br />" . $stmt->error );

  if ( $result->num_rows == 0 ) return "";

  $text  = "<h3>Run ID:</h3>\n";
  $text .= "<select name='$select_name' id='$select_name' size='1'>\n" .
           "    <option value='-1'>Please select...</option>\n";
  while ( list( $reportID, $runID ) = mysqli_fetch_array( $result ) )
  {
    $selected = ( $current_ID == $reportID ) ? " selected='selected'" : "";
    $text .= "    <option value='$reportID'$selected>$runID</option>\n";
  }
  $result->close();
  $stmt->close();

  $text .= "  </select>\n";

  return $text;
}

// Function to return a list of triples, if we know the reportID
function tripleList( $link, $current_ID = NULL )
{
  // Account for user selecting the Please select... choice
  $current_ID = ( $current_ID == -1 ) ? NULL : $current_ID;

  $text = '';

  if ( isset( $current_ID ) )
  {
    // We have a legit runID, so let's get a list of triples
    //  associated with the run
    $text .= "<h3>Reports for Individual Samples:</h3>\n";
    // language=MariaDB
    $query  = "SELECT reportTripleID, triple, dataDescription, runType " .
              "FROM reportTriple, report, experiment " .
              "WHERE reportTriple.reportID = ? " .
              "AND triple NOT LIKE '0%' " .           // Combined triples look like 0/Z/9999
              "AND reportTriple.reportID = report.reportID " .
              "AND report.experimentID = experiment.experimentID " .
              "ORDER BY triple ";
    $stmt = mysqli_prepare( $link, $query );
    $stmt->bind_param( "i", $current_ID );
    $stmt->execute();

    $result = $stmt->get_result()
            or die( "Query failed : $query<br />" . $stmt->error );

    $text .= "<ul>\n";
    while ( list( $tripleID, $tripleDesc, $dataDesc, $runType ) = mysqli_fetch_array( $result ) )
    {
      list( $cell, $channel, $wl ) = explode( "/", $tripleDesc );
      $description = ( empty($dataDesc) ) ? "" : "; Descr: $dataDesc";
      $radius      = $wl / 1000.0;    // If WA data
      $display = ( $runType == "WA" )
               ? "Cell: $cell; Channel: $channel; Radius: $radius$description"
               : "Cell: $cell; Channel: $channel; Wavelength: $wl$description";
      $text .= "  <li><a href='view_reports.php?triple=$tripleID'>$display</a></li>\n";
    }
    $result->close();
    $stmt->close();
    $text .= "</ul>\n";
  }

  return $text;
}

// Function to return a link to the combo reports, if there are any
function combo_info( $link, $current_ID )
{
  // Account for user selecting the Please select... choice
  $current_ID = ( $current_ID == -1 ) ? NULL : $current_ID;

  $text = '';

  if ( isset( $current_ID ) )
  {
    // We have a legit runID, so let's get a list of triples
    //  associated with the run
    // language=MariaDB
    $query  = "SELECT reportTripleID, dataDescription " .
              "FROM reportTriple " .
              "WHERE reportID = ? " .
              "AND triple LIKE '0%' " .               // Combined triples look like 0/Z/9999
              "ORDER BY triple ";
    $stmt = mysqli_prepare( $link, $query );
    $stmt->bind_param( "i", $current_ID );
    $stmt->execute();
    $result = $stmt->get_result()
            or die( "Query failed : $query<br />" . $stmt->error );

    // In this case we might not have any
    if ( $result->num_rows > 0 )
    {
      $text .= "<h3>Combination Plots:</h3>\n";

      $text .= "<ul>\n";
      while ( list( $tripleID, $dataDesc ) = mysqli_fetch_array( $result ) )
      {
        $description = ( empty($dataDesc) ) ? "" : "$dataDesc";
        $text .= "  <li><a href='view_reports.php?combo=$tripleID'>$description</a></li>\n";
      }

      $text .= "</ul><br /><br />\n";
    }
    $result->close();
    $stmt->close();
  }

  return $text;
}

// A function to retrieve the reportTriple detail
function tripleDetail( $link, $tripleID, $selected_docTypes = array() )
{
  // Let's start with header information
  // language=MariaDB
  $query  = "SELECT personID, report.reportID, report.runID, " .
            "triple, dataDescription, runType " .
            "FROM reportTriple, report, reportPerson, experiment " .
            "WHERE reportTripleID = ? " .
            "AND reportTriple.reportID = report.reportID " .
            "AND report.reportID = reportPerson.reportID " .
            "AND report.experimentID = experiment.experimentID ";
  $stmt = mysqli_prepare( $link, $query );
  $stmt->bind_param( "i", $tripleID );
  $stmt->execute();
  $result = $stmt->get_result()
          or die( "Query failed : $query<br />" . $stmt->error );
  list ( $personID, $reportID, $runID, $tripleDesc, $dataDesc, $runType )
       = mysqli_fetch_array( $result );
  list ( $cell, $channel, $wl ) = explode( "/", $tripleDesc );
  $radius      = $wl / 1000.0;    // If WA data
  $description = ( empty($dataDesc) ) ? "" : "; Descr: $dataDesc";
  $text  = "<h3>Run ID: $runID</h3>\n";
  $text .= ( $runType == "WA" )
         ? "<h4>Cell: $cell; Channel: $channel; Radius: $radius$description</h4>\n"
         : "<h4>Cell: $cell; Channel: $channel; Wavelength: $wl$description</h4>\n";
  $result->close();
  $stmt->close();

  // Figure out which document types to display in a flexible way, so it will still
  //  work when new ones are added
  $docTypes  = array();
  $docTypes2 = array();
  $query  = "SELECT DISTINCT documentType FROM reportDocument " .
            "ORDER BY documentType ";
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );
  while ( list( $docType ) = mysqli_fetch_array( $result ) )
  {
    // all checkboxes should be checked initially, except svg/svgz
    if ( empty( $selected_docTypes ) && $docType != 'svg' && $docType != 'svgz' )
    {
       $docTypes[ $docType ] = true;
       $docTypes2[] = $docType;
    }

    else if ( empty( $selected_docTypes ) )
    {
       $docTypes[ 'svgz' ] = false;
    }

    else
    {
       $docTypes[ $docType ] = ( in_array( $docType, $selected_docTypes ) );
       if ( in_array( $docType, $selected_docTypes ) )
          $docTypes2[] = $docType;
    }
  }

  // Now create the checkboxes so the user can change it
  $checkboxes = '';
  $jquery     = '';
  foreach ( $docTypes as $docType => $active )
  {
    $checked = ( $active ) ? " checked='checked'" : "";
    $checkboxes .= "      <input type='checkbox' id='image_{$tripleID}_$docType'$checked /> $docType<br />\n";
  }

  $text .= <<<HTML
    <div>
      <p>Include the following report document types:</p>
      $checkboxes
    </div>

    <script>
      $(":checkbox").click( change_docType );
    </script>

HTML;

  // Now create a list of available analysis types
  $atypes = array();
  // language=MariaDB
  $query  = "SELECT DISTINCT analysis, label " .
            "FROM documentLink, reportDocument " .
            "WHERE documentLink.reportTripleID = ? " .
            "AND documentLink.reportDocumentID = reportDocument.reportDocumentID " .
            "ORDER BY analysis ";
  $stmt = mysqli_prepare( $link, $query );
  $stmt->bind_param( "i", $tripleID );
  $stmt->execute();
  $result = $stmt->get_result()
            or die( "Query failed : $query<br />\n" . $stmt->error );
  while ( list( $atype, $label ) = mysqli_fetch_array( $result ) )
  {
    $parts = explode( ":", $label );
    $atypes[$atype] = $parts[0];      // The analysis part of the label
  }
  $result->close();
  $stmt->close();

  // Make a little link bar
  $links = array();
  foreach ( $atypes as $atype => $alabel )
    $links[] = "<a href='#$atype'>$atype</a>";
  // Add solution
  $links[] = "<a href='#solution'>Solution</a>";
  $linkbar = ( count($links) < 2 ) ? "" : ( "Jump to: " . implode( " | ", $links ) );

  // Figure out which types of documents to display
  $select_docs = "AND documentType IN ('" . implode( "','", $docTypes2 ) . "') ";
  $bind_clause = implode(',', array_fill(0, count($docTypes2), '?'));
  $bind_string = str_repeat('s', count($docTypes2));
  foreach ( $atypes as $atype => $alabel )
  {
    // language=MariaDB
    $query  = "SELECT reportDocument.reportDocumentID, label, documentType " .
              "FROM documentLink, reportDocument " .
              "WHERE documentLink.reportTripleID = ? " .
              "AND documentLink.reportDocumentID = reportDocument.reportDocumentID " .
              "AND analysis = ? and documentType IN (" . $bind_clause . ")" .
              "ORDER BY subAnalysis ";
    $args = [$tripleID, $atype, ...$docTypes2];
    $stmt = mysqli_prepare( $link, $query );
    $stmt->bind_param( "is".$bind_string, ...$args );
    $stmt->execute();
    $result = $stmt->get_result()
              or die( "Query failed : $query<br />\n" . $stmt->error );

    if ( $result->num_rows < 1 ) continue;

    $text .= "<p class='reporthead'><a name='$atype'></a>$alabel</p>\n" .
             "<ul>\n";
    while ( list( $docID, $label, $doctype ) = mysqli_fetch_array( $result ) )
    {
      list( $anal, $subanal, $doctype_text ) = explode( ":", $label );

      // Add document type suffix unless that is already part of the title
      $include_doctype = "";

      if ( in_array( $doctype, array( 'png', 'svg', 'svgz' ) ) )
      {
        if ( strpos( $subanal, 'Plot' ) === false )
        { // No "Plot" in title, so add it
          $include_doctype = " Plot";
        }

        // Add additional information if PNG/SVGZ both possible
        if ( in_array( 'png', $selected_docTypes )  &&
             ( in_array( 'svgz', $selected_docTypes ) ||
               in_array( 'svg',  $selected_docTypes ) ) )
        {
          if ( strpos( $doctype, 'png' ) !== false )
            $include_doctype .= " (PNG)";
          else if ( strpos( $doctype, 'svgz' ) !== false )
            $include_doctype .= " (SVGZ)";
          else if ( strpos( $doctype, 'svg' ) !== false )
            $include_doctype .= " (SVG)";
        }
      }

      else if ( in_array( $doctype, array( 'html', 'rpt' ) ) )
      { // No "Report" in title, so add it
        if ( strpos( $subanal, 'Report' ) === false )
        {
          $include_doctype = " Report";
        }
      }

      else
      { // No "Table" in title, so add it
        if ( strpos( $subanal, 'Table' ) === false )
        {
          $include_doctype = " Table";
        }
      }

      // Add the entry for a document
      $text .= "  <li><a href='#$atype' onclick='show_report_detail( $docID );'>" .
               "$subanal{$include_doctype}</a></li>\n";
    }

    $text .= "</ul>\n";

    // Let's add links to make things easier to get around
    $self = $_SERVER['PHP_SELF'];
    $text .= <<<HTML
    <form name='$alabel' action='$self' method='post'>
      <p><input type='hidden' name='personID' value='$personID' />
         <input type='hidden' name='reportID' value='$reportID' />
         <input type='submit' name='change_cell' value='Select another report?' />
         $linkbar</p>
    </form>
HTML;

    $result->close();
    $stmt->close();
  }

  // Now let's get information about the solution in this cell
  // language=MariaDB
  $query  = "SELECT experimentID, triple " .
            "FROM report, reportTriple " .
            "WHERE reportTripleID = ? " .
            "AND report.reportID = reportTriple.reportID ";
  $stmt = mysqli_prepare( $link, $query );
  $stmt->bind_param( "i", $tripleID );
  $stmt->execute();
  $result = $stmt->get_result()
            or die( "Query failed : $query<br />\n" . $stmt->error );
  list ( $experimentID, $triple_desc ) = mysqli_fetch_array( $result );
  $result->close();
  $stmt->close();
  $text .= <<<HTML
    <p class='reporthead'><a name='solution'></a>Solution Data</p>
    <ul>
        <li><a href='#solution'
               onclick="show_solution_detail( 'solution', $experimentID, '$triple_desc' );">
               Solution Information</a></li>
        <li><a href='#solution'
               onclick="show_solution_detail( 'analyte', $experimentID, '$triple_desc' );">
               Analyte Information</a></li>
        <li><a href='#solution'
               onclick="show_solution_detail( 'buffer', $experimentID, '$triple_desc' );">
               Buffer Information</a></li>

    </ul>
HTML;

  // Let's add links to make things easier to get around
  $self = $_SERVER['PHP_SELF'];
  $text .= <<<HTML
  <form name='$alabel' action='$self' method='post'>
    <p><input type='hidden' name='personID' value='$personID' />
       <input type='hidden' name='reportID' value='$reportID' />
       <input type='submit' name='change_cell' value='Select another report?' />
       $linkbar</p>
  </form>
HTML;

  return $text;
}

// A function to retrieve the reportTriple detail for combinations
function comboDetail( $link, $tripleID )
{
  // Let's start with header information
  // language=MariaDB
  $query  = "SELECT personID, report.reportID, dataDescription " .
            "FROM reportTriple, report, reportPerson " .
            "WHERE reportTripleID = ? " .
            "AND reportTriple.reportID = report.reportID " .
            "AND report.reportID = reportPerson.reportID ";
  $stmt = mysqli_prepare( $link, $query );
  $stmt->bind_param( "i", $tripleID );
  $stmt->execute();
  $result = $stmt->get_result()
          or die( "Query failed : $query<br />" . $stmt->error );
  list ( $personID, $reportID, $dataDesc )
       = mysqli_fetch_array( $result );
  $result->close();
  $stmt->close();
  $text = "<h3>Combinations:</h3>\n" .
          "<h4>$dataDesc</h4>\n";

  // Now create a list of available analysis types
  $atypes = array();
  // language=MariaDB
  $query  = "SELECT DISTINCT analysis, label " .
            "FROM documentLink, reportDocument " .
            "WHERE documentLink.reportTripleID = ? " .
            "AND documentLink.reportDocumentID = reportDocument.reportDocumentID ";
  $stmt = mysqli_prepare( $link, $query );
  $stmt->bind_param( "i", $tripleID );
  $stmt->execute();
  $result = $stmt->get_result()
          or die( "Query failed : $query<br />\n" . $stmt->error );
  while ( list( $atype, $label ) = mysqli_fetch_array( $result ) )
  {
    $parts = explode( ":", $label );
    $atypes[$atype] = $parts[0];      // The analysis part of the label
  }
  $result->close();
  $stmt->close();
  // language=MariaDB
  $query  = "SELECT reportDocument.reportDocumentID, label " .
      "FROM documentLink, reportDocument " .
      "WHERE documentLink.reportTripleID = ? " .
      "AND documentLink.reportDocumentID = reportDocument.reportDocumentID " .
      "AND analysis = ? " .
      "ORDER BY subAnalysis ";
  $stmt = mysqli_prepare( $link, $query );
  foreach ( $atypes as $atype => $alabel )
  {
    $stmt->bind_param( "is", $tripleID, $atype );
    $stmt->execute();
    $result = $stmt->get_result()
              or die( "Query failed : $query<br />\n" . $stmt->error );

    $text .= "<p class='reporthead'><a name='$atype'></a>$alabel</p>\n" .
             "<ul>\n";
    while ( list( $docID, $label ) = mysqli_fetch_array( $result ) )
    {
      list( $anal, $subanal, $doctype ) = explode( ":", $label );
      $text .= "  <li><a href='#$atype' onclick='show_report_detail( $docID );'>$subanal ($doctype)</a></li>\n";
    }
    $result->close();
    $text .= "</ul>\n";
  }

  // Let's add a back link to make things easier to get to the list of reports
  $self = $_SERVER['PHP_SELF'];
  $text .= <<<HTML
  <form action='$self' method='post'>
    <p><input type='hidden' name='personID' value='$personID' />
       <input type='hidden' name='reportID' value='$reportID' />
       <input type='submit' name='change_cell' value='Select another report?' /></p>
  </form>
HTML;

  return $text;
}
?>
