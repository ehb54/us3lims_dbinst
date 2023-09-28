<?php
include 'checkinstance.php';

// Are we authorized to view this page?
if ( !isset($_SESSION['id']) )
{
  do_notauthorized();
  exit();
}

include 'config.php';
include 'db.php';

if ( isset( $_GET['ID'] ) )
{
  $documentID = $_GET['ID'];

  do_getDoc( $link, $documentID );
  exit();
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

// Function to display a file
function do_getDoc( $link, $documentID )
{
  // Let's start with header information
  $header  = get_header_info( $link, $documentID );

  // Now the content
  $content = get_document_content( $link, $documentID );

  if ( empty( $content ) )    // Then we must be echoing something directly
    return;

  echo <<<HTML
<html>
<head>
  <title>US Lims Database - Report Detail</title>
  <link rel="stylesheet" type="text/css" href="css/main.css" />
</head>

<body>

  $header
  $content
  <p><a href='javascript:window.close();'>Close Window</a></p>

</body></html>
HTML;

}

// Function to get document header content
function get_header_info( $link, $documentID )
{
  // Let's start with header information
  $query  = "SELECT report.runID, triple, runType " .
            "FROM documentLink, reportTriple, report, experiment " .
            "WHERE reportDocumentID = $documentID " .
            "AND documentLink.reportTripleID = reportTriple.reportTripleID " .
            "AND reportTriple.reportID = report.reportID " .
            "AND report.experimentID = experiment.experimentID ";
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );
  list ( $runID, $tripleDesc, $runType ) = mysqli_fetch_array( $result );
  list ( $cell, $channel, $wl ) = explode( "/", $tripleDesc );
  $radius      = $wl / 1000.0;    // If WA data

  // Now the information we need from the document table
  $query  = "SELECT reportDocument.label, reportDocument.filename, editedData.filename, " .
            "documentType " .
            "FROM reportDocument, editedData " .
            "WHERE reportDocumentID = $documentID " .
            "AND reportDocument.editedDataID = editedData.editedDataID ";
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );
  list( $label, $rfilename, $efilename, $doctype ) = mysqli_fetch_array( $result );
  list( $anal, $subanal, $doctype_text ) = explode( ":", $label );
  $parts = explode( ".", $efilename );
  $edit_profile = $parts[1];
  $triple = ( $runType == "WA" )
          ? "Cell $cell, Channel $channel, Radius $radius<br />\n"
          : "Cell $cell, Channel $channel, Wavelength $wl<br />\n";

  // Create header information
  $header = "<div>\n" .
            "<h1>$anal</h1>\n" .
            "<h2>Run ID: $runID<br />\n" .
            $triple .
            "Edited Dataset: $edit_profile</h2>\n" .
            "<p><b>$subanal ($doctype_text)</b><br />\n" .
            "<b>Filename:</b>$rfilename<br />\n" .
            "</div>\n";

  // html already has a header in it  
  if ( $doctype == 'html' || $doctype == 'svgz' )
    $header = "";

  return $header;
}

// Function to get the document content. Method varies depending on the type
function get_document_content( $link, $documentID )
{
  global $full_path, $data_dir;

  // First the document and the document type
  $query  = "SELECT documentType, filename, contents " .
            "FROM reportDocument " .
            "WHERE reportDocumentID = $documentID ";
  $result = mysqli_query( $link, $query )
            or die( "Query failed : $query<br />\n" . mysqli_error($link) );
  $row = mysqli_fetch_array( $result );
  $doctype  = $row['documentType'];
  $filename = $row['filename'];
  $contents = $row['contents'];
  //$csize   = mb_strlen( $contents, '8bit' );

  // Check to see if there was anything
  if ( ! $contents )
    $text = "<p>The requested record has no document</p>\n";

  else if ( $doctype == 'html' )
  {
    $text = "<div>\n" .
              $contents .
            "</div>\n";
  }

  else if ( $doctype == 'dat' || 
            $doctype == 'rpt' ||
            $doctype == 'csv' )
  {
    $file = $data_dir . $filename;
    $r    = fopen( $file, "w+" );
    fwrite( $r, $contents );
    fclose( $r );
    // Figure out the apache file name, assuming a subdirectory
    $apache_file = substr( $data_dir, strlen( $full_path ) ) . $filename;

    $text = "<div><a href='$apache_file' target=_blank>Download</a></div>";
    $text .= "<div><pre>\n" .
              $contents .
            "</pre></div>\n";
  }

  else if ( $doctype == 'png' )
  {
    // Create a new file in the data directory using the known filename
    $file = $data_dir . $filename;
    $r    = fopen( $file, "wb+" );
    fwrite( $r, $contents );
    fclose( $r );

    // Figure out the apache file name, assuming a subdirectory
    $apache_file = substr( $data_dir, strlen( $full_path ) ) . $filename;

    // Now create some html that includes the file
    $text = <<<HTML
    <div> 
      <a href='$apache_file' target=_blank>Download</a>
    </div>
    <div>
      <img src='$apache_file' alt='UltraScan 3 png file' />
    </div>
HTML;
  }

  else if ( $doctype == 'svgz'  ||  $doctype == 'svg' )
  {
    // Code to echo the blob out to the user directly.
  
    // Send it to the user without writing it out as a file
    header( "Pragma: public" ); // required
    header( "Expires: 0" );
    header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
    header( "Cache-Control: private", false ); // required for certain browsers
    header( "Content-Type: image/svg+xml" );
    header( "Content-Disposition: attachment; filename=$filename" );
    header( "Content-Transfer-Encoding: binary" );
    // header( "Content-Length: " . $csize );
    echo $contents;
    return "";
  }

  else
    $text = "<p>Unsupported document format</p>\n";

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
