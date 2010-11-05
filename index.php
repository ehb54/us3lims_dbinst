<?php
/*
 * index.php
 *
 * main page 
 *
 */
session_start();

include 'config.php';

$page_title = "Welcome!";
$css = 'css/index.css';
include 'top.php';
include 'links.php';
?>
<div id='content'>

  <h1 class="title"><?php echo $org_name; ?></h1>

  <div class="imageright">
    <table cellpadding="0" cellspacing="0">
    <tr><td><img src="#" alt="A photo" 
            width="0" height="0"/></td></tr>
    <tr><td class='caption'>A caption</td></tr>
    </table>
  </div>
  <h3><em>Welcome to the Portal for the UltraScan Laboratory Information Management System!</em></h3>

<p> This website provides public database support for users of the <a
href='http://www.ultrascan.uthscsa.edu'>UltraScan software</a>.  You can use this
portal to store your sedimentation data and associated data for your
experiments in the UltraScan LIMS, and share your data in a public forum with
colleagues.  For users of UltraScan's 2-dimensional spectrum analysis and
genetic algorithm analysis, we also offer access to high performance computing
facilities available at UTHSCSA through this portal. Such services are made
available on a first come, first serve basis, and may be restricted when excess
computer capacity is not available. You will need to contact the <a
href='mailto:demeler@biochem.uthscsa.edu'>facility director</a> to obtain all
necessary passwords to use these services.</p>

<p><b>DISCLAIMER:</b></p>

<p>We do not take any responsibility for data loss due to hardware failure,
software error, operator error or other cause. It is your responsibility to
always make backups of your data. You are free to use our facility at no cost.
Support is provided via the <a
href="http://www.ultrascan.uthscsa.edu/mailman/listinfo/ultrascan">UltraScan
mailing list</a>. Please keep in mind that we cannot warrant for the security
of your data. You assume all risks involved with placing your data on our
server. This server is a public service, and information placed on this server
can be shared by other users. If you need a private, secure database please
contact us to make arrangements for such a service.</p>

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
<ul>
<p>
<b>
Calculations were performed on the UltraScan LIMS cluster at the<br/>
Bioinformatics Core Facility at the University of Texas Health<br/>
Science Center at San Antonio and the Lonestar cluster at the<br/>
Texas Advanced Computing Center supported by NSF Teragrid Grant<br/>
#MCB070038 (to Borries Demeler)."</p>

</b>
</ul>

<p>Please forward the link to each manuscript citing our facility to
<a href='mailto:demeler@biochem.uthscsa.edu'>demeler@biochem.uthscsa.edu</a></p>


<p> Thank you for visiting and feel free to send us your comments!</p>

<p><a href='mailto:demeler@biochem.uthscsa.edu'>Borries Demeler, Ph.D.</a><br/>
Associate Professor<br/>
Facility Director</p>

<table cellspacing='0px' cellpadding='0px' class='imagelinks'>
  <tr><td><img src='images/teragrid.png'></td>
      <td><img src='images/TACC.gif'></td></tr>
  <tr><td><a href='http://www.teragrid.org/'>Teragrid</a></td>
      <td><a href='http://www.tacc.utexas.edu'>
             Texas Advanced<br />Computing Center</a></td></tr>
  <tr><td colspan='2' style='padding-top:2em;'><img src='images/uthscsa.gif'></td></tr>
  <tr><td colspan='2'><a href='http://www.uthscsa.edu'>
              The University of Texas<br />Health Science Center</a></td></tr>
</table>


</div>

<?php
include 'bottom.php';
?>
