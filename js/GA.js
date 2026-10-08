// Javascript for GA controls

// jQuery slider controls
$(document).ready(function()
{
  // Montecarlo Slider setup
  $("#montecarlo-min").val(1);
  $("#montecarlo-max").val(100);
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
      $( "#mc_iterations" ).val(ui.value);
    },
  });

  $("#mc_iterations").on( 'change', function()
  {
    if ( this.value < 1 ) this.value = 1;
    if ( this.value > 100 ) this.value = 100;
    $("#montecarlo-slider").slider( 'value', this.value );
  });

  // Demes Slider setup
  $("#demes-min").val(1);
  $("#demes-max").val(100);
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
      $( "#demes-value" ).val(ui.value);
    },
  });

  $("#demes-value").on( 'change', function()
  {
    $("#demes-slider").slider( 'value', this.value );
  });

  // Population Slider setup
  $("#genes-min").val(25);
  $("#genes-max").val(1000);
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
      $( "#genes-value" ).val(ui.value);
    },
  });

  $("#genes-value").on( 'change', function()
  {
    $("#genes-slider").slider( 'value', this.value );
  });

  // Generations Slider setup
  $("#generations-min").val(25);
  $("#generations-max").val(500);
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
      $( "#generations-value" ).val(ui.value);
    },
  });

  $("#generations-value").on( 'change', function()
  {
    $("#generations-slider").slider( 'value', this.value );
  });

  // Crossover Slider setup
  $("#crossover-min").val(0);
  $("#crossover-max").val(100);
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
      $( "#crossover-value" ).val(ui.value);
    },
  });

  $("#crossover-value").on( 'change', function()
  {
    $("#crossover-slider").slider( 'value', this.value );
  });

  // Mutation Slider setup
  $("#mutation-min").val(0);
  $("#mutation-max").val(100);
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
      $( "#mutation-value" ).val(ui.value);
    },
  });

  $("#mutation-value").on( 'change', function()
  {
    $("#mutation-slider").slider( 'value', this.value );
  });

  // Plague Slider setup
  $("#plague-min").val(0);
  $("#plague-max").val(100);
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
      $( "#plague-value" ).val(ui.value);
    },
  });

  $("#plague-value").on( 'change', function()
  {
    $("#plague-slider").slider( 'value', this.value );
  });

  // Elitism Slider setup
  $("#elitism-min").val(0);
  $("#elitism-max").val(5);
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
      $( "#elitism-value" ).val(ui.value);
    },
  });

  $("#elitism-value").on( 'change', function()
  {
    $("#elitism-slider").slider( 'value', this.value );
  });

  // Migration Slider setup
  $("#migration-min").val(0);
  $("#migration-max").val(50);
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
      $( "#migration-value" ).val(ui.value);
    },
  });

  $("#migration-value").on( 'change', function()
  {
    $("#migration-slider").slider( 'value', this.value );
  });

  // Regularization Slider setup
  // Values from 0-100, but this is in % so divide by 100 later
  $("#regularization-min").val(0);
  $("#regularization-max").val(100);
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
      $( "#regularization-value" ).val(ui.value);
    },
  });

  $("#regularization-value").on( 'change', function()
  {
    $("#regularization-slider").slider( 'value', this.value );
  });

  // Random Seed Slider setup
  $("#seed-min").val(0);
  $("#seed-max").val(1000);
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
      $( "#seed-value" ).val(ui.value);
    },
  });

  $("#seed-value").on( 'change', function()
  {
    $("#seed-slider").slider( 'value', this.value );
  });

  // Simpoints Slider setup
  $("#simpoints-min").val(50);
  $("#simpoints-max").val(5000);
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
      $( "#simpoints-value" ).val(ui.value);
    },
  });

  $("#simpoints-value").on( 'change', function()
  {
    $("#simpoints-slider").slider( 'value', this.value );
  });

  // Band_volume Slider setup
  $("#band_volume-min").val(0.0);
  $("#band_volume-max").val(0.05);
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
      $( "#band_volume-value" ).val(ui.value);
    },
  });

  $("#band_volume-value").on( 'change', function()
  {
    $("#band_volume-slider").slider( 'value', this.value );
  });

  // Parallel masters group count setup
  $("#PMGC_enable").on( 'change', function()
  {
    if ( $("#PMGC_enable").is(":checked") )
    {
       $("#PMGC_count").show();
       $("#req_mgroupcount").val(4);
    }

    else
    {
       $("#PMGC_count").hide();
       $("#req_mgroupcount").val(1);
    }

  });
  // Conc_threshold Slider setup
  $("#conc_threshold-min").val(0.00000001);
  $("#conc_threshold-max").val(0.1);
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
      $( "#conc_threshold-value" ).val(Math.pow(10, ui.value));
    },
  });

  $("#conc_threshold-value").on( 'change', function()
  {
    $("#conc_threshold-slider").slider( 'value', Math.log(this.value) / Math.log(10) );
  });

  // S_grid Slider setup
  $("#s_grid-min").val(10);
  $("#s_grid-max").val(200);
  $("#s_grid-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   100,
    min:     10,
    max:     200,
    step:    1,
    slide: function( event, ui )
    {
      $( "#s_grid-value" ).val(ui.value);
    },
  });

  $("#s_grid-value").on( 'change', function()
  {
    $("#s_grid-slider").slider( 'value', this.value );
  });

  // K_grid Slider setup
  $("#k_grid-min").val(10);
  $("#k_grid-max").val(200);
  $("#k_grid-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   100,
    min:     10,
    max:     200,
    step:    1,
    slide: function( event, ui )
    {
      $( "#k_grid-value" ).val(ui.value);
    },
  });

  $("#k_grid-value").on( 'change', function()
  {
    $("#k_grid-slider").slider( 'value', this.value );
  });

  // Mutate_sigma Slider setup
  $("#mutate_sigma-min").val(10);
  $("#mutate_sigma-max").val(40);
  $("#mutate_sigma-slider").slider(
  {
    animate: true,
    range:   "min",
    value:   20,
    min:     10,
    max:     40,
    step:    1,
    slide: function( event, ui )
    {
      $( "#mutate_sigma-value" ).val(ui.value);
    },
  });

  $("#mutate_sigma-value").on( 'change', function()
  {
    $("#mutate_sigma-slider").slider( 'value', this.value );
  });

  // Mutate_s Slider setup
  $("#mutate_s-min").val(0);
  $("#mutate_s-max").val(100);
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
      $( "#mutate_s-value" ).val(ui.value);
    },
  });

  $("#mutate_s-value").on( 'change', function()
  {
    $("#mutate_s-slider").slider( 'value', this.value );
  });

  // Mutate_k Slider setup
  $("#mutate_k-min").val(0);
  $("#mutate_k-max").val(100);
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
      $( "#mutate_k-value" ).val(ui.value);
    },
  });

  $("#mutate_k-value").on( 'change', function()
  {
    $("#mutate_k-slider").slider( 'value', this.value );
  });

  // Mutate s/k Slider setup
  $("#mutate_sk-min").val(0);
  $("#mutate_sk-max").val(100);
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
      $( "#mutate_sk-value" ).val(ui.value);
    },
  });

  $("#mutate_sk-value").on( 'change', function()
  {
    $("#mutate_sk-slider").slider( 'value', this.value );
  });

  // Debug_level Slider setup
  $("#debug_level-min").val(0);
  $("#debug_level-max").val(4);
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
      $( "#debug_level-value" ).val(ui.value);
    },
  });

  $("#debug_level-value").on( 'change', function()
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

