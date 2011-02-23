<?php
/*
 * controls_GA.php
 *
 * Class that has functions applicable to the display of GA/GA-MW controls
 * Inherits from Controls
 *
 */
include 'lib/controls.php';

class Controls_GA extends Controls
{
  public $max_buckets;
  public $solute_count;

  public function pageTitle()
  {
    return 'GA Analysis';
  }

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
                        onchange="demes.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="demes-max" size='5'
                        onchange="demes.setMaximum(parseInt(this.value))" 
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
                        onchange="genes.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="genes-max" size='5'
                        onchange="genes.setMaximum(parseInt(this.value))"
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
                        onchange="s.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="h-max" size='5'
                        onchange="s.setMaximum(parseInt(this.value))" 
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
                        onchange="crossover.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="crossover-max" size='5'
                        onchange="crossover.setMaximum(parseInt(this.value))" 
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
                        onchange="mutation.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="mutation-max" size='5'
                        onchange="mutation.setMaximum(parseInt(this.value))" 
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
                        onchange="plague.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="plague-max" size='5'
                        onchange="plague.setMaximum(parseInt(this.value))" 
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
                        onchange="elitism.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="elitism-max" size='5'
                        onchange="elitism.setMaximum(parseInt(this.value))" 
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
                        onchange="migration.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="migration-max" size='5'
                        onchange="migration.setMaximum(parseInt(this.value))" 
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
                        onchange="regularization.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="regularization-max" size='5'
                        onchange="regularization.setMaximum(parseInt(this.value))" 
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
                        onchange="seed.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="seed-max" size='5'
                        onchange="seed.setMaximum(parseInt(this.value))" 
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
                        onchange="conc_threshold.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="conc_threshold-max" size='5'
                        onchange="conc_threshold.setMaximum(parseInt(this.value))" 
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
                        onchange="s_grid.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="s_grid-max" size='5'
                        onchange="s_grid.setMaximum(parseInt(this.value))" 
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
                        onchange="k_grid.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="k_grid-max" size='5'
                        onchange="k_grid.setMaximum(parseInt(this.value))" 
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
                        onchange="mutate_sigma.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="mutate_sigma-max" size='5'
                        onchange="mutate_sigma.setMaximum(parseInt(this.value))" 
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
                        onchange="mutate_s.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="mutate_s-max" size='5'
                        onchange="mutate_s.setMaximum(parseInt(this.value))" 
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
                        onchange="mutate_k.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="mutate_k-max" size='5'
                        onchange="mutate_k.setMaximum(parseInt(this.value))" 
                        disabled="disabled"/>
        </fieldset>
HTML;
  }

  // Function to display the mutate_sk input
  function mutate_sk_setup()
  {
  echo<<<HTML
        <fieldset class='option_value'>
          <legend>Mutate s/k</legend>

          <div class="slider" id="mutate_sk-slider">
            <input class="slider-input" id="mutate_sk-slider-input"/>
          </div>

          <br/>
          Value: <input name="mutate_sk_value" size='5'
                        value="mutate_sk.setValue(parseInt(this.value))" 
                        id="mutate_sk_value"
                        onchange="mutate_sk.setValue(parseInt(this.value))"/>
          Minimum: <input id="mutate_sk-min" size='5'
                        onchange="mutate_sk.setMinimum(parseInt(this.value))" 
                        disabled="disabled"/>
          Maximum: <input id="mutate_sk-max" size='5'
                        onchange="mutate_sk.setMaximum(parseInt(this.value))" 
                        disabled="disabled"/>
        </fieldset>
HTML;
  }

  // Function to display controls for one dataset
  function display( $dataset_id, $num_datasets )
  {
  ?>
      <fieldset>
        <legend>Initialize Genetic Algorithm Parameters -
              <?php echo "{$_SESSION['request'][$dataset_id]['filename']}; " .
                         "Dataset " . ($dataset_id + 1) . " of $num_datasets";?></legend>

        <?php if ( $dataset_id == 0 ) $this->montecarlo(); ?>

        <p><button onclick="return toggle('advanced');" id='show'>
          Show Advanced Options</button></p>

        <div id='advanced' style='display:none'>

  <?php
    if ( $dataset_id == 0 )
    {
      // First time only
      $this->demes_setup(); 
      $this->genes_setup();
      $this->generations_setup();
      $this->crossover_percent();
      $this->mutation_percent();
      $this->plague_percent();
      $this->elitism_setup();
      $this->migration_rate();
      $this->ga_regularization_setup();
      $this->random_seed();

      $this->simpoints_input();
      $this->band_volume_input();
      $this->radial_grid_input();
      $this->time_grid_input();

      $this->conc_threshold_setup();
      $this->s_grid_setup();
      $this->k_grid_setup();
      $this->mutate_sigma_setup();
      $this->mutate_s_setup();
      $this->mutate_k_setup();
      $this->mutate_sk_setup();
    }

    else
    {
      // These are displayed each time.
      $this->simpoints_input();
      $this->band_volume_input();
      $this->radial_grid_input();
      $this->time_grid_input();
    }

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

  // Controls for solute processing

  // For use on solutes page
  function initSolutes( $advancedLevel, $mc_iterations )
  {
    // Figure out $max_buckets and $solute_count
    $this->max_buckets = 100;
  
    if ( $advancedLevel == 0 )
      $this->max_buckets = ( $mc_iterations == 1 ) ? 25 : 10;
  
    // Process changes in the number of solutes
    // $this->solute_count can be between 1 and $this->max_buckets
    $this->solute_count = 5;
    if ( isset($_GET['count']) )
    {
      if ( $_GET['count'] < 1 ) $this->solute_count = 1;
  
      else if ( $_GET['count'] > $this->max_buckets ) $this->solute_count = $this->max_buckets;
  
      else $this->solute_count = $_GET['count'];
    }
    
  }

  // Function to display a form for uploading a file of solute information
  function solute_file_setup()
  {
    echo <<<HTML
      <form enctype="multipart/form-data" action="{$_SERVER['PHP_SELF']}" method="post">
        <fieldset style="background: #eeeeee">
          <legend>Select File to Upload</legend>
          <input type="file" name="file-upload" size="30"/>
          <input type="submit" name="upload_submit" value="Submit"/>
        </fieldset>
      </form>
      <br/><br/>
HTML;
  }

  // Function to display a form to enter number of solutes
  function solute_count_setup()
  {
    echo <<<HTML
    <form name="SoluteValue" action=''>
      <fieldset>
        <legend>Set Number of Solutes</legend>
        <br/>
        Value: <input type='text' name='sol' id='sol'
                      onchange='javascript:get_solute_count(this);' 
                      value="$this->solute_count" size='10'/>
                      Range: (Minimum:1 ~ Maximum:$this->max_buckets) 
      </fieldset>
    </form>
HTML;
  }

  // Function to display a varying number of solutes
  function solute_setup( $buckets )
  {
    $solute_text = <<<HTML
      <fieldset>
      <legend>Setup solutes</legend>
HTML;

    for ( $i = 1; $i <= $this->solute_count; $i++ )
    {
      $s_min = ( isset( $buckets[$i]['s_min'] ) ) ? $buckets[$i]['s_min'] : '';
      $s_max = ( isset( $buckets[$i]['s_max'] ) ) ? $buckets[$i]['s_max'] : '';
      $f_min = ( isset( $buckets[$i]['f_min'] ) ) ? $buckets[$i]['f_min'] : 1;
      $f_max = ( isset( $buckets[$i]['f_max'] ) ) ? $buckets[$i]['f_max'] : 4;

      $solute_text .= <<<HTML
        <div id='solutes{$i}'>
          Solute $i: s-min    <input type='text' name='{$i}_min' id='{$i}_min' 
                                     size='8' value='$s_min' />
                     s-max    <input type='text' name='{$i}_max' id='{$i}_max'
                                     size='8' value='$s_max' />
                     f/f0-min <input type='text' name='{$i}_ff0_min' id='{$i}_ff0_min'
                                     size='5' value='$f_min' />
                     f/f0-max <input type='text' name='{$i}_ff0_max' id='{$i}_ff0_max'
                                     size='5' value='$f_max' />
        </div>
        <br/><br/>
HTML;
    }

    $solute_text .= <<<HTML
      <input class='submit' type='button'
             onclick="window.location='GA_1.php'" value='Setup GA Control'/>
      <input type='hidden' name='solute-value' value="$this->solute_count"/>
      </fieldset>
HTML;

    echo $solute_text;
  }

  // Function to process the uploading of a solute file
  function upload_file( &$buckets, $upload_dir )
  {
    $buckets = array();
    
    if ( ( ! isset( $_FILES['file-upload'] ) )   || 
         ( $_FILES['file-upload']['size'] == 0 ) )
      return 'No file was uploaded';

    $uploadFileName=$_FILES['file-upload']['name'];
    $uploadFile = $upload_dir . "/" . $uploadFileName;

    if ( ! move_uploaded_file( $_FILES['file-upload']['tmp_name'], $uploadFile) ) 
      return 'Uploaded file could not be moved to data directory';
    
    if ( ! ( $lines = file( $uploadFile, FILE_IGNORE_NEW_LINES ) ) )
      return 'Uploaded file could not be read';

    $this->solute_count = (int) $lines[0];  // First line total solutes
    
    // Check that the solute count is in range
    if ( ($this->solute_count < 1 ) || ($this->solute_count > $this->max_buckets) )
    {
      $msg = "Error. The count in the first line of " .
             "$uploadFile ($this->solute_count) is out of range. " .
             "Acceptable values: " .
             "Minimum: 1 ~ Maximum: $this->max_buckets.";

      if ( $this->max_buckets == 25 )
      {
        $msg = "If your analysis includes more than the maximum buckets " .
               "then the system is likely not appropriate for GA analysis. " .
               "Heterogeneous samples and continuous distributions are " .
               "only to be analyzed by the 1/2DSA analysis.";
      }

      return $msg;
    }
    
    $count_lines = count($lines) - 1;

    // Check that the file has the right number of lines.
    if ( $count_lines != $this->solute_count  ||  $count_lines < 1 )
    {
      $msg = "Error.  Count in first line of $uploadFile ($this->solute_count) " .
             "does not match the number of lines of data ($count_lines) " .
             "or is invalid.";

      return $msg; 
    }

    // Get the values, checking for floating numbers too
    $error = false;
    for ($i = 1; $i <= $this->solute_count; $i++ )
    {
      $nums = explode(",", $lines[$i] );
      
      for ($j = 0; $j < 4; $j++ )
      {
        $num = trim( $nums[$j] );
        if ( ereg( '^[-+]?[0-9]*\.?[0-9]*$', $num ) )
          settype( $num, 'float' );

        else
        {
          $error   = true;
          $num     = '';
        }

        switch ($j) 
        {
          case 0 :
             $buckets[$i]['s_min'] = $num;
             break;

          case 1 :
             $buckets[$i]['s_max'] = $num;
             break;

          case 2 :
             $buckets[$i]['f_min'] = $num;
             break;

          case 3 :
             $buckets[$i]['f_max'] = $num;
             break;

        }
      }
    }

    if ( $error )
    {
      $msg = "One or more input values from the data file is not a " .
             "floating-point number. It (They) have been replaced with " .
             "empty values.";
      return $msg;
    }

    return '';
  }

}
?>
