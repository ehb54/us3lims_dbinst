<?php
session_start();

// Are we authorized to view this page?
if ( !isset($_SESSION['id']) )
{
  do_notauthorized();
  exit();
}

include 'config.php';
include 'db.php';

if ( isset( $_GET['type']   ) && 
     isset( $_GET['expID']  ) &&
     isset( $_GET['triple'] ) )
{
  switch ( $_GET['type'] )
  {
    case 'solution' :
    case 'analyte'  :
    case 'buffer'   :
      do_getInfo( $_GET['type'], $_GET['expID'], $_GET['triple'] );
      break;

    default :
      display_error( "Unauthorized request." );
      break;
  }
}

else
  display_error( "Unauthorized request." );

exit();

// Display not authorized text
function do_notauthorized()
{
  echo <<<HTML
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
<meta name="verify-v1" content="+TIfXSnY08mlIGLtDJVkQxTV4kDYMoWu2GLfWLI7VBE=" />
</head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
<hr>
<address>Apache/2.2.3 (CentOS) Server at uslims3.uthscsa.edu Port 443</address>
</body></html>

HTML;
}

// Function to display info about a solution, analyte, or buffer
function do_getInfo( $type, $experimentID, $triple )
{
  // Let's start with header information
  $header  = get_header_info( $type, $experimentID, $triple );
  $ptype   = ucwords( $type );

  // Now the content
  $content = get_document_content( $type, $experimentID, $triple );

  echo <<<HTML
<html>
<head>
  <title>US Lims Database - $ptype Detail</title>
  <link rel="stylesheet" type="text/css" href="css/main.css" />
  <link rel="stylesheet" type="text/css" href="css/reports.css" />
</head>

<body>

  $header
  $content

  <p><a href='javascript:window.close();'>Close Window</a></p>

</body></html>
HTML;

}

// Function to get document header content
function get_header_info( $type, $experimentID, $triple )
{
  // Let's start with header information
  $query  = "SELECT runID, runType " .
            "FROM experiment " .
            "WHERE experimentID = $experimentID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );
  list ( $runID, $runType ) = mysql_fetch_array( $result );
  list ( $cell, $channel, $wl ) = explode( "/", $triple );
  $radius      = $wl / 1000.0;    // If WA data

  // Create header information
  $triple = ( $runType == "WA" )
          ? "Cell $cell, Channel $channel, Radius $radius<br />\n"
          : "Cell $cell, Channel $channel, Wavelength $wl<br />\n";

  $ptype = ucwords( $type );
  $header = "<div>\n" .
            "<h1>$ptype Information</h1>\n" .
            "<h2>Run ID: $runID<br />\n" .
            $triple .
            "</div>\n";

  return $header;
}

// Function to get the solution information. Method varies depending on the type
function get_document_content( $type, $experimentID, $triple )
{
  // First we want to find the solutionID based on the experiment and triple
  $query  = "SELECT filename, solutionID AS sID " .
            "FROM rawData " .
            "WHERE experimentID = $experimentID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  $solutionID = -1;
  while ( list( $filename, $sID ) = mysql_fetch_array( $result ) )
  {
    $parts = explode( '.', $filename );
    $t     = $parts[2] . '/' . $parts[3] . '/' . $parts[4];
    if ( $t == $triple )
    {
       $solutionID = $sID;
       break;
    }
  }

  // Check to see if there was anything
  if ( $solutionID == -1 )
    $text = "<p>The solution associated with this experiment/triple could not be found</p>\n";

  else if ( $type == 'solution' )
    $text = get_solutionInfo( $solutionID );

  else if ( $type == 'analyte' )
    $text = get_analyteInfo( $solutionID );

  else if ( $type == 'buffer' )
    $text = get_bufferInfo( $solutionID );

  return $text;
}

