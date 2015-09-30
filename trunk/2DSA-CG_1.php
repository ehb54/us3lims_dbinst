<?php
/*
 * 2DSA-CG_1.php
 *
 * A place to start entering submission parameters for the 2DSA analysis with
 *   custom grid
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

// Create the payload manager
$payload  = new Payload_2DSA_CG( $_SESSION );

// First, let's see if the "TIGRE" button has been pressed
if ( isset($_POST['TIGRE']) )
{
  $dataset_id = $num_datasets - 1;

  // Save cluster information
  if ( isset($_POST['cluster']) )
  {
    list( $cluster_name, $cluster_shortname, $queue ) = explode(":", $_POST['cluster'] );
    $_SESSION['cluster']              = array();
    $_SESSION['cluster']['name']      = $cluster_name;
    $_SESSION['cluster']['shortname'] = $cluster_shortname;
    $_SESSION['cluster']['queue']     = $queue;
  }

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
  $payload->add( 'cluster', $_SESSION['cluster'] );
  $payload->acquirePostedData( $dataset_id, $num_datasets );
  $payload->save();

  // Check to see if the file is too big
  if ( $advanceLevel == 0 )
    ; //    check_filesize();

//  $payload->show();
  header("Location: 2DSA-CG_2.php");
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

// Get some other session data
$editMeniscus = $_SESSION['request'][$dataset_id]['editMeniscus'];
$dataLeft     = $_SESSION['request'][$dataset_id]['dataLeft'];

// Start displaying page
$page_title = '2DSA Analysis with Custom Grid';
$css = 'css/slider.css';
$js = 'js/analysis.js,js/2DSA.js';
include 'header.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title"><?php echo $page_title; ?></h1>
  <!-- Place page content here -->

<div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post"
      onsubmit="return validate(this, 
                <?php echo $advanceLevel; ?>, 
                <?php echo $dataset_id; ?>,
                <?php echo $num_datasets; ?>, 
                <?php echo $separate_datasets; ?>,
                <?php echo $editMeniscus; ?>,
                <?php echo $dataLeft; ?>);" >


<?php
// if ( isset($error) ) echo $error;

  $max_dset_id   = $num_datasets - 1;
  $dataset_id    = min( $dataset_id, $max_dset_id );
  $display_dsid  = ( $advanced_review == 0 ) ? 0 : $dataset_id;

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

  echo "  </fieldset>\n";

  if ( $dataset_id == $max_dset_id )
    echo tigre();

?>

</form>
<?php $payload->show(); ?>

</div>

</div>

<?php
include 'footer.php';
exit();

// A function to display controls for one dataset
function display( $dataset_id, $num_datasets )
{
  // Get edited data profile
  $parts = explode( ".", $_SESSION['request'][$dataset_id]['editFilename'] );
  $edit_text = $parts[1];
  echo "  <fieldset>" .
       "    <legend>Initialize 2DSA Parameters - " .
       "{$_SESSION['request'][$dataset_id]['filename']}; " .
       "Edit profile: $edit_text; " .
       "            Dataset " . ($dataset_id + 1) . " of $num_datasets</legend>\n";

  if ( $dataset_id == 0 )
  {
    CG_select_setup();
    uniform_grid_setup();
    montecarlo();

    if ( $num_datasets > 1  &&  $separate_datasets == 2 )
    {
      PMGC_option();
    }

    tinoise_option();
  }
 
  echo<<<HTML
    <p><button onclick="return toggle('advanced');" id='show'>
      Show Advanced Options</button></p>

    <div id='advanced' style='display:none;'>

HTML;

  if ( $dataset_id == 0 )
  {
    rinoise_option();
    fit_meniscus();
    iterations_option();

    debug_option();
  }

  simpoints_input();
  band_volume_input();
  radial_grid_input();
  time_grid_input();

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
