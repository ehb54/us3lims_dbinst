<?php
/*
 * top.php
 *
 */

include 'config.php';

if (!isset($page_title)) $page_title = '';
if (!isset($page_css  )) $page_css   = 'page';

echo<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<!--
Description   : Website designed and implemented by Dan Zollars 
                and Borries Demeler, 2010

Copyright     : Copyright (c), 2011
                Bioinformatics Core Facility
                Department of Biochemistry
                UTHSCSA
                All Rights Reserved

Website       : http://bioinformatics.uthscsa.edu

Version       : beta

Released      : 6/30/2011

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
   <table class='noborder'>
   <tr><td><img src='images/USLIMS3-banner.png' alt='USLims 3 banner' /></td>
       <td style='vertical-align:middle;width:400px;'>

       <div id="cse-search-form">Loading</div>
       <script src="http://www.google.com/jsapi" type="text/javascript"></script>
       <script type="text/javascript"> 
         google.load('search', '1', {language : 'en', style : google.loader.themes.MINIMALIST});
         google.setOnLoadCallback(function() {
           var customSearchControl = new google.search.CustomSearchControl('007201445830912588415:jg05a0rix7y');
           customSearchControl.setResultSetSize(google.search.Search.FILTERED_CSE_RESULTSET);
           var options = new google.search.DrawOptions();
           options.enableSearchboxOnly("http://$org_site/search.php");    
           customSearchControl.draw('cse-search-form', options);
         }, true);
       </script>

       </td>
   </tr>
   </table>
   <span style='font-size:20px;font-weight:bold;color:white;padding:0 1em;'>
    $org_name</span>

HTML;

include 'topmenu.php';

echo<<<HTML
</div>

<!-- Begin page content -->
<div id='$page_css'>

HTML;

if ( ! isset( $nolinks ) )
  include 'links.php';
?>
