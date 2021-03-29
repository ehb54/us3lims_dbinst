<?php
/*
 * links.php
 *
 * Include file that contains links
 *  Needs session_start(), config.php
 *
 */

$userlevel = ( isset( $_SESSION['userlevel'] ) ) ? $_SESSION['userlevel'] : -1;

$projects_menu = <<<HTML
  <h4>Project</h4>
  <a href='https://$org_site/view_projects.php'>Projects</a>
  <a href='https://$org_site/edit_images.php'>Images</a>
  <a href='https://$org_site/view_reports.php'>Reports</a>
  <a href='https://$org_site/data_sharing.php'>Sharing</a>

HTML;

$analysis_menu = <<<HTML
  <h4>Analysis</h4>
  <a href='https://$org_site/queue_setup_1.php'>Queue Setup</a>
  <a href='https://$org_site/2DSA_1.php'>2DSA Analysis</a>
  <a href='https://$org_site/2DSA-CG_1.php'>2DSA Custom Grid</a>
  <a href='https://$org_site/GA_1.php'>GA Analysis</a>
  <a href='https://$org_site/DMGA_1.php'>Discrete GA</a>
  <a href='https://$org_site/PCSA_1.php'>PCSA Analysis</a>
  <a href='https://$org_site/runID_info.php'>RunID Info</a></li>

HTML;

$monitor_menu = <<<HTML
  <h4>Status Monitor</h4>
  <a href='https://$org_site/queue_viewer.php'>Queue Status</a>
  <a href='https://$org_site/show_clusters.php'>Cluster Status</a>

HTML;

$general_menu = <<<HTML
  <h4>General</h4>
  <a href='https://$org_site/profile.php?edit=12'>Change My Info</a>
  <a href='https://$org_site/view_database_info.php'>Database Login Info</a>
  <a href="partners.php">Partners</a>
  <a href='contacts.php'>Contacts</a>
  <a href='mailto:$admin_email'>Webmaster</a>
  <a href='data_security.php'>Data Security</a>
  <a href='http://$org_site/logout.php'>Logout</a>

HTML;

$general_menu_1 = <<<HTML
  <h4>General</h4>
  <a href='https://$org_site/profile.php?edit=12'>Change My Info</a>
  <a href="partners.php">Partners</a>
  <a href='contacts.php'>Contacts</a>
  <a href='mailto:$admin_email'>Webmaster</a>
  <a href='data_security.php'>Data Security</a>
  <a href='http://$org_site/logout.php'>Logout</a>

HTML;

if ( $userlevel == 5 )  // level 5 = super admin ( developer )
{
  $sidebar_menu = <<<HTML
  <h4>Navigation</h4>
  <a href="https://$org_site/index.php">Welcome!</a>
  <a href='https://$org_site/admin_links.php'>Admin Info</a>
  $projects_menu
  $analysis_menu
  $monitor_menu
  $general_menu

HTML;
}

else if ( $userlevel == 4 )  // userlevel 4 = admin
{
  $sidebar_menu = <<<HTML
  <h4>Navigation</h4>
  <a href="https://$org_site/index.php">Welcome!</a>
  <a href='https://$org_site/admin_links.php'>Admin Info</a>
  $projects_menu
  $analysis_menu
  $monitor_menu
  $general_menu

HTML;
}

else if ( $userlevel == 3 )  // userlevel 3 = superuser
{
  $sidebar_menu = <<<HTML
  <h4>Navigation</h4>
  <a href="https://$org_site/index.php">Welcome!</a>
  <a href='https://$org_site/admin_links.php'>Admin Info</a>
  $projects_menu
  $analysis_menu
  $monitor_menu
  $general_menu

HTML;
}

else if ( $userlevel == 2 )  // userlevel 2 = Data analyst
{
  $sidebar_menu = <<<HTML
  <h4>Navigation</h4>
  <a href="https://$org_site/index.php">Welcome!</a>
  $projects_menu
  $analysis_menu
  $monitor_menu
  $general_menu

HTML;
}

else if ( $userlevel == 1 )  // level 1 = privileged user
{
  $sidebar_menu = <<<HTML
  <h4>Navigation</h4>
  <a href="https://$org_site/index.php">Welcome!</a>
  $projects_menu
  $general_menu_1

HTML;
}

