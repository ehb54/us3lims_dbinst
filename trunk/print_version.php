<?php
/*
 * print_version.php
 *
 * A program to create a page suitable for printing
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

include 'config.php';

$page_title   = $_SESSION['print_title'];
$page_content = $_SESSION['print_text'];

echo<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<!--
Description   : Website designed and implemented by Dan Zollars 
                and Borries Demeler, 2010

Copyright     : Copyright (c), 2010
                Bioinformatics Core Facility
                Department of Biochemistry
                UTHSCSA
                All Rights Reserved

Website       : http://bioinformatics.uthscsa.edu

Version       : beta

Released      : 3/1/2010

-->
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title>$page_title -
         $org_name</title>
  <meta name="Author" content="$site_author" />
  <meta name="keywords" content="$site_keywords" />
  <meta name="description" content="$site_desc" />
  <meta name="robots" content="index, nofollow" />
  <link rel="shortcut icon" href="images/favicon.ico" />
  <link href="css/main_print.css" rel="stylesheet" type="text/css" />
  <link href="css/print_version.css" rel="stylesheet" type="text/css" />
  <script src="js/sorttable.js" type="text/javascript"></script>

HTML;

?>
</head>

<body>

<!-- begin header -->
<div id="header">
  <h3 style='text-align:left;'>UltraScan III LIMS Portal</h3>
</div>

<?php

echo <<<HTML
  <!-- Begin page content -->
<div id='content_print'>
  <h2>$page_title</h2>

  <div>$page_content</div>
</div>

HTML;

  $today  = date("Y\-m\-d");
echo<<<HTML
  <!-- end content -->
  <div style="clear: both;"></div>

<!-- end page -->
<div id="footer">
<div id="info">
  Date Printed: $today <br />
  Copyright &copy; $copyright_date<br />
  Bioinformatics<br />
  Core Facility,<br />
  UTHSCSA
</div>
</div>

</body>
</html>

HTML;
?>
