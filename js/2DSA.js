// Javascript for 2DSA controls

// Protect ourselves in case the controls aren't present

try {
var meniscus = new Slider(document.getElementById("meniscus-slider"), 
                          document.getElementById("meniscus-slider-input"));
meniscus.setMinimum(3);
meniscus.setMaximum(30);
meniscus.setValue(10);
document.getElementById("meniscus_points").value = meniscus.getValue();
document.getElementById("meniscus-min").value = meniscus.getMinimum();
document.getElementById("meniscus-max").value = meniscus.getMaximum();

meniscus.onchange = function () 
{
  document.getElementById("meniscus_points").value = meniscus.getValue();
  document.getElementById("meniscus-min").value = meniscus.getMinimum();
  document.getElementById("meniscus-max").value = meniscus.getMaximum();
};

} catch(e_meniscus) {}

try {
var iterations = new Slider(document.getElementById("iterations-slider"), 
                            document.getElementById("iterations-slider-input"));
iterations.setMinimum(1);
iterations.setMaximum(10);
iterations.setValue(3);
document.getElementById("max_iterations").value = iterations.getValue();
document.getElementById("iterations-min").value = iterations.getMinimum();
document.getElementById("iterations-max").value = iterations.getMaximum();

iterations.onchange = function () 
{
  document.getElementById("max_iterations").value = iterations.getValue();
  document.getElementById("iterations-min").value = iterations.getMinimum();
  document.getElementById("iterations-max").value = iterations.getMaximum();
};

} catch(e_iterations) {}

try {
var debug_level = new Slider(document.getElementById("debug_slider"), 
                             document.getElementById("debug_slider_input"));
debug_level.setMinimum(0);
debug_level.setMaximum(4);
debug_level.setValue(0);
document.getElementById("debug_level").value = debug_level.getValue();
document.getElementById("debug_level_min").value = debug_level.getMinimum();
document.getElementById("debug_level_max").value = debug_level.getMaximum();

debug_level.onchange = function () 
{
  document.getElementById("debug_level").value = debug_level.getValue();
  document.getElementById("debug_level_min").value = debug_level.getMinimum();
  document.getElementById("debug_level_max").value = debug_level.getMaximum();
};

} catch(e_debug_level) {}

//Montecarlo Slider setup
try {
var montecarlo = new Slider(document.getElementById("montecarlo-slider"), 
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

        var mc_iterations = document.getElementById( "mc_iterations" );
        var PMGC_option = document.getElementById( "PMGC_option" );

        if ( mc_iterations.value > 1 )
           PMGC_option.style.display = "block";
        else
           PMGC_option.style.display = "none";
};

} catch(e_montecarlo) {}

// Parallel masters group count setup
try {
var PMGC_enable       = document.getElementById( "PMGC_enable"       );
var PMGC_count        = document.getElementById( "PMGC_count"        );
var req_mgroupcount   = document.getElementById( "req_mgroupcount"   );

PMGC_enable.onchange = function ()
{
  if ( PMGC_enable.checked )
  {
    PMGC_count.style.display = "block";
    req_mgroupcount.value    = 4;
  }
  else
  {
    PMGC_count.style.display = "none";
    req_mgroupcount.value    = 1;
  }
}

} catch(e_PMGC_option) {}

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

} catch(e_simpoints) {}

// Band_volume Slider setup
var band_volume    = new Slider(document.getElementById("band_volume-slider"),
                                document.getElementById("band_volume-slider-input"));
