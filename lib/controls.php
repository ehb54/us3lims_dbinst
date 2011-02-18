<?php
/*
 * controls.php
 *
 * base class relating to the display of controls in the html window
 *
 */

abstract class Controls
{
  abstract public function pageTitle();

  // Function to display monte carlo option
  function montecarlo()
  {
  echo<<<HTML
      <fieldset class='option_value'>
        <legend>Monte Carlo Iterations</legend>
        <div class="slider" id="montecarlo-slider">
           <input class="slider-input" id="montecarlo-slider-input"/>
        </div>
        <br/>
        Value: <input id="mc_iterations" size='5'
                      onchange="montecarlo.setValue(parseInt(this.value))"
                      name="mc_iterations"
                      value="montecarlo.setValue(parseInt(this.value))"/>

        Minimum: <input id="montecarlo-min" size='5'
                        onchange="montecarlo.setMinimum(parseInt(this.value))"
                        disabled="disabled"/>

        Maximum: <input id="montecarlo-max" size='5'
                        onchange="montecarlo.setMaximum(parseInt(this.value))"
                        disabled="disabled"/>
      </fieldset>
HTML;

  }

  // Function to display # simulation points input
  function simpoints_input()
  {
  echo<<<HTML
        <fieldset class='option_value'>
          <legend>Simulation Points</legend>
          <div class="slider" id="simpoints-slider">
            <input class="slider-input" id="simpoints-slider-input"/>
          </div>
          <br/>
          Value: <input id="simpoints-value"  size='5'
                        onchange="simpoints.setValue(parseInt(this.value))" 
                        name="simpoints-value" 
                        value="simpoints.setValue(parseInt(this.value))"/>
          Minimum: <input id="simpoints-min"  size='5'
                        onchange="simpoints.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="simpoints-max"  size='5'
                        onchange="simpoints.setMaximum(parseInt(this.value))" 
                        disabled="disabled"/>
        </fieldset>
HTML;
  }

  // Function to display band loading volume input (in ml)
  function band_volume_input()
  {
  echo<<<HTML
        <fieldset class='option_value'>
          <legend>Band Loading Volume</legend>
          <div class="slider" id="band_volume-slider">
            <input class="slider-input" id="band_volume-slider-input"/>
          </div>
          <br/>
          Value: <input id="band_volume-value"  size='5'
                        onchange="band_volume.setValue(parseInt(this.value))" 
                        name="band_volume-value" 
                        value="band_volume.setValue(parseInt(this.value))"/>
          Minimum: <input id="band_volume-min"  size='5'
                        onchange="band_volume.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="band_volume-max"  size='5'
                        onchange="band_volume.setMaximum(parseInt(this.value))" 
                        disabled="disabled"/>
        </fieldset>
HTML;
  }

  // Function to display radial grid input
  function radial_grid_input()
  {
  echo<<<HTML
        <fieldset class='option_value'>
          <legend>Radial Grid</legend>
            <select name="radial_grid">
              <option value="0" selected="selected">ASTFEM</option>
              <option value="1">Claverie</option>
              <option value="2">Moving hat</option>
            </select>
        </fieldset>
HTML;
  }

  // Function to display time grid input
  function time_grid_input()
  {
  echo<<<HTML
          <fieldset class='option_value'>
            <legend>Time Grid</legend>
            <input type="radio" name="time_grid"  size='5'
                        value="1" checked='checked' /> ASTFEM<br/>
            <input type="radio" name="time_grid"  size='5'
                        value="0" /> Constant<br/>
          </fieldset>
HTML;
  }

  // Function to check the file sizes
  function check_filesize()
  {
    // check sizes of the files we're submitting
    $max_size = 0;
    foreach ( $_SESSION['request'] as $cellinfo )
    {
      $filename = $cellinfo['path'] . '/' . $cellinfo['filename'];
      $file_size = filesize( $filename );
      if ( $file_size > $max_size ) $max_size = $file_size;
    }

    if ( $max_size < 2000 ) return;

    include 'config.php';

    // Display error page
    $page_title = "Job Rejected";
    include 'top.php';
    include 'links.php';

    echo <<<HTML
    <!-- Begin page content -->
    <div id='content'>

      <h1 class="title">Job Rejected</h1>
      <!-- Place page content here -->

      <p>Your dataset exceeds 0.5 MB in size. This exceeds the currently
         allowed dataset. Please re-edit your data and reduce the number
         of scans included by either deleting baseline scans at the end
         of the experiment or by using the "Scan Exclusion Profile" editor.</p>  

      <p>If you have any questions about this policy please contact
         Borries Demeler (<a href="mailto:demeler@biochem.uthscsa.edu">
         demeler@biochem.uthscsa.edu</a>).</p>

      <p><a href="queue_setup_1.php">Submit another request</a></p>

    </div>

HTML;

    include 'bottom.php';
  }
}
?>
