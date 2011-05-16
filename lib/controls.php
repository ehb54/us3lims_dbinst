<?php
/*
 * controls.php
 *
 * functions relating to the display of controls in the html window
 *
 */

// These are controls that can be displayed in multiple analysis methods

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
                      disabled="disabled"/>

      Maximum: <input id="montecarlo-max" size='5'
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
                      disabled="disabled"/>
        Maximum: <input id="simpoints-max"  size='5'
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
                      disabled="disabled"/>
        Maximum: <input id="band_volume-max"  size='5'
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

// Controls that are used in the 2DSA Analysis

// Function to display s-value setup
function s_value_setup()
{
echo<<<HTML
    <fieldset class='option_value'class='option_value'>
       <legend>S-Value Setup</legend>
       <input type="text" value="1" name="s_value_min"/> S-Value Minimum<br/>
       <input type="text" value="10" name="s_value_max"/> S-Value Maximum<br/>
       <input type="text" value="10" name="s_resolution"/> S-Value Resolution (# of grid points)<br/>
    </fieldset>
HTML;
}
 
// Function to display MW_constraint setup
function mw_constraint()
{
echo<<<HTML
    <fieldset class='option_value' id='mw_constraints'>
       <legend>Molecular Weight Constraints</legend>
       <input type="text" value="100" name="mw_value_min"/> Molecular Weight Minimum (Daltons)<br/>
       <input type="text" value="1000" name="mw_value_max"/> Molecular Weight Maximum (Daltons)<br/>
       <input type="text" value="10" name="grid_res"/> Grid Resolution<br/>
       <input type="text" value="4" name="oligomer" id="largest_oligomer"
              onchange='generate_oligomer_string();'/> Largest Oligomer<br/>
       <input type='checkbox' name='selectmonomer' value='1' id='selectmonomer'
              onchange='generate_oligomer_string();'/> Select Individual Monomers
    </fieldset>
HTML;
}

// function to display f/f0 setup
function f_f0_setup()
{
echo<<<HTML
    <fieldset class='option_value'>
      <legend>f/f0 Setup</legend>
      <input type="text" value="1" name="ff0_min"/> f/f0 minimum<br/>
      <input type="text" value="4" name="ff0_max"/> f/f0 maximum<br/>
      <input type="text" value="10" name="ff0_resolution"/> f/f0 Resolution (# of grid points)<br/>
    </fieldset>
HTML;
}

// Function to display uniform grid setup
function uniform_grid_setup()
{
echo<<<HTML
    <fieldset class='option_value'>
      <legend>Uniform Grid Repetitions Setup</legend>
      <input type="text" value="6" name="uniform_grid"/> Uniform Grid Repetitions<br/>
    </fieldset>
HTML;
}

// Function to display the time invariant noise option
function tinoise_option()
{
echo<<<HTML
     <fieldset class='option_value'>
      <legend>Fit Time Invariant Noise</legend>
      <input type="radio" name="tinoise_option" value="1"/> On<br/>
      <input type="radio" name="tinoise_option" value="0" checked='checked'/> Off<br/>
    </fieldset>
HTML;
}

// Function to display the radially-invariant noise option
function rinoise_option()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Fit Radially Invariant Noise</legend>
        <input type="radio" name="rinoise_option" value="1"/>&nbsp; On<br/>
        <input type="radio" name="rinoise_option" value="0" checked='checked'/>&nbsp; Off<br/>
      </fieldset>
HTML;
}

// Function to display the fit meniscus option
function fit_meniscus()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Fit Meniscus</legend>
        <input type="radio" name="meniscus_option" value="1" onclick="show_ctl(5);"/> On<br/>
        <input type="radio" name="meniscus_option" value="0" onclick="hide(5);" 
               checked='checked'/> Off<br/>
               
        <div style="display:none" id="mag5">
          <br/>
          <input type="text" name="meniscus_range" value="0.01"/>Meniscus Fit Range (cm)<br/>
          <br/>
          <fieldset>
            <legend>Meniscus Point Count</legend>
            <div class="slider" id="meniscus-slider">
               <input class="slider-input" id="meniscus-slider-input"/>
            </div>
            <br/>
            Value: <input id="meniscus_points" size='5'
                          onchange="meniscus.setValue(parseInt(this.value))" 
                          name="meniscus_points" 
                          value="meniscus.setValue(parseInt(this.value))"/>
                          
            Minimum: <input id="meniscus-min" size='5'
                            disabled="disabled"/>
                            
            Maximum: <input id="meniscus-max" size='5'
                            disabled="disabled"/>
          </fieldset>
        </div>
      </fieldset>
HTML;
}
 
