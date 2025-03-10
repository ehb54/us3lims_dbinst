<?php
include 'checkinstance.php';

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
  $personID  = $_GET['pID'] ?? $_SESSION['id'];

  $text = people_select( $link, 'people_select', $personID );

  echo $text;
  exit();
}

else if ( $infoType == 'r' )
{
  // Return some dynamic text about available runs
  $personID  = $_GET['pID'] ?? $_SESSION['id'];
  $currentID = $_GET['rID'] ?? -1;

  $text = run_select( $link, 'run_select', $currentID, $personID );

  echo $text;
  exit();
}

else if ( $infoType == 't' )
{
  // Return a dynamic list of associated triples
  $currentID = $_GET['rID'] ?? -1;

  $text = tripleList( $link, $currentID );

  echo $text;
  exit();
}

else if ( $infoType == 'c' )
{
  // Return a dynamic list of associated combinations
  $currentID = $_GET['rID'] ?? -1;

  $text = combo_info( $link, $currentID );

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