// Function to get some information about the solution
function get_solutionInfo( $solutionID )
{
  $query  = "SELECT solutionGUID, description, commonVbar20, storageTemp, notes " .
            "FROM solution " .
            "WHERE solutionID = $solutionID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );
  list( $guid, $desc, $vbar, $temp, $notes ) = mysql_fetch_array( $result );

  $analyte_list = array();
  $query  = "SELECT description, amount " .
            "FROM solutionAnalyte, analyte " .
            "WHERE solutionAnalyte.solutionID = $solutionID " .
            "AND solutionAnalyte.analyteID = analyte.analyteID " .
            "ORDER BY description ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );
  while ( list( $analyte_desc, $am ) = mysql_fetch_array( $result ) )
    $analyte_list[$analyte_desc] = $am;

  $analyte_text = "<table cellspacing='0' cellpadding='3px'>\n" .
                  "  <tr><th>Analyte Description</th>\n" .
                  "      <th>Amount</th></tr>\n";
  foreach ( $analyte_list as $ad => $amount )
    $analyte_text .= "  <tr><td>$ad</td><td>$amount</td></tr>\n";
  $analyte_text .= "</table>\n";

  $buffer_list = array();
  $query  = "SELECT description " .
            "FROM solutionBuffer, buffer " .
            "WHERE solutionBuffer.solutionID = $solutionID " .
            "AND solutionBuffer.bufferID = buffer.bufferID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );
  list( $buffer_desc ) = mysql_fetch_array( $result );

  // Now display it
  $text = <<<HTML
  <table cellspacing='0' cellpadding='3' class='solution_info' >
    <tr><th>GUID:</th><td>$guid</td></tr>
    <tr><th>Description:</th><td>$desc</td></tr>
    <tr><th>Common VBar @ 20&deg; C:</th><td>$vbar</td></tr>
    <tr><th>Temperature:</th><td>$temp</td></tr>
    <tr><th>Notes:</th><td>$notes</td></tr>
    <tr><th>Buffer:</th><td>$buffer_desc</td></tr>
    <tr><th>Analytes:</th><td>$analyte_text</td></tr>
  </table>

HTML;

  return $text;
}

// Function to get some information about the analyte
function get_analyteInfo( $solutionID )
{
  // First get a list of the analytes in the solution
  $analyte_list = array();
  $query  = "SELECT sa.analyteID " .
            "FROM solutionAnalyte sa, analyte " .
            "WHERE solutionID = $solutionID " .
            "AND sa.analyteID = analyte.analyteID " .
            "ORDER BY description ";
  $result = mysql_query( $query )
           or die( "Query failed : $query<br />\n" . mysql_error() );
  while ( list( $analyteID ) = mysql_fetch_array( $result ) )
    $analyte_list[] = $analyteID;

  // Initialize some stuff
  $text = '';

  // Now for each analyte...
  $analyte_no = 1;
  foreach ( $analyte_list as $ID )
  {
    // ...get all the values
    $query  = "SELECT analyteGUID, type, sequence, vbar, description, " .
              "spectrum, molecularWeight, " .
              "doubleStranded, complement, _3prime, _5prime, " .
              "sodium, potassium, lithium, magnesium, calcium, " .
              "fname, lname " .
              "FROM analyte a, analytePerson ap, people p " .
              "WHERE a.analyteID = $ID " .
              "AND a.analyteID = ap.analyteID " .
              "AND ap.personID = p.personID ";
    $result = mysql_query( $query )
             or die( "Query failed : $query<br />\n" . mysql_error() );
    $row    = mysql_fetch_array( $result );

    foreach ( $row as $key => $value )
      $$key = $value;

    // ...and display them
    $sequence_text = nl2br( $sequence );
    $analyte_no_text = ( count( $analyte_list ) < 2 ) ? "" : "<h3>Analyte $analyte_no</h3>\n";
    $text .= <<<HTML
    $analyte_no_text
    <h4>Analyte Information</h4>
    <p>Description: $description</p>
    <table cellspacing='0' cellpadding='3' class='solution_info' >
      <tr><th>GUID:</th><td>$analyteGUID</td></tr>
      <tr><th>Type:</th><td>$type</td></tr>
      <tr><th>Sequence:</th><td>$sequence_text</td></tr>
      <tr><th>VBar:</th><td>$vbar</td></tr>
      <tr><th>Spectrum:</th><td>$spectrum</td></tr>
      <tr><th>Molecular Weight:</th><td>$molecularWeight</td></tr>
      <tr><th>Owner:</th><td>$lname, $fname</td></tr>
    </table>

HTML;

    // ...nucleotide info too
    $doubleStranded_text = ( $doubleStranded == 1 ) ? "yes" : "no";
    $complement_text     = ( $complement     == 1 ) ? "yes" : "no";
    $_3prime_text        = ( $_3prime        == 1 ) ? "yes" : "no";
    $_5prime_text        = ( $_5prime        == 1 ) ? "yes" : "no";

    $text .= <<<HTML
    <h4>Nucleotide Information</h4>
    <table cellspacing='0' cellpadding='3' class='solution_info' >
      <tr><th>Double Stranded?</th><td>$doubleStranded_text</td></tr>
      <tr><th>Complement?</th><td>$complement_text</td></tr>
      <tr><th>3 Prime?</th><td>$_3prime_text</td></tr>
      <tr><th>5 Prime?</th><td>$_5prime_text</td></tr>
      <tr><th>Sodium:</th><td>$sodium</td></tr>
      <tr><th>Potassium:</th><td>$potassium</td></tr>
      <tr><th>Lithium:</th><td>$lithium</td></tr>
      <tr><th>Magnesium:</th><td>$magnesium</td></tr>
      <tr><th>Calcium:</th><td>$calcium</td></tr>
    </table>

HTML;

  $analyte_no++;
  }

  return $text;
}

