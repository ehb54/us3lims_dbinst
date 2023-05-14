<?php
/*
 * queue_setup_1.php
 *
 * A place to begin queue setup for a supercomputer analysis
 *
 */
include_once 'checkinstance.php';
elogrsp( __FILE__ );

// ini_set('display_errors', 'On');

//$time0=microtime(true);
if ( ($_SESSION['userlevel'] != 2) &&
     ($_SESSION['userlevel'] != 3) &&
     ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) )    // only data analyst and up
{
  if ( $is_cli ) {
    $errstr = "ERROR: " . __FILE__ . " user level is insufficient";
    echo "$errstr\n";
    $cli_errors[] = $errstr;
    return;
  } else {
    header('Location: index.php');
  }
  exit();
}

if ( isset( $_GET['reset'] ) )
{
  unset( $_SESSION['new_submitter'] );
  unset( $_SESSION['add_owner'] );
  unset( $_SESSION['new_expID'] );
  unset( $_SESSION['new_cells'] );

  header( "Location: {$_SERVER['PHP_SELF']}" );
  if ( $is_cli ) {
    echo __FILE__ . " exiting 2\n";
  }
  exit();
}

include 'config.php';
include 'db.php';

// Reset multiple dataset processing for later
unset( $_SESSION['separate_datasets'] );
// Likewise for edit selection type and advanced review
unset( $_SESSION['edit_select_type' ] );
unset( $_SESSION['advanced_review'  ] );

// Get posted information, if any
// Reset submitter email address, in case previous experiment had different owner, etc.
$submitter_email = $_SESSION['email'];
if ( isset( $_POST['submitter_email'] ) )
{
  $submitter_email = $_POST['submitter_email'];
}

else if ( isset( $_SESSION['new_submitter'] ) )
{
  $submitter_email = $_SESSION['new_submitter'];
}

$add_owner = ( isset( $_POST['add_owner'] ) ) ? 1 : 0;

$experimentID = 0;
//$time1=microtime(true) - $time0;
if ( isset( $_POST['expIDs'] ) )
{
  $_SESSION['new_expIDs'] = array();
  foreach( $_POST['expIDs'] as $expID )
  {
    $experimentID = $expID;
  }
}

else if ( isset( $_SESSION['new_expID'] ) )
{
  $experimentID = $_SESSION['new_expID'];
}
//$time2=microtime(true) - $time0;

// Let's see if we should go to the next page
if ( isset( $_POST['next'] ) )
{
  $_SESSION['new_submitter'] = $submitter_email;
  $_SESSION['add_owner']     = $add_owner;
  $_SESSION['new_expID']     = $experimentID;

  // Extract rawDataID and filename from cells[]
  $_SESSION['new_cells']     = array();
  if ( isset( $_POST['cells'] ) )
  {
    foreach( $_POST['cells'] as $cell )
    {
      list( $rawDataID, $filename ) = explode( ":", $cell );
      $_SESSION['new_cells'][$rawDataID] = $filename;
    }
  }
  

  if ( $is_cli ) {
    $_REQUEST=[];
    $_POST   =[];
    include "queue_setup_2.php";
    return;
  } else {
    header( "Location: queue_setup_2.php" );
    exit();
  }
}

// Start displaying page
$page_title = "Queue Setup (part 1)";
$css = 'css/queue_setup.css';
include 'header.php';
include 'lib/motd.php';

?>
<!-- Begin page content -->
<div id='content'>

  <a name='title'></a>
  <h1 class="title">Queue Setup (part 1)</h1>

<?php
// Verify that submission is ok now
motd_block();
//$time3=microtime(true) - $time0;

// If we are here, either userlevel is 4 or it's not blocked

$email_text      = get_email_text($link);
//$time4=microtime(true) - $time0;
$experiment_text = get_experiment_text($link);
//$time5=microtime(true) - $time0;
$cell_text       = get_cell_text($link);
//$time6=microtime(true) - $time0;
$submit_text     = "<p style='padding-bottom:3em;'></p>\n";  // a spacer
if ( $experimentID != 0 )
{
  $submit_text = <<<HTML
  <p><input type='button' value='Select Different Experiment'
            onclick='window.location="{$_SERVER['PHP_SELF']}?reset=true";' />
     <input type='submit' name='next' value='Add to Queue'/>
  </p>

HTML;
}

