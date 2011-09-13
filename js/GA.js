// Javascript for GA controls


// Protect ourselves in case the controls aren't present

// Demes Slider setup
try {
var demes          = new Slider(document.getElementById("demes-slider"), 
                                document.getElementById("demes-slider-input"));

demes.setMinimum(1);
demes.setMaximum(100);
demes.setValue(31);
document.getElementById("demes-value").value = demes.getValue();
document.getElementById("demes-min").value = demes.getMinimum();
document.getElementById("demes-max").value = demes.getMaximum();

demes.onchange = function () 
{
        document.getElementById("demes-value").value = demes.getValue();
        document.getElementById("demes-min").value = demes.getMinimum();
        document.getElementById("demes-max").value = demes.getMaximum();
};

} catch(e_demes) {}

// Genes Slider setup
try {
var genes          = new Slider(document.getElementById("genes-slider"), 
                                document.getElementById("genes-slider-input"));

genes.setMinimum(25);
genes.setMaximum(1000);
genes.setValue(100);
document.getElementById("genes-value").value = genes.getValue();
document.getElementById("genes-min").value = genes.getMinimum();
document.getElementById("genes-max").value = genes.getMaximum();

genes.onchange = function () 
{
        document.getElementById("genes-value").value = genes.getValue();
        document.getElementById("genes-min").value = genes.getMinimum();
        document.getElementById("genes-max").value = genes.getMaximum();
};

} catch(e_genes) {}


//Seed Slider setup
try {
var seed           = new Slider(document.getElementById("seed-slider"), 
                                document.getElementById("seed-slider-input"));

seed.setMinimum(0);
seed.setMaximum(1000);
seed.setValue(0);
document.getElementById("seed-value").value = seed.getValue();
document.getElementById("seed-min").value = seed.getMinimum();
document.getElementById("seed-max").value = seed.getMaximum();

seed.onchange = function () 
{
        document.getElementById("seed-value").value = seed.getValue();
        document.getElementById("seed-min").value = seed.getMinimum();
        document.getElementById("seed-max").value = seed.getMaximum();
};

} catch(e_seed) {}

//Montecarlo Slider setup
try {
var montecarlo     = new Slider(document.getElementById("montecarlo-slider"), 
                                document.getElementById("montecarlo-slider-input"));

montecarlo.setMinimum(1);
montecarlo.setMaximum(100);
montecarlo.setValue(1);
document.getElementById("mc_iterations").value = montecarlo.getValue();
document.getElementById("montecarlo-min").value = montecarlo.getMinimum();
document.getElementById("montecarlo-max").value = montecarlo.getMaximum();

montecarlo.onchange = function () 
{
        document.getElementById("mc_iterations").value = montecarlo.getValue();
        document.getElementById("montecarlo-min").value = montecarlo.getMinimum();
        document.getElementById("montecarlo-max").value = montecarlo.getMaximum();
};

} catch(e_montecarlo) {}

// Crossover Slider setup
try {
var crossover      = new Slider(document.getElementById("crossover-slider"), 
                                document.getElementById("crossover-slider-input"));

crossover.setMinimum(0);
crossover.setMaximum(100);
crossover.setValue(50);
document.getElementById("crossover-value").value = crossover.getValue();
document.getElementById("crossover-min").value = crossover.getMinimum();
document.getElementById("crossover-max").value = crossover.getMaximum();

crossover.onchange = function () 
{
        document.getElementById("crossover-value").value = crossover.getValue();
        document.getElementById("crossover-min").value = crossover.getMinimum();
        document.getElementById("crossover-max").value = crossover.getMaximum();
};

} catch(e_crossover) {}


// Mutation Slider setup
try {
var mutation       = new Slider(document.getElementById("mutation-slider"), 
                                document.getElementById("mutation-slider-input"));

mutation.setMinimum(0);
mutation.setMaximum(100);
mutation.setValue(50);
document.getElementById("mutation-value").value = mutation.getValue();
document.getElementById("mutation-min").value = mutation.getMinimum();
document.getElementById("mutation-max").value = mutation.getMaximum();

mutation.onchange = function () 
{
        document.getElementById("mutation-value").value = mutation.getValue();
        document.getElementById("mutation-min").value = mutation.getMinimum();
        document.getElementById("mutation-max").value = mutation.getMaximum();
};

} catch(e_mutation) {}

