<?php
/*
 * compatibility_guide.php
 *
 * A set of tables showing information about chemical compatibility
 *
 */
session_start();

$acids = <<<HTML
<table cellspacing='0' cellpadding='10' class='resources' style='width:50%;'>

<thead>
<tr><th class='group'>Chemical Group</th><th class='group'>Anodized Aluminum</th>
    <th class='group'>Charcoal/ Epoxy</th><th class='desc'>Description</th></tr>
</thead>

<tbody>
<tr><th rowspan='26'>Acids (aq)</th><td>S</td><td>S</td>
    <td class='desc'>acetic acid (5%)/ethanoic acid</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>acetic acid (60%)/ethanoic acid</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>acetic acid (glacial)/ethanoic acid</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>boric acid</td></tr>

<tr><td>S</td><td>U</td><td class='desc'>chromic acid (10%)</td></tr>
<tr><td>S</td><td>S</td>
    <td class='desc'>citric acid/2-hydroxy-1,2,3-propanetricarboxylic acid</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>hydrochloric acid (10%)</td></tr>
<tr><td>U</td><td>U</td><td class='desc'>hydrochloric acid (50%)</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>iodoacetic acid/2-iodoethanoic acid</td></tr>
<tr><td>S</td><td>M</td><td class='desc'>mercaptoacetic acid/2-mercaptoethanoic acid</td></tr>
<tr><td>S</td><td>U</td><td class='desc'>nitric acid (10%)</td></tr>
<tr><td>S</td><td>U</td><td class='desc'>nitric acid (50%)</td></tr>
<tr><td>&ndash;</td><td>S</td><td class='desc'>oleic acid/cis-9-octadecenoic acid</td></tr>

<tr><td>U</td><td>S</td><td class='desc'>oxalic acid/ethanedioic acid</td></tr>
<tr><td>U</td><td>1</td><td class='desc'>perchloric acid (70%)1</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>phosphoric acid mixture (10%)</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>phosphoric acid mixture (50%)</td></tr>
<tr><td>S</td><td>M</td><td class='desc'>picric acid/2,4,6-trinitrophenol</td></tr>

<tr><td>&ndash;</td><td>-</td><td class='desc'>saturated fatty acids (see stearic acid)</td></tr>
<tr><td>&ndash;</td><td>S</td><td class='desc'>stearic acid/octadecanoic acid</td></tr>
<tr><td>U</td><td>S</td>
    <td class='desc'>sulfosalicylic acid/3-carboxy-4-hydroxybenzenesulfonic acid</td></tr>
<tr><td>U</td><td>U</td><td class='desc'>sulfuric acid (10%)</td></tr>
<tr><td>U</td><td>U</td><td class='desc'>sulfuric acid (50%)</td></tr>

<tr><td>&ndash;</td><td>-</td><td class='desc'>thioglycolic acid (see mercaptoacetic acid)</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>trichloroacetic acid/trichloroethanoic acid</td></tr>
<tr><td>&ndash;</td><td>-</td><td class='desc'>unsaturated fatty acids (sea oleic acid)</td></tr>
</tbody>

</table>

<div style='clear:left;'></div>

HTML;

$bases = <<<HTML
<table cellspacing='0' cellpadding='10' class='resources' style='width:50%;'>

<thead>
<tr><th class='group'>Chemical Group</th><th class='group'>Anodized Aluminum</th>
    <th class='group'>Charcoal/ Epoxy</th><th class='desc'>Description</th></tr>
</thead>

<tbody>
<tr><th rowspan='8'>Bases (aq)</th><td>U</td><td>S</td>
    <td class='desc'>ammonium hydroxide (10%)</td></tr>

<tr><td>U</td><td>U</td><td class='desc'>ammonium hydroxide (28%)</td></tr>

<tr><td>S</td><td>U</td><td class='desc'>aniline/benzenamine</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>potassium hydroxide (5%)</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>potassium hydroxide (45%)</td></tr>
<tr><td>S</td><td>U</td><td class='desc'>pyridine (50%)/azabenzene</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>sodium hydroxide (1%)</td></tr>