//$time7=microtime(true) - $time0;
//$email_text .= "  <p>time1 = $time1 </p>";
//$email_text .= "  <p>time2 = $time2 </p>";
//$email_text .= "  <p>time3 = $time3 </p>";
//$email_text .= "  <p>time4 = $time4 </p>";
//$email_text .= "  <p>time5 = $time5 </p>";
//$email_text .= "  <p>time6 = $time6 </p>";
//$email_text .= "  <p>time7 = $time7 </p>";
//  <form action="{$_SERVER['PHP_SELF']}#title" method="post">
//$this_url = "https://$org_site/queue_setup_1.php";
//$this_url = "{$_SERVER['PHP_SELF']}#title" method="post">
//  <form action="$this_url#title" method="post">
echo <<<HTML
  <form action="{$_SERVER['PHP_SELF']}#title" method="post">
    <fieldset>
      <legend>Initial Queue Setup</legend>

      $email_text
      $experiment_text
      $cell_text

<button  type="button" onclick="SelectAllCells()">Select all cells</button>

        <script>
            function SelectAllCells(){
                options = document.getElementById("cells");
                options[0].selected = false;
                for (i=1; i < options.length; i++)
                {
                    options[i].selected = true;
                }
            }
        </script>

    </fieldset>

    $submit_text

  </form>

HTML;

motd_submit();

// Add rss information from TACC
require_once 'lib/rss_fetch.inc';

//$time8=microtime(true) - $time0;
$url = 'http://www.tacc.utexas.edu/rss/TACCUserNews.xml';
$num_items = 3;
$rss = fetch_rss($url);
$items = array_slice($rss->items, 0, $num_items);
//  $items = array();

echo "<h3>{$rss->channel['title']}</h3>\n";

// Generate table
echo "<table cellpadding='7' cellspacing='0'>\n";
foreach ( $items as $item )
{
  $title       = $item['title'];
  $url         = $item['link'];
  $description = $item['description'];

  echo <<<HTML
  <tr><td><a href=$url>$title</a></td>
      <td>$description</td></tr>

HTML;
}
echo "</table>\n";
//$time9=microtime(true) - $time0;
//echo "  <p>time8 = $time8 </p>";
//echo "  <p>time9 = $time9 </p>";

?>

</div>

<?php
include 'footer.php';
if ( $is_cli ) {
  echo __FILE__ . " exiting 4\n";
}
exit();

function get_email_text($link)
{
  global $submitter_email, $add_owner;

  $msg1  = "";
  $msg1a = "";
  if ( isset( $_SESSION['message1'] ) )
  {
    $msg1  = "<p class='message'>{$_SESSION['message1']}</p>";
    $msg1a = "<span class='message'>*</span>";
    unset( $_SESSION['message1'] );
  }

  // Check if current user is the data owner
  $checked = ( $add_owner == 1 ) ? " checked='checked'" : "";
  $copy_owner = '';
  if ( $_SESSION['loginID'] != $_SESSION['id'] )
  {
    $copy_owner = "Add e-mail address of data owner?" .
                  "<input type='checkbox' name='add_owner'$checked style='width:5em;'/>";
  }

  $text = <<<HTML
        <p>Please enter the following information so
        we can track your queue.<p>

        <p>Enter the email address you would like notifications sent to:</p>
        <p>$msg1a<input type="text" name="submitter_email"
                  value="$submitter_email"/><br />
           $copy_owner
        </p>
        $msg1

HTML;

  return( $text );
}

