<?php
/*
 * buffer_extinction.php
 *
 * Extinction information for a few buffers
 *
 */
session_start();

include 'config.php';

// Start displaying page
$page_title = "Buffer Extinction Profiles";
$css = 'css/us3_resources.css';
include 'top.php';
include 'links.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Buffer Extinction Profiles For Various Buffers*</h1>
  <!-- Place page content here -->
  <table cellspacing='0' cellpadding='20' class='resources' style='width:90%'>
  <thead>
    <tr class='header'>
      <th>Buffer</th>
      <th>Full Scale Graph</th>
      <th>Zoomed Graph</th>
      <th>1 mM ASCII Data</th>
      <th>pKa values<br/>(25&deg; C)</th>
    
      <th>pH range </th>
    </tr>
  </thead>
  
  <tbody>
    <tr>
      <th>Acetate Buffer</th>
      <td><a href='#' onclick='show_image( "buffer_images/acetate1.png" );'>200nm-245nm</a></td>
      <td><a href='#' onclick='show_image( "buffer_images/acetate2.png" );'>200nm-250nm</a></td>
      <td><a href='#' onclick='get_detail( "buffer_images/Acetate.html" );'>200nm-351nm</a></td>
    
      <td>4.76</td>
      <td>3.6 &ndash; 5.6</td>
    </tr>
    <tr>
      <th>HEPES Buffer</th>
      <td><a href='#' onclick='show_image( "buffer_images/hepes.png"  );'>207nm-245nm</a></td>
      <td><a href='#' onclick='show_image( "buffer_images/hepes2.png" );'>207nm-250nm</a></td>
    
      <td><a href='#' onclick='get_detail( "buffer_images/Hepes.html" );'>207nm-351nm</a></td>
      <td>7.48</td>
      <td>6.8 &ndash; 8.2</td>
    </tr>
    <tr class='lowUV'>
      <th>MOPS Buffer</th>
      <td><a href='#' onclick='show_image( "buffer_images/mops1.png" );'>200nm-260nm</a></td>
      <td><a href='#' onclick='show_image( "buffer_images/mops2.png" );'>200nm-260nm</a></td>
      <td><a href='#' onclick='get_detail( "buffer_images/Mops.html" );'>200nm-351nm</a></td>
      <td>7.14</td>
      <td>6.5 &ndash; 7.9</td>
    </tr>
    <tr class='lowUV'>
      <th>Sodium Phosphate</th>
    
      <td><a href='#' onclick='show_image( "buffer_images/Phosphate1.png" );'>200nm-220nm</a></td>
      <td><a href='#' onclick='show_image( "buffer_images/Phosphate2.png" );'>200nm-220nm</a></td>
      <td><a href='#' onclick='get_detail( "buffer_images/Phosphate.html" );'>200nm-350nm</a></td>
      <td>2.15<br/>7.20<br/>12.33</td>
      <td>1.7 &ndash; 2.9<br/> 5.8 &ndash; 8.0<br/> &ndash; </td>
    
    </tr>
    <tr>
      <th>Tris Buffer</th>
      <td><a href='#' onclick='show_image( "buffer_images/tris1.png" );'>207nm-240nm</a></td>
      <td><a href='#' onclick='show_image( "buffer_images/tris2.png" );'>207nm-240nm</a></td>
      <td><a href='#' onclick='get_detail( "buffer_images/Tris.html" );'>207nm-351nm</a></td>
      <td>8.06</td>
    
      <td>7.5 &ndash; 9.0</td>
    </tr>
    <tr>
      <th>PIPES Buffer</th>
      <td><a href='#' onclick='show_image( "buffer_images/Pipes1.png" );'>200nm-240nm</a></td>
      <td><a href='#' onclick='show_image( "buffer_images/Pipes2.png" );'>200nm-235nm</a></td>
      <td><a href='#' onclick='get_detail( "buffer_images/Pipes.html" );'>200nm-351nm</a></td>
    
      <td>6.76</td>
      <td>6.1 &ndash; 7.5</td>
    </tr>
    <tr class='lowUV'>
      <th>Sodium Chloride</th>
      <td><a href='#' onclick='show_image( "buffer_images/Sodium1.png" );'>201nm-230nm</a></td>
      <td><a href='#' onclick='show_image( "buffer_images/Sodium2.png" );'>201nm-230nm</a></td>
      <td><a href='#' onclick='get_detail( "buffer_images/Sodium.html" );'>201nm-351nm</a></td>
      <td>&ndash;</td>
      <td>&ndash;</td>
    </tr>
    <tr class='lowUV'>
      <th>MES Buffer</th>
      <td><a href='#' onclick='show_image( "buffer_images/MES-1.png"  );'>200nm-280nm</a></td>
      <td><a href='#' onclick='show_image( "buffer_images/MES-2.png"  );'>200nm-280nm</a></td>
      <td><a href='#' onclick='get_detail( "buffer_images/MES-1.html" );'>200nm-351nm</a></td>
      <td>6.10</td>
      <td>5.5 &ndash; 6.7</td>
    </tr>
    <tr>
      <th> Cesium Chloride </th>
    
      <td><a href='#' onclick='show_image( "buffer_images/CsCl1.png"  );'>210nm-275nm</a></td>
      <td><a href='#' onclick='show_image( "buffer_images/CsCl2.png"  );'>210nm-275nm</a></td>
      <td><a href='#' onclick='get_detail( "buffer_images/CsCl1.html" );'>210nm-350nm</a></td>
      <td>&ndash;</td>
      <td>&ndash;</td>
    </tr>
    
    <tr> 
      <td colspan='6'>(Highlighted buffers are suitable for measurement in low UV)</td>
    
    </tr>
  </tbody>

  </table>
  <div style='clear:left;'></div>

  <p class='caption'>*All data measured by Divjot Kumar, San Antonio</p>

</div>

<?php
include 'bottom.php';
exit();
?>
