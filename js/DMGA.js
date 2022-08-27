// Javascript for DMGA controls

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
    if ( this.value < 1 ) this.value = 1;
    if ( this.value > 100 ) this.value = 100;
    $("#montecarlo-slider").slider( 'value', this.value );
  });

  // Demes Slider setup
  $("#demes-min").attr('value', 1  );
  $("#demes-max").attr('value', 100);
  $("#demes-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   1,
    min:     1,
    max:     100,
    step:    1,
    slide: function( event, ui )
    {
      $( "#demes-value" ).attr('value', ui.value);
    },
  });

  $("#demes-value").change( function()
  {
    $("#demes-slider").slider( 'value', this.value );
  });

  // Population Slider setup
  $("#genes-min").attr('value', 25  );
  $("#genes-max").attr('value', 1000);
  $("#genes-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   200,
    min:     25,
    max:     1000,
    step:    1,
    slide: function( event, ui )
    {
      $( "#genes-value" ).attr('value', ui.value);
    },
  });

  $("#genes-value").change( function()
  {
    $("#genes-slider").slider( 'value', this.value );
  });

  // Generations Slider setup
  $("#generations-min").attr('value', 25  );
  $("#generations-max").attr('value', 500);
  $("#generations-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   100,
    min:     25,
    max:     500,
    step:    1,
    slide: function( event, ui )
    {
      $( "#generations-value" ).attr('value', ui.value);
    },
  });

  $("#generations-value").change( function()
  {
    $("#generations-slider").slider( 'value', this.value );
  });

  // Crossover Slider setup
  $("#crossover-min").attr('value', 0  );
  $("#crossover-max").attr('value', 100);
  $("#crossover-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   50,
    min:     0,
    max:     100,
    step:    1,
    slide: function( event, ui )
    {
      $( "#crossover-value" ).attr('value', ui.value);
    },
  });

  $("#crossover-value").change( function()
  {
    $("#crossover-slider").slider( 'value', this.value );
  });

  // Mutation Slider setup
  $("#mutation-min").attr('value', 0  );
  $("#mutation-max").attr('value', 100);
  $("#mutation-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   50,
    min:     0,
    max:     100,
    step:    1,
    slide: function( event, ui )
    {
      $( "#mutation-value" ).attr('value', ui.value);
    },
  });

  $("#mutation-value").change( function()
  {
    $("#mutation-slider").slider( 'value', this.value );
  });

  // Plague Slider setup
  $("#plague-min").attr('value', 0  );
  $("#plague-max").attr('value', 100);
  $("#plague-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   4,
    min:     0,
    max:     100,
    step:    1,
    slide: function( event, ui )
    {
      $( "#plague-value" ).attr('value', ui.value);
    },
  });

  $("#plague-value").change( function()
  {
    $("#plague-slider").slider( 'value', this.value );
  });

  // Elitism Slider setup
  $("#elitism-min").attr('value', 0 );
  $("#elitism-max").attr('value', 5 );
  $("#elitism-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   2,
    min:     0,
    max:     5,
    step:    1,
    slide: function( event, ui )
    {
      $( "#elitism-value" ).attr('value', ui.value);
    },
  });

  $("#elitism-value").change( function()
  {
    $("#elitism-slider").slider( 'value', this.value );
  });

  // Migration Slider setup
  $("#migration-min").attr('value', 0  );
  $("#migration-max").attr('value', 50);
  $("#migration-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   3,
    min:     0,
    max:     50,
    step:    1,
    slide: function( event, ui )
    {
      $( "#migration-value" ).attr('value', ui.value);
    },
  });

  $("#migration-value").change( function()
  {
    $("#migration-slider").slider( 'value', this.value );
  });

  // Regularization Slider setup
  // Values from 0-100, but this is in % so divide by 100 later
  $("#regularization-min").attr('value', 0  );
  $("#regularization-max").attr('value', 100);
  $("#regularization-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   5,
    min:     0,
    max:     100,
    step:    1,
    slide: function( event, ui )
    {
      $( "#regularization-value" ).attr('value', ui.value);
    },
  });

  $("#regularization-value").change( function()
  {
    $("#regularization-slider").slider( 'value', this.value );
  });

  // Random Seed Slider setup
  $("#seed-min").attr('value', 0   );
  $("#seed-max").attr('value', 1000);
  $("#seed-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   0,
    min:     0,
    max:     1000,
    step:    1,
    slide: function( event, ui )
    {
      $( "#seed-value" ).attr('value', ui.value);
    },
  });

  $("#seed-value").change( function()
  {
    $("#seed-slider").slider( 'value', this.value );
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

  // Parallel masters group count setup
  $("#PMGC_enable").change( function()
  {
    if ( $("#PMGC_enable").is(":checked") )
    {
       $("#PMGC_count").show();
       $("#req_mgroupcount").attr( 'value', 8 );
       $("#clusters-nopmg").hide();
       $("#clusters-pmg").show();
    }

    else
    {
       $("#PMGC_count").hide();
       $("#req_mgroupcount").attr( 'value', 1 );
       $("#clusters-nopmg").show();
       $("#clusters-pmg").hide();
    }

  });
  // Conc_threshold Slider setup
  $("#conc_threshold-min").attr('value', 0.00000001 );
  $("#conc_threshold-max").attr('value', 0.1 );
  $("#conc_threshold-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   -6,
    min:     -8,
    max:     -1,
    step:     1,
    slide: function( event, ui )
    {
      $( "#conc_threshold-value" ).attr('value', Math.pow(10, ui.value) );
    },
  });

  $("#conc_threshold-value").change( function()
  {
    $("#conc_threshold-slider").slider( 'value', Math.log(this.value) / Math.log(10) );
  });

  // P_grid Slider setup
  $("#p_grid-min").attr('value', 50 );
  $("#p_grid-max").attr('value', 5000);
  $("#p_grid-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   1000,
    min:     50,
    max:     5000,
    step:    1,
    slide: function( event, ui )
    {
      $( "#p_grid-value" ).attr('value', ui.value);
    },
  });

  $("#p_grid-value").change( function()
  {
    $("#p_grid-slider").slider( 'value', this.value );
  });

  // Mutate_sigma Slider setup
  $("#mutate_sigma-min").attr('value', -10 );
  $("#mutate_sigma-max").attr('value', 10 );
  $("#mutate_sigma-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   0,
    min:     -10,
    max:     10,
    step:    1,
    slide: function( event, ui )
    {
      $( "#mutate_sigma-value" ).attr('value', ui.value);
    },
  });

  $("#mutate_sigma-value").change( function()
  {
    $("#mutate_sigma-slider").slider( 'value', this.value );
  });

  // Mutate_s Slider setup
  $("#mutate_s-min").attr('value', 0 );
  $("#mutate_s-max").attr('value', 100 );
  $("#mutate_s-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   20,
    min:     0,
    max:     100,
    step:    1,
    slide: function( event, ui )
    {
      $( "#mutate_s-value" ).attr('value', ui.value);
    },
  });

  $("#mutate_s-value").change( function()
  {
    $("#mutate_s-slider").slider( 'value', this.value );
  });

  // Mutate_k Slider setup
  $("#mutate_k-min").attr('value', 0 );
  $("#mutate_k-max").attr('value', 100 );
  $("#mutate_k-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   20,
    min:     0,
    max:     100,
    step:    1,
    slide: function( event, ui )
    {
      $( "#mutate_k-value" ).attr('value', ui.value);
    },
  });

  $("#mutate_k-value").change( function()
  {
    $("#mutate_k-slider").slider( 'value', this.value );
  });

  // Mutate s/k Slider setup
  $("#mutate_sk-min").attr('value', 0 );
  $("#mutate_sk-max").attr('value', 100 );
  $("#mutate_sk-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   20,
    min:     0,
    max:     100,
    step:    1,
    slide: function( event, ui )
    {
      $( "#mutate_sk-value" ).attr('value', ui.value);
    },
  });

  $("#mutate_sk-value").change( function()
  {
    $("#mutate_sk-slider").slider( 'value', this.value );
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
                   "contact Borries Demeler (borries.demeler@umontana.edu).";

  return( true );
}

function validate_multiple( f )
{
  // Advanced users don't go through these tests
  if ( advanceLevel > 0 ) return( true );

  var contact_bo = "\nIf you have any questions about this policy, please " +
                   "contact Borries Demeler (borries.demeler@umontana.edu).";

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