<tr><td>U</td><td>S</td><td class='desc'>sodium hydroxide (&gt;l%)</td></tr>
</tbody>

</table>

<div style='clear:left;'></div>

HTML;

$salts = <<<HTML
<table cellspacing='0' cellpadding='10' class='resources' style='width:50%;'>
<thead>
<tr><th class='group'>Chemical Group</th><th class='group'>Anodized Aluminum</th>
    <th class='group'>Charcoal/ Epoxy</th><th class='desc'>Description</th></tr>
</thead>

<tbody>
<tr><th rowspan='23'>Salts (aq)</th><td>U</td><td>S</td><td class='desc'>aluminum chloride</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>ammonium acetate/ammonium ethanoate</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>ammonium carbonate</td></tr>
<tr><td>&ndash;</td><td>S</td><td class='desc'>ammonium phosphate</td></tr>

<tr><td>M</td><td>S</td><td class='desc'>ammonium sulfate</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>barium salts</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>calcium chloride</td></tr>
<tr><td>U</td><td>S</td>
    <td class='desc'>guanidine hydrochloride/l-aminomethanamidine hydrochloride</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>magnesium chloride</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>nickel salts</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>potassium bromide</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>potassium carbonate</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>potassium chloride</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>potassium permanganate</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>silver nitrate</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>sodium borate</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>sodium carbonate</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>sodium chloride</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>sodium nitrate</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>sodium sulfate</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>sodium sulfite</td></tr>
<tr><td>U</td><td>S</td><td class='desc'>zinc chloride</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>zinc sulfate</td></tr>
</tbody>

</table>

<div style='clear:left;'></div>

HTML;

$gradients = <<<HTML
<table cellspacing='0' cellpadding='10' class='resources' style='width:50%;'>
<thead>
<tr><th class='group'>Chemical Group</th><th class='group'>Anodized Aluminum</th>
    <th class='group'>Charcoal/ Epoxy</th><th class='desc'>Description</th></tr>
</thead>

<tbody>
<tr><th rowspan='17'>Gradient Forming Materials (aq)</th><td>&ndash;</td><td>S</td>
    <td class='desc'>cesium acetate/cesium ethanoate</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>cesium bromide</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>cesium chloride</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>cesium formate/cesium methanoate</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>cesium iodide</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>cesium sulfate</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>dextran or dextran sulfate</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>Ficoll-Paque</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>glycerol/1,2,3-propanetriol</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>metrizamide</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>rubidium bromide</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>rubidium chloride</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>sodium bromide</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>sodium iodide</td></tr>

<tr><td>S</td><td>S</td>
    <td class='desc'>sucrose/beta-D-fructofuranosyl-alpha-D-glucopyranoside</td></tr>
<tr><td>S</td><td>S</td>
    <td class='desc'>sucrose, alkaline/beta-D-fructofuranosyl-alpha-D-</td></tr>
<tr><td></td><td></td><td class='desc'>glucopyranoside</td></tr>
</tbody>

</table>

<div style='clear:left;'></div>

HTML;

$solvents = <<<HTML
<table cellspacing='0' cellpadding='10' class='resources' style='width:50%;'>
<thead>
<tr><th class='group'>Chemical Group</th><th class='group'>Anodized Aluminum</th>
    <th class='group'>Charcoal/ Epoxy</th><th class='desc'>Description</th></tr>
</thead>

<tbody>
<tr><th rowspan='29'>Solvents</th><td>S</td><td>U</td><td class='desc'>acetone/2-propanone3</td></tr>

<tr><td>S</td><td>M</td><td class='desc'>acetonitrile/ethanenitrile3</td></tr>
<tr><td>S</td><td>U</td><td class='desc'>benzene3</td></tr>
<tr><td>1</td><td>U</td><td class='desc'>carbon tetrachloride/tetrachloromethane</td></tr>
<tr><td>1</td><td>S</td><td class='desc'>chloroform/trichloromethane</td></tr>

