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
      <fieldset>
        <legend>Monte Carlo Iterations</legend>
        <div class='newslider' id='montecarlo-slider'></div>
        <br />
        Value:   <input name='mc_iterations' 
                        id='mc_iterations'
                        value='1' />
        Minimum: <input id="montecarlo-min" 
                        disabled="disabled" />
        Maximum: <input id="montecarlo-max" 
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display PMGC options
function PMGC_option()
{
echo<<<HTML
      <fieldset>
        <legend>Parallel Masters Group Option</legend>

        <table class='noborder' >
        <tr><td>

        <input type='checkbox' name='PMGC_enable' id='PMGC_enable' /> 
          Use Parallel Processing
        <br/>
        <fieldset name='PMGC_count' id='PMGC_count' style='display:none;'>
          <legend>Group Count</legend>
            <select name="req_mgroupcount" id='req_mgroupcount'>
              <option value='1' selected='selected'>1</option>
              <option value='2'>2</option>
              <option value='4'>4</option>
              <option value='8'>8</option>
            </select>
        </fieldset>

        </td>

        <td style='width:50%;'>
          Note: The actual group count number is dependent on the capacity and the 
          architecture of the selected back end system. Generally, the group count number
          will be adjusted downward to 1, 2, 4, or 8 depending on the number of montecarlo 
          iterations requested and the capacity of the system. Currently, the maximum 
          number of cores that can be requested ( that is, MC x PMGC ) are as follows:
          <table class='noborder' style='margin:0px auto;'>
            <tr><th>Cluster</th><th>Max cores</th></tr>
            <tr><td>stampede</td><td style='text-align:center;'>256</td></tr>
            <tr><td>lonestar</td><td style='text-align:center;'>288</td></tr>
            <tr><td>trestles</td><td style='text-align:center;'>256</td></tr>
            <tr><td>alamo</td><td style='text-align:center;'>(no PMG)</td></tr>
            <tr><td>bcf</td><td style='text-align:center;'>(no PMG)</td></tr>
          </table>
        </td></tr>

        </table>

      </fieldset>
HTML;
}

