<?php
/*
 * buffer_pH.php
 *
 * pH tables for potassium phosphate and sodium phosphate buffers
 *
 */
session_start();

include 'config.php';

// Start displaying page
$page_title = "PO4 Buffer pH";
$css = 'css/us3_resources.css';
include 'header.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">PO<sub>4</sub> Buffer pH Tables</h1>
  <!-- Place page content here -->

<?php

$pot_table = <<<HTML
<table cellspacing='0' cellpadding='10' class='resources'>
<caption>pH table for Potassium<br/>phosphate buffer at 25&deg;C</caption>

<thead>
  <tr><th>pH</th>
      <th>% K<sub>2</sub>HPO<sub>4</sub><br/>(dibasic)</th>
      <th>% KH<sub>2</sub>PO<sub>4</sub><br/>(monobasic)</th>
  </tr>
</thead>

<tbody>
  <tr> <td>5.8</td> <td>8.5</td>  <td>91.5</td> </tr>

  <tr> <td>6.0</td> <td>13.2</td> <td>86.8</td> </tr>
  <tr> <td>6.2</td> <td>19.2</td> <td>80.8</td> </tr>
  <tr> <td>6.4</td> <td>27.8</td> <td>72.2</td> </tr>
  <tr> <td>6.6</td> <td>38.1</td> <td>61.9</td> </tr>
  <tr> <td>6.8</td> <td>49.7</td> <td>50.3</td> </tr>

  <tr> <td>7.0</td> <td>61.5</td> <td>38.5</td> </tr>
  <tr> <td>7.2</td> <td>71.7</td> <td>28.3</td> </tr>
  <tr> <td>7.4</td> <td>80.2</td> <td>19.8</td> </tr>
  <tr> <td>7.6</td> <td>86.6</td> <td>13.4</td> </tr>
  <tr> <td>7.8</td> <td>90.8</td> <td>9.2</td>  </tr>

  <tr> <td>8.0</td> <td>94.0</td> <td>6.0</td>  </tr>
</tbody>

</table>
HTML;

$sod_table = <<<HTML

<table cellspacing='0' cellpadding='10' class='resources'>
<caption>pH table for Sodium<br/>phosphate buffer at 25&deg;C</caption>

<thead>
  <tr><th>pH</th>
      <th>% Na<sub>2</sub>HPO<sub>4</sub><br/>(dibasic)</th>
      <th>% NaH<sub>2</sub>PO<sub>4</sub><br/>(monobasic)</th>
  </tr>
</thead>

<tbody>
  <tr> <td>5.8</td> <td>7.9</td>  <td>92.1</td> </tr>

  <tr> <td>6.0</td> <td>12.0</td> <td>88.0</td> </tr>
  <tr> <td>6.2</td> <td>17.8</td> <td>82.2</td> </tr>
  <tr> <td>6.4</td> <td>25.5</td> <td>74.5</td> </tr>
  <tr> <td>6.6</td> <td>35.2</td> <td>64.8</td> </tr>
  <tr> <td>6.8</td> <td>46.3</td> <td>53.7</td> </tr>

  <tr> <td>7.0</td> <td>57.7</td> <td>42.3</td> </tr>
  <tr> <td>7.2</td> <td>68.4</td> <td>31.6</td> </tr>
  <tr> <td>7.4</td> <td>77.4</td> <td>22.6</td> </tr>
  <tr> <td>7.6</td> <td>84.5</td> <td>15.5</td> </tr>
  <tr> <td>7.8</td> <td>89.6</td> <td>10.4</td> </tr>

  <tr> <td>8.0</td> <td>93.2</td> <td>6.8</td>  </tr>
</tbody>
</table>

HTML;

echo <<<HTML
   <table cellspacing='0' cellpadding='20' class='noborder'>
     <tr><td>$pot_table</td>
         <td>$sod_table</td>
     </tr>
   </table>

HTML;

?>

</div>

<?php
include 'footer.php';
exit();
?>