<tr><td>S</td><td>U</td><td class='desc'>cresol mixture/methylphenol</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>cyclohexane3</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>diethyl ether/ethoxyethane3</td></tr>
<tr><td>&ndash;</td><td>M</td><td class='desc'>diethyl ketone/3-pentanone3</td></tr>
<tr><td>S</td><td>M</td><td class='desc'>N,N-dimethyiformamide/N,N-dimethylmethanamide3</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>dimethyl sulfoxide/sulfinylbis(methane)</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>dioxane/1,4-dioxacyclohexane3</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>ethanol (50%)3</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>ethanol (95%)3</td></tr>
<tr><td>&ndash;</td><td>&ndash;</td><td class='desc'>ether (see diethyl ether)</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>ethyl acetate/ethyl ethanoate3</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>ethylene glycol/ 1,2-ethanediol</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>hexane3</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>isopropyl alcohol/2-propanol3</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>kerosene3</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>methanol3</td></tr>
<tr><td>1</td><td>S</td><td class='desc'>methylene chloride/dichloromethane</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>methyl ethyl ketone/2-butanone3</td></tr>
<tr><td>S</td><td>M</td><td class='desc'>phenol (5%)</td></tr>
<tr><td>S</td><td>U</td><td class='desc'>phenol (50%)</td></tr>

<tr><td>S</td><td>U</td><td class='desc'>tetrahydrofuran3</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>toluene/methylbenzene3</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>water</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>xylene mixture/dimethylbenzene3</td></tr>
</tbody>

</table>

<div style='clear:left;'></div>

HTML;

$detergents = <<<HTML
<table cellspacing='0' cellpadding='10' class='resources' style='width:50%;'>
<thead>
<tr><th class='group'>Chemical Group</th><th class='group'>Anodized Aluminum</th>
    <th class='group'>Charcoal/ Epoxy</th><th class='desc'>Description</th></tr>
</thead>

<tbody>
<tr><th rowspan='19'>Detergents</th><td>U</td><td>S</td><td class='desc'>Alconox</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>deoxycholate, sodium dodecyl sulfate, Triton X-100</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>Haemo-Sol</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>Solution 555Tm (20%)</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>Zephiran chloride (1%) </td></tr>

<tr><td>&ndash;</td><td>&ndash;</td>
    <td class='desc'>dibutyl phthalate (see n-butyl phthalate)</td></tr>
<tr><td>S</td><td>S</td>
    <td class='desc'>diethyl pyrocarbonate/pyrocarbonic acid diethyl ester</td></tr>
<tr><td>&ndash;</td><td>U</td><td class='desc'>ethylene oxide vapor4/oxirane3</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>formaldehyde/methanal</td></tr>

<tr><td>&ndash;</td><td>&ndash;</td><td class='desc'>formalin (40%) (see formaldehyde)</td></tr>
<tr><td>U</td><td>U</td><td class='desc'>hydrogen peroxide (3%)</td></tr>
<tr><td>U</td><td>U</td><td class='desc'>hydrogen peroxide (10%)1</td></tr>
<tr><td>S</td><td>M</td><td class='desc'>2-mercaptoethanol3</td></tr>
<tr><td>S</td><td>S</td><td class='desc'>oils (petroleum)</td></tr>

<tr><td>&ndash;</td><td>S</td><td class='desc'>oils (other)</td></tr>
<tr><td>S</td><td>S</td>
    <td class='desc'>physiologic media (e.g., culture media, milk, serum, urine)</td></tr>
<tr><td>S</td><td>M</td><td class='desc'>sodium hypochlorite</td></tr>
<tr><td>S</td><td>S</td>
    <td class='desc'>Tris buffer (neutral pH)/tris(hydroxymethyl)aminomethane</td></tr>

<tr><td>S</td><td>S</td><td class='desc'>urea</td></tr>
</tbody>

</table>