// Function to display # simulation points input
function simpoints_input()
{
echo<<<HTML
      <fieldset>
        <legend>Simulation Points</legend>
        <div class='newslider' id='simpoints-slider'></div>
        <br />
        Value:   <input name='simpoints-value' 
                        id='simpoints-value'
                        value='200' />
        Minimum: <input id="simpoints-min" 
                        disabled="disabled" />
        Maximum: <input id="simpoints-max" 
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display band loading volume input (in ml)
function band_volume_input()
{
echo<<<HTML
      <fieldset>
        <legend>Band Loading Volume</legend>
        <div class='newslider' id='band_volume-slider'></div>
        <br />
        Value:   <input name='band_volume-value' 
                        id='band_volume-value'
                        value='0.015' />
        Minimum: <input id="band_volume-min" 
                        disabled="disabled" />
        Maximum: <input id="band_volume-max" 
                        disabled="disabled" />
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

// Function to display debug options
function debug_option()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Debug Options</legend>
        <input type='checkbox' name='debug_timings' id='debug_timings'/> 
          Debug Timings
        <br/>
        <fieldset>
          <legend>Debug Level</legend>
          <div class="newslider" id="debug_level-slider"></div>
          <br/>
          Value:   <input name='debug_level-value'
                          id='debug_level-value'
                          value='0' />
          Minimum: <input id='debug_level-min'
                          disabled='disabled' />
          Maximum: <input id='debug_level-max'
                          disabled='disabled' />

        </fieldset>
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

// Function to display s grid points
function s_grid_points()
{
echo<<<HTML
    <fieldset class='option_value'>
       <legend>S-Value Resolution</legend>
       <input type="text" value="1" name="s_value_min"/> S-Value Minimum<br/>
       <input type="text" value="10" name="s_value_max"/> S-Value Maximum<br/>
       <input type="text" value="60" name="s_grid_points"/> S-Value Resolution (total grid points)<br/>
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
function ff0_grid_points()
{
echo<<<HTML
    <fieldset class='option_value'>
      <legend>f/f0 Resolution</legend>
      <input type="text" value="1" name="ff0_min"/> f/f0 Minimum<br/>
      <input type="text" value="4" name="ff0_max"/> f/f0 Maximum<br/>
      <input type="text" value="60" name="ff0_grid_points"/> f/f0 Resolution (total grid points)<br/>
    </fieldset>
HTML;
}

// Function to display a custom grid model dropdown
function CG_select_setup()
{
  $personID = $_SESSION['id'];
  $query  = "SELECT model.modelID, description, lastUpdated " .
            "FROM modelPerson, model " .
            "WHERE personID = $personID " .
            "AND modelPerson.modelID = model.modelID " .
            "AND description LIKE '%CustomGrid%' " .
            "ORDER BY lastUpdated DESC ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  if ( mysql_num_rows( $result ) == 0 )
  {
    echo <<<HTML
      <fieldset class='option_value'>
        <legend>Custom Grid Model</legend>
        <p>There are no custom grid models available</p>
      </fieldset>
HTML;

    return;
}

  $models = '';
  while ( list( $modelID, $description, $lastUpdated ) = mysql_fetch_array( $result ) )
  {
    $descr = explode( ".", $description );
    array_pop( $descr );                  // pop off the .model part
    $description = implode( ".", $descr );
    $models .= "            <option value='$modelID'>$lastUpdated $description</option>\n";
  }
 
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Custom Grid Model</legend>
          <select name="CG_modelID">
            $models
          </select>
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
          <input type="text" name="meniscus_range" value="0.03"/>Meniscus Fit Range (cm)<br/>
          <br/>
          <fieldset>
            <legend>Meniscus Grid Points</legend>
            <div class='newslider' id='meniscus-slider'></div>
            <br />
            Value:   <input name='meniscus_points' 
                            id='meniscus_points'
                            value='10' />
            Minimum: <input id="meniscus-min" 
                            disabled="disabled" />
            Maximum: <input id="meniscus-max" 
                            disabled="disabled" />
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
            <div class='newslider' id='iterations-slider'></div>
            <br />
            Value:   <input name='max_iterations' 
                            id='max_iterations'
                            value='3' />
            Minimum: <input id="iterations-min" 
                            disabled="disabled" />
            Maximum: <input id="iterations-max" 
                            disabled="disabled" />
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

        <table class='noborder' >
        <tr><td>

        <div class='newslider' id='demes-slider'></div>
        <br />
        Value:   <input name='demes-value' 
                        id='demes-value'
                        value='31' />
        Minimum: <input id="demes-min" 
                        disabled="disabled" />
        Maximum: <input id="demes-max" 
                        disabled="disabled" />

        </td>

        <td style='width:50%;'>
          Note: The actual number of
          demes is dependent on the capacity and the architecture of the selected back end system.  
          Generally, demes + 1 will be adjusted upward in units of 8, 12, or 16, but limited
          to the capacity of the system. Currently, the maximum demes values are as follows:
          <table class='noborder' style='margin:0px auto;'>
            <tr><th>Cluster</th><th>Max demes</th></tr>
            <tr><td>bcf</td><td style='text-align:center;'>31</td></tr>
            <tr><td>alamo</td><td style='text-align:center;'>51</td></tr>
            <tr><td>stampede</td><td style='text-align:center;'>63</td></tr>
            <tr><td>lonestar</td><td style='text-align:center;'>35</td></tr>
            <tr><td>trestles</td><td style='text-align:center;'>63</td></tr>
            <tr><td>gordon</td><td style='text-align:center;'>63</td></tr>
          </table>
        </td></tr>

        </table>

      </fieldset>
HTML;
}

// Function to display the population size input
function genes_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Population Size</legend>
        <div class='newslider' id='genes-slider'></div>
        <br />
        Value:   <input name='genes-value' 
                        id='genes-value'
                        value='100' />
        Minimum: <input id="genes-min" 
                        disabled="disabled" />
        Maximum: <input id="genes-max" 
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display the generations input
function generations_setup()
{
echo<<<HTML
       <fieldset>
        <legend>Generations</legend>
        <div class='newslider' id='generations-slider'></div>
        <br />
        Value:   <input name='generations-value' 
                        id='generations-value'
                        value='100' />
        Minimum: <input id="generations-min" 
                        disabled="disabled" />
        Maximum: <input id="generations-max" 
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display the crossover percent setup
function crossover_percent()
{
echo<<<HTML
      <fieldset>
        <legend>Crossover Percent</legend>
        <div class='newslider' id='crossover-slider'></div>
        <br />
        Value:   <input name='crossover-value' 
                        id='crossover-value'
                        value='50' />
        Minimum: <input id="crossover-min" 
                        disabled="disabled" />
        Maximum: <input id="crossover-max" 
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display the mutation percent setup
function mutation_percent()
{
echo<<<HTML
      <fieldset>
        <legend>Mutation Percent</legend>
        <div class='newslider' id='mutation-slider'></div>
        <br />
        Value:   <input name='mutation-value' 
                        id='mutation-value'
                        value='50' />
        Minimum: <input id="mutation-min" 
                        disabled="disabled" />
        Maximum: <input id="mutation-max" 
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display the plague percent setup
function plague_percent()
{
echo<<<HTML
      <fieldset>
        <legend>Plague Percent</legend>
        <div class='newslider' id='plague-slider'></div>
        <br />
        Value:   <input name='plague-value' 
                        id='plague-value'
                        value='4' />
        Minimum: <input id="plague-min" 
                        disabled="disabled" />
        Maximum: <input id="plague-max" 
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display the elitism setup
function elitism_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Elitism</legend>
        <div class='newslider' id='elitism-slider'></div>
        <br />
        Value:   <input name='elitism-value' 
                        id='elitism-value'
                        value='2' />
        Minimum: <input id="elitism-min" 
                        disabled="disabled" />
        Maximum: <input id="elitism-max" 
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display the setup of the migration rate
function migration_rate()
{
echo<<<HTML
      <fieldset>
        <legend>Migration Rate</legend>
        <div class='newslider' id='migration-slider'></div>
        <br />
        Value:   <input name='migration-value' 
                        id='migration-value'
                        value='3' />
        Minimum: <input id="migration-min" 
                        disabled="disabled" />
        Maximum: <input id="migration-max" 
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display the regularization setup for GA methods
function ga_regularization_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Regularization (in %)</legend>
        <div class='newslider' id='regularization-slider'></div>
        <br />
        Value:   <input name='regularization-value' 
                        id='regularization-value'
                        value='5' />
        Minimum: <input id="regularization-min" 
                        disabled="disabled" />
        Maximum: <input id="regularization-max" 
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display the random seed input
function random_seed()
{
echo<<<HTML
      <fieldset>
        <legend>Random Seed</legend>
        <div class='newslider' id='seed-slider'></div>
        <br />
        Value:   <input name='seed-value' 
                        id='seed-value'
                        value='0' />
        Minimum: <input id="seed-min" 
                        disabled="disabled" />
        Maximum: <input id="seed-max" 
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display the conc_threshold input
function conc_threshold_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Concentration Threshold</legend>
        <div class="newslider" id="conc_threshold-slider"></div>
        <br/>
        Value:   <input name="conc_threshold-value"
                        id="conc_threshold-value"
                        value="0.00001" />
        Minimum: <input id="conc_threshold-min"
                        disabled="disabled"/>
        Maximum: <input id="conc_threshold-max"
                        disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the s_grid input
function s_grid_setup()
{
echo<<<HTML
      <fieldset>
        <legend>S Grid</legend>
        <div class="newslider" id="s_grid-slider"></div>
        <br/>
        Value:   <input name="s_grid-value"
                        id="s_grid-value"
                        value="100" />
        Minimum: <input id="s_grid-min"
                        disabled="disabled"/>
        Maximum: <input id="s_grid-max"
                        disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the k_grid input
function k_grid_setup()
{
echo<<<HTML
      <fieldset>
        <legend>K Grid</legend>
        <div class="newslider" id="k_grid-slider">
        </div>
        <br/>
        Value:   <input name="k_grid-value"
                        id="k_grid-value"
                        value="100" />
        Minimum: <input id="k_grid-min"
                        disabled="disabled" />
        Maximum: <input id="k_grid-max"
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display the mutate_sigma input
function mutate_sigma_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Mutate Sigma</legend>
        <div class="newslider" id="mutate_sigma-slider"></div>
        <br/>
        Value: <input name="mutate_sigma-value"
                      id="mutate_sigma-value"
                      value="20" />
        Minimum: <input id="mutate_sigma-min"
                      disabled="disabled"/>
        Maximum: <input id="mutate_sigma-max"
                      disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the mutate_s input
function mutate_s_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Mutate s</legend>
        <div class="newslider" id="mutate_s-slider">
        </div>
        <br/>
        Value:   <input name="mutate_s-value"
                        id="mutate_s-value"
                        value="20" />
        Minimum: <input id="mutate_s-min"
                        disabled="disabled"/>
        Maximum: <input id="mutate_s-max"
                        disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the mutate_k input
function mutate_k_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Mutate k</legend>
        <div class="newslider" id="mutate_k-slider"></div>
        <br/>
        Value:   <input name="mutate_k-value"
                        id="mutate_k-value"
                        value="20" />
        Minimum: <input id="mutate_k-min"
                        disabled="disabled"/>
        Maximum: <input id="mutate_k-max"
                        disabled="disabled"/>
      </fieldset>
HTML;
}

// Function to display the mutate_sk input
function mutate_sk_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Mutate s and k</legend>
        <div class="newslider" id="mutate_sk-slider">
        </div>
        <br/>
        Value:   <input name="mutate_sk-value"
                        id="mutate_sk-value"
                        value="20" />
        Minimum: <input id="mutate_sk-min"
                        disabled="disabled" />
        Maximum: <input id="mutate_sk-max"
                        disabled="disabled" />
      </fieldset>
HTML;
}

?>
