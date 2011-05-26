<?php
/*
 * velocity.php
 *
 * A brief explanation of a sedimentation velocity experiment
 *
 */
session_start();

include 'config.php';

// Start displaying page
$page_title = "Sedimentation Velocity Experiments";
include 'top.php';
include 'links.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Sedimentation Velocity Experiments</h1>
  <!-- Place page content here -->

  <p>Sedimentation velocity experiments are performed at high speed to overcome
  the effect of diffusion. Sedimentation will separate the components in the
  sample providing a very sensitive assay for composition. In a velocity
  experiment the speed is generally so great that back diffusion from the bottom
  of the cell is minimal compared to the rate of sedimentation such that a moving
  boundary will form, behind which will be little to no material left. The
  evolution of the shape of the boundary over time is representative of the
  composition, sedimentation and diffusion properties of the sample and in ideal
  cases can be analyzed for molecular weight.</p>

  <img src='images/velocity.png' alt='A diagram of sedimentation experiment'/>

  <p>Velocity experiment: a moving boundary is generated because the rotor speed
  is large enough to prevent back diffusion from the cell bottom to influence the
  absorbance near the meniscus, which will deplete over time.</p>

</div>

<?php
include 'bottom.php';
exit();
?>