<div style='clear:left;'></div>

HTML;

$other = <<<HTML
<table cellspacing='0' cellpadding='10' class='resources' style='width:50%;'>
<thead>
<tr><th class='group'>Chemical Group</th><th class='group'>Anodized Aluminum</th>
    <th class='group'>Charcoal/ Epoxy</th><th class='desc'>Description</th></tr>
</thead>

<tbody>
<tr>
  <th>Other</th><td>S</td><td>S</td>
  <td class='desc'>n-butyl phthalate4/dibutyl 1,2-benzenedicarboxylate</td>
</tr>
</tbody>

</table>

<div style='clear:left;'></div>

HTML;

$legend = <<<HTML
<table cellspacing='0' cellpadding='10' class='resources' style='width:50%;'>
<thead>
  <tr><th colspan='2' class='desc'>Legend</th></tr>
</thead>

<tbody>
  <tr><th>S</th><td class='desc'>satisfactory resistance</td></tr>
  <tr><th>M</th><td class='desc'>marginal resistance</td></tr>
  <tr><th>U</th><td class='desc'>unsatisfactory resistance</td></tr>

  <tr><th>1</th><td class='desc'>explosion hazard due to possible material/chemical 
                    reaction under rotor failure conditions</td></tr>
  <tr><th>2</th><td class='desc'>OK below 26&deg; C only</td></tr>
  <tr><th>3</th><td class='desc'>Flammability hazard. Not recommended for use in any
  type of centrifuge because vapors may be ignited by exposure to electrical 
  contacts. Depending on the centrifuge type, such exposure could occur 
  either during normal centrifugation or under failure conditions.</td></tr>
  <tr><th>4</th><td class='desc'>nonaqueous</td></tr>

  <tr><th>&ndash;</th><td class='desc'>Unknown</td></tr>
</tbody>

</table>

<div style='clear:left;'></div>

HTML;

include 'config.php';

// Start displaying page
$page_title = "Chemical Compatibility Guide";
$css = 'css/us3_resources.css';
include 'top.php';
include 'links.php';

echo <<<HTML
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Chemical Compatibility Guide</h1>
  <!-- Place page content here -->

  <p>Click on a link to display the table you want.</p>

  <div>
  <a id='show_acids' 
     href='javascript:toggleDisplay( "acid_div", "show_acids", "Acids (aqueous)" );'>
     Show Acids (aqueous)</a><br />
  <div id='acid_div' class='toggle'>$acids</div>

  <a id='show_bases' 
     href='javascript:toggleDisplay( "base_div", "show_bases", "Bases (aqueous)" );'>
     Show Bases (aqueous)</a><br />
  <div id='base_div' class='toggle'>$bases</div>

  <a id='show_salts' 
     href='javascript:toggleDisplay( "salt_div", "show_salts", "Salts (aqueous)" );'>
     Show Salts (aqueous)</a><br />
  <div id='salt_div' class='toggle'>$salts</div>

  <a id='show_gradients' 
     href='javascript:toggleDisplay( "gradient_div", "show_gradients", "Gradient-forming Materials (aqueous)" );'>
     Show Gradient-forming Materials (aqueous)</a><br />
  <div id='gradient_div' class='toggle'>$gradients</div>

  <a id='show_detergents' 
     href='javascript:toggleDisplay( "detergent_div", "show_detergents", "Detergents" );'>
     Show Detergents</a><br />
  <div id='detergent_div' class='toggle'>$detergents</div>

  <a id='show_solvents' 
     href='javascript:toggleDisplay( "solvent_div", "show_solvents", "Solvents" );'>
     Show Solvents</a><br />
  <div id='solvent_div' class='toggle'>$solvents</div>

  <a id='show_other' 
     href='javascript:toggleDisplay( "other_div", "show_other", "Other" );'>
     Show Other</a><br />
  <div id='other_div' class='toggle'>$other</div>

  <div>$legend</div>
  </div>

</div>

HTML;

include 'bottom.php';
exit();
?>
