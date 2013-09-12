// Javascript for 2DSA controls

// jQuery slider controls
$(document).ready(function()
{
  // Montecarlo Slider setup
  $("#montecarlo-min").attr('value', 1  );
  $("#montecarlo-max").attr('value', 100);
  $("#montecarlo-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   1,
    min:     1,
    max:     100,
    step:    1,
    slide: function( event, ui )
    {
      $( "#mc_iterations" ).attr('value', ui.value);
    },
  });

  $("#mc_iterations").change( function()
  {
    $("#montecarlo-slider").slider( 'value', this.value );
  });

  // Meniscus Slider setup
  $("#meniscus-min").attr('value', 3  );
  $("#meniscus-max").attr('value', 30 );
  $("#meniscus-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   10,
    min:     3,
    max:     30,
    step:    1,
    slide: function( event, ui )
    {
      $( "#meniscus_points" ).attr('value', ui.value);
    },
  });

  $("#meniscus_points").change( function()
  {
    $("#meniscus-slider").slider( 'value', this.value );
  });

  // Iterations Slider setup
  $("#iterations-min").attr('value', 1  );
  $("#iterations-max").attr('value', 10 );
  $("#iterations-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   3,
    min:     1,
    max:     10,
    step:    1,
    slide: function( event, ui )
    {
      $( "#max_iterations" ).attr('value', ui.value);
    },
  });

  $("#max_iterations").change( function()
  {
    $("#iterations-slider").slider( 'value', this.value );
  });

  // Simpoints Slider setup
  $("#simpoints-min").attr('value', 50  );
  $("#simpoints-max").attr('value', 5000);
  $("#simpoints-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   200,
    min:     50,
    max:     5000,
    step:    1,
    slide: function( event, ui )
    {
      $( "#simpoints-value" ).attr('value', ui.value);
    },
  });

  $("#simpoints-value").change( function()
  {
    $("#simpoints-slider").slider( 'value', this.value );
  });

  // Band_volume Slider setup
  $("#band_volume-min").attr('value', 0.0 );
  $("#band_volume-max").attr('value', 0.05);
  $("#band_volume-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   0.015,
    min:     0.0,
    max:     0.05,
    step:    0.0001,
    slide: function( event, ui )
    {
      $( "#band_volume-value" ).attr('value', ui.value);
    },
  });

  $("#band_volume-value").change( function()
  {
    $("#band_volume-slider").slider( 'value', this.value );
  });

  // Debug_level Slider setup
  $("#debug_level-min").attr('value', 0 );
  $("#debug_level-max").attr('value', 4 );
  $("#debug_level-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   0,
    min:     0,
    max:     4,
    step:    1,
    slide: function( event, ui )
    {
      $( "#debug_level-value" ).attr('value', ui.value);
    },
  });

  $("#debug_level-value").change( function()
  {
    $("#debug_level-slider").slider( 'value', this.value );
  });

});

function show_ctl(num) 
{
  var which = document.getElementById('mag'+num);
  which.style.display = 'block';
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
  }

  return false;
}

function validate( f, advanceLevel, dataset_num, count_datasets, separate_datasets,
                      meniscus_radius, data_left )
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

    // What if the s_values are entirely inside the -0.1 ~ +0.1 range?
    if ( (s_value_min >= -0.1) && (s_value_max <=  0.1) )
    {
      alert( "Your s-value range is entirely within the -0.1 ~ 0.1 range. " +
             "This range is not allowed." );
      return( false );
    }

    // Some of the s_value range should be outside the - 0.1 ~ + 0.1 range
    if ( (s_value_min <= -0.1) && (s_value_max >= -0.1) ||
         (s_value_min <=  0.1) && (s_value_max >=  0.1) )
    {
      var answer = confirm( "Your s-value range overlaps the -0.1 ~ 0.1 range. " +
                            "This range will be excluded from the 2DSA analysis." +
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

  // What if the user requests meniscus fitting but the fit range extends into 
  // the data?
  var meniscus_option = 0;
  if ( valid_field(f.meniscus_option) )
    meniscus_option = parseInt( get_radio_value(f.meniscus_option) );

  if ( meniscus_option == 1 )
  {
    var meniscus_range = parseFloat( f.meniscus_range.value );

    // alert( "meniscus range = " + meniscus_range +
    //        "\nmeniscus radius = " + meniscus_radius +
    //        "\nleftmost data point = " + data_left );

    var range_limit = data_left - meniscus_radius - 0.002; // a fudge factor
    range_limit = Math.round( range_limit * 2000 ) / 1000; // multiply by 2 and round to 3 digits
    if ( meniscus_range > range_limit )
    {
       f.meniscus_range.value = range_limit;
       alert( "The meniscus fit range extends beyond the left data range limit. " +
              "The meniscus range has been reduced to " + range_limit );
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
      alert( "Meniscus fitting may only be performed when no Monte Carlo " +
             "iterations are requested.\n" + contact_bo );
      return( false );
    }

    // Iterative fitting
    var iterations_option = 0;
    if ( valid_field(f.iterations_option) )
      iterations_option = parseInt( get_radio_value(f.iterations_option) );
    // alert( "iterations_option = " + iterations_option );

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
  var monte_carlo = 1;
  if ( valid_field(f.mc_iterations) )
    monte_carlo = parseInt( f.mc_iterations.value );

  // alert("iterations_option = " + iterations_option);
  // alert( "monte_carlo value = " + monte_carlo );

  if ( iterations_option == 1  &&  monte_carlo > 1 )
  {
    alert( "Iterative fitting is not allowed for Monte Carlo on multiple datasets.\n" +
           contact_bo );
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
