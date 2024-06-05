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

  <h1 class="title"><center>Welcome to the UltraScan3 LIMS Portal!</h1>
<center><b>(Database:
<?php
echo  $dbname ;
?>
)</b></center>

<p> This website offers access to the UltraScan
Laboratory Information Management System (USLIMS), an <a
href='https://sciencegateways.org/resources/10043'>ACCESS Science Gateway</a>
supported by an allocation through an ACCESS community account.
This system provides web and database support for users of the
<a href='http://ultrascan.aucsolutions.com'>UltraScan software</a>. You
can use this portal to access data associated with your sedimentation
experiments, and share your data with collaborators. Authorized users
can also use this site to model analytical ultracentrifugation experiments
with UltraScan's high-performance analysis modules by submitting
analysis jobs to local or remote computing clusters.

Remote HPC infrastructure services are made freely available through
an NSF ACCESS community account for academic and not-for-profit users
(see below for funding credits).  To obtain access to this resource
please contact the <a href='mailto:demeler@gmail.com'>project
director</a>. </p>

<p><b>DISCLAIMER:</b></p>

<p>We do not take any responsibility for data loss due to hardware
failure, software error, operator error or other cause. It is
your responsibility to always make backups of your data. You are
free to use this resource at no cost.  Support is provided by <a
href="https://aucsolutions.com">AUC Solutions, LLC</a>. Please keep in mind that we cannot guarantee the
security of your data. You assume all risks involved with placing your
data on our server. This site is a public service, and can not guarantee
that information placed on this server will always remain private.  If you
need a private, secure database please contact us to make arrangements
for such a service.</p>

<p> Funding for this facility is provided through multiple sources:</p>

<ul>
  <li><a href='http://www.nsf.gov'>The National Science Foundation</a>, Access Community Allocation Grant TG-MCB070038 (to Borries Demeler)</li>

  <li><a href='http://www.nih.gov'>The National Institutes of Health</a>, Grant R01GM120600 (to Emre Brookes, Borries Demeler, and Alexey Savelyev)</li>

</ul>

<p> When publishing, please credit our facility as follows:</p>

<p><b>
UltraScan calculations were supported by NSF ACCESS Grant #MCB070038 (to Borries Demeler).</b></p>

<p>Please enter the link to each manuscript citing this resource on the
<a href='https://www.ultrascan.aucsolutions.com/submit-references.php'>UltraScan submission website</a>.</p>
<p>Before logging in, if you have not done so, please visit 
   <a href='data_security.php'>the Data Security page</a> to accept our 
   certificate authority. This will make it easier to use the secure part 
   of our website. Thanks!</p>

<p><a href='mailto:demeler@gmail.com'>Borries Demeler, Ph.D.</a><br/>
Professor<br/> 
UltraScan Project Director</p>

</div>

<?php
include 'footer.php';
?>
