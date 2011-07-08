<?php
/*
 * global_menu.php
 *
 * Writes a different global menu depending on userlevel, or if logged in
 *
 * Requires session to be started already
 */

$userlevel = ( isset( $_SESSION['userlevel'] ) ) ? $_SESSION['userlevel'] : -1;

$home_menu = <<<HTML
            <li><a href="http://$org_site/index.php">Home</a></li>

HTML;

$projects_menu = <<<HTML
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='view_projects.php'>View/Edit Projects</a></li>
                <li class='separator'></li>
                <li><a href='#' onclick='construction();'>Enter Image</a></li>
              </ul></li>

HTML;

$analysis_menu = <<<HTML
            <li class='submenu'><a href='#'>Analysis</a>
              <ul class='level2'>
                <li><a href='queue_setup_1.php'>Queue Setup</a></li>
                <li class='separator'></li>
                <li><a href='2DSA_1.php'>2DSA Analysis</a></li>
                <li><a href='GA_1.php'>GA Analysis</a></li>
              </ul></li>

HTML;

$monitor_menu = <<<HTML
            <li class='submenu'><a href='#'>Status Monitor</a>
              <ul class='level2'>
                <li><a href='queue_viewer.php'>Queue Status</a></li>
                <li><a href='http://grid.uthscsa.edu'>Cluster Status</a></li>
              </ul></li>

HTML;

$resources_menu = <<<HTML
            <li class='submenu'><a href='#'>Resources -></a>
              <ul class='level3'>
                <li><a href='buffer_pH.php'>PO4 Buffer pH</a></li>
                <li><a href='buffer_extinction.php'>Buffer Extinction</a></li>
                <li><a href='compatibility_guide.php'>Compatibility Guide</a></li>
              </ul></li>

HTML;

$help_menu = <<<HTML
            <li class='submenu'><a href='#'>Help</a>
              <ul class='level2'>
                $resources_menu
              </ul></li>

HTML;

if ( $userlevel == 5 ) // super admin
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            $home_menu
            $projects_menu
            $analysis_menu
            $monitor_menu
            $help_menu
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 4 ) // admin
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            $home_menu
            $projects_menu
            $analysis_menu
            $monitor_menu
            $help_menu
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 3 ) // super user
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            $home_menu
            $projects_menu
            $analysis_menu
            $monitor_menu
            $help_menu
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 2 ) // analyst
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            $home_menu
            $projects_menu
            $analysis_menu
            $help_menu
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 1 ) // privileged user
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            $home_menu
            $projects_menu
            $help_menu
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 0 ) // regular user
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            $home_menu
            $projects_menu
            $help_menu
          </ul>
        </div>

HTML;
}

else // not logged in, userlevel not 0-5 for some reason, etc.
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            $home_menu

          </ul>
        </div>

HTML;
}

