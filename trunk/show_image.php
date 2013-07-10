<?php
include 'checkinstance.php';

// Are we authorized to view this page?
if ( !isset($_SESSION['id']) )
{
  do_notauthorized();
  exit();
}

include 'db.php';

if ( isset( $_GET['ID'] ) )
{
  $imageID = $_GET['ID'];

  do_getImage( $imageID );
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
<address>Apache/2.2.3 (CentOS) Server at uslims3.uthscsa.edu Port 80</address>
</body></html>

HTML;
}

// Function to display a file
function do_getImage( $imageID )
{

  $query  = "SELECT filename, gelPicture " .
            "FROM image " .
            "WHERE imageID = '$imageID' ";
  $result = mysql_query( $query )
            or die("Query failed : $query<br />" . mysql_error());

  // Get the image
  $row      = mysql_fetch_array( $result );
  $data     = $row['gelPicture'];
  $filename = $row['filename'];
  $csize    = mb_strlen( $data, '8bit' );

  // Check to see if there was anything
  if ( ! $data )
    display_error( 'The requested record is empty.' );

  // Figure out the mime-type based on the extension
  $parts = explode( '.', $filename );
  $ext   = array_pop( $parts ); 

  switch ( $ext )
  {
    case 'jpg'  :
    case 'jpeg' :
    case 'JPG'  :
    case 'JPEG' :
      $mime_type = 'image/jpeg';
      break;

    case 'png'  :
    case 'PNG'  :
      $mime_type = 'image/png';
      break;

    default     :
      $mime_type = 'image';
      break;
  }

/*
  // Create a new file in data/ and add a .pdf extention
  $file     = tempnam( getcwd() . 'data', 'temp');
  $filename = basename( $file );
  $file     = "data/" . $filename . ".pdf";

  // Write the pdf file
  $r = fopen( $file, "wb+" );
  fwrite( $r, $data );
  fclose( $r );

  // Send it to the user
  header( "Location: $file" );
*/

  // This ought to work, but is sporadic. Some people download ok, others
  // get blank pages
///*
  // Send it to the user without writing it out as a file
  header( "Pragma: public" ); // required
  header( "Expires: 0" );
  header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
  header( "Cache-Control: private", false ); // required for certain browsers
  header( "Content-type: $mime_type" );
  header( "Content-disposition: attachment; filename=$filename" );
  header( "Content-Transfer-Encoding: binary" );
  header( "Content-Length: " . $csize );
  echo $data;
//*/
}

// Function to display an error of one sort or another and exit
function display_error( $error_text )
{
  echo <<<HTML
<html>
<head>
  <title>Graduate Student Applications Database - display file error</title>
  <meta name="verify-v1" content="+TIfXSnY08mlIGLtDJVkQxTV4kDYMoWu2GLfWLI7VBE=" />
  <link rel="stylesheet" type="text/css" href="css/main.css" />
</head>

<body>

  <p>$error_text</p>
  <p>[<a href=login_success.php>Return to the Main Menu</a>]</p>

</body></html>
HTML;

exit();
}

?>
