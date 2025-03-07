<?php
// Get information from queue_setup_1.php
function get_setup_1( $link )
{
  if ( isset( $_SESSION['new_submitter'] ) )
  {
    $new_submitter = trim( $_SESSION['new_submitter'] );

    // Replace blanks with commas, in case user added others
    $new_submitter = preg_replace ( "/,/", ',', $new_submitter);
    $new_submitter = preg_replace ( "/\s+/", ',', $new_submitter);
    $new_submitter = preg_replace ( "/,+/", ',', $new_submitter);
    $new_submitter = preg_replace ( "/^,/", '', $new_submitter);
  }

  else if ( isset( $_SESSION['submitter_email'] ) )
    $new_submitter = $_SESSION['submitter_email'];

  if ( empty( $new_submitter ) )
  {
    // Must have, or can't proceed
    $_SESSION['message1'] = "*   Email address was missing";
  }

  if ( isset($_SESSION['add_owner']) && $_SESSION['add_owner'] == 1 )
  {
    $query  = "SELECT email FROM people " .
              "WHERE personID = {$_SESSION['id']} ";
    $result = mysqli_query( $link, $query )
              or die("Query failed : $query<br />" . mysqli_error($link));
    list($owner_email) = mysqli_fetch_array($result);

    // Let's double check that the owner email isn't already there
    $pos = strpos($new_submitter, $owner_email);
    if ($pos === false)       // have to use === to find the string in position 0 too
      $new_submitter .= ",$owner_email";
  }

  // Get experiment ID or default to 0
  $new_experimentID = $_SESSION['new_expID'] ?? 0;

  if ( $new_experimentID == 0 )
  {
    // Another must have
    $_SESSION['message2'] = "**  You must choose an experiment before proceeding";
  }

  // Now check for cells (at least one should be selected)
  $new_cell_array = $_SESSION['new_cells'] ?? array();

  if ( count( $new_cell_array ) < 1 )
  {
    $_SESSION['message3'] = "*** You must select at least one cell";
  }

  if ( isset( $_SESSION['message1'] ) || 
       isset( $_SESSION['message2'] ) ||
       isset( $_SESSION['message3'] ) )
  {
    header("Location: queue_setup_1.php");
    exit();
  }

  // Ok, input parameters are here, so we can proceed
  unset( $_SESSION['new_submitter'] );
  unset( $_SESSION['add_owner'] );
  unset( $_SESSION['new_expID'] );
  unset( $_SESSION['new_cells'] );

  $_SESSION['submitter_email'] = $new_submitter;
  $_SESSION['experimentID']    = $new_experimentID;

  // Set up a temporary data structure
  unset( $_SESSION['cells'] );
  $cells = array();
  foreach ( $new_cell_array as $rawDataID => $filename )
  {
    $cells[$rawDataID] = array();
    $cells[$rawDataID]['path']         = dirname ( $filename );
    $cells[$rawDataID]['filename']     = basename( $filename );
    $cells[$rawDataID]['editedDataID'] = 0;
    $cells[$rawDataID]['editFilename'] = '';
//    $cells[$rawDataID]['modelID']      = 0;
    $cells[$rawDataID]['noiseIDs']     = array();
  }
  $cell['experimentID'] = $new_experimentID;

  $_SESSION['cells'] = $cells;
}

// Build information from current page
function get_setup_2( $link )
{
  $no_posts=true;

  if ( isset( $_POST['editedDataID'] ) )
  {
    $no_posts=false;
    foreach ( $_POST['editedDataID'] as $rawDataID => $editedDataID )
    {
      $_SESSION['cells'][$rawDataID]['editedDataID'] = $editedDataID;

      // Get other things we need too
      $query  = "SELECT e.filename, e.data, r.experimentID FROM editedData e JOIN rawData r on r.rawDataID = e.rawDataID " .
                "LEFT OUTER JOIN experimentPerson ep on ep.experimentID = r.experimentID  ".
                "LEFT OUTER JOIN experiment ex on r.experimentID = ex.experimentID ".
                "LEFT OUTER JOIN projectPerson pp on pp.projectID = ex.projectID " .
                "WHERE e.editedDataID = ? and (ep.personID = ? or pp.personID = ?) ";
      $stmt = mysqli_prepare( $link, $query );
      $stmt->bind_param( 'iii', $editedDataID, $_SESSION['id'], $_SESSION['id'] );
      $stmt->execute() or die( "Query failed : $query<br />\n" . $stmt->error );
      $result = $stmt->get_result() or die( "Query failed : $query<br />\n" . $stmt->error );
      list( $editFilename, $editXML, $expID ) = mysqli_fetch_array( $result );
      $stmt->close();
      $result->close();
      $_SESSION['cells'][$rawDataID]['editFilename'] = $editFilename;
      $_SESSION['cells'][$rawDataID]['experimentID']      = $expID;
      getOtherEditInfo( $rawDataID, $editXML );
    }
  }

  if ( isset( $_POST['noiseIDs'] ) )
  {
    $no_posts=false;
    foreach ( $_POST['noiseIDs'] as $rawDataID => $noiseIDs )
    {
      $_SESSION['cells'][$rawDataID]['noiseIDs'] = array();

      // Check if user had the "Select noise..." selected
      if ( ! in_array( 'null', $noiseIDs ) )
         $_SESSION['cells'][$rawDataID]['noiseIDs'] = $noiseIDs;   // each of these is an array
    }
  }
  if ( $no_posts )
  {
    if ( isset( $_SESSION['edit_select_type'] ) )
    {
      if ( $_SESSION['edit_select_type'] == 0 )
      { // Auto-Edit-Select:  get latest edits and noises
        get_latest_edits( $link );
      }
    }
  }
}

