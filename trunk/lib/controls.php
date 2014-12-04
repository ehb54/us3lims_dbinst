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
                        size='12'
                        value='1' />
        Minimum: <input id="montecarlo-min"
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="montecarlo-max"
                        size='12'
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

        <div>
          <p>
          Note: The actual group count number is dependent on the capacity and the 
          architecture of the selected back end system. Generally, the group count number
          will be adjusted downward to 1/2/4/8/16/32 depending on the number of demes or
          datasets requested and the capacity of the system. Currently, the maximum number of
          cores that can be requested ( that is, #cores_per_group x #groups ) are as follows:
          </p>
          <table class='noborder' style='margin:0px auto;'>
            <tr><th>Cluster</th><th>Max cores</th></tr>
            <tr><td>stampede</td><td style='text-align:center;'>1024</td></tr>
            <tr><td>lonestar</td><td style='text-align:center;'>1152</td></tr>
            <tr><td>trestles</td><td style='text-align:center;'>1024</td></tr>
            <tr><td>juropa</td><td style='text-align:center;'>1024</td></tr>
            <tr><td>alamo</td><td style='text-align:center;'>32</td></tr>
            <tr><td>bcf</td><td style='text-align:center;'>(no PMG)</td></tr>
          </table>
        </div>

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
              <option value='16'>16</option>
              <option value='32'>32</option>
            </select>
        </fieldset>

        </td>

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
                        size='12'
                        value='200' />
        Minimum: <input id="simpoints-min"
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="simpoints-max"
                        size='12'
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
                        size='12'
                        value='0.015' />
        Minimum: <input id="band_volume-min"
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="band_volume-max"
                        size='12'
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
                          size='3'
                          value='0' />
          Minimum: <input id='debug_level-min'
                          size='3'
                          disabled='disabled' />
          Maximum: <input id='debug_level-max'
                          size='3'
                          disabled='disabled' />

        </fieldset>
        <fieldset>
          <legend>Debug Text</legend>
          Value:   <input name='debug_text-value' 
                          id='debug_text-value'
                          size='24'
                          value='' />
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
       <input type="text" value="64" name="s_grid_points"/> S-Value Resolution (total grid points)<br/>
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
      <input type="text" value="64" name="ff0_grid_points"/> f/f0 Resolution (total grid points)<br/>
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

// Function to display a discrete GA model dropdown
function DMGA_select_setup()
{
  $personID = $_SESSION['id'];
  $query  = "SELECT model.modelID, description, lastUpdated " .
            "FROM modelPerson, model " .
            "WHERE personID = $personID " .
            "AND modelPerson.modelID = model.modelID " .
            "AND description LIKE '%DMGA_Constr%' " .
            "ORDER BY lastUpdated DESC ";
            //"AND description LIKE '%CustomGrid%' " .
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />\n" . mysql_error() );

  if ( mysql_num_rows( $result ) == 0 )
  {
    echo <<<HTML
      <fieldset class='option_value'>
        <legend>Discrete GA Constraints Model</legend>
        <p>There are no discrete GA constraints models available</p>
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
        <legend>Discrete GA Constraints Model</legend>
          <select name="DC_modelID">
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
      <legend>Uniform Grid Repetitions</legend>
      <select name="uniform grid" id='uniform_grid'>
        <option value='1'>1</option>
        <option value='2'>2</option>
        <option value='3'>3</option>
        <option value='4'>4</option>
        <option value='5'>5</option>
        <option value='6' selected='selected'>6</option>
        <option value='7'>7</option>
        <option value='8'>8</option>
        <option value='9'>9</option>
        <option value='10'>10</option>
        <option value='11'>11</option>
        <option value='12'>12</option>
        <option value='13'>13</option>
        <option value='14'>14</option>
        <option value='15'>15</option>
        <option value='16'>16</option>
        <option value='17'>17</option>
        <option value='18'>18</option>
        <option value='19'>19</option>
        <option value='20'>20</option>
      </select>
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
        <input type="radio" name="meniscus_option" value="1" onclick="show_ctl(1);"/> On<br/>
        <input type="radio" name="meniscus_option" value="0" onclick="hide(1);" 
               checked='checked'/> Off<br/>
        <div style="display:none" id="mag1">
          <br/>
          <input type="text" name="meniscus_range" value="0.03"/>Meniscus Fit Range (cm)<br/>
          <br/>
          <fieldset>
            <legend>Meniscus Grid Points</legend>
            <div class='newslider' id='meniscus-slider'></div>
            <br />
            Value:   <input name='meniscus_points' 
                            id='meniscus_points'
                            size='12'
                            value='10' />
            Minimum: <input id="meniscus-min" 
                            size='12'
                            disabled="disabled" />
            Maximum: <input id="meniscus-max" 
                            size='12'
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
               onclick="show_ctl(2);"/> On<br/>
        <input type="radio" name="iterations_option" value="0" 
               onclick="hide(2);" checked='checked'/> Off<br/>
        <div style="display:none" id="mag2">
          <br/>
          <fieldset>
            <legend>Maximum Number of Iterations</legend>
            <div class='newslider' id='iterations-slider'></div>
            <br />
            Value:   <input name='max_iterations' 
                            id='max_iterations'
                            size='12'
                            value='3' />
            Minimum: <input id="iterations-min" 
                            size='12'
                            disabled="disabled" />
            Maximum: <input id="iterations-max" 
                            size='12'
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

        <div class='newslider' id='demes-slider'></div>
        <br />
        Value:   <input name='demes-value' 
                        id='demes-value'
                        size='4'
                        value='31' />
        Minimum: <input id="demes-min" 
                        size='3'
                        disabled="disabled" />
        Maximum: <input id="demes-max" 
                        size='3'
                        disabled="disabled" />

        <div>
        <p>
          Note: The actual number of
          demes is dependent on the capacity and the architecture of the selected back end system.  
          Generally, demes + 1 will be adjusted upward in units of 8, 12, or 16, but limited
          to the capacity of the system. Currently, the maximum demes values are as follows:
          <table class='noborder' style='margin:0px auto;'>
            <tr><th>Cluster</th><th>Max demes</th></tr>
            <tr><td>stampede</td><td style='text-align:center;'>63</td></tr>
            <tr><td>lonestar</td><td style='text-align:center;'>35</td></tr>
            <tr><td>trestles</td><td style='text-align:center;'>63</td></tr>
            <tr><td>gordon</td><td style='text-align:center;'>63</td></tr>
            <tr><td>juropa</td><td style='text-align:center;'>63</td></tr>
            <tr><td>alamo</td><td style='text-align:center;'>31</td></tr>
            <tr><td>bcf</td><td style='text-align:center;'>31</td></tr>
          </table>
        </p>
        </div>

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
                        size='12'
                        value='200' />
        Minimum: <input id="genes-min" 
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="genes-max" 
                        size='12'
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
                        size='12'
                        value='100' />
        Minimum: <input id="generations-min" 
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="generations-max" 
                        size='12'
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
                        size='12'
                        value='50' />
        Minimum: <input id="crossover-min" 
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="crossover-max" 
                        size='12'
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
                        size='12'
                        value='50' />
        Minimum: <input id="mutation-min" 
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="mutation-max" 
                        size='12'
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
                        size='12'
                        value='4' />
        Minimum: <input id="plague-min" 
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="plague-max" 
                        size='12'
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
                        size='12'
                        value='2' />
        Minimum: <input id="elitism-min" 
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="elitism-max" 
                        size='12'
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
                        size='12'
                        value='3' />
        Minimum: <input id="migration-min" 
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="migration-max" 
                        size=12
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
                        size='12'
                        value='5' />
        Minimum: <input id="regularization-min" 
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="regularization-max" 
                        size='12'
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
                        size='12'
                        value='0' />
        Minimum: <input id="seed-min" 
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="seed-max" 
                        size='12'
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
                        size='12'
                        value="0.00001" />
        Minimum: <input id="conc_threshold-min"
                        size='12'
                        disabled="disabled"/>
        Maximum: <input id="conc_threshold-max"
                        size='12'
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
                        size='12'
                        value="100" />
        Minimum: <input id="s_grid-min"
                        size='12'
                        disabled="disabled"/>
        Maximum: <input id="s_grid-max"
                        size='12'
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
                        size='12'
                        value="100" />
        Minimum: <input id="k_grid-min"
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="k_grid-max"
                        size='12'
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display the s_grid input
function p_grid_setup()
{
echo<<<HTML
      <fieldset>
        <legend>Constraints Parameter Grid</legend>
        <div class="newslider" id="p_grid-slider"></div>
        <br/>
        Value:   <input name="p_grid-value"
                        id="p_grid-value"
                        size='12'
                        value="1000" />
        Minimum: <input id="p_grid-min"
                        size='12'
                        disabled="disabled"/>
        Maximum: <input id="p_grid-max"
                        size='12'
                        disabled="disabled"/>
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
                      size='12'
                      value="0" />
        Minimum: <input id="mutate_sigma-min"
                      size='12'
                      disabled="disabled"/>
        Maximum: <input id="mutate_sigma-max"
                      size='12'
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
                        size='12'
                        value="20" />
        Minimum: <input id="mutate_s-min"
                        size='12'
                        disabled="disabled"/>
        Maximum: <input id="mutate_s-max"
                        size='12'
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
                        size='12'
                        value="20" />
        Minimum: <input id="mutate_k-min"
                        size='12'
                        disabled="disabled"/>
        Maximum: <input id="mutate_k-max"
                        size='12'
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
                        size='12'
                        value="20" />
        Minimum: <input id="mutate_sk-min"
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="mutate_sk-max"
                        size='12'
                        disabled="disabled" />
      </fieldset>
HTML;
}

// Function to display minimize option input
function minimize_opt_setup()
{
echo<<<HTML
      <fieldset class='option_value'>
        <legend>Gradient Search Minimization</legend>
          <select name="minimize_opt-value">
            <option value="2" selected="selected">At any final generation</option>
            <option value="1">Only after full generations</option>
          </select>
      </fieldset>
HTML;
}

// Function to display various PCSA parameters
function pcsa_pars_setup()
{
echo<<<HTML
    <fieldset name='curve_type' id='curve_type'>
      <legend>Parametrically Constrained Spectrum Analysis Curve Type</legend>
      <select name="curve_type" id='curve_type' onChange="show_hide(this.value,3,4)">
        <option value="SL">Straight Line</option>
        <option value="IS" selected='selected'>Increasing Sigmoid</option>
        <option value="DS">Decreasing Sigmoid</option>
        <option value="All">All [ SL + IS + DS ]</option>
        <option value="HL">Horizontal Line [ C(s) ]</option>
      </select>
    </fieldset>
    <fieldset class='option_value'>
      <legend>S-Value & f/f0 Ranges</legend>
      <input type="text" value="1" name="s_value_min"/> S-Value Minimum<br/>
      <input type="text" value="10" name="s_value_max"/> S-Value Maximum<br/>
      <input type="text" value="1" name="ff0_min"/> f/f0 Minimum<br/>
      <input type="text" value="4" name="ff0_max"/> f/f0 Maximum<br/>
    </fieldset>
    <div style="display:block" id="mag3">
    <fieldset name='vars_count' id='vars_count'>
      <legend>Variations Count</legend>
        <div class='newslider' id='varcount-slider'></div>
        <br />
        Value:   <input name='vars_count' 
                        id='varcount_points'
                        size='12'
                        value='10' />
        Minimum: <input id="varcount-min"
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="varcount-max"
                        size='12'
                        disabled="disabled" />
    </fieldset>
    </div>
    <div style="display:none" id="mag4">
    <fieldset name='hlvs_count' id='hlvs_count'>
      <legend>Variations Count (HL)</legend>
        <div class='newslider' id='hlvcount-slider'></div>
        <br />
        Value:   <input name='hl_vars_count' 
                        id='hlvcount_points'
                        size='12'
                        value='100' />
        Minimum: <input id="hlvcount-min"
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="hlvcount-max"
                        size='12'
                        disabled="disabled" />
    </fieldset>
    </div>
    <fieldset name='gfit_iters' id='gfit_iters'>
      <legend>Grid Fit Iterations</legend>
        <div class='newslider' id='gfititer-slider'></div>
        <br />
        Value:   <input name='gfit_iterations' 
                        id='gfit_iterations'
                        size='12'
                        value='3' />
        Minimum: <input id="gfititer-min"
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="gfititer-max"
                        size='12'
                        disabled="disabled" />
    </fieldset>
    <fieldset name='thr_deltr_ratio' id='thr_deltr_ratio'>
      <legend>Threshold Delta-RMSD Ratio</legend>
      <select name="thr_deltr_ratio" id='thr_deltr_ratio'>
        <option value="0.0001">0.001</option>
        <option value="0.0001" selected='selected'>0.0001</option>
        <option value="0.0001">0.00001</option>
        <option value="0.0001">0.000001</option>
      </select>
    </fieldset>
    <fieldset name='curve_points' id='curve_points'>
      <legend>Curve Resolution Points</legend>
        <div class='newslider' id='curvpoint-slider'></div>
        <br />
        Value:   <input name='curves_points' 
                        id='curves_points'
                        size='12'
                        value='200' />
        Minimum: <input id="curvpoint-min"
                        size='12'
                        disabled="disabled" />
        Maximum: <input id="curvpoint-max"
                        size='12'
                        disabled="disabled" />
    </fieldset>
    <fieldset class='option_value'>
      <legend>Tikhonov Regularization</legend>
      <input type="radio" name="tikreg_option"  onclick="hide(5);"
                   value="0" checked='checked'/>&nbsp; Off<br/>
      <input type="radio" name="tikreg_option"  onclick="show_ctl(5);"
                   value="1" />&nbsp; On (specified alpha)<br/>
      <input type="radio" name="tikreg_option"  onclick="hide(5);"
                   value="2" />&nbsp; On (auto-computed alpha)<br/>
      <div style="display:none" id="mag5">
        <br/>
        <input type="text" name="tikreg_alpha" value="0.01" size='6'/>&nbsp;&nbsp; Regularization Alpha Parameter<br/>
      </div>
    </fieldset>
HTML;
}

?>
