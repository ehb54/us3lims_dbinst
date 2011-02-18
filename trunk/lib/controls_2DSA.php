<?php
/*
 * controls_2DSA.php
 *
 * Class that contains functions relevant to the display of 2DSA/2DSA-MW controls
 * Inherits from Controls
 *
 */
include 'lib/controls.php';

class Controls_2DSA extends Controls
{
  public function pageTitle()
  {
    return '2DSA Analysis';
  }

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

  // Function to display the regularization setup
  function regularization_setup()
  {
  echo<<<HTML
        <fieldset class='option_value'>
          <legend>Regularization Setup</legend>
          <input type="text" value="0" name="regularization"/> Regularization<br/>
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
            <input type="text" name="meniscus-range" value="0.01"/> Meniscus Fit Range (cm)<br/>
            <br/>
            <fieldset>
              <legend>Meniscus Range</legend>
              <div class="slider" id="meniscus-slider">
                 <input class="slider-input" id="meniscus-slider-input"/>
              </div>
              <br/>
              Value: <input id="meniscus_range" 
                            onchange="meniscus.setValue(parseInt(this.value))" 
                            name="meniscus_range" 
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
              Value: <input id="max_iterations" 
                            onchange="iterations.setValue(parseInt(this.value))" 
                            name="max_iterations" 
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

  // A function to display controls for one dataset
  function display( $dataset_id, $num_datasets )
  {
    echo "  <fieldset>" .
         "    <legend>Initialize 2DSA Parameters - {$_SESSION['request'][$dataset_id]['filename']}" .
         "            Dataset " . ($dataset_id + 1) . " of $num_datasets</legend>\n";

    if ( $dataset_id == 0 )
    {
      $this->s_value_setup();
      $this->f_f0_setup();
      $this->uniform_grid_setup();
      $this->montecarlo();
      $this->tinoise_option();
    }
   
    echo<<<HTML
      <p><button onclick="return toggle('advanced');" id='show'>
        Show Advanced Options</button></p>

      <div id='advanced' style='display:none;'>

HTML;

    if ( $dataset_id == 0 )
    {
      $this->regularization_setup();
      $this->fit_meniscus();
      $this->iterations_option();
    }

    $this->simpoints_input();
    $this->band_volume_input();
    $this->radial_grid_input();
    $this->time_grid_input();

    if ( $dataset_id == 0 )
      $this->rinoise_option();

    echo<<<HTML
      </div>

      <input class="submit" type="button" 
              onclick='window.location="queue_setup_2.php"' 
              value="Edit Profiles"/>
      <input class="submit" type="button" 
              onclick='window.location="queue_setup_1.php"' 
              value="Change Experiment"/>
    </fieldset>
HTML;
  }

}
?>
