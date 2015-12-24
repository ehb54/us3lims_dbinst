<?php
/*
 * GA_1.php
 *
 * A place to start entering submission parameters for the GA analysis
 *
 */
include_once 'checkinstance.php';

if ( ($_SESSION['userlevel'] != 2) &&
     ($_SESSION['userlevel'] != 3) &&
     ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) )    // only data analyst and up
{
  header('Location: index.php');
  exit();
} 

// Verify that job submission is ok now
include 'lib/motd.php';
if ( motd_isblocked() && ($_SESSION['userlevel'] < 4) )
{
  header("Location: index.php");
  exit();
}

// Verify that there is something in the queue
if ( ! isset( $_SESSION['request'] ) || sizeof( $_SESSION['request'] ) < 1 )
{
  header("Location: queue_setup_1.php");
  exit();
}

// define( 'DEBUG', true );

include 'config.php';
include 'db.php';
include 'lib/utility.php';
include 'lib/payload_manager.php';
include 'lib/controls.php';

// Make sure the advancement level is set
$advanceLevel = ( isset($_SESSION['advancelevel']) )
              ? $_SESSION['advancelevel'] : 0;

$separate_datasets = ( isset( $_SESSION['separate_datasets'] ) )
                   ? $_SESSION['separate_datasets'] : 1;
$advanced_review   = ( isset( $_SESSION['advanced_review'] ) )
                   ? $_SESSION['advanced_review'] : 0;

// To support multiple datasets, let's keep track of which one we're on
$num_datasets = sizeof( $_SESSION['request'] );

// Verify that this is not composite jobs
if ( $num_datasets > 1  &&  $separate_datasets == 1 )
{
  header("Location: queue_setup_1.php");
  exit();
}

// Create the payload manager
$payload  = new Payload_GA( $_SESSION );

// Now let's see if the "last" button has been pressed
if ( isset($_POST['last']) )
{
  $dataset_id = ( $_POST['dataset_id'] < $num_datasets - 1 )
                ? $_POST['dataset_id'] : $num_datasets - 1;

  if ( $num_datasets > 1  &&  $advanced_review == 0 )
  { // For multiple datasets and no advanced review, create all payloads
    $payload->clear();
    for ( $jdataset_id = 0; $jdataset_id < ( $num_datasets - 1 ); $jdataset_id++ )
    {
      $payload->acquirePostedData( $jdataset_id, $num_datasets );
    }
    $payload->save();
  }

  // get previous payload data and add this session to it
  $payload->restore();
  $payload->acquirePostedData( $dataset_id, $num_datasets );
  $payload->save();

  header( "Location: GA_2.php" );
  exit();
}

// Now let's see if the "next" button has been pressed
else if ( isset($_POST['next']) )
{
  $dataset_id = ( $_POST['dataset_id'] < $num_datasets - 1 )
                ? $_POST['dataset_id'] : $num_datasets - 1;

  // get previous payload data and add this session to it
  $payload->restore();
  $payload->acquirePostedData( $dataset_id, $num_datasets );
  $payload->save();

  $dataset_id++;
}

// In this case it's the first time here
else
{
  $dataset_id = 0;

  // Add the initial options to the payload
  $payload->clear();

  $payload->save();

  // If multi-data and no advanced review, point to last dataset
  if ( $advanced_review == 0  &&  $num_datasets > 1 )
    $dataset_id = $num_datasets - 1;
}

// Start displaying page
$page_title = 'GA Analysis';
$css = 'css/slider.css';
$js = 'js/analysis.js,js/GA.js';
include 'header.php';

$new_noise = '';
if ( isset($_SESSION['edit_select_type'])  &&
     $_SESSION['edit_select_type'] == 0 )
{  // If latest edits and noise, reset the noises
  set_latest_noises();
  $new_noise = $_SESSION['new_noise'];
}

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title"><?php echo $page_title; ?></h1>
  <!-- Place page content here -->
  <?php echo $new_noise; ?>

<div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post"
      onsubmit="return validate(this, 
                <?php echo $advanceLevel; ?>, 
                <?php echo $num_datasets; ?>);" >


<?php
//  if ( isset($error) ) echo $error;
  $max_dset_id  = $num_datasets - 1;
  $dataset_id   = ( $dataset_id < $num_datasets ) ? $dataset_id : $max_dset_id;
  $display_dsid = ( $advanced_review == 0 ) ? 0 : $dataset_id;

  display( $display_dsid, $num_datasets );

  // Display some information about the current dataset
  echo "  <fieldset>\n" .
       "    <h3>Dataset control:</h3>\n" .
       "    <ul>\n" .
       "      <li>Current dataset number: " . ($dataset_id + 1) . "</li>\n" .
       "      <li>Run Name: {$_SESSION['request'][$dataset_id]['filename']}</li>\n" .
       "      <li>Number of datasets: $num_datasets</li>\n" .
       "    </ul>\n";

  // Add some controls to move to the next dataset, if there's more than one
  if ( $num_datasets > 1  &&  $dataset_id < $max_dset_id )
  {
    echo "    <input type='hidden' name='dataset_id' value='$dataset_id' />\n" .
         "    <input class='submit' type='submit' name='next' value='Next Dataset --&gt;' /></p>\n";
  }

  // Add controls to save the last or only dataset
  if ( $dataset_id == $max_dset_id )
  {
    echo "    <input type='hidden' name='dataset_id' value='$dataset_id' />\n" .
         "    <input class='submit' type='submit' name='last' value='Enter Solute Data--&gt;' /></p>\n";
  }

  echo "  </fieldset>\n";

?>

</form>
<?php $payload->show(); ?>

</div>

</div>

<?php
include 'footer.php';
exit();

// Function to display controls for one dataset
function display( $dataset_id, $num_datasets )
{
  // Get edited data profile
  $parts = explode( ".", $_SESSION['request'][$dataset_id]['editFilename'] );
  $edit_text = $parts[1];
?>
    <fieldset>
      <legend>Initialize Genetic Algorithm Parameters -
            <?php echo "{$_SESSION['request'][$dataset_id]['filename']}; " .
                       "Edit profile: $edit_text; " .
                       "Dataset " . ($dataset_id + 1) . " of $num_datasets";?></legend>

      <?php 
            if ( $dataset_id == 0 ) 
            {
              montecarlo(); 
              if ( $num_datasets == 1 )
              {
                PMGC_option();
              }
            }
      ?>

      <p><button onclick="return toggle('advanced');" id='show'>
        Show Advanced Options</button></p>

      <div id='advanced' style='display:none'>

<?php
  if ( $dataset_id == 0 )
  {
    // First time only
    demes_setup(); 
    genes_setup();
    generations_setup();
    crossover_percent();
    mutation_percent();
    plague_percent();
    elitism_setup();
    migration_rate();
    ga_regularization_setup();
    random_seed();

    simpoints_input();
    band_volume_input();
    radial_grid_input();
    time_grid_input();

    conc_threshold_setup();
    s_grid_setup();
    k_grid_setup();
    mutate_sigma_setup();
    mutate_s_setup();
    mutate_k_setup();
    mutate_sk_setup();

    debug_option();
  }

  else
  {
    // These are displayed each time.
    simpoints_input();
    band_volume_input();
    radial_grid_input();
    time_grid_input();
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
?>
