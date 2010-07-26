<?php
/*
 * 2DSA_2.php
 *
 * Final database update and submission for the 2DSA analysis
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

define( 'DEBUG', true );

include 'config.php';
include 'db.php';
include 'lib/utility.php';
include 'lib/payload_manager.php';
include 'lib/analysis.php';
include 'lib/HPC_analysis.php';

// Create the payload manager and restore the data
$payload = new payload_manager( $_SESSION );
$payload->restore();

// Create the HPC analysis agent and write data to the db
$HPC = new HPC_analysis();
$HPCAnalysisRequestID = $HPC->writeDB( $payload );

// Start displaying page
$page_title = "2DSA Analysis Submitted";
include 'top.php';
include 'links.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">2DSA Analysis Submitted</h1>
  <!-- Place page content here -->

<?php echo "<p>HPCAnalysisRequestID = $HPCAnalysisRequestID</p>\n"; ?>

<?php 
  if ( isset($message) ) echo "<p class='message'>$message</p>\n";
 ?>
  
  <p>Thank you, your job was accepted to bcf and is currently processing, an
  email will be sent to <?php echo $_SESSION['queue_email']; ?> when the job is
  completed.</p>  

  <p><a href="queue_setup_1.php">Submit another request</a></p>

</div>

<?php
include 'bottom.php';
exit();
?>
<?php
/*
// Write the file(s), accounting for separating datasets
$file = new file_writer();
if ( $_SESSION['separate_datasets'] )
{
  $dataset_count = $payload->payload_get( 'count' );
  for ( $i = 0; $i < $dataset_count; $i++ )
    $theFiles[ $i ] = $file->write( $payload->payload_get_dataset( $i ) );
}

else
  $theFiles[ 0 ] = $file->write( $payload->payload_get() );

if ( DEBUG )
{
  echo "<pre>SessionID = " . session_id() . "\n";
  echo "Time() = " . time() . "\n</pre>\n";
  echo "<pre>Filenames:\n";
  foreach ( $theFiles as $theFile )
    echo "$theFile\n";
  echo "</pre>\n";

  exit();
}

// EXEC COMMAND FOR TIGRE 
if ( isset($_SESSION['cluster']) )
{
  $cluster = $_SESSION['cluster'];
  unset( $_SESSION['cluster'] );

  foreach ( $theFiles as $theFile )
  {
    $to = "dzollars@gmail.com";
    $subject = "Logging from demo_ana_8b.php..."; 
    $e_msg = "SessionID = " . session_id() . "\n";
    $e_msg .= "Time() = " . time() . "\n\n";
    $e_msg .= "Filename:\n";
    $e_msg .= "$theFile\n";
    mail($to, $subject, $e_msg);

    $submit  = "echo gc_tigre $theFile $cluster > " .
               "/share/apps64/ultrascan/etc/us_gridpipe";
    exec($submit, $retval);
  }
}

*/
?>
