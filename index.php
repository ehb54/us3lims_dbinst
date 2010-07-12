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
  <h3><em>Welcome!</em></h3>

  <p>Welcome to the website for the UltraScan III LIMS portal (under
     development).</p>

  <p><a href='mailto:demeler@biochem.uthscsa.edu'>Borries Demeler, Ph.D.</a><br/>
     Associate Professor<br/> 
     Facility Director</p>

</div>

<?php
include 'bottom.php';
?>
