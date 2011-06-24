<?php
/*
 * license.php
 *
 * License information for UltraScan
 *
 */
session_start();

include 'config.php';

// Start displaying page
$page_title = "Copyright and License Information";
include 'top.php';
include 'links.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Copyright and License Information</h1>
  <!-- Place page content here -->

  <p>The <b><i>UltraScan</i></b> software is copyright protected
  by international laws.  The copyright is owned by 
  <a href='mailto:demeler@biochem.uthscsa.edu'>Borries
  Demeler, PhD</a> (1989-present) and <a href="http://www.uthscsa.edu">
  The University of Texas Health Science Center at San Antonio</a>
  (1997-present). All rights reserved.</p>

  The entire software, including documentation, source code,
  LIMS and grid middleware components are protected by the <a
  href='http://www.gnu.org/copyleft/gpl.html'>GNU General Public License,
  version 3</a>.

</div>

<?php
include 'bottom.php';
exit();
?>
