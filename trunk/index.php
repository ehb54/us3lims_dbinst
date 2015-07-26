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

<p> This website offers access to the UltraScan
Laboratory Information Management System (USLIMS), an <a
href='https://www.xsede.org/gateways-overview'> XSEDE Science Gateway</a>
supported by an allocation through an XSEDE community account.
This system provides web and database support for users of the
<a href='http://ultrascan.uthscsa.edu'>UltraScan software</a>. You
can use this portal to access data associated with your sedimentation
experiments, and share your data with collaborators. Authorized users
can also use this site to model analytical ultracentrifugation experiments
with UltraScan's high-performance analysis modules by submitting
analysis jobs to computing clusters available at the University of
Texas Health Science Center and XSEDE sites at the Texas Advanced
Computing Center and at Indiana University.  These services are made
available through an NSF XSEDE community account (see below for
funding credits).  To obtain access to this resource please contact the
<a href='mailto:demeler@biochem.uthscsa.edu'>project director</a>.  </p>

<p><b>DISCLAIMER:</b></p>

<p>We do not take any responsibility for data loss due to hardware
failure, software error, operator error or other cause. It is
your responsibility to always make backups of your data. You are
free to use this resource at no cost.  Support is provided via the <a
href="http://ultrascan.uthscsa.edu/mailman/listinfo/ultrascan">UltraScan
mailing list</a>. Please keep in mind that we cannot guarantee the
security of your data. You assume all risks involved with placing your
data on our server. This site is a public service, and can not guarantee
that information placed on this server will always remain private.  If you
need a private, secure database please contact us to make arrangements
for such a service.</p>

<p> Funding for this facility is provided through multiple sources:</p>

<ul>

  <li><a href='http://www.biochem.uthscsa.edu'>Department of Biochemistry</a>,
  <a href='http://www.uthscsa.edu'>University of Texas Health Science Center at
  San Antonio</a> </li>

  <li>User fees collected from collaborators and users of the <a
  href='http://www.cauma.uthscsa.edu'>UltraScan facility</a> at UTHSCSA.</li>

  <li>San Antonio Life Science Institute Grant #10001642</li>

  <li><a href='http://www.nsf.gov'>The National Science Foundation</a>, Grants
  DBI-9974819, ANI-228927, DBI-9724273, TG-MCB070038 (all to Borries Demeler)</li>

  <li><a href='http://www.nih.gov'>The National Institutes of Health</a>, Grant NCRR-R01RR022200 (to Borries Demeler)</li>

</ul>

<p> When publishing, please credit our facility as follows:</p>

<p><b>
Calculations were performed on the UltraScan LIMS cluster at the<br/>
Bioinformatics Core Facility at the University of Texas Health<br/>
Science Center at San Antonio and multiple High Performance Computing
clusters supported by NSF XSEDE Grant<br/>
#MCB070038 (to Borries Demeler)."</b></p>

<p>Please enter the link to each manuscript citing this resource on the
<a href='http://ultrascan.uthscsa.edu/ultrascan-refs.html'>UltraScan
submission website</a>.</p>

<p>Before logging in, if you have not done so, please visit 
   <a href='data_security.php'>the Data Security page</a> to accept our 
   certificate authority. This will make it easier to use the secure part 
   of our website. Thanks!</p>

<p><a href='mailto:demeler@biochem.uthscsa.edu'>Borries Demeler, Ph.D.</a><br/>
Associate Professor<br/> 
UltraScan Project Director</p>

</div>

<?php
include 'footer.php';
?>
