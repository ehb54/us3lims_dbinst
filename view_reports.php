<?php
/*
 * view_reports.php
 *
 * View the report information that was stored in the DB by UltraScan III
 *
 */
include_once 'checkinstance.php';

$myID = $_SESSION['id'];

include 'config.php';
include 'db.php';
include 'lib/utility.php';
include 'lib/reports.php';
ini_set('display_errors', 'On');

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
   $personID = $_POST['personID'] ?? $myID;
   $reportID = $_POST['reportID'] ?? -1;

   $person_info = people_select( $link, 'people_select', $personID );
   $run_info    = run_select( $link, 'run_select', $reportID, $personID );
   $triple_list = tripleList( $link, $reportID );
   $combo_info  = combo_info( $link, $reportID );

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
{
   $docTypes = array();
   if ( isset( $_GET['a'] ) )
   {
      // Check the listed document types against the db
      $proposed_docTypes = explode( ',', $_GET['a'] );

      $dbTypes = array();
      $query  = "SELECT DISTINCT documentType FROM reportDocument " .
                "ORDER BY documentType ";
      $result = mysqli_query( $link, $query )
                or die( "Query failed : $query<br />\n" . mysqli_error($link) );
      while ( list( $dbType ) = mysqli_fetch_array( $result ) )
         $dbTypes[] = $dbType;

      // Now prune out document types that aren't in the db
      $docTypes = array();
      foreach ( $proposed_docTypes as $t )
      {
          if ( in_array( $t, $dbTypes ) )
             $docTypes[] = $t;
      }
   }

   $text = tripleDetail( $link, $_GET['triple'], $docTypes );
}

else if ( isset( $_GET['combo'] ) )
   $text = comboDetail( $link, $_GET['combo'] );

else
{
  $person_info = people_select( $link, 'people_select', $myID );
  $run_info    = run_select( $link, 'run_select' );

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