// Function to get some information about the buffer
function get_bufferInfo( $solutionID )
{
  // First get the buffer in the solution
  $query  = "SELECT bufferID " .
            "FROM solutionBuffer " .
            "WHERE solutionID = $solutionID ";
  $result = mysql_query( $query )
           or die( "Query failed : $query<br />\n" . mysql_error() );
  list( $bufferID ) = mysql_fetch_array( $result );

  // Initialize some stuff
  $text = '';

  // First information from the buffer as a whole
  $query  = "SELECT bufferGUID, description, compressibility, " .
            "pH, viscosity, density " .
            "FROM buffer " .
            "WHERE bufferID = $bufferID ";
  $result = mysql_query( $query )
           or die( "Query failed : $query<br />\n" . mysql_error() );
  $row    = mysql_fetch_array( $result );

  foreach ( $row as $key => $value )
    $$key = $value;

  // Display it
  $text .= <<<HTML
  <h4>Buffer Information</h4>
  <p>$description</p>
  <table cellspacing='0' cellpadding='3' class='solution_info' >
    <tr><th>GUID:</th><td>$bufferGUID</td></tr>
    <tr><th>Compressibility:</th><td>$compressibility</td></tr>
    <tr><th>pH:</th><td>$pH</td></tr>
    <tr><th>Viscosity:</th><td>$viscosity</td></tr>
    <tr><th>Density:</th><td>$density</td></tr>
  </table>

HTML;

  // Now the buffer components
  $query  = "SELECT units, description, viscosity, density, concentration " .
            "FROM bufferLink, bufferComponent " .
            "WHERE bufferID = $bufferID " .
            "AND bufferLink.bufferComponentID = bufferComponent.bufferComponentID " .
            "ORDER BY description ";
  $result = mysql_query( $query )
           or die( "Query failed : $query<br />\n" . mysql_error() );

  while ( $row = mysql_fetch_array( $result ) )
  {
    foreach ( $row as $key => $value )
      $$key = $value;

    // Display it
    $text .= <<<HTML
    <h4>Component Information ($description)</h4>
    <table cellspacing='0' cellpadding='3' class='solution_info' >
      <tr><th>Units:</th><td>$units</td></tr>
      <tr><th>Viscosity:</th><td>$viscosity</td></tr>
      <tr><th>Density:</th><td>$density</td></tr>
      <tr><th>Concentration:</th><td>$concentration</td></tr>
    </table>

HTML;
  }

  return $text;
}

// Function to display an error of one sort or another and exit
function display_error( $error_text )
{
  echo <<<HTML
<html>
<head>
  <title>US Lims Database - display file error</title>
  <meta name="verify-v1" content="+TIfXSnY08mlIGLtDJVkQxTV4kDYMoWu2GLfWLI7VBE=" />
  <link rel="stylesheet" type="text/css" href="css/main.css" />
</head>

<body>

  <p>$error_text</p>
  <p>[<a href='index.php'>Return to the Main Menu</a>]</p>

</body></html>
HTML;

exit();
}

?>
