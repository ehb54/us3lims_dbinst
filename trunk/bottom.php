<?php
/*
 * bottom.php
 *
 */

 // Some pages seem to need a couple of <br /> to signal 
  //  the end of the page...

$filename = basename( $_SERVER['PHP_SELF'] );
$modtime = date( "F d, Y", filectime( $filename ) );

echo<<<HTML
  <!-- end content -->
  <div style="clear: both;">&nbsp;</div>

  <!-- end page -->
  <div id="footer">
	<div id='info'>
	</div>

	  <div id='info2'><hr>Last modified on $modtime --
         <a href='license.php'>Copyright &copy; notice and license information</a> UltraScan Project, UTHSCSA
      </div>
 
  </div>
</div>

</body>
</html>

HTML;
?>