// Function to display the iterations option
function iterations_option()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Use Iterative Method</legend>
        <input type="radio" name="iterations_option" value="1" 
               onclick="show_ctl(6);"/> On<br/>
        <input type="radio" name="iterations_option" value="0" 
               onclick="hide(6);" checked='checked'/> Off<br/>
        <div style="display:none" id="mag6">
          <br/>
          <fieldset>
            <legend>Maximum Number of Iterations</legend>
            <div class="slider" id="iterations-slider">
               <input class="slider-input" id="iterations-slider-input"/>
            </div>
            <br/>
            Value: <input id="max_iterations" size='5'
                          onchange="iterations.setValue(parseInt(this.value))" 
                          name="max_iterations" 
                          value="iterations.setValue(parseInt(this.value))"/>
                       
            Minimum: <input id="iterations-min" size='5'
                            disabled="disabled"/>
                      
            Maximum: <input id="iterations-max" size='5'
                            disabled="disabled"/>                                        
          </fieldset>
        </div>
      </fieldset>
HTML;
}

// Controls that are used in the GA analysis

// Function to display the demes input
function demes_setup()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Demes</legend>

        <div class="slider" id="demes-slider">
          <input class="slider-input" id="demes-slider-input"/>
        </div>

        <br/>
        Value: <input name="demes-value" size='5'
                      value="demes.setValue(parseInt(this.value))" 
                      id="demes-value"
                      onchange="demes.setValue(parseInt(this.value))"/>
        Minimum: <input id="demes-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="demes-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the population size input
function genes_setup()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Population Size</legend>
        <div class="genes" id="genes-slider">
          <input class="slider-input" id="genes-slider-input"/>
        </div>
        <br/>

        Value: <input name="genes-value" size='5'
                      value="genes.setValue(parseInt(this.value))" 
                      id="genes-value"
                      onchange="genes.setValue(parseInt(this.value))"/>
        Minimum: <input id="genes-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="genes-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the generations input
