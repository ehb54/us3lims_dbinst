<?php
/*
 * template.php
 *
 * A starting point for a new page
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( $_SESSION['userlevel'] != 6 )    // Admin only
{
  header('Location: index.php');
  exit();
} 

include 'config.php';

$page_title = "Template";
$css = 'css/template.css';
include 'top.php';
include 'links.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Template</h1>
  <!-- Place page content here -->

</div>

<?php
include 'bottom.php';
?>
