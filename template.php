<?php
/*
 * template.php
 *
 * A starting point for a new page
 *
 */
include_once 'checkinstance.php';

if ( ($_SESSION['userlevel'] != 3) &&
     ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) )    // super, admin and super admin only
{
  header('Location: index.php');
  exit();
} 

include 'config.php';
include 'db.php';

// Start displaying page
$page_title = "Template";
$js = 'js/template.js,js/sorttable.js';
$css = 'css/template.css';
include 'header.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Template</h1>
  <!-- Place page content here -->

</div>

<?php
include 'footer.php';
exit();
?>
