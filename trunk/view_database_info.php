<?php
/*
 * view_database_info.php
 *
 * View the database login info for userlevel >= 2 users. 
 *
 */
include_once 'checkinstance.php';

if ( ($_SESSION['userlevel'] < 2) )
{
  header('Location: index.php');
  exit();
} 

include 'config.php';
include 'db.php';

// Start displaying page
$page_title = "Database Login Info";
$css = 'css/view_database_info.css';
include 'header.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Database Login Info</h1>
  <!-- Place page content here -->

<?php
$parts = explode( "_", $database_name );
$description = ucwords( $parts[ 1 ] . " Database" );

echo <<<HTML

<div id='main'>

<h3>Database Login Info</h3>

<p>In order to configure your UltraScan III application to work with this
database, please use the information printed below. This information
should be entered into the database configuration dialog in UltraScan III,
which can be opened by selecting:</p>

<p>&ldquo;Edit:Preferences:Database Preferences:Change.&rdquo;</p>

<p>Then click on &ldquo;Reset&rdquo; and enter the following information (use
   copy/paste, but don&rsquo;t add any leading or trailing blanks):</p>

<table cellpadding='4' cellspacing='0'>
  <tr><th>Database Description:</th>
      <td>You are free to put what you like here</td></tr>

  <tr><th>User Name:</th>
      <td>$secure_user</td></tr>

  <tr><th>Password:</th>
      <td>$secure_pw</td></tr>

  <tr><th>Database Name:</th>
      <td>$dbname</td></tr>

  <tr><th>Host Address</th>
      <td>uslims3.uthscsa.edu</td></tr>

  <tr><th>Investigator Email</th>
      <td>{$_SESSION['email']}</td></tr>

  <tr><th>Investigator Password</th>
      <td>The password you logged in here with</td></tr>

</table>

</div>

</div>

HTML;

include 'footer.php';
?>
