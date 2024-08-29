<?php
/*
 * queue_setup_3.php
 *
 * Display all chosen cells and associate edit profiles, models and noise
 *
 */
include_once 'checkinstance.php';
elogrs( __FILE__ );

if ( ($_SESSION['userlevel'] != 2) &&
     ($_SESSION['userlevel'] != 3) &&
     ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) )    // only data analyst and up
{
  header('Location: index.php');
  if ( $is_cli ) {
    echo __FILE__ . " exiting 1\n";
  }
  exit();
} 

// Verify that job submission is ok now
include_once 'lib/motd.php';
if ( motd_isblocked() && ($_SESSION['userlevel'] < 4) )
{
  header("Location: index.php");
  if ( $is_cli ) {
    echo __FILE__ . " exiting 2\n";
  }
  exit();
}

// Check if user has elected to remove one of the datasets in the queue
if ( isset($_GET['removeID']) )
{
  $removeID = $_GET['removeID'];

  if ( sizeof( $_SESSION['request'] ) == 1)
    unset( $_SESSION['request'] );

  else
  {
    // More than one, so we can move higher numbered ones into one lower position
    for ($i = $removeID; $i < sizeof( $_SESSION['request']) - 1; $i++ )
      $_SESSION['request'][$i] = $_SESSION['request'][$i+1];

    // Last one is the one to delete
    unset( $_SESSION['request'][$i] );
  }

  // Has to be redirected to avoid another removal from the queue just by
  //  refreshing the screen
  header("Location: {$_SERVER['PHP_SELF']}");
  if ( $is_cli ) {
    echo __FILE__ . " exiting 3\n";
  }
  exit();
}

// Check if the clear queue button has been pressed
if ( isset($_GET['clear']) )
{
  unset( $_SESSION['request'] );

  header("Location: {$_SERVER['PHP_SELF']}");
  if ( $is_cli ) {
    echo __FILE__ . " exiting 4\n";
  }
  exit();
}

// Check the status of the advanced review radio buttons
if ( isset( $_POST['advanced_review'] ) )
{
  $advanced_review = $_POST['advanced_review'] == 'adv_disabled'
                     ? 0
                     : 1;
}

else if ( isset( $_SESSION['advanced_review'] ) )
  $advanced_review = $_SESSION['advanced_review'];

else
  $advanced_review = 0;

if ( $advanced_review )
{
  $adv_disabled_cked = "";
  $adv_enabled_cked  = " checked='checked'";
}

else
{
  $adv_disabled_cked = " checked='checked'";
  $adv_enabled_cked  = "";
}

$_SESSION['advanced_review'] = $advanced_review;

// Check the status of the separate datasets radio buttons
if ( isset( $_POST['separate_datasets'] ) )
{
  $separate_datasets = $_POST['separate_datasets'] == 'global'
                     ? 0
                     : ( $_POST['separate_datasets'] == 'separate'
                         ? 1 : 2 );
}

else if ( isset( $_SESSION['separate_datasets'] ) )
  $separate_datasets = $_SESSION['separate_datasets'];

else
  $separate_datasets = 1;

$_SESSION['separate_datasets'] = $separate_datasets;
$advancelevel   = ( isset($_SESSION['advancelevel']) )
                ? $_SESSION['advancelevel'] : 0;

// Set up some web stuff
$button_message = ( $separate_datasets )
                ? "Click here to proceed as a global fit"
                : "Click here to proceed as separate jobs";
$separate_text  = ( $separate_datasets == 1 )
                ? "proceed as separate jobs"
                : "proceed as a global fit";
$GA_disabled    = "";
$GA_notes       = "";
$GA_opt_text    = "";

if ( $separate_datasets != 0 )
{
  $separate_checked  = "";
  $composite_checked = "";
  $global_checked    = "";
  $button_message    = "Click here to proceed as a global fit";
  if ( $separate_datasets == 1 )
  {
    $separate_checked  = " checked='checked'";
    $separate_text     = "proceed as separate jobs";
  }
  else
  {
    $composite_checked = " checked='checked'";
    $separate_text     = "proceed as composite job(s)";
  }
  if ( isset( $_SESSION['request'] ) && sizeof( $_SESSION['request'] ) > 1 )
  {
    $GA_disabled       = " disabled='disabled'";
    $GA_notes          = "<p><b>NOTE:</b>&nbsp;&nbsp;" .
                         "GA disabled for non-global job(s) with multiple datasets.</p>";
  }
  else
  {
    $GA_opt_text = <<<HTML
  <p><input type="button" value="Setup GA Control"
            onclick='window.location="GA_1.php"'$GA_disabled />
     <input type="button" value="Setup Discrete Model GA Control"
            onclick='window.location="DMGA_1.php"'$GA_disabled /></p>
HTML;
  }
}

