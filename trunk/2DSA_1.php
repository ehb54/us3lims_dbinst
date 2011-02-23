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
include 'lib/controls_2DSA.php';

// Make sure the advancement level is set
$advanceLevel = ( isset($_SESSION['advancelevel']) )
              ? $_SESSION['advancelevel'] : 0;

$separate_datasets = ( isset( $_SESSION['separate_datasets'] ) )
                   ? $_SESSION['separate_datasets'] : 1;

// To support multiple datasets, let's keep track of which one we're on
$num_datasets = sizeof( $_SESSION['request'] );

// Create the payload manager
$payload  = new Payload_2DSA( $_SESSION );

// Create the display controls
$controls = new Controls_2DSA();

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

  // get previous payload data and add this session to it
  $payload->restore();
  $payload->add( 'cluster', $_SESSION['cluster'] );
  $payload->acquirePostedData( $dataset_id, $num_datasets );
  $payload->save();

  // Check to see if the file is too big
  if ( $advanceLevel == 0 )
    ; //    check_filesize();

//  $payload->show();
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
}

// Start displaying page
$page_title = $controls->pageTitle();
$css = 'css/luna/luna.css';    // This is for the slider
$js = 'js/analysis.js,js/range.js,js/timer.js,js/slider.js';
include 'top.php';
include 'links.php';

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
                <?php echo $separate_datasets; ?>);" >


<?php
//  if ( isset($error) ) echo $error;

  $controls->display( $dataset_id, $num_datasets );

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
<?php $payload->show(); ?>

</div>

<!-- This must be loaded down here, after all the controls are on the page -->
<script type='text/javascript' src='js/2DSA.js'></script>
</div>

<?php
include 'bottom.php';
exit();
?>
