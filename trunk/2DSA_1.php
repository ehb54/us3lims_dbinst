<?php
/*
 * 2DSA_1.php
 *
 * A place to start entering submission parameters for the 2DSA analysis
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

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

// define( 'DEBUG', true );

include 'config.php';
include 'db.php';
include 'lib/utility.php';
include 'lib/payload_manager.php';
include 'lib/analysis.php';

// Make sure the advancement level is set
$advanceLevel = ( isset($_SESSION['advancelevel']) )
              ? $_SESSION['advancelevel'] : 0;

$separate_datasets = ( isset( $_SESSION['separate_datasets'] ) )
                   ? $_SESSION['separate_datasets'] : 1;

// To support multiple datasets, let's keep track of which one we're on
$num_datasets = sizeof( $_SESSION['request'] );

// Create the payload manager
$payload = new payload_manager( $_SESSION );

// First, let's see if the "TIGRE" button has been pressed
if ( isset($_POST['TIGRE']) )
{
  $dataset_id = $num_datasets - 1;

  // Save cluster information
  if ( isset($_POST['cluster']) )
  {
    list( $cluster_name, $cluster_shortname ) = explode(":", $_POST['cluster'] );
    $_SESSION['cluster']              = array();
    $_SESSION['cluster']['name']      = $cluster_name;
    $_SESSION['cluster']['shortname'] = $cluster_shortname;
  }

  // get previous payload data and add this session to it
  $payload->restore();
  save_posted_data($dataset_id);
  $payload->save();

  // Check to see if the file is too big
  if ( $advanceLevel == 0 )
    ; //    check_filesize();

//  show_mem( "After pressing TIGRE" );
  header("Location: 2DSA_2.php");
  exit();
}

// Now let's see if the "next" button has been pressed
else if ( isset($_POST['next']) )
{
  $dataset_id = ( $_POST['dataset_id'] < $num_datasets - 1 )
                ? $_POST['dataset_id'] : $num_datasets - 1;

  // get previous payload data and add this session to it
  $payload->restore();
  save_posted_data($dataset_id);
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
}

// Start displaying page
$page_title = "2DSA Analysis";
$css = 'css/luna/luna.css';    // This is for the slider
$js = 'js/analysis.js,js/range.js,js/timer.js,js/slider.js';
include 'top.php';
include 'links.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">2DSA Analysis</h1>
  <!-- Place page content here -->

<div>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post"
      onsubmit="return validate(this, 
                <?php echo $advanceLevel; ?>, 
                <?php echo $dataset_id; ?>,
                <?php echo $num_datasets; ?>, 
                <?php echo $separate_datasets; ?>);" >


<?php
//  if ( isset($error) ) echo $error;

  display_controls();

  // Display some information about the current dataset
  echo "  <fieldset>\n" .
       "    <h3>Dataset control:</h3>\n" .
       "    <ul>\n" .
       "      <li>Current dataset number: " . ($dataset_id + 1) . "</li>\n" .
       "      <li>Run Name: {$_SESSION['request'][$dataset_id]['filename']}</li>\n" .
       "      <li>Number of datasets: $num_datasets</li>\n" .
       "    </ul>\n";

  // Add some controls to move to the next dataset, if there's more than one
  if ( $num_datasets > 1 && $dataset_id < $num_datasets - 1 )
  {
    echo "    <input type='hidden' name='dataset_id' value='$dataset_id' />\n" .
         "    <input class='submit' type='submit' name='next' value='Next Dataset --&gt;' /></p>\n";
  }

  echo "  </fieldset>\n";

  if ( $dataset_id == $num_datasets - 1 )
    echo tigre();

?>

</form>
<?php // show_mem( "From main" ); ?>

</div>

<!-- This must be loaded down here, after all the controls are on the page -->
<script type='text/javascript' src='js/2DSA.js'></script>
</div>

<?php
include 'bottom.php';
exit();

// A function to display controls for one dataset
function display_controls()
{
  global $dataset_id, $num_datasets;

  echo "  <fieldset>" .
       "    <legend>Initialize 2DSA Parameters - {$_SESSION['request'][$dataset_id]['filename']}" .
       "            Dataset " . ($dataset_id + 1) . " of $num_datasets</legend>\n";

  if ( $dataset_id == 0 )
  {
    s_value_setup();
    f_f0_setup();
    uniform_grid_setup();
    montecarlo();
    tinoise_option();
  }
?>
  
    <p><button onclick="return toggle('advanced');" id='show'>Show Advanced Options</button></p>

    <div id='advanced' style='display:none;'>

<?php
  if ( $dataset_id == 0 )
  {
    regularization_setup();
    fit_meniscus();
    iterations_option();
  }

  simpoints_input();
  band_volume_input();
  radial_grid_input();
  time_grid_input();

  if ( $dataset_id == 0 )
    rinoise_option();
?>

    </div>

    <input class="submit" type="button" 
            onclick='window.location="queue_setup_2.php"' 
            value="Edit Profiles"/>
    <input class="submit" type="button" 
            onclick='window.location="queue_setup_1.php"' 
            value="Change Experiment"/>
  </fieldset>

<?php
}

function save_posted_data($dataset_id)
{
  global $payload, $num_datasets, $dbname, $dbhost;
  global $udpport, $ipaddr;

  // A lot of this only gets posted the first time through
  if ( $dataset_id == 0 )
  {
    $payload->add( 'method', '2DSA' );
    $payload->add( 'cluster', $_SESSION['cluster'] );

    $udp                  = array();
    $udp['udpport']       = $udpport;
    $udp['ip']            = $ipaddr;
    $payload->add( 'server', $udp );

    $payload->add( 'directory', $_SESSION['request'][$dataset_id]['path'] );
    $payload->add( 'datasetCount', $num_datasets );

    $database             = array();
    $database['name']     = $dbname;
    $database['host']     = $dbhost;
    $database['user_email'] = $_SESSION['email'];
    $database['submitter_email'] = $_SESSION['submitter_email'];
    $payload->add( 'database', $database );

    $job_parameters                     = array();
    $job_parameters['s_min']            = $_POST['s_value_min'];
    $job_parameters['s_max']            = $_POST['s_value_max'];
    $job_parameters['s_resolution']     = $_POST['s_resolution'];
    $job_parameters['ff0_min']          = $_POST['ff0_min'];
    $job_parameters['ff0_max']          = $_POST['ff0_max'];
    $job_parameters['ff0_resolution']   = $_POST['ff0_resolution'];
    $job_parameters['uniform_grid']     = $_POST['uniform_grid'];
    $job_parameters['mc_iterations']    = $_POST['mc_iterations'];
    $job_parameters['tinoise_option']   = $_POST['tinoise_option'];
    $job_parameters['regularization']   = $_POST['regularization'];
    $job_parameters['meniscus_range']   = ( $_POST['meniscus_option'] == 1 )
                                        ? $_POST['meniscus_range'] : 0.0;
    $job_parameters['meniscus_points']  = ( $_POST['meniscus_option'] == 1 )
                                        ? $_POST['meniscus_points'] : 1;
    $job_parameters['max_iterations']   = ( $_POST['iterations_option'] == 1 )
                                        ? $_POST['max_iterations'] : 1;
    $job_parameters['rinoise_option']   = $_POST['rinoise_option'];
    $job_parameters['experimentID']     = $_SESSION['experimentID'];
    $payload->add( 'job_parameters', $job_parameters );

    $dataset = array();
      $dataset[ 0 ]['files']      = array();   // This will be done later
      $dataset[ 0 ]['parameters'] = array();
  }

  // These will be done every time
  $rawDataID = $_SESSION['request'][$dataset_id]['rawDataID'];

  // we need the stretch function from the rotor table
  $rotor_stretch = "0 0";
  $query  = "SELECT coeff1, coeff2 " .
            "FROM rawData, experiment, rotorCalibration " .
            "WHERE rawData.rawDataID = $rawDataID " .
            "AND rawData.experimentID = experiment.experimentID " .
            "AND experiment.rotorCalibrationID = rotorCalibration.rotorCalibrationID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />" . mysql_error());
  if ( mysql_num_rows( $result ) > 0 )
  {
    list( $coeff1, $coeff2 ) = mysql_fetch_array( $result );      // should be 1
    $rotor_stretch = "$coeff1 $coeff2";
  }

  // We need the centerpiece bottom
  $centerpiece_bottom = 7.3;
  $centerpiece_shape  = 'standard';
  $query  = "SELECT shape, bottom " .
            "FROM rawData, cell, abstractCenterpiece " .
            "WHERE rawData.rawDataID = $rawDataID " .
            "AND rawData.experimentID = cell.experimentID " .
            "AND cell.abstractCenterpieceID = abstractCenterpiece.abstractCenterpieceID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />" . mysql_error());
  if ( mysql_num_rows ( $result ) > 0 )
    list( $centerpiece_shape, $centerpiece_bottom ) = mysql_fetch_array( $result );      // should be 1

  // We also need some information about the solution in this cell
  $vbar20 = 0.0;
  $query  = "SELECT commonVbar20 " .
            "FROM rawData, solution " .
            "WHERE rawData.rawDataID = $rawDataID " .
            "AND rawData.solutionID = solution.solutionID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />" . mysql_error());
  if ( mysql_num_rows( $result ) > 0 )
    list( $vbar20 ) = mysql_fetch_array( $result );      // should be 1

  // Finally, some buffer information
  $density = 0.0;
  $viscosity = 0.0;
  $query  = "SELECT viscosity, density " .
            "FROM rawData, solutionBuffer, buffer " .
            "WHERE rawData.rawDataID = $rawDataID " .
            "AND rawData.solutionID = solutionBuffer.solutionID " .
            "AND solutionBuffer.bufferID = buffer.bufferID ";
  $result = mysql_query( $query )
            or die( "Query failed : $query<br />" . mysql_error());
  if ( mysql_num_rows ( $result ) > 0 )
    list( $viscosity, $density ) = mysql_fetch_array( $result );      // should be 1

  // Get arrays with multiple dataset data
  $dataset                    = $payload->get('dataset');

  // Add new element to the arrays
  $parameters                 = array();
  $parameters                 = $dataset['parameters'];
  $parameters['rawDataID']    = $_SESSION['request'][$dataset_id]['rawDataID'];
  $parameters['auc']          = $_SESSION['request'][$dataset_id]['filename'];
  $parameters['editedDataID'] = $_SESSION['request'][$dataset_id]['editedDataID'];
  $parameters['edit']         = $_SESSION['request'][$dataset_id]['editFilename'];
//  $parameters['modelID']      = $_SESSION['request'][$dataset_id]['modelID'];
  $parameters['noiseIDs']     = array();
  $parameters['noiseIDs']     = $_SESSION['request'][$dataset_id]['noiseIDs'];
  
  $parameters['simpoints']    = $_POST['simpoints-value'];
  $parameters['band_volume']  = ( $centerpiece_shape == 'band_forming' )
                              ? $_POST['band_volume-value']
                              : 0.0;
  $parameters['radial_grid']  = $_POST['radial_grid'];
  $parameters['time_grid']    = $_POST['time_grid'];

  // These are simulation parameters looked up in the db
  $parameters['rotor_stretch'] = $rotor_stretch;
  $parameters['centerpiece_bottom'] = $centerpiece_bottom;
  $parameters['vbar20']       = $vbar20;
  $parameters['density']      = $density;
  $parameters['viscosity']    = $viscosity;

  // Replace arrays with revised datasets
  $dataset[$dataset_id]       = $parameters;
  $payload->add( 'dataset', $dataset );

}

function show_mem($string = "")
{
  if ( DEBUG ) 
  {
    global $payload;

    echo '<pre>';
    echo "$string\n";
    echo 'Payload...';
    print_r( $payload->get() );
    echo 'Session variables...';
    print_r( $_SESSION );
    echo '</pre>';
  }

}
?>
