<?php
/*
 * equilibrium.php
 *
 * A brief description of an equilibrium experiment
 *
 */
session_start();

include 'config.php';

// Start displaying page
$page_title = "Sedimentation Equilibrium Experiments";
$js = 'js/template.js,js/sorttable.js';
$css = 'css/template.css';
include 'top.php';
include 'links.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Sedimentation Equilibrium Experiments</h1>
  <!-- Place page content here -->

  <p> Sedimentation equilibrium is a technique where the sample is spun at lower
  rotor speeds than in a velocity experiment. The sample will simultaneously
  sediment and back-diffuse from the bottom of the cell and produce a gradient,
  which will not change once equilibrium is obtained. As soon as equilibrium has
  been obtained, net flow of material in the cell will cease and sedimentation
  and diffusion will balance each other. The curvature of the gradient can be
  influenced by varying the rotor speed, and multiple gradients obtained at
  different speeds can be used to globally fit the data to a model. While not
  very sensitive for the detection of heterogeneity (velocity analysis is better
  suited), equilibrium experiments provide molecular weight with superior
  accuracy for pauci-disperse systems and can provide thermodynamic information
  such as binding strength for reversible hetero- or self-associating systems.
  Sedimentation equilibrium analysis is the method of choice when determining if
  and at which concentration a protein will dimerize.</p>

  <img src='images/equilibrium.png' alt='A diagram of a sedimentation equilibrium experiment'/>

  <p> Equilibrium Experiment: the rotor speed is slow enough such that the back
  diffusion at the bottom of the cell will balance out the sedimentation and form
  an equilibrium gradient.</p>


</div>

<?php
include 'bottom.php';
exit();
?>
