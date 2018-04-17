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

$imageType = $_GET['type'];

include 'db.php';

if ( $imageType == 'buffer' )
{
  do_getBuffer( $link );
  exit();
}

else if ( $imageType == 'analyte' )
{
  do_getAnalyte( $link );
  exit();
}

else if ( $imageType == 'solution' )
{
  do_getSolution( $link );
  exit();
}

else
  display_error( "Unsupported image link type." );

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

// Function to return some dynamic text to jQuery about buffers
function do_getBuffer( $link )
{
  $ID = $_SESSION['id'];

  $query  = "SELECT b.bufferID, description " .
            "FROM buffer b, bufferPerson p " .
            "WHERE p.personID = $ID " .
            "AND b.bufferID = p.bufferID " .
            "ORDER BY description ";
  $result = mysqli_query( $link, $query )
            or die("Query failed : $query<br />" . mysqli_error($link));

  if ( mysqli_num_rows($result) == 0 )
  {
    // Nothing to link to
    echo "There are no buffers to link to.";
    return;
  }

  $text = "<select name='bufferID' size='1'>\n";
  while ( list( $bufferID, $description ) = mysqli_fetch_array( $result ) )
    $text .= "  <option value='$bufferID'>$description</option>\n";

  $text .= "</select>\n";

  echo "Current buffers:<br />\n";
  echo $text;
}

// Function to return some dynamic text to jQuery about analytes
function do_getAnalyte( $link )
{
  $ID = $_SESSION['id'];

  $query  = "SELECT a.analyteID, description " .
            "FROM analyte a, analytePerson p " .
            "WHERE p.personID = $ID " .
            "AND a.analyteID = p.analyteID " .
            "ORDER BY description ";
  $result = mysqli_query( $link, $query )
            or die("Query failed : $query<br />" . mysqli_error($link));

  if ( mysqli_num_rows($result) == 0 )
  {
    // Nothing to link to
    echo "There are no analytes to link to.";
    return;
  }

  $text = "<select name='analyteID' size='1'>\n";
  while ( list( $analyteID, $description ) = mysqli_fetch_array( $result ) )
    $text .= "  <option value='$analyteID'>$description</option>\n";

  $text .= "</select>\n";

  echo "Current analytes:<br />\n";
  echo $text;
}

// Function to return some dynamic text to jQuery about solutions
function do_getSolution( $link )
{
  $ID = $_SESSION['id'];

  $query  = "SELECT s.solutionID, description " .
            "FROM solution s, solutionPerson p " .
            "WHERE p.personID = $ID " .
            "AND s.solutionID = p.solutionID " .
            "ORDER BY description ";
  $result = mysqli_query( $link, $query )
            or die("Query failed : $query<br />" . mysqli_error($link));

  if ( mysqli_num_rows($result) == 0 )
  {
    // Nothing to link to
    echo "There are no solutions to link to.";
    return;
  }

  $text = "<select name='solutionID' size='1'>\n";
  while ( list( $solutionID, $description ) = mysqli_fetch_array( $result ) )
    $text .= "  <option value='$solutionID'>$description</option>\n";

  $text .= "</select>\n";

  echo "Current solutions:<br />\n";
  echo $text;
}

?>