// Get edit profiles
function get_editedData( $link, $rawDataID, $editedDataID = 0 )
{
  // language=MariaDB
  $query  = "SELECT e.editedDataID, e.label, e.filename " .
            "FROM editedData e JOIN rawData r on r.rawDataID = e.rawDataID " .
            "LEFT OUTER JOIN experimentPerson ep on ep.experimentID = r.experimentID  ".
            "LEFT OUTER JOIN experiment ex on r.experimentID = ex.experimentID ".
            "LEFT OUTER JOIN projectPerson pp on pp.projectID = ex.projectID " .
            "WHERE e.rawDataID = ? and (ep.personID = ? or pp.personID = ?) ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'iii', $rawDataID, $_SESSION['id'], $_SESSION['id'] );
  $stmt->execute() or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result() or die( "Query failed : $query<br />\n" . $stmt->error );


  $profile    = "<select name='editedDataID[$rawDataID]'" .
                "  onchange='this.form.submit();' size='3'>\n" .
                "  <option value='null'>Select edit profile...</option>\n";
  while ( list( $eID, $label, $fn ) = mysqli_fetch_array( $result ) )
  {
    $parts    = explode( ".", $fn ); // runID, editID, runType, c,c,w, xml
    $edit_txt  = $parts[1];
    $selected = ( $editedDataID == $eID ) ? " selected='selected'" : "";
    $profile .= "  <option value='$eID'$selected>$label [$edit_txt]</option>\n";
  }
  $result->close();
  $stmt->close();
  $profile   .= "</select>\n";

  return( $profile );
}

// Get the noise files
function get_noise( $link, $rawDataID, $editedDataID, $noiseIDs )
{
  $noise  = "<select name='noiseIDs[$rawDataID][]' multiple='multiple'" .
            "  onchange='this.form.submit();' size='8'>\n" .
            "  <option value='null'>Select noise ...</option>\n";
  // language=MariaDB
  $query  = "SELECT n.noiseID, n.modelID, n.noiseType, n.timeEntered " .
            "FROM noise n " .
            "join editedData e on e.editedDataID = n.editedDataID JOIN rawData r on r.rawDataID = e.rawDataID " .
            "LEFT OUTER JOIN experimentPerson ep on ep.experimentID = r.experimentID  ".
            "LEFT OUTER JOIN experiment ex on r.experimentID = ex.experimentID ".
            "LEFT OUTER JOIN projectPerson pp on pp.projectID = ex.projectID " .
            "WHERE e.editedDataID = ? and (ep.personID = ? or pp.personID = ?) " .
            "ORDER BY n.timeEntered DESC ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'iii', $editedDataID, $_SESSION['id'], $_SESSION['id'] );
  $stmt->execute() or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result() or die( "Query failed : $query<br />\n" . $stmt->error );


  while ( list( $nID, $modelID, $noiseType, $time ) = mysqli_fetch_array( $result ) )
  {
    $selected = ( in_array( $nID, $noiseIDs ) ) ? " selected='selected'" : "";
    $noise .= "  <option value='$nID'$selected>[$nID] $noiseType - $time</option>\n";
  }
  $result->close();
  $stmt->close();

  $noise   .= "</select>\n";

  return( $noise );
}

