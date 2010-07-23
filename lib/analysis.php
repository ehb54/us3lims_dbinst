<?php
/*
 * demo_ana.lib.php
 *
 * Controls that are common to all the demo_ana programs
 *
 */

// Functions common to all demo_ana programs

// Function to display monte carlo option
function montecarlo()
{
echo<<<HTML
    <fieldset style="background: #eeeeee">
      <legend>Monte Carlo Iterations</legend>
      <div class="slider" id="montecarlo-slider">
         <input class="slider-input" id="montecarlo-slider-input"/>
      </div>
      <br/>
      Value: <input id="montecarlo_value" size='5'
                    onchange="montecarlo.setValue(parseInt(this.value))"
                    name="montecarlo_value"
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
      <fieldset>
        <legend>Simulation Points</legend>
        <div class="slider" id="simpoints-slider">
          <input class="slider-input" id="simpoints-slider-input"/>
        </div>
        <br/>
        Value: <input id="simpoints-value" 
                      onchange="simpoints.setValue(parseInt(this.value))" 
                      name="simpoints-value" 
                      value="simpoints.setValue(parseInt(this.value))"/>
        Minimum: <input id="simpoints-min" 
                      onchange="simpoints.setMinimum(parseInt(this.value))" 
                      disabled="disabled"/>
        Maximum: <input id="simpoints-max" 
                      onchange="simpoints.setMaximum(parseInt(this.value))" 
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display band loading volume input (in ml)
function band_volume_input()
{
echo<<<HTML
      <fieldset>
        <legend>Band Loading Volume</legend>
        <div class="slider" id="band_volume-slider">
          <input class="slider-input" id="band_volume-slider-input"/>
        </div>
        <br/>
        Value: <input id="band_volume-value" 
                      onchange="band_volume.setValue(parseInt(this.value))" 
                      name="band_volume-value" 
                      value="band_volume.setValue(parseInt(this.value))"/>
        Minimum: <input id="band_volume-min" 
                      onchange="band_volume.setMinimum(parseInt(this.value))" 
                      disabled="disabled"/>
        Maximum: <input id="band_volume-max" 
                      onchange="band_volume.setMaximum(parseInt(this.value))" 
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display radial grid input
function radial_grid_input()
{
echo<<<HTML
      <fieldset style="background: #eeeeee">
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
        <fieldset style="background: #eeeeee">
          <legend>Time Grid</legend>
          <input type="radio" name="time_grid" 
                      value="1" checked='checked' /> ASTFEM<br/>
          <input type="radio" name="time_grid" 
                      value="0" /> Constant<br/>
        </fieldset>
HTML;
}

// Functions in the 2DSA or 2DSA_MW programs

// Function to display s-value setup
function s_value_setup()
{
echo<<<HTML
    <fieldset style="background: #eeeeee">
       <legend>S-Value Setup</legend>
       <input type="text" value="1" name="s_value_min"/> S-Value Minimum<br/>
       <input type="text" value="10" name="s_value_max"/> S-Value Maximum<br/>
       <input type="text" value="10" name="s_value_res"/> S-Value Resolution<br/>
    </fieldset>
HTML;
}
 
// Function to display MW_constraint setup
function mw_constraint()
{
echo<<<HTML
    <fieldset style="background: #eeeeee" id='mw_constraints'>
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
    <fieldset style="background: #eeeeee">
      <legend>f/f0 Setup</legend>
      <input type="text" value="1" name="ff0_min"/> f/f0 minimum<br/>
      <input type="text" value="4" name="ff0_max"/> f/f0 maximum<br/>
      <input type="text" value="10" name="ff0_resolution"/> f/f0 resolution<br/>
    </fieldset>
HTML;
}

// Function to display uniform grid setup
function uniform_grid_setup()
{
echo<<<HTML
    <fieldset style="background: #eeeeee">
      <legend>Uniform Grid Repetitions Setup</legend>
      <input type="text" value="6" name="uniform_grid"/> Uniform Grid Repetitions<br/>
    </fieldset>
HTML;
}

// Function to display the time invariant noise option
function tinoise_option()
{
echo<<<HTML
     <fieldset style="background: #eeeeee">
      <legend>Fit Time Invariant Noise</legend>
      <input type="radio" name="tinoise_option" value="1"/> On<br/>
      <input type="radio" name="tinoise_option" value="0" checked='checked'/> Off<br/>
    </fieldset>
HTML;
}

// Function to display the regularization setup
function regularization_setup()
{
echo<<<HTML
      <fieldset style="background: #eeeeee">
        <legend>Regularization Setup</legend>
        <input type="text" value="0" name="regularization"/> Regularization<br/>
      </fieldset>
HTML;
}
 
// Function to display the fit meniscus option
function fit_meniscus()
{
echo<<<HTML
      <fieldset style="background: #eeeeee">
        <legend>Fit Meniscus</legend>
        <input type="radio" name="meniscus_option" value="1" onclick="show_ctl(5);"/> On<br/>
        <input type="radio" name="meniscus_option" value="0" onclick="hide(5);" 
               checked='checked'/> Off<br/>
               
        <div style="display:none" id="mag5">
          <br/>
          <input type="text" name="meniscus-range" value="0.01"/> Meniscus Fit Range (cm)<br/>
          <br/>
          <fieldset>
            <legend>Meniscus Grid Points</legend>
            <div class="slider" id="meniscus-slider">
               <input class="slider-input" id="meniscus-slider-input"/>
            </div>
            <br/>
            Value: <input id="meniscus-value" 
                          onchange="meniscus.setValue(parseInt(this.value))" 
                          name="meniscus-value" 
                          value="meniscus.setValue(parseInt(this.value))"/>
                          
            Minimum: <input id="meniscus-min" 
                            onchange="meniscus.setMinimum(parseInt(this.value))" 
                            disabled="disabled"/>
                            
            Maximum: <input id="meniscus-max" 
                            onchange="meniscus.setMaximum(parseInt(this.value))" 
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
      <fieldset style="background: #eeeeee">
        <legend>Use Iterative Method</legend>
        <input type="radio" name="iterations_option" value="1" 
               onclick="show_ctl(6);"/> On<br/>
        <input type="radio" name="iterations_option" value="0" 
               onclick="hide(6);" checked='checked'/> Off<br/>
        <div style="display:none" id="mag6">
          <br/>
          <fieldset>
            <legend>Number of Iterations</legend>
            <div class="slider" id="iterations-slider">
               <input class="slider-input" id="iterations-slider-input"/>
            </div>
            <br/>
            Value: <input id="iterations-value" 
                          onchange="iterations.setValue(parseInt(this.value))" 
                          name="iterations-value" 
                          value="iterations.setValue(parseInt(this.value))"/>
                       
            Minimum: <input id="iterations-min" 
                            onchange="iterations.setMinimum(parseInt(this.value))" 
                            disabled="disabled"/>
                      
            Maximum: <input id="iterations-max" 
                            onchange="iterations.setMaximum(parseInt(this.value))" 
                            disabled="disabled"/>                                        
          </fieldset>
        </div>
      </fieldset>
HTML;
}
 
// Function to display the radially-invariant noice option
function rinoise_option()
{
echo<<<HTML
      <fieldset style="background: #eeeeee">
        <legend>Fit Radially Invariant Noise</legend>
        <input type="radio" name="rinoise_option" value="1"/>&nbsp; On<br/>
        <input type="radio" name="rinoise_option" value="0" checked='checked'/>&nbsp; Off<br/>
      </fieldset>
HTML;
}

// Function to display the demes input
function demes_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Demes</legend>

        <div class="slider" id="demes-slider">
          <input class="slider-input" id="demes-slider-input"/>
        </div>

        <br/>
        Value: <input name="demes-value"
                      value="demes.setValue(parseInt(this.value))" 
                      id="demes-value"
                      onchange="demes.setValue(parseInt(this.value))"/>
        Minimum: <input id="demes-min"
                      onchange="demes.setMinimum(parseInt(this.value))" 
                      disabled="disabled"/>
        Maximum: <input id="demes-max"
                      onchange="demes.setMaximum(parseInt(this.value))" 
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the population size input
function genes_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Population Size</legend>
        <div class="genes" id="genes-slider">
          <input class="slider-input" id="genes-slider-input"/>
        </div>
        <br/>

        Value: <input name="genes-value"
                      value="genes.setValue(parseInt(this.value))" 
                      id="genes-value"
                      onchange="genes.setValue(parseInt(this.value))"/>
        Minimum: <input id="genes-min"
                      onchange="genes.setMinimum(parseInt(this.value))" 
                      disabled="disabled"/>
        Maximum: <input id="genes-max"
                      onchange="genes.setMaximum(parseInt(this.value))"
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the generations input
function generations_setup()
{
echo<<<HTML
       <fieldset>
        <legend>Generations</legend>
        <div class="slider" id="generations-slider">
          <input class="slider-input" id="generations-slider-input"/>
        </div>
        <br/>
        Value: <input name="generations-value"
                      value="s.setValue(parseInt(this.value))" 
                      id="h-value" 
                      onchange="s.setValue(parseInt(this.value))"/>
        Minimum: <input id="h-min" 
                      onchange="s.setMinimum(parseInt(this.value))" 
                      disabled="disabled"/>
        Maximum: <input id="h-max" 
                      onchange="s.setMaximum(parseInt(this.value))" 
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the crossover percent setup
function crossover_percent()
{
echo<<<HTML
      <fieldset>
        <legend>Crossover Percent</legend>
        <div class="slider" id="crossover-slider">
          <input class="slider-input" id="crossover-slider-input"/>
        </div>
        <br/>
        Value: <input name="crossover-value" 
                      value="crossover.setValue(parseInt(this.value))" 
                      id="crossover-value" 
                      onchange="crossover.setValue(parseInt(this.value))"/>
        Minimum: <input id="crossover-min" 
                      onchange="crossover.setMinimum(parseInt(this.value))" 
                      disabled="disabled"/>
        Maximum: <input id="crossover-max" 
                      onchange="crossover.setMaximum(parseInt(this.value))" 
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the mutation percent setup
function mutation_percent()
{
echo<<<HTML
      <fieldset>
        <legend>Mutation Percent</legend>
        <div class="slider" id="mutation-slider">
          <input class="slider-input" id="mutation-slider-input"/>
        </div>
        <br/>
        Value: <input name="mutation-value" 
                      value="mutation.setValue(parseInt(this.value))" 
                      id="mutation-value" 
                      onchange="mutation.setValue(parseInt(this.value))"/>
        Minimum: <input id="mutation-min" 
                      onchange="mutation.setMinimum(parseInt(this.value))" 
                      disabled="disabled"/>
        Maximum: <input id="mutation-max" 
                      onchange="mutation.setMaximum(parseInt(this.value))" 
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the plague percent setup
function plague_percent()
{
echo<<<HTML
      <fieldset>
        <legend>Plague Percent</legend>
        <div class="slider" id="plague-slider">
          <input class="slider-input" id="plague-slider-input"/>
        </div>
        <br/>
        Value: <input id="plague-value" 
                      onchange="plague.setValue(parseInt(this.value))" 
                      name="plague-value" 
                      value="plague.setValue(parseInt(this.value))"/>
        Minimum: <input id="plague-min" 
                      onchange="plague.setMinimum(parseInt(this.value))" 
                      disabled="disabled"/>
        Maximum: <input id="plague-max" 
                      onchange="plague.setMaximum(parseInt(this.value))" 
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the elitism setup
function elitism_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Elitism</legend>
        <div class="slider" id="elitism-slider">
          <input class="slider-input" id="elitism-slider-input"/>
        </div>
        <br/>
        Value: <input id="elitism-value" 
                      onchange="elitism.setValue(parseInt(this.value))" 
                      name="elitism-value" 
                      value="elitism.setValue(parseInt(this.value))"/>
        Minimum: <input id="elitism-min" 
                      onchange="elitism.setMinimum(parseInt(this.value))" 
                      disabled="disabled"/>
        Maximum: <input id="elitism-max" 
                      onchange="elitism.setMaximum(parseInt(this.value))" 
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the setup of the migration rate
function migration_rate()
{
echo<<<HTML
      <fieldset>
        <legend>Migration Rate</legend>
        <div class="slider" id="migration-slider">
          <input class="slider-input" id="migration-slider-input"/>
        </div>
        <br/>
        Value: <input id="migration-value" 
                      onchange="migration.setValue(parseInt(this.value))" 
                      name="migration-value" 
                      value="migration.setValue(parseInt(this.value))"/>
        Minimum: <input id="migration-min" 
                      onchange="migration.setMinimum(parseInt(this.value))" 
                      disabled="disabled"/>
        Maximum: <input id="migration-max" 
                      onchange="migration.setMaximum(parseInt(this.value))" 
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the regularization setup for GA methods
function ga_regularization_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Regularization</legend>
        <div class="slider" id="regularization-slider">
          <input class="slider-input" id="regularization-slider-input"/>
        </div>
        <br/>
        Value: <input id="regularization-value" 
                      onchange="regularization.setValue(parseInt(this.value))" 
                      name="regularization-value" 
                      value="regularization.setValue(parseInt(this.value))"/>
        Minimum: <input id="regularization-min" 
                      onchange="regularization.setMinimum(parseInt(this.value))" 
                      disabled="disabled"/>
        Maximum: <input id="regularization-max" 
                      onchange="regularization.setMaximum(parseInt(this.value))" 
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the random seed input
function random_seed()
{
echo<<<HTML
      <fieldset>
        <legend>Random Seed</legend>
        <div class="slider" id="seed-slider">
          <input class="slider-input" id="seed-slider-input"/>
        </div>
        <br/>
        Value: <input id="seed-value" 
                      onchange="seed.setValue(parseInt(this.value))" 
                      name="seed-value" 
                      value="seed.setValue(parseInt(this.value))"/>
        Minimum: <input id="seed-min" 
                      onchange="seed.setMinimum(parseInt(this.value))" 
                      disabled="disabled"/>
        Maximum: <input id="seed-max" 
                      onchange="seed.setMaximum(parseInt(this.value))" 
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the GA time-invariant noise option
function ga_tinoise_option()
{
echo<<<HTML
        <fieldset style="background: #eeeeee">
          <legend>Fit Time Invariant Noise</legend>
          <input type="radio" name="ga_tinoise_option" 
                      value="1"/> On<br/>
          <input type="radio" name="ga_tinoise_option" 
                      value="0" checked='checked'/> Off<br/>
        </fieldset>
HTML;
}

// Function to display the ga radially-invariant noise option
function ga_rinoise_option()
{
echo<<<HTML
        <fieldset style="background: #eeeeee">
          <legend>Fit Radially Invariant Noise</legend>
          <input type="radio" name="ga_rinoise_option" 
                      value="1"/> On<br/>
          <input type="radio" name="ga_rinoise_option" 
                      value="0" checked='checked'/> Off<br/>
        </fieldset>
HTML;
}

// Function to display the fit meniscus setup
function ga_fit_meniscus_option()
{
echo<<<HTML
        <fieldset style="background: #eeeeee">
          <legend>Fit Meniscus</legend>
          <input type="radio" name="ga_meniscus_option" 
                      value="1" onclick="show_ctl(5);"/> On<br/>
          <input type="radio" name="ga_meniscus_option" 
                      value="0" onclick="hide(5);" 
                      checked='checked'/> Off<br/>
          <div style="display:none" id="mag5">
            <input type="text" name="ga_meniscus-range" 
                      value="0.000"/> Meniscus Fit Range (cm)<br/>
          </div>
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

  if ( $max_size < 512000 ) return;

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
?>
