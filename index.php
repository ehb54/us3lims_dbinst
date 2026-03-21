<?php
/*
 * index.php
 *
 * main page 
 *
 */
include 'checkinstance.php';

$page_title = "Welcome!";
$css = 'css/index.css';

include 'header.php';

?>
<div id='content'>

  <h1 class="title">Welcome to the XSEDE Science Gateway for UltraScan!</h1>

<p>
The UltraScan Laboratory Information Management System (USLIMS)
provides web and database support for users of the
<a href='http://ultrascan.aucsolutions.com'>UltraScan software</a>. You
can use this portal to access data associated with your sedimentation
experiments, and share your data with collaborators. Authorized users
can also use this site to model analytical ultracentrifugation experiments
with UltraScan's high-performance computing modules, providing parallelized
workflows for optimization.

<p> Funding for the UltraScan software development is provided through multiple sources:</p>

<ul>

  <li><a href='http://www.nih.gov'>The National Institutes of Health</a>,
  Grants NCRR-R01RR022200 and NIGMS-RO1GM120600</li>
  <li><a href='http://www.nsf.gov'>The National Science Foundation</a>, Grants
  DBI-9974819, ANI-228927, DBI-9724273, TG-MCB070038 (all to Borries Demeler)</li>

  <li>Canada Research Chairs program C150–2017-00015</li>
  <li>The Canadian Natural Science and Engineering Research Council, Discovery Grant DG-RGPIN-2019–05637</li>
  <li><a href='https://www.aucsolutions.com'>AUC Solutions</a></li>
  <li>San Antonio Life Science Institute Grant #10001642</li>

</ul>


</div>

<?php
include 'footer.php';
?>