// Plague Slider setup
try {
var plague         = new Slider(document.getElementById("plague-slider"), 
                                document.getElementById("plague-slider-input"));

plague.setMinimum(0);
plague.setMaximum(100);
plague.setValue(4);
document.getElementById("plague-value").value = plague.getValue();
document.getElementById("plague-min").value = plague.getMinimum();
document.getElementById("plague-max").value = plague.getMaximum();

plague.onchange = function () 
{
        document.getElementById("plague-value").value = plague.getValue();
        document.getElementById("plague-min").value = plague.getMinimum();
        document.getElementById("plague-max").value = plague.getMaximum();
};

} catch(e_plague) {}


// Elitism Slider setup
try {
var elitism        = new Slider(document.getElementById("elitism-slider"), 
                                document.getElementById("elitism-slider-input"));

elitism.setMinimum(0);
elitism.setMaximum(5);
elitism.setValue(2);
document.getElementById("elitism-value").value = elitism.getValue();
document.getElementById("elitism-min").value = elitism.getMinimum();
document.getElementById("elitism-max").value = elitism.getMaximum();

elitism.onchange = function () 
{
        document.getElementById("elitism-value").value = elitism.getValue();
        document.getElementById("elitism-min").value = elitism.getMinimum();
        document.getElementById("elitism-max").value = elitism.getMaximum();
};

} catch(e_elitism) {}

// Migration Slider setup
try {
var migration      = new Slider(document.getElementById("migration-slider"), 
                                document.getElementById("migration-slider-input"));

migration.setMinimum(0);
migration.setMaximum(50);
migration.setValue(3);
document.getElementById("migration-value").value = migration.getValue();
document.getElementById("migration-min").value = migration.getMinimum();
document.getElementById("migration-max").value = migration.getMaximum();

migration.onchange = function () 
{
        document.getElementById("migration-value").value = migration.getValue();
        document.getElementById("migration-min").value = migration.getMinimum();
        document.getElementById("migration-max").value = migration.getMaximum();
};

} catch(e_migration) {}

// Regularization Slider setup
try {
var regularization = new Slider(document.getElementById("regularization-slider"), 
                                document.getElementById("regularization-slider-input"));

regularization.setMinimum(0);
regularization.setMaximum(100);
regularization.setValue(5);
document.getElementById("regularization-value").value = 0.05;
document.getElementById("regularization-min").value = 0;
document.getElementById("regularization-max").value = 1;

regularization.onchange = function () 
{
        document.getElementById("regularization-value").value = regularization.getValue()/100;
        document.getElementById("regularization-min").value = 0;
        document.getElementById("regularization-max").value = 1;
};

} catch(e_regularization) {}

// Generations slider setup
try {
var s              = new Slider(document.getElementById("generations-slider"), 
                                document.getElementById("generations-slider-input"));

s.setMinimum(25);
s.setMaximum(500);
s.setValue(100);
document.getElementById("h-value").value = s.getValue();
document.getElementById("h-min").value = s.getMinimum();
document.getElementById("h-max").value = s.getMaximum();

s.onchange = function () 
{
        document.getElementById("h-value").value = s.getValue();
        document.getElementById("h-min").value = s.getMinimum();
        document.getElementById("h-max").value = s.getMaximum();
};

} catch(e_s) {}

// Simpoints Slider setup
try {
var simpoints      = new Slider(document.getElementById("simpoints-slider"),
                                document.getElementById("simpoints-slider-input"));
simpoints.setMinimum(50);
simpoints.setMaximum(5000);
simpoints.setValue(200);
document.getElementById("simpoints-value").value = simpoints.getValue();
document.getElementById("simpoints-min").value = simpoints.getMinimum();
document.getElementById("simpoints-max").value = simpoints.getMaximum();
simpoints.onchange = function () 
{
        document.getElementById("simpoints-value").value = simpoints.getValue();
        document.getElementById("simpoints-min").value = simpoints.getMinimum();
        document.getElementById("simpoints-max").value = simpoints.getMaximum();
};

} catch (e_simpoints) {}