else
{
  $separate_checked  = "";
  $composite_checked = "";
  $global_checked    = " checked='checked'";
  $separate_text     = "proceed as a global fit";
  $button_message    = "Click here to proceed as separate jobs";
  $GA_opt_text = <<<HTML
  <p><input type="button" value="Setup GA Control"
            onclick='window.location="GA_1.php"'$GA_disabled />
     <input type="button" value="Setup Discrete Model GA Control"
            onclick='window.location="DMGA_1.php"'$GA_disabled /></p>
HTML;
}

include 'config.php';
include 'db.php';

// Start displaying page
$page_title = "Queue Setup (completed)";
$css = 'css/queue_setup.css';
include 'header.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Queue Setup (completed)</h1>

<?php

if ( isset( $_SESSION['request'] ) && sizeof( $_SESSION['request'] ) > 0 )
{
  $out_text = "";
  foreach ( $_SESSION['request'] as $removeID => $cellinfo )
  {
    $editedData_text  = get_editedData_qs3( $link, $cellinfo['editedDataID'] );
    $noise_text       = get_noise_qs3( $link, $cellinfo['noiseIDs'] );

    $out_text .= <<<HTML
    <fieldset>
      <legend style='font-size:110%;font-weight:bold;'>{$cellinfo['filename']}
              <a href='{$_SERVER['PHP_SELF']}?removeID=$removeID'>Remove?</a></legend>

      <table cellpadding='3' cellspacing='0'>
      <tr><th>Edit Profile</th>
          <td>$editedData_text</td></tr>
      <tr><th>Noise</th>
          <td>$noise_text</td></tr>
      </table>

    </fieldset>

HTML;

  }

  // Some controls only support one dataset
  $disabled = ( sizeof( $_SESSION['request'] ) == 1 ) ?
              "" : " disabled='disabled' ";

  $multiset_notes = "";
  if ( $disabled  &&  $advancelevel != 0 )
  { // Multiset and Advance Level
    $multiset_notes = <<<HTML
    <fieldset>
    <legend>Multiple dataset notes:</legend>
    <ul class='multi_notes'>
      <li><form action='$_SERVER[PHP_SELF]' method='post'>
          Multiple datasets can be submitted either separated into
          multiple jobs or as a global fit to a single model.
          Currently, you are set up to $separate_text. To change,
          select one of the options below before proceeding.

          <table cellspacing='0' cellpadding='3px'>
          <tr><td><label>
                  <input type='radio' name='separate_datasets'
                         value='separate'$separate_checked
                         onclick='this.form.submit();' />
                         Proceed as separate jobs</label></td></tr>
          <tr><td><label>
                  <input type='radio' name='separate_datasets'
                         value='global'$global_checked
                         onclick='this.form.submit();' />
                         Proceed as a global fit</label></td></tr>
          <tr><td><label>
                  <input type='radio' name='separate_datasets'
                         value='composite'$composite_checked
                         onclick='this.form.submit();' />
                         Proceed as composite job(s)</label></td></tr>
          </table>
          </form></li>
      <li><form action='$_SERVER[PHP_SELF]' method='post'>
          By default, control parameters apply to all datasets.
          Alternatively, you may choose to review each cell and
          modify Advanced Controls on a cell-by-cell basis.
          <table cellspacing='0' cellpadding='3px'>
          <tr><td><label>
                  <input type='radio' name='advanced_review'
                         value='adv_disabled'$adv_disabled_cked
                         onclick='this.form.submit();' />
                         Apply controls to all datasets
                  </label></td></tr>
          <tr><td><label>
                  <input type='radio' name='advanced_review'
                         value='adv_enabled'$adv_enabled_cked
                         onclick='this.form.submit();' />
                         Review advanced controls for each cell
                  </label></td></tr>
          </table>
          </form>
      </li>
    </ul>
    </fieldset>

HTML;
  }
  else if ( $disabled  &&  $advancelevel == 0 )
  { // Multiset and NOT Advance Level
    $multiset_notes = <<<HTML
    <fieldset>
    <legend>Multiple dataset notes:</legend>
    <ul class='multi_notes'>
      <li><form action='$_SERVER[PHP_SELF]' method='post'>
          Multiple datasets can be submitted either separated into
          multiple jobs or as a global fit to a single model.
          Currently, you are set up to $separate_text. To change,
          select one of the options below before proceeding.

          <table cellspacing='0' cellpadding='3px'>
          <tr><td><label>
                  <input type='radio' name='separate_datasets'
                         value='separate'$separate_checked
                         onclick='this.form.submit();' />
                         Proceed as separate jobs</label></td></tr>
          <tr><td><label>
                  <input type='radio' name='separate_datasets'
                         value='global'$global_checked
                         onclick='this.form.submit();' />
                         Proceed as a global fit</label></td></tr>
          </table>
          </form></li>
    </ul>
    </fieldset>

HTML;
  }

  echo <<<HTML
  <h4>Select data flow and analysis types</h4>

  <div>
  $multiset_notes

  <form action="queue_setup_2.php" method="post">

  <p><input type='button' value='Select Different Experiment'
            onclick='window.location="queue_setup_1.php";' /> 
     <input type="submit" name='setup_2' value="Edit Profile Information"/></p>

  <p><input type="button" value="Setup 2DSA Control"
            onclick='window.location="2DSA_1.php"' />
     <input type="button" value="Setup 2DSA Control with Custom Grid"
            onclick='window.location="2DSA-CG_1.php"' /></p>

  $GA_opt_text

  <p><input type="button" value="Setup PCSA Control"
            onclick='window.location="PCSA_1.php"' />
     <input type="button" value="Clear Queue"
            onclick='window.location="{$_SERVER['PHP_SELF']}?clear=clear"'/></p>
  <!--
     <input type="button" value="Setup 2DSA Control with MW Constraint"
            onclick='window.location="2DSA-MW_1.php"' disabled='disabled' /></p>
     <input type="button" value="Setup GA Control with MW Constraint"
            onclick='window.location="GA-MW_1.php"' disabled='disabled' /></p>

  <p><input type="button" value="Nonlinear Model GA Control"
            onclick='window.location="GA_SC_1.php"' disabled='disabled' $disabled />
  -->
  $GA_notes

HTML;

  if ( $disabled  &&  $advancelevel != 0 )
  { // Multiset and Advance Level
    echo <<<HTML
    <h4>Review submitted edit profiles and noise files for each cell</h4>

    <p>Double check the information for each cell, and if it is not correct, 
       please click on one of the buttons to edit it again, or to start over.
       If the queue information is correct, please select the <em>Analysis</em>
       global menu above and choose which type of analysis you would like to
       perform.</p>
      $out_text
HTML;
  }
  echo <<<HTML

  </form>
  </div>

HTML;
}

