<?php
/*
 * view_reports.php
 *
 * View the report information that was stored in the DB by UltraScan III
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

$myID = $_SESSION['id'];

include 'config.php';
include 'db.php';
include 'lib/utility.php';
include 'lib/reports.php';

// Start displaying page
$page_title = "View Reports";
$css = 'css/reports.css';
$js  = 'js/reports.js';
include 'header.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">View Reports</h1>
  <!-- Place page content here -->

<?php
if ( isset( $_POST['change_cell'] ) )
{
   $personID = isset( $_POST['personID'] ) ? $_POST['personID'] : $myID;
   $reportID = isset( $_POST['reportID'] ) ? $_POST['reportID'] : -1;

   $person_info = people_select( 'people_select', $personID );
   $run_info    = run_select( 'run_select', $reportID, $personID );
   $triple_list = tripleList( $reportID );
   $combo_info  = combo_info( $reportID );

  $text =<<<HTML
  <div id='personID'>$person_info</div>
  <div id='report_content'>
    <div id='runID'>$run_info</div>
    <div id='tripleID'>$triple_list</div>
    <div id='combos'>$combo_info</div>
  </div>

  <script>
    $('#people_select').change( change_person );
    $('#run_select')   .change( change_run_select );
  </script>
HTML;
}

else if ( isset( $_GET['triple'] ) )
   $text = tripleDetail( $_GET['triple'] );

else if ( isset( $_GET['combo'] ) )
   $text = comboDetail( $_GET['combo'] );

else
{
  $person_info = people_select( 'people_select', $myID );
  $run_info    = run_select( 'run_select' );

  $text =<<<HTML
  <div id='personID'>$person_info</div>
  <div id='report_content'>
    <div id='runID'>$run_info</div>
    <div id='tripleID'></div>
    <div id='combos'></div>
  </div>

  <script>
    $('#people_select').change( change_person );
    $('#run_select')   .change( change_run_select );
  </script>
HTML;
}

echo $text;
?>
</div>

<?php
include 'footer.php';
exit();

?>