// Function to get information from the XML to check meniscus value later
function getOtherEditInfo( $rawDataID, $xml )
{
  $parser = new XMLReader();
  $parser->xml( $xml );

  while( $parser->read() )
  {
     $type = $parser->nodeType;

     if ( $type == XMLReader::ELEMENT )
     {
        $name = $parser->name;
  
        if ( $name == "meniscus" )
        {
          $parser->moveToAttribute( 'radius' );
          $_SESSION['cells'][$rawDataID]['editMeniscus'] = $parser->value;
        }

        else if ( $name == 'data_range' )
        {
          $parser->moveToAttribute( 'left' );
          $_SESSION['cells'][$rawDataID]['dataLeft'] = $parser->value;

          $parser->moveToAttribute( 'right' );
          $_SESSION['cells'][$rawDataID]['dataRight'] = $parser->value;
        }
     }
  }

  $parser->close();
}

// Get latest edit and noises information for all cells
function get_latest_edits( $link )
{
  global $is_cli;
  global $cli_errors;

  $_SESSION['request'] = array();
  $count = 0;
  // language=MariaDB
  $query  = "SELECT e.editedDataID, e.label, e.filename, e.data, " .
      " e.lastUpdated, r.experimentID " .
      "FROM editedData e JOIN rawData r on r.rawDataID = e.rawDataID " .
      "LEFT OUTER JOIN experimentPerson ep on ep.experimentID = r.experimentID " .
      "LEFT OUTER JOIN experiment ex on ex.experimentID = r.experimentID " .
      "LEFT OUTER JOIN projectPerson pp on pp.projectID = ex.projectID " .
      "WHERE e.rawDataID = ? and (ep.personID = ? or pp.personID = ?) " .
      "ORDER BY e.label, e.lastUpdated DESC";
  $stmt = $link->prepare( $query );
  foreach( $_SESSION['cells'] as $rawDataID => $cell )
  {

    $stmt->bind_param( 'iii', $rawDataID, $_SESSION['id'], $_SESSION['id'] );
    $stmt->execute() or die( "Query failed : $query<br />\n" . $stmt->error );
    $result = $stmt->get_result()
            or die("Query failed : $query<br />\n" . $stmt->error);


    list( $editedDataID, $label, $filename, $editXML, $expID ) = mysqli_fetch_array( $result );
    $result->close();
    getOtherEditInfo( $rawDataID, $editXML );
    if ( isset( $is_cli ) && $is_cli ) {
        if ( !strlen( $editedDataID ) ) {
            $errstr = "ERROR: " . __FILE__ . " edited data missing\n";
            echo "$errstr\n";
            $cli_errors[] = $errstr;
            return;
        }
    }


    $noiseIDs = array();
    $query2  = "SELECT noiseID, noiseType, timeEntered " .
              "FROM noise " .
              "WHERE editedDataID = ? " .
              "ORDER BY timeEntered DESC ";
    $stmt2 = $link->prepare( $query2 );
    $stmt2->bind_param( 'i', $editedDataID );
    $stmt2->execute() or die( "Query failed : $query2<br />\n" . $stmt2->error );
    $result2 = $stmt2->get_result() or die( "Query failed : $query2<br />\n" . $stmt2->error );


    $knoise = 0;
    $prtype = "";
    $prtime = 0;
    while ( list( $noiseID, $noiseType, $time ) = mysqli_fetch_array( $result2 ) )
    {
      if ( $knoise == 0 )
      {
        $noiseIDs[$knoise] = $noiseID;
        $prtype = $noiseType;
        $prtime = (int)$time;
        $knoise++;
      }
      else if ( $knoise == 1 )
      {
        if ( $prtype == $noiseType )    break;
        if ( ( (int)$time - $prtime ) > 2 )  break;
        $noiseIDs[$knoise] = $noiseID;
        $knoise++;
        break;
      }
    }
    $result2->close();
    $stmt2->close();

    $cell = $_SESSION['cells'][$rawDataID];
    $_SESSION['request'][$count]['rawDataID']    = $rawDataID;
    $_SESSION['request'][$count]['experimentID'] = $cell['experimentID'] ?? $expID;
    $_SESSION['request'][$count]['path']         = $cell['path'];
    $_SESSION['request'][$count]['filename']     = $cell['filename'];
    $_SESSION['request'][$count]['editedDataID'] = $editedDataID;
    $_SESSION['request'][$count]['editFilename'] = $filename;
    $_SESSION['request'][$count]['editMeniscus'] = $cell['editMeniscus'];
    $_SESSION['request'][$count]['dataLeft']     = $cell['dataLeft'];
    $_SESSION['request'][$count]['dataRight']    = $cell['dataRight'];
    $_SESSION['request'][$count]['noiseIDs']     = $noiseIDs;
    $_SESSION['cells'][$rawDataID]['editedDataID']  = $editedDataID;
    $_SESSION['cells'][$rawDataID]['editFilename']  = $filename;
    $_SESSION['cells'][$rawDataID]['noiseIDs']      = $noiseIDs;
    $count++;
  }
  $stmt->close();
}