else if ( $userlevel == 0 )  // level 0 = regular user
{
  $sidebar_menu = <<<HTML
  <h4>Navigation</h4>
  <a href="https://$org_site/index.php">Welcome!</a>
  $projects_menu
  $general_menu_1

HTML;
}

else // not logged in
{
  $sidebar_menu = <<<HTML
  <a href="http://$org_site/index.php">Welcome!</a>
  <a href="partners.php">Partners</a>
  <a href='contacts.php'>Contacts</a>
  <a href='mailto:$admin_email'>Webmaster</a>
  <a href='data_security.php'>Data Security</a>
  <a href='https://$org_site/login.php'>Login</a>

HTML;
}

echo<<<HTML
      
<div id='sidebar'>

  $sidebar_menu

  <!-- A spacer -->
  <!--div style='padding-bottom:20em;'></div-->

</div>
HTML;

// Function used by 2DSA_1.php, 2DSA-CG_1.php, ... to re-determine
//  the latest noise associated with latest edits
function set_latest_noises( $link )
{
  $nreq     = count( $_SESSION['request'] );
  $nndiff   = 0;
  $count    = 0;
  while ( $count < $nreq )
  { // Loop through latest edits examining associated noises
    $editedDataID = $_SESSION['request'][$count]['editedDataID'];
    $rawDataID    = $_SESSION['request'][$count]['rawDataID'];

    $nIDsOld      = $_SESSION['request'][$count]['noiseIDs'];
    $noiseIDs     = array();

    $query  = "SELECT noiseID, noiseType, timeEntered " .
              "FROM noise " .
              "WHERE editedDataID = $editedDataID " .
              "ORDER BY timeEntered DESC ";

    $result = mysqli_query( $link, $query )
            or die("Query failed : $query<br />\n" . mysqli_error($link));

    $knoise = 0;
    $prtype = "";
    $prtime = 0;
    while ( list( $noiseID, $noiseType, $time ) = mysqli_fetch_array( $result ) )
    { // Loop through noises in reverse date order to save IDs of latest for the edit
      if ( $knoise == 0 )
      { // First encountered noise is the latest
        $noiseIDs[$knoise] = $noiseID;
        $prtype = $noiseType;
        $prtime = (int)$time;
        $knoise++;
      }
      else if ( $knoise == 1 )
      { // If second is different type, same time; it is latest
        if ( $prtype == $noiseType )    break;
        $tdiff = (int)$time - $prtime;
        if ( $tdiff > 2 ) break;
        $noiseIDs[$knoise] = $noiseID;
        $knoise++;
        break;
      }
    }

    $_SESSION['request'][$count]['noiseIDs']     = $noiseIDs;
    $_SESSION['cells'][$rawDataID]['noiseIDs']   = $noiseIDs;
    $nno1    = count( $nIDsOld  );
    $nno2    = count( $noiseIDs );
    if ( $nno1 != $nno2 )
    { // Different count of noises in edit, all are new
      $nndiff += $nno2;
    }
    else
    { // Same count:  examine for differing IDs
      for ( $ii = 0; $ii < $nno1; $ii++ )
      {
        if ( $nIDsOld[ $ii ] != $noiseIDs[ $ii ] )
          $nndiff++;
      }
    }
    $count++;
  }
  $new_noise = '';

  // Compose additional page string documenting any new noise determination
  if ( $nndiff === 0 )
  {
    $new_noise = "<div style='color:blue;'>" .
      "Previously selected latest edits and noises are in force." .
      "</div>\n";
  }
  else if ( $nndiff === 1 )
  {
    $new_noise = "<div style='color:blue;'>" .
      "Previously selected latest edits are in force.<br/>" .
      "1 new latest noise was detected." .
      "</div>\n";
  }
  else
  {
    $new_noise = "<div style='color:blue;'>" .
      "Previously selected latest edits are in force.<br/>" .
      "$nndiff new latest noises were detected." .
      "</div>\n";
  }
  $_SESSION['new_noise'] = $new_noise;
}

?>