else
{
  echo <<<HTML
  <p>Your Queue is currently empty</p>
  <p>Please go back to add one or more experiments into the queue.</p>
  <p><input type='button' value='Select Experiment'
            style='width:12em;' onclick='window.location="queue_setup_1.php";'/>
  </p>
HTML;
}

?>

</div>

<?php

include 'footer.php';
if ( $is_cli ) {
   return;
}
exit();

// Get edit profiles
function get_editedData_qs3( $link, $editedDataID )
{
  $query  = "SELECT label, filename " .
            "FROM editedData " .
            "WHERE editedDataID = ? ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( "i", $editedDataID );
  $stmt->execute()
        or die("Query failed : $query<br />\n" . $stmt->error);
  $result = $stmt->get_result()
        or die("Query failed : $query<br />\n" . $stmt->error);
  list( $label, $fn ) = mysqli_fetch_array( $result );
  $stmt->close();
  $result->close();
  $parts    = explode( ".", $fn ); // runID, editID, runType, c,c,w, xml
  $edit_txt  = $parts[1];
  $profile = "<span>$label [$edit_txt]</span>";

  return( $profile );
}

/*
// Get the models
function get_model( $link, $modelID )
{
  $query  = "SELECT description " .
            "FROM model " .
            "WHERE modelID = $modelID ";

  $result = mysqli_query( $link, $query )
          or die("Query failed : $query<br />\n" . mysqli_error($link));

  list( $descr ) = mysqli_fetch_array( $result );
  $model = "<span>[$modelID] $descr</span>";

  return( $model );
}
*/

// Get the noise files
function get_noise_qs3( $link, $noiseIDs )
{
  if ( empty( $noiseIDs ) )
    return( "" );

  $commaIDs = implode(",", $noiseIDs );
  $query  = "SELECT noiseID, modelID, noiseType " .
            "FROM noise " .
            "WHERE noiseID IN ( $commaIDs ) ";

  $result = mysqli_query( $link, $query )
          or die("Query failed : $query<br />\n" . mysqli_error($link));

  $noise = "";
  while ( list( $nID, $modelID, $noiseType ) = mysqli_fetch_array( $result ) )
    $noise .= "<span>[$nID] $noiseType</span><br />\n";

  return( $noise );
}
?>