function generations_setup()
{
echo<<<HTML
       <fieldset class='option_value'>
        <legend>Generations</legend>
        <div class="slider" id="generations-slider">
          <input class="slider-input" id="generations-slider-input"/>
        </div>
        <br/>
        Value: <input name="generations-value" size='5'
                      value="s.setValue(parseInt(this.value))" 
                      id="h-value" 
                      onchange="s.setValue(parseInt(this.value))"/>
        Minimum: <input id="h-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="h-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the crossover percent setup
function crossover_percent()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Crossover Percent</legend>
        <div class="slider" id="crossover-slider">
          <input class="slider-input" id="crossover-slider-input"/>
        </div>
        <br/>
        Value: <input name="crossover-value" size='5'
                      value="crossover.setValue(parseInt(this.value))" 
                      id="crossover-value" 
                      onchange="crossover.setValue(parseInt(this.value))"/>
        Minimum: <input id="crossover-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="crossover-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the mutation percent setup
function mutation_percent()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Mutation Percent</legend>
        <div class="slider" id="mutation-slider">
          <input class="slider-input" id="mutation-slider-input"/>
        </div>
        <br/>
        Value: <input name="mutation-value" size='5'
                      value="mutation.setValue(parseInt(this.value))" 
                      id="mutation-value" 
                      onchange="mutation.setValue(parseInt(this.value))"/>
        Minimum: <input id="mutation-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="mutation-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the plague percent setup
function plague_percent()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Plague Percent</legend>
        <div class="slider" id="plague-slider">
          <input class="slider-input" id="plague-slider-input"/>
        </div>
        <br/>
        Value: <input id="plague-value" size='5'
                      onchange="plague.setValue(parseInt(this.value))" 
                      name="plague-value" 
                      value="plague.setValue(parseInt(this.value))"/>
        Minimum: <input id="plague-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="plague-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the elitism setup
function elitism_setup()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Elitism</legend>
        <div class="slider" id="elitism-slider">
          <input class="slider-input" id="elitism-slider-input"/>
        </div>
        <br/>
        Value: <input id="elitism-value" size='5'
                      onchange="elitism.setValue(parseInt(this.value))" 
                      name="elitism-value" 
                      value="elitism.setValue(parseInt(this.value))"/>
        Minimum: <input id="elitism-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="elitism-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the setup of the migration rate
function migration_rate()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Migration Rate</legend>
        <div class="slider" id="migration-slider">
          <input class="slider-input" id="migration-slider-input"/>
        </div>
        <br/>
        Value: <input id="migration-value" size='5'
                      onchange="migration.setValue(parseInt(this.value))" 
                      name="migration-value" 
                      value="migration.setValue(parseInt(this.value))"/>
        Minimum: <input id="migration-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="migration-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the regularization setup for GA methods
function ga_regularization_setup()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Regularization</legend>
        <div class="slider" id="regularization-slider">
          <input class="slider-input" id="regularization-slider-input"/>
        </div>
        <br/>
        Value: <input id="regularization-value" size='5'
                      onchange="regularization.setValue(parseInt(this.value))" 
                      name="regularization-value" 
                      value="regularization.setValue(parseInt(this.value))"/>
        Minimum: <input id="regularization-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="regularization-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the random seed input
function random_seed()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Random Seed</legend>
        <div class="slider" id="seed-slider">
          <input class="slider-input" id="seed-slider-input"/>
        </div>
        <br/>
        Value: <input id="seed-value" size='5'
                      onchange="seed.setValue(parseInt(this.value))" 
                      name="seed-value" 
                      value="seed.setValue(parseInt(this.value))"/>
        Minimum: <input id="seed-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="seed-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the conc_threshold input
function conc_threshold_setup()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Concentration Threshold</legend>

        <div class="slider" id="conc_threshold-slider">
          <input class="slider_input" id="conc_threshold-slider-input"/>
        </div>

        <br/>
        Value: <input name="conc_threshold-value" size='5'
                      value="conc_threshold.setValue(parseInt(this.value))" 
                      id="conc_threshold-value"
                      onchange="conc_threshold.setValue(parseInt(this.value))"/>
        Minimum: <input id="conc_threshold-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="conc_threshold-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the s_grid input
function s_grid_setup()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>S Grid</legend>

        <div class="slider" id="s_grid-slider">
          <input class="slider-input" id="s_grid-slider-input"/>
        </div>

        <br/>
        Value: <input name="s_grid-value" size='5'
                      value="s_grid.setValue(parseInt(this.value))" 
                      id="s_grid-value"
                      onchange="s_grid.setValue(parseInt(this.value))"/>
        Minimum: <input id="s_grid-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="s_grid-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the k_grid input
function k_grid_setup()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>K Grid</legend>

        <div class="slider" id="k_grid-slider">
          <input class="slider-input" id="k_grid-slider-input"/>
        </div>

        <br/>
        Value: <input name="k_grid-value" size='5'
                      value="k_grid.setValue(parseInt(this.value))" 
                      id="k_grid-value"
                      onchange="k_grid.setValue(parseInt(this.value))"/>
        Minimum: <input id="k_grid-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="k_grid-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the mutate_sigma input
function mutate_sigma_setup()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Mutate Sigma</legend>

        <div class="slider" id="mutate_sigma-slider">
          <input class="slider-input" id="mutate_sigma-slider-input"/>
        </div>

        <br/>
        Value: <input name="mutate_sigma-value" size='5'
                      value="mutate_sigma.setValue(parseInt(this.value))" 
                      id="mutate_sigma-value"
                      onchange="mutate_sigma.setValue(parseInt(this.value))"/>
        Minimum: <input id="mutate_sigma-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="mutate_sigma-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the mutate_s input
function mutate_s_setup()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Mutate s</legend>

        <div class="slider" id="mutate_s-slider">
          <input class="slider-input" id="mutate_s-slider-input"/>
        </div>

        <br/>
        Value: <input name="mutate_s_value" size='5'
                      value="mutate_s.setValue(parseInt(this.value))" 
                      id="mutate_s_value"
                      onchange="mutate_s.setValue(parseInt(this.value))"/>
        Minimum: <input id="mutate_s-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="mutate_s-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the mutate_k input
function mutate_k_setup()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Mutate k</legend>

        <div class="slider" id="mutate_k-slider">
          <input class="slider-input" id="mutate_k-slider-input"/>
        </div>

        <br/>
        Value: <input name="mutate_k_value" size='5'
                      value="mutate_k.setValue(parseInt(this.value))" 
                      id="mutate_k_value"
                      onchange="mutate_k.setValue(parseInt(this.value))"/>
        Minimum: <input id="mutate_k-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="mutate_k-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the mutate_sk input
function mutate_sk_setup()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Mutate s and k</legend>

        <div class="slider" id="mutate_sk-slider">
          <input class="slider-input" id="mutate_sk-slider-input"/>
        </div>

        <br/>
        Value: <input name="mutate_sk_value" size='5'
                      value="mutate_sk.setValue(parseInt(this.value))" 
                      id="mutate_sk_value"
                      onchange="mutate_sk.setValue(parseInt(this.value))"/>
        Minimum: <input id="mutate_sk-min" size='5'
                      disabled="disabled"/>
        Maximum: <input id="mutate_sk-max" size='5'
                      disabled="disabled"/>
      </fieldset>
HTML;
}

?>
