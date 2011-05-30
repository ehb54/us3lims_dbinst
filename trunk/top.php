<?php
/*
 * top.php
 *
 */

include 'config.php';
include 'global_menu.php';

if (!isset($page_title)) $page_title = '';

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

Released      : 8/1/2010

-->

<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title>$page_title -
         $org_name</title>
  <meta name="Author" content="$site_author" />
  <meta name="keywords" content="$site_keywords" />
  <meta name="description" content="$site_desc" />
  <meta name="robots" content="index, follow" />
  <meta name="verify-v1" content="+TIfXSnY08mlIGLtDJVkQxTV4kDYMoWu2GLfWLI7VBE=" />
  <link rel="shortcut icon" href="images/favicon.ico" />
  <link href="css/common.css" rel="stylesheet" type="text/css" />
  <script src="js/main.js" type="text/javascript"></script>

HTML;

if ( isset( $css ) )
{
  $css_files = explode(",", $css);
  foreach ($css_files as $css_file)
    echo "  <link rel='stylesheet' type='text/css' href='$css_file' />\n";
}

if ( isset( $js ) )
{
  $javascripts = explode(",", $js);
  foreach ($javascripts as $javascript)
    echo "  <script src='$javascript' type='text/javascript'></script>\n";
}

if ( ! isset( $onload ) )
  $onload = '';

echo<<<HTML

</head>

<body $onload>

<!-- begin header -->
<div id="header" style='text-align:center;'> 
   <img src='images/UltraScan3-LIMS-banner.png' alt='UltraScan3 banner' />
   <span style='font-size:20px;font-weight:bold;color:white;padding:0 1em;'>
    $org_name</span>
    $global_menu
</div>

<!-- Begin page content -->
<div id='page'>

HTML;
?>