// Band_volume Slider setup
try {
var band_volume    = new Slider(document.getElementById("band_volume-slider"),
                                document.getElementById("band_volume-slider-input"));

band_volume.setMinimum(0);
band_volume.setMaximum(50);
band_volume.setValue(15);
document.getElementById("band_volume-value").value = band_volume.getValue() / 1000.0;
document.getElementById("band_volume-min").value = band_volume.getMinimum() / 1000.0;
document.getElementById("band_volume-max").value = band_volume.getMaximum() / 1000.0;

band_volume.onchange = function () 
{
        document.getElementById("band_volume-value").value 
          = band_volume.getValue() / 1000.0;
        document.getElementById("band_volume-min").value 
          = band_volume.getMinimum() / 1000.0;
        document.getElementById("band_volume-max").value 
          = band_volume.getMaximum() / 1000.0;
};

} catch(e_band_volume) {}

// Concentration Threshhold Slider setup
try {
var conc_threshold          = new Slider(document.getElementById("conc_threshold-slider"), 
                                document.getElementById("conc_threshold-slider-input"));

conc_threshold.setMinimum(-8);
conc_threshold.setMaximum(-1);
conc_threshold.setValue(-6);
document.getElementById("conc_threshold-value").value 
  = Math.pow(10,conc_threshold.getValue());
document.getElementById("conc_threshold-min").value 
  = Math.pow(10,conc_threshold.getMinimum());
document.getElementById("conc_threshold-max").value 
  = Math.pow(10,conc_threshold.getMaximum());

conc_threshold.onchange = function () 
{
        document.getElementById("conc_threshold-value").value
          = Math.pow(10,conc_threshold.getValue());
        document.getElementById("conc_threshold-min").value
          = Math.pow(10,conc_threshold.getMinimum());
        document.getElementById("conc_threshold-max").value
          = Math.pow(10,conc_threshold.getMaximum());
};

} catch(e_conc_threshold) {}

// S Grid Slider setup
try {
var s_grid          = new Slider(document.getElementById("s_grid-slider"), 
                                document.getElementById("s_grid-slider-input"));

s_grid.setMinimum(10);
s_grid.setMaximum(200);
s_grid.setValue(100);
document.getElementById("s_grid-value").value = s_grid.getValue();
document.getElementById("s_grid-min").value = s_grid.getMinimum();
document.getElementById("s_grid-max").value = s_grid.getMaximum();

s_grid.onchange = function () 
{
        document.getElementById("s_grid-value").value = s_grid.getValue();
        document.getElementById("s_grid-min").value = s_grid.getMinimum();
        document.getElementById("s_grid-max").value = s_grid.getMaximum();
};

} catch(e_s_grid) {}

// K Grid Slider setup
try {
var k_grid          = new Slider(document.getElementById("k_grid-slider"), 
                                document.getElementById("k_grid-slider-input"));

k_grid.setMinimum(10);
k_grid.setMaximum(200);
k_grid.setValue(100);
document.getElementById("k_grid-value").value = k_grid.getValue();
document.getElementById("k_grid-min").value = k_grid.getMinimum();
document.getElementById("k_grid-max").value = k_grid.getMaximum();

k_grid.onchange = function () 
{
        document.getElementById("k_grid-value").value = k_grid.getValue();
        document.getElementById("k_grid-min").value = k_grid.getMinimum();
        document.getElementById("k_grid-max").value = k_grid.getMaximum();
};

} catch(e_k_grid) {}

