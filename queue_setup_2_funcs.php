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

  // Get experiment ID
  $new_experimentID = 0;
  if ( isset( $_SESSION['new_expID'] ) )
  {
    $new_experimentID = $_SESSION['new_expID'];
  }

  if ( $new_experimentID == 0 )
  {
    // Another must have
    $_SESSION['message2'] = "**  You must choose an experiment before proceeding";
  }

  // Now check for cells (at least one should be selected)
  $new_cell_array = array();
  if ( isset( $_SESSION['new_cells'] ) )
  {
    $new_cell_array = $_SESSION['new_cells'];
  }

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
      $query  = "SELECT filename, data FROM editedData " .
                "WHERE editedDataID = $editedDataID ";
      $result = mysqli_query( $link, $query )
              or die("Query failed : $query<br />\n" . mysqli_error($link));
      list( $editFilename, $editXML ) = mysqli_fetch_array( $result );
      $_SESSION['cells'][$rawDataID]['editFilename'] = $editFilename;
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
  $query  = "SELECT editedDataID, label, filename " .
            "FROM editedData " .
            "WHERE rawDataID = $rawDataID ";
  $result = mysqli_query( $link, $query )
          or die("Query failed : $query<br />\n" . mysqli_error($link));

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

  $profile   .= "</select>\n";

  return( $profile );
}

// Get the noise files
function get_noise( $link, $rawDataID, $editedDataID, $noiseIDs )
{
  $noise  = "<select name='noiseIDs[$rawDataID][]' multiple='multiple'" .
            "  onchange='this.form.submit();' size='8'>\n" .
            "  <option value='null'>Select noise ...</option>\n";

  $query  = "SELECT noiseID, modelID, noiseType, timeEntered " .
            "FROM noise " .
            "WHERE editedDataID = $editedDataID " .
            "ORDER BY timeEntered DESC ";

  $result = mysqli_query( $link, $query )
          or die("Query failed : $query<br />\n" . mysqli_error($link));

  while ( list( $nID, $modelID, $noiseType, $time ) = mysqli_fetch_array( $result ) )
  {
    $selected = ( in_array( $nID, $noiseIDs ) ) ? " selected='selected'" : "";
    $noise .= "  <option value='$nID'$selected>[$nID] $noiseType - $time</option>\n";
  }

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
  $_SESSION['request'] = array();
  $count = 0;
  foreach( $_SESSION['cells'] as $rawDataID => $cell )
  {
    $query  = "SELECT editedDataID, label, filename, data, " .
              " lastUpdated " .
              "FROM editedData " .
              "WHERE rawDataID = $rawDataID " .
              "ORDER BY label, lastUpdated DESC";
    $result = mysqli_query( $link, $query )
            or die("Query failed : $query<br />\n" . mysqli_error($link));

    list( $editedDataID, $label, $filename, $editXML ) = mysqli_fetch_array( $result );
    getOtherEditInfo( $rawDataID, $editXML );

    $noiseIDs = array();
    $query  = "SELECT noiseID, noiseType, timeEntered " .
              "FROM noise " .
              "WHERE editedDataID = $editedDataID " .
              "ORDER BY timeEntered DESC ";

    $result = mysqli_query( $link, $query )
            or die("Query failed : $query<br />\n" . mysqli_error($link));

    $knoise = 0;
    $prtype = "";
    $prtime = 0;
    while ( list( $noiseID, $noiseType, $time ) = mysqli_fetch_array( $result ) )
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

    $cell = $_SESSION['cells'][$rawDataID];
    $_SESSION['request'][$count]['rawDataID']    = $rawDataID;
    $_SESSION['request'][$count]['experimentID'] = $cell['experimentID'];
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
}
