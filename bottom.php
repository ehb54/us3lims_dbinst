<?php
/*
 * bottom.php
 *
 */

include 'config.php';

 // Some pages seem to need a couple of <br /> to signal 
  //  the end of the page...

echo<<<HTML
  <!-- end content -->
  <div style="clear: both;">&nbsp;</div>

  <!-- end page -->
  <div id="footer">
  <div id="info">
    <a href="mailto:$admin_email">Contact Webmaster</a><br />
    Updated: $last_update <br />
    Copyright &copy; $copyright_date<br />
    Bioinformatics<br />
    Core Facility,<br />
    UTHSCSA
  </div>
  </div>
</div>

</body>
</html>

HTML;
?>