// Mutate Sigma Slider setup
try {
var mutate_sigma          = new Slider(document.getElementById("mutate_sigma-slider"), 
                                document.getElementById("mutate_sigma-slider-input"));

mutate_sigma.setMinimum(10);
mutate_sigma.setMaximum(40);
mutate_sigma.setValue(20);
document.getElementById("mutate_sigma-value").value = mutate_sigma.getValue() / 10.0;
document.getElementById("mutate_sigma-min").value = mutate_sigma.getMinimum() / 10.0;
document.getElementById("mutate_sigma-max").value = mutate_sigma.getMaximum() / 10.0;

mutate_sigma.onchange = function () 
{
        document.getElementById("mutate_sigma-value").value = mutate_sigma.getValue() / 10.0;
        document.getElementById("mutate_sigma-min").value = mutate_sigma.getMinimum() / 10.0;
        document.getElementById("mutate_sigma-max").value = mutate_sigma.getMaximum() / 10.0;
};

} catch(e_mutate_sigma) {}

// Mutate s Slider setup
try {
var mutate_s          = new Slider(document.getElementById("mutate_s-slider"), 
                                document.getElementById("mutate_s-slider-input"));

mutate_s.setMinimum(0);
mutate_s.setMaximum(100);
mutate_s.setValue(20);
document.getElementById("mutate_s_value").value = mutate_s.getValue();
document.getElementById("mutate_s-min").value = mutate_s.getMinimum();
document.getElementById("mutate_s-max").value = mutate_s.getMaximum();

mutate_s.onchange = function () 
{
        document.getElementById("mutate_s_value").value = mutate_s.getValue();
        document.getElementById("mutate_s-min").value = mutate_s.getMinimum();
        document.getElementById("mutate_s-max").value = mutate_s.getMaximum();
};

} catch(e_mutate_s) {}

// Mutate k Slider setup
try {
var mutate_k          = new Slider(document.getElementById("mutate_k-slider"), 
                                document.getElementById("mutate_k-slider-input"));

mutate_k.setMinimum(0);
mutate_k.setMaximum(100);
mutate_k.setValue(20);
document.getElementById("mutate_k_value").value = mutate_k.getValue();
document.getElementById("mutate_k-min").value = mutate_k.getMinimum();
document.getElementById("mutate_k-max").value = mutate_k.getMaximum();

mutate_k.onchange = function () 
{
        document.getElementById("mutate_k_value").value = mutate_k.getValue();
        document.getElementById("mutate_k-min").value = mutate_k.getMinimum();
        document.getElementById("mutate_k-max").value = mutate_k.getMaximum();
};

} catch(e_mutate_k) {}

// Mutate s/k Slider setup
try {
var mutate_sk          = new Slider(document.getElementById("mutate_sk-slider"), 
                                document.getElementById("mutate_sk-slider-input"));

mutate_sk.setMinimum(0);
mutate_sk.setMaximum(100);
mutate_sk.setValue(20);
document.getElementById("mutate_sk_value").value = mutate_sk.getValue();
document.getElementById("mutate_sk-min").value = mutate_sk.getMinimum();
document.getElementById("mutate_sk-max").value = mutate_sk.getMaximum();

mutate_sk.onchange = function () 
{
        document.getElementById("mutate_sk_value").value = mutate_sk.getValue();
        document.getElementById("mutate_sk-min").value = mutate_sk.getMinimum();
        document.getElementById("mutate_sk-max").value = mutate_sk.getMaximum();
};

} catch(e_mutate_sk) {}

try {
var debug_level = new Slider(document.getElementById("debug_slider"), 
                             document.getElementById("debug_slider_input"));
debug_level.setMinimum(0);
debug_level.setMaximum(4);
debug_level.setValue(0);
document.getElementById("debug_level").value = debug_level.getValue();

debug_level.onchange = function () 
{
  document.getElementById("debug_level").value = debug_level.getValue();
};

} catch(e_debug_level) {}

window.onresize = function () 
{
  redraw_controls();
};

