<?php
/*
 * examples.php
 *
 * Examples of things one can do with ultracentrifugation
 *
 */
session_start();

include 'config.php';

// Start displaying page
$page_title = "AUC Examples";
include 'top.php';
include 'links.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Examples of Biological Problems Suitable for AUC Analysis</h1>
  <!-- Place page content here -->

  <h4 class='secondary'>Purity Assessment</h4>
  <p>Obtain a very sensitve measure of the purity of your sample. Sedimentation
  velocity experiments can detect small amounts of impurities in your sample,
  and identify conformational heterogeneity, as well as molecular weight
  heterogeneity. A combination of sedimentation velocity and sedimentation
  equilibrium experiments can assist in distinguishing between the two.</p>

  <h4 class='secondary'>Mutation Effects</h4>
  <p>By making mutations to your sample, you may be able to effect changes in 
  sample characteristics that may be monitored by analytical ultracentrifugation.
  These changes may affect binding, self-assocition, molecular weight, function
  and conformation. AUC serves as an important analytical tool to asses these
  changes.</p>

  <h4 class='secondary'>Molecular Weight Determination</h4>

  <p>For pure samples and paucidisperse samples of two or three discrete components 
  it is possible to obtain molecular weights, as well as limited shape 
  information, such as frictional coefficients, diffusion coefficients and
  model axial ratios for hypothetical models such as prolate and oblate
  ellipsoids, long rods and spheres (Stokes radii). 
  </p>

  <h4 class='secondary'>Binding Stoichiometry</h4>
  <p>For well separated ligand/substrate systems it may be possible to determine
  binding stoichiometries for the individual components. If different 
  chromophores exist, it may be possible to separate the signal of ligand
  and substrate to sufficiently to observe bound and unbound species
  separately.
  </p>

  <h4 class='secondary'>Self-Association behavior</h4>
  <p>For self-associating systems it is often possible to determine a monomer
  molecular weight, the extent of oligomerization and the strength of the 
  association, and to obtain quantitative measures for the association and 
  dissociation constants. This type of experiment is often used to assess the
  effect of one or more mutations on a putative binding region and the 
  resulting binding strength.</p>

  <h4 class='secondary'>Conformational Changes and Effects</h4>
  <p>For many systems the conformational state of a molecule is of interest.
  You may be interested if buffer conditions (salt, pH, etc), temperature,
  or even mutations have an effect on the conformation of the sample, or 
  function of the sample, as it is related to conformation.</p>
  </ul>


  <p>Here is a short list of the types of scientific questions that can be
  answered with Analytical Ultracentrifugation:</p>

  <ul>
    <li>Is my sample homogeneous or heterogeneous?</li>
    <li>Does my sample self-associate?</li>
    <li>Do I have aggregation?</li>
    <li>What molecular weight is my sample?</li>
    <li>Does my sample bind to the ligand?</li>

    <li>What is the stoichiometry of binding?</li>
    <li>Are there conformational changes resulting from different 
        buffer/temperature conditions (salt, pH, ligand concentration, etc)?</li>
    <li>Do the mutations I have designed affect the strength of binding, 
        self-association, conformation, stoichiometry, etc.?</li>
    <li>Is my sample suitable (pure enough) for X-ray crystallography, NMR?</li>
    <li>How does my multi-enzyme complex associate? What are the steps and are 
        there intermediates?</li>
    <li>What overall shape does my sample have?</li>

    <li>What is my sample&rsquo;s sedimentation or diffusion coefficient?</li>
    <li>What is the Stoke&rsquo;s radius of my sample?</li>
    <li>I have a mixture of two (three) proteins, one has a different chromophore 
        (for example, a heme group), how is this protein behaving by itself?</li>
    <li>How does salt affect the concentration dependency (concentration dependent
        nonideality) of my sample?</li>
    <li>Is the heterogeneity in S due to molecular weight, isomeric shape effects,
        or both?</li>
  </ul>

      <p>Analytical ultracentrifugation has been used successfully on many
      biological and synthetic systems, here is partial list: </p>

  <ul> 
    <li>systems with molecular weights between a few hundred daltons all the way
        up to many million daltons</li>
    <li>proteins (incl. small peptides, glycosylated proteins, membrane proteins,
        micelles)</li>
    <li>nucleic acids (single stranded, double stranded, supercoiled, nucleotides
        and nucleotide analogs)</li>
    <li>whole virus particles</li>
    <li>interacting systems</li>

    <li>synthetic molecules (latex beads, synthetic polymers, etc)</li>
    <li>polysaccharides</li>
    <li>protein-D(R)NA binding, protein-protein binding</li>
    <li>active enzyme-substrate systems</li> 
  </ul>


</div>

<?php
include 'bottom.php';
exit();
?>
