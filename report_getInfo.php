<?php
session_start();

// Are we authorized to view this page?
if ( !isset($_SESSION['id']) )
{
  do_notauthorized();
  exit();
}

if ( !isset($_GET['type']) )
{
  display_error( "Unauthorized request." );
  exit();
}

$infoType = $_GET['type'];

include 'db.php';
include 'lib/reports.php';

if ( $infoType == 'p' )
{
  // Return some dynamic text about people
  //  who have granted user permission to view reports
  $personID  = ( isset( $_GET['pID'] ) ) ? $_GET['pID'] : $_SESSION['id'];

  $text = people_select( 'people_select', $personID );

  echo $text;
  exit();
}

else if ( $infoType == 'r' )
{
  // Return some dynamic text about available runs
  $personID  = ( isset( $_GET['pID'] ) ) ? $_GET['pID'] : $_SESSION['id'];
  $currentID = ( isset( $_GET['rID'] ) ) ? $_GET['rID'] : -1;

  $text = run_select( 'run_select', $currentID, $personID );

  echo $text;
  exit();
}

else
  display_error( "Unsupported info type." );

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
?>
