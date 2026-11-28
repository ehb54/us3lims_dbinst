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
include 'lib/access.php';

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
  $reportID = viewable_document_report( $link, $documentID );
  if ( $reportID === NULL )
  {
    http_response_code( 403 );
    display_error( "You do not have access to that document." );
  }

  $content = get_document_content( $link, $documentID );

  if ( empty( $content ) )    // Then we must be echoing something directly
    return;

  $header  = get_header_info( $link, $documentID, $reportID );

  echo <<<HTML
<html>
<head>
  <title>US Lims Database - Report Detail</title>
  <link rel="stylesheet" type="text/css" href="css/reports.css?v=5855cc374ffa" />
  <script src="js/main.js" type="text/javascript"></script>
</head>

<body>

  <main class="document-view">
  $header
  $content
  <p><a href='#' class='onclick-window-close'>Close Window</a></p>
  </main>

</body></html>
HTML;

}

// Function to get document header content
function get_header_info( $link, $documentID, $reportID )
{
  // Let's start with header information
  $query  = "SELECT report.runID, triple, runType " .
            "FROM documentLink, reportTriple, report, experiment " .
            "WHERE reportDocumentID = ? " .
            "AND report.reportID = ? " .
            "AND documentLink.reportTripleID = reportTriple.reportTripleID " .
            "AND reportTriple.reportID = report.reportID " .
            "AND report.experimentID = experiment.experimentID ";
  $stmt = mysqli_prepare( $link, $query );
  mysqli_stmt_bind_param( $stmt, "ii", $documentID, $reportID );
  $stmt->execute() or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result() or die( "Query failed : $query<br />\n" . $stmt->error );
  list ( $runID, $tripleDesc, $runType ) = mysqli_fetch_array( $result );
  list ( $cell, $channel, $wl ) = explode( "/", $tripleDesc );
  $radius      = $wl / 1000.0;    // If WA data
  $result->close();
  $stmt->close();
  // Now the information we need from the document table
  $query  = "SELECT reportDocument.label, reportDocument.filename, editedData.filename, " .
            "documentType " .
            "FROM reportDocument, editedData " .
            "WHERE reportDocumentID = ? " .
            "AND reportDocument.editedDataID = editedData.editedDataID ";
  $stmt = mysqli_prepare( $link, $query );
  mysqli_stmt_bind_param( $stmt, "i", $documentID );
  $stmt->execute() or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result() or die( "Query failed : $query<br />\n" . $stmt->error );
  list( $label, $rfilename, $efilename, $doctype ) = mysqli_fetch_array( $result );
  list( $anal, $subanal, $doctype_text ) = explode( ":", $label );
  $result->close();
  $stmt->close();
  $parts = explode( ".", $efilename );
  $edit_profile = $parts[1];
  $triple = ( $runType == "WA" )
          ? "Cell $cell, Channel $channel, Radius $radius<br />\n"
          : "Cell $cell, Channel $channel, Wavelength $wl<br />\n";

  // Create header information
  $header = "<div class='document-heading'>\n" .
            "<h1>$anal</h1>\n" .
            "<h2>Run ID: $runID</h2>\n" .
            "<p>" .
            $triple .
            "Edited Dataset: $edit_profile</p>\n" .
            "<p><b>$subanal ($doctype_text)</b></p>\n" .
            "</div>\n";

  // html already has a header in it  
  if ( $doctype == 'html' || $doctype == 'svgz' )
    $header = "";

  return $header;
}

// Function to get the document content. Method varies depending on the type
function get_document_content( $link, $documentID )
{
  // First the document and the document type
  $query  = "SELECT documentType, filename, contents " .
            "FROM reportDocument " .
            "WHERE reportDocumentID = ? ";
  $stmt = mysqli_prepare( $link, $query );
  $stmt->bind_param( "i", $documentID );
  $stmt->execute() or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result() or die( "Query failed : $query<br />\n" . $stmt->error );

  $row = mysqli_fetch_array( $result );
  $result->close();
  $stmt->close();
  $doctype  = $row['documentType'];
  $filename = $row['filename'];
  $contents = $row['contents'];
  //$csize   = mb_strlen( $contents, '8bit' );

  // The same page sends the file itself, so the permission check covers it
  $file_url   = "report_detail.php?ID=" . (int) $documentID . "&amp;file=1";
  $file_types = array( 'dat' => 'text/plain',
                       'rpt' => 'text/plain',
                       'csv' => 'text/csv',
                       'png' => 'image/png' );

  // Check to see if there was anything
  $download = "";
  if ( ! $contents )
    $text = "<p>The requested record has no document</p>\n";

  else if ( isset( $_GET['file'] ) && isset( $file_types[ $doctype ] ) )
  {
    send_file( $contents, $filename, $file_types[ $doctype ],
               ( $doctype == 'png' ) ? 'inline' : 'attachment' );
    return "";
  }

  else if ( $doctype == 'html' )
  {
    // Stored reports are whole documents; show the body only, since CSP blocks their <style>.
    $body = preg_match( '~<body[^>]*>(.*)</body>~is', $contents, $m ) ? $m[1] : $contents;
    $body = preg_replace( '~<style\b[^>]*>.*?</style>~is', '', $body );
    $text = "<div class='stored-report'>\n" .
              $body .
            "</div>\n";
  }

  else if ( $doctype == 'dat' || 
            $doctype == 'rpt' ||
            $doctype == 'csv' )
  {
    $download = "<a href='$file_url' target=_blank>Download</a>";
    $text = "<div><pre>\n" .
              $contents .
            "</pre></div>\n";
  }

  else if ( $doctype == 'png' )
  {
    $download = "<a href='$file_url' target=_blank>Download</a>";
    $text = <<<HTML
    <div>
      <img src='$file_url' alt='UltraScan 3 png file' />
    </div>
HTML;
  }

  else if ( $doctype == 'svgz'  ||  $doctype == 'svg' )
  {
    send_file( $contents, $filename, 'image/svg+xml', 'attachment' );
    return "";
  }

  else
    $text = "<p>Unsupported document format</p>\n";

  if ( $doctype != 'html' && $doctype != 'svgz' )
  {
    $display_filename = htmlspecialchars( (string) $filename, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
    $text = "<p class='document-file'><b>Filename:</b> $display_filename $download</p>\n" . $text;
  }

  return $text;
}

// Function to send a document to the user without writing it out as a file
function send_file( $contents, $filename, $mime_type, $disposition )
{
  $safe_name = preg_replace( '/[^A-Za-z0-9._-]/', '_', $filename );

  session_write_close();   // So a long download does not hold up the user's other pages
  header( "Cache-Control: private, no-store" );
  header( "Content-Type: $mime_type" );
  header( "Content-Disposition: $disposition; filename=\"$safe_name\"" );
  header( "X-Content-Type-Options: nosniff" );
  echo $contents;
}

// Function to display an error of one sort or another and exit
function display_error( $error_text )
{
  echo <<<HTML
<html>
<head>
  <title>US Lims Database - display file error</title>
  <meta name="verify-v1" content="+TIfXSnY08mlIGLtDJVkQxTV4kDYMoWu2GLfWLI7VBE=" />
  <link rel="stylesheet" type="text/css" href="css/reports.css?v=5855cc374ffa" />
</head>

<body>

  <p>$error_text</p>
  <p>[<a href='index.php'>Return to the Main Menu</a>]</p>

</body></html>
HTML;

exit();
}

?>