try {
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

window.onresize = function () 
{
  redraw_controls();
};

function redraw_controls()
{
  meniscus.recalculate();
  iterations.recalculate();
  debug_level.recalculate();
  montecarlo.recalculate();
  simpoints.recalculate();
  band_volume.recalculate();
}

function show_ctl(num) 
{
  var which = document.getElementById('mag'+num);
  which.style.display = 'block';
  if( num == 5 || num == 6 ) 
  {
    meniscus.recalculate();
    iterations.recalculate();
  }
}

function hide(num) 
{
  var which = document.getElementById('mag'+num);
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

function validate( f, advanceLevel, dataset_num, count_datasets, separate_datasets )
{
  // Advanced users don't go through these tests
  if ( advanceLevel > 0 ) return( true );

  // None of the controls we're checking exist after the first dataset
  if ( dataset_num > 0 ) return( true );

  // Handling could be different depending on single or multiple datasets
  // First, checks that are common to both

  // Make sure there is one on the page
  if ( valid_field(f.s_value_min) )
  {
    var s_value_min = parseFloat(f.s_value_min.value);
    var s_value_max = parseFloat(f.s_value_max.value);

    // alert("s_value_min = " + s_value_min +
    //       "\ns_value_max = " + s_value_max);

    if ( s_value_max < s_value_min )
    {
      var swap = confirm( "The maximum s-value is less than the minimum " +
                          "s-value. If you would like to switch them, " +
                          "please click on OK to continue. If you would " +
                          "rather edit them yourself, click cancel to " +
                          "return to the submission form." );

      if ( ! swap ) return( false );

      f.s_value_min.value = s_value_max;
      f.s_value_max.value = s_value_min;
    }

    s_value_min         = parseFloat(f.s_value_min.value);  // These might have changed
    s_value_max         = parseFloat(f.s_value_max.value);

    // |s_value_min| >= 0.1
    if ( Math.abs(s_value_min) < 0.1 )
    {
      alert( "Absolute value of s-min must be >= 0.1" );
      f.s_value_min.focus();
      return( false );
    }

    // |s_value_min| out of range (most likely < -10000) 
    if ( Math.abs(s_value_min) > 10000 )
    {
      alert( "Absolute value of s-min must be <= 10000" );
      f.s_value_min.focus();
      return( false );
    }

    // |s_max| >= 0.1
    if ( Math.abs(s_value_max) < 0.1 )
    {
      alert( "Absolute value of s-max must be >= 0.1" );
      f.s_value_max.focus();
      return( false );
    }

    // |s_max| out of range
    if ( Math.abs(s_value_max) > 10000 )
    {
      alert( "Absolute value of s-max must be <= 10000" );
      f.s_value_max.focus();
      return( false );
    }

    // What if s range includes -0.1 ~ 0.1?
    if ( s_value_min < -0.1 &&
         s_value_max >  0.1 )
    {
      var answer = confirm( "Your s-value range overlaps the -0.1 ~ 0.1 range. " +
                            "This range is normally excluded from the 2DSA analysis." +
                            "Please click OK to continue, or click cancel to " +
                            "return to the submission form." );

      if ( ! answer ) return( false );
    }
  }

  // Verify these fields exist
  if ( valid_field(f.mw_value_min) )
  {
    var mw_value_min = parseFloat(f.mw_value_min.value);
    var mw_value_max = parseFloat(f.mw_value_max.value);

    // alert("mw_value_min = " + mw_value_min +
    //       "\nmw_value_max = " + mw_value_max);

    if ( mw_value_max < mw_value_min )
    {
      var swap = confirm( "The maximum mw-value is less than the minimum " +
                          "mw-value. If you would like to switch them, " +
                          "please click on OK to continue. If you would " +
                          "rather edit them yourself, click cancel to " +
                          "return to the submission form." );

      if ( ! swap ) return( false );

      f.mw_value_min.value = mw_value_max;
      f.mw_value_max.value = mw_value_min;
    }
  }

  if ( valid_field(f.ff0_min) )
  {
    var ff0_min = parseFloat(f.ff0_min.value);
    var ff0_max = parseFloat(f.ff0_max.value);

    // alert("ff0_min = " + ff0_min +
    //       "\nff0_max = " + ff0_max);

    if ( ff0_max < ff0_min )
    {
      var swap = confirm( "The maximum f/f0-value is less than the minimum " +
                          "f/f0-value. If you would like to switch them, " +
                          "please click on OK to continue. If you would " +
                          "rather edit them yourself, click cancel to " +
                          "return to the submission form." );

      if ( ! swap ) return( false );

      f.ff0_min.value = ff0_max;
      f.ff0_max.value = ff0_min;
    }
  }

  if ( count_datasets == 1 || separate_datasets == 1 )
    return( validate_single(f) );

  else
    return( validate_multiple(f) );

  return( true );
}

function validate_single( f )
{    
  var contact_bo = "\nIf you have any questions about this policy, please " +
                   "contact Borries Demeler (demeler@biochem.uthscsa.edu).";

  // Some things should only be done if monte carlo is 1
  var monte_carlo = 1;
  if ( valid_field(f.mc_iterations) )
    monte_carlo = parseInt( f.mc_iterations.value );

  // alert( "monte_carlo value = " + monte_carlo );

  if ( monte_carlo > 1 )
  {
    // Meniscus fitting
    var meniscus_option = 0;
    if ( valid_field(f.meniscus_option) )
      meniscus_option = parseInt( get_radio_value(f.meniscus_option) );

    // alert( "meniscus_option = " + meniscus_option );

    if ( meniscus_option == 1 )
    {
      alert( "Meniscus fitting may only be performed when no monte carlo " +
             "iterations are requested.\n" + contact_bo );
      return( false );
    }

    // Iterative fitting
    var iterations_option = 0;
    if ( valid_field(f.iterations_option) )
      iterations_option = parseInt( get_radio_value(f.iterations_option) );

    // alert( "iterations_option = " + iterations_option );

    if ( iterations_option == 1 )
    {
      alert( "Iterative fitting may only be performed when no monte carlo " +
             "iterations are requested.\n" + contact_bo );
      return( false );
    }

    var ti_noise = 0;
    if ( valid_field(f.tinoise_option) )
      ti_noise = parseInt( get_radio_value(f.tinoise_option) );

    var ri_noise = 0;
    if ( valid_field(f.rinoise_option) )
      ri_noise = parseInt( get_radio_value(f.rinoise_option) );

    // alert("tinoise_option value = " + ti_noise +
    //       "\nrinoise_option value = " + ri_noise);

    if ( ti_noise == 1 || ri_noise == 1 )
    {
      alert( "Time Invariant Noise subtraction and Radially Invariant Noise " +
             "subtraction may only be performed when no monte carlo " +
             "iterations are requested.\n" + contact_bo );
      return( false );
    }
  }

  else
  {
    // Otherwise ti noise subtraction should be performed
    var ti_noise = 0;
    if ( valid_field(f.tinoise_option) )
      ti_noise = parseInt( get_radio_value(f.tinoise_option) );

    // alert("tinoise_option value = " + ti_noise);

    var tinoise_ok = ( ti_noise == 1 ) ||
                     confirm( "If ti noise subtraction has already been performed " +
                              "on this data, click OK to continue. If not, click " +
                              "cancel and turn on \"Fit Time Invariant Noise.\"\n" +
                              contact_bo );

    if ( ! tinoise_ok ) return( false );
  }

  return( true );
}

function validate_multiple( f )
{
  var contact_bo = "\nIf you have any questions about this policy, please " +
                   "contact Borries Demeler (demeler@biochem.uthscsa.edu).";

  // For global analyses ti and ri noise subtraction should not be checked
  var ti_noise = 0;
  if ( valid_field(f.tinoise_option) )
    ti_noise = parseInt( get_radio_value(f.tinoise_option) );

  var ri_noise = 0;
  if ( valid_field(f.rinoise_option) )
    ri_noise = parseInt( get_radio_value(f.rinoise_option) );

  // alert("tinoise_option value = " + ti_noise +
  //       "\nrinoise_option value = " + ri_noise);

  if ( ti_noise == 1 || ri_noise == 1 )
  {
    alert( "Time Invariant Noise and Radially Invariant Noise fitting should " +
           "not be requested on a global analysis.\n" +
           contact_bo );
    return( false );
  }

  // No meniscus fitting in global analyses
  var meniscus_option = 0;
  if ( valid_field(f.meniscus_option) )
    meniscus_option = parseInt( get_radio_value(f.meniscus_option) );

  // alert("meniscus_option = " + meniscus_option);

  if ( meniscus_option == 1 )
  {
    alert( "Meniscus fitting is not allowed on multiple datasets.\n" +
           contact_bo);
    return( false );
  }

  // No iterative fitting in global analyses
  var iterations_option = 0;
  if ( valid_field(f.iterations_option) )
    iterations_option = parseInt( get_radio_value(f.iterations_option) );

  // alert("iterations_option = " + iterations_option);

  if ( iterations_option == 1 )
  {
    alert( "Iterative fitting is not allowed on multiple datasets.\n" +
           contact_bo);
    return( false );
  }

  // Let's only produce this message the first time. On subsequent pages
  // most of the controls are absent, so...
  if ( valid_field(f.tinoise_option) )
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