function get_experiment_text($link)
{
  global $experimentID;

//$time0=microtime(true);
  // Get a list of experiments
  $query  = "SELECT   experimentID, DATE( dateUpdated ) AS udate, runID " .
            "FROM     projectPerson, project, experiment " .
            "WHERE    projectPerson.personID = {$_SESSION['id']} " .
            "AND      project.projectID = projectPerson.projectID " .
            "AND      experiment.projectID = project.projectID " .
            "ORDER BY udate DESC, runID ";
  $result = mysqli_query( $link, $query )
            or die("Query failed : $query<br />\n" . mysqli_error($link));

  $experiment_list = "<select id='expIDs' name='expIDs[]' multiple='multiple' size='15' " .
                     "  onchange='this.form.submit();'>\n" .
                     "  <option value='null'>run ID not selected...</option>\n";

  while ( list( $expID, $udate, $runID ) = mysqli_fetch_array( $result ) )
  {
    $selected = ( $expID == $experimentID )
              ? " selected='selected'"
              : "";
    $experiment_list .= "  <option value='$expID'$selected>$udate $runID</option>\n";
  }
//$time1=microtime(true)-$time0;
//$experiment_list .= "  <option value=time1>$time1</option>\n";

  $experiment_list .= "</select>\n";

  $msg2  = "";
  $msg2a = "";
  if ( isset( $_SESSION['message2'] ) )
  {
    $msg2  = "<p class='message'>{$_SESSION['message2']}</p>";
    $msg2a = "<span class='message'>**</span>";
    unset( $_SESSION['message2'] );
  }

  $text = <<<HTML
      <p>Select the UltraScan experiments (run IDs) you would like to add to the Analysis Queue.</p>
      <p>$msg2a $experiment_list</p>
      $msg2

HTML;

  return( $text );
}

function get_cell_text($link)
{
  global $experimentID;
//$time0=microtime(true);

  if ( $experimentID == 0 )
  {
    $rawData_list = "<select name='cells[]' multiple='multiple' size='4'>\n" .
                       "  <option value='null'>Select runID first...</option>\n";
    $rawData_list .= "</select>\n";
  }

  else
  {
    // We have a legit experimentID, so let's get a list of cells
    //  (auc files) in experiment
    $rrawIDs  = array();
    $rrunIDs  = array();
    $rrfiles  = array();
    $kraw     = 0;
//$time1=microtime(true)-$time0;

    if ( isset( $_POST['expIDs'] ) )
    {
      foreach( $_POST['expIDs'] as $experimentID )
      { // First accumulate arrays of rawDataID,runID,filename
        $query  = "SELECT rawDataID, runID, filename " .
                  "FROM   rawData, experiment " .
                  "WHERE  rawData.experimentID = $experimentID " .
                  "AND    rawData.experimentID = experiment.experimentID ";
        $result = mysqli_query( $link, $query )
                  or die("Query failed : $query<br />\n" . mysqli_error($link));

        while ( list( $rawDataID, $runID, $filename ) = mysqli_fetch_array( $result ) )
        {
          $rrawIDs[ $kraw      ] = $rawDataID;
          $rrunIDs[ $rawDataID ] = $runID;
          $rrfiles[ $rawDataID ] = $filename;
          $kraw++;
        }
      }
    }
//$time2=microtime(true)-$time0;

    // Now construct the list items of run,filename;
    //  but only where the AUC has at least one Edit child
    $rawData_list = "<select id='cells' name='cells[]' multiple='multiple' size='20'>\n" .
                       "  <option value='null'>Select cells...</option>\n";

    for ( $kraw = 0; $kraw < count($rrawIDs); $kraw++ )
    {
      $rawDataID = $rrawIDs[ $kraw      ];
      $runID     = $rrunIDs[ $rawDataID ];
      $filename  = $rrfiles[ $rawDataID ];

      $query  = "SELECT COUNT(*) ".
                "FROM editedData " .
                "WHERE rawDataID = $rawDataID";
      $result = mysqli_query( $link, $query )
                or die("Query failed : $query<br />\n" . mysqli_error($link));
      list( $count ) = mysqli_fetch_array( $result );

      if ( $count > 0 )
        $rawData_list .= "  <option value='$rawDataID:$filename'>$runID $filename</option>\n";
    }
//$time3=microtime(true)-$time0;
//$rawData_list .= "  <option value='time1time2time3'>$time1 $time2 $time3</option>\n";

    $rawData_list .= "</select>\n";
  }

  $msg3  = "";
  $msg3a = "";
  if ( isset( $_SESSION['message3'] ) )
  {
    $msg3  = "<p class='message'>{$_SESSION['message3']}</p>";
    $msg3a = "<span class='message'>***</span>";
    unset( $_SESSION['message3'] );
  }

  $text = <<<HTML
      <p>Select the cells you wish to process.<br />
      <em>You can select multiple cells at once.</em></p>

      <p>$msg3a $rawData_list</p>
      $msg3

HTML;

  return( $text );
}
?>
