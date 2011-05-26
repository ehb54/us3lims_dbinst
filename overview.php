<?php
/*
 * overview.php
 *
 * An overview of AUC 
 *
 */
session_start();

include 'config.php';

// Start displaying page
$page_title = "Overview of AUC";
include 'top.php';
include 'links.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Overview</h1>
  <!-- Place page content here -->

  <h4>Problem Solving using Analytical Ultracentrifugation</h4>

  <p>Analytical ultracentrifugation is a powerful biophysical technique that can
  be used to answer a large range of questions frequently encountered in modern
  biochemical research. The UltraScan Database Portal website is offered to users
  of the UltraScan software to provide public database access and provide free
  LIMS service.</p>

  <p>In order to help you identify potential targets for AUC experiments in your
  research, we have assembled a <a href='examples.php'>list of commonly
  encountered scenarios</a> in biological research that can be addressed with
  AUC. In an effort to determine the best approach towards your AUC research
  needs, we have developed this web site, which will help you identify the most
  suitable service option and experimental design for your situation.</p>

</div>

<?php
include 'bottom.php';
exit();
?>