function redraw_controls()
{
  s.recalculate();
  demes.recalculate();
  genes.recalculate();
  mutation.recalculate();
  regularization.recalculate();
  crossover.recalculate();
  plague.recalculate();
  elitism.recalculate();
  migration.recalculate();
  s.recalculate();
  simpoints.recalculate();
  band_volume.recalculate();

  conc_threshold.recalculate();
  s_grid.recalculate();
  k_grid.recalculate();
  mutate_sigma.recalculate();
  mutate_s.recalculate();
  mutate_k.recalculate();
  mutate_sk.recalculate();

  debug_level.recalculate();
}

function show_ctl(num) 
{
    which = document.getElementById('mag'+num);
    which.style.display = 'block';
}

function hide(num) 
{
    which = document.getElementById('mag'+num);
    which.style.display='none';
}

function toggle(area)
{
  var which = document.getElementById(area);
  var text  = document.getElementById('show'); 

  if ( which.style.display == 'block' ) 
  {  
    if ( document.all )  // old IE
      text.innerHTML = "Show Advanced Options";
    else
      text.textContent = "Show Advanced Options";
    which.style.display = 'none';
  }
  else
  {
    if ( document.all )  // old IE
      text.innerHTML = "Hide Advanced Options";
    else
      text.textContent = "Hide Advanced Options";
    which.style.display = 'block';
    redraw_controls();
  }

  return false;
}

function validate( f, advanceLevel, count_datasets )
{
  // Handling could be different depending on single or multiple datasets
  // First, checks that are common to both

  // mutate_s, mutate_k, and mutate_sk have to add to 100 or less
  if ( valid_field(f.mutate_s_value) )
  {
    var mutate_s  = parseInt( f.mutate_s_value.value );
    var mutate_k  = parseInt( f.mutate_k_value.value );
    var mutate_sk = parseInt( f.mutate_sk_value.value );

    if ( mutate_s + mutate_k + mutate_sk > 100 )
    {
      alert( "The sum of the mutate_s, mutate_k and mutate_sk fields " +
             "must be less than or equal to 100. Please click on ok " +
             "to return to the submission form and correct one of more " +
             "of these values." );
      return( false );
    }
  }

  // Verify these fields exist
  // Only for GA-MW analysis
  if ( valid_field(f.mw_min) )
  {
    var mw_min = parseFloat(f.mw_min.value);
    var mw_max = parseFloat(f.mw_max.value);

    // alert("mw_min = " + mw_min +
    //       "\nmw_max = " + mw_max);

    if ( mw_max < mw_min )
    {
      var swap = confirm( "The maximum mw-value is less than the minimum " +
                          "mw-value. If you would like to switch them, " +
                          "please click on OK to continue. If you would " +
                          "rather edit them yourself, click cancel to " +
                          "return to the submission form." );

      if ( ! swap ) return( false );

      f.mw_min.value = mw_max;
      f.mw_max.value = mw_min;
    }
  }

  if ( count_datasets == 1 )
    return( validate_single(f) );

  else
    return( validate_multiple(f) );

  return( true );
}

function validate_single( f )
{    
  var contact_bo = "\nIf you have any questions about this policy, please " +
                   "contact Borries Demeler (demeler@biochem.uthscsa.edu).";

  return( true );
}

function validate_multiple( f )
{
  // Advanced users don't go through these tests
  if ( advanceLevel > 0 ) return( true );

  var contact_bo = "\nIf you have any questions about this policy, please " +
                   "contact Borries Demeler (demeler@biochem.uthscsa.edu).";

  // Let's only produce this message the first time. On subsequent pages
  // most of the controls are absent, so...
  if ( valid_field(f.simpoints-value) )
  {
    var multiple_ok = confirm( "You have selected more than one dataset " +
                      "to be fitted in this analysis. Are you sure you want " +
                      "to perform a global analysis on all included datasets? " +
                      "The fitted model will be a compromise between all " +
                      "included datasets and not return the best possible fit " +
                      "for each individual dataset. This will also significantly " +
                      "increase the computing time. If this is not what you want " +
                      "to do, please click on cancel and go back to the dataset " +
                      "selection and delete the extra datasets from the queue. " +
                      "Otherwise, select OK to continue.");

    if ( ! multiple_ok ) return( false );
  }

  return( true );
}

