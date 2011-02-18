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

  // Function to display controls for one dataset
  function display( $dataset_id, $num_datasets )
  {
  ?>
      <fieldset>
        <legend>Initialize Genetic Algorithm Parameters -
              <?php echo "{$_SESSION['request_id'][$dataset_id]['file']}; " .
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
}
?>
