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
                <li><a href='#' onclick='construction();'>Enter Peptide Sequence</a></li>
                <li><a href='#' onclick='construction();'>Enter Nucleic Acid Sequence</a></li>
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
                <li><a href='http://grid.uthscsa.edu'>UTHSCSA Cluster Status</a></li>
              </ul></li>

HTML;

$contactus_menu = <<<HTML
            <li><a href="contactus.php">Contact Us</a></li>

HTML;

$background_menu = <<<HTML
            <li class='submenu'><a href='#'>Background -></a>
              <ul class='level3'>
                <li><a href='overview.php'>Overview</a></li>
                <li><a href='examples.php'>Examples</a></li>
                <li><a href='velocity.php'>Velocity</a></li>
                <li><a href='equilibrium.php'>Equilibrium</a></li>
              </ul></li>

HTML;

$resources_menu = <<<HTML
            <li class='submenu'><a href='#'>Resources -></a>
              <ul class='level3'>
                <li><a href='#'>A Resource</a></li>
              </ul></li>

HTML;

$help_menu = <<<HTML
            <li class='submenu'><a href='#'>Help</a>
              <ul class='level2'>
                $background_menu
                $resources_menu
                <li class='separator'></li>
                <li><a href='http://www.ultrascan.uthscsa.edu'>UltraScan Software</a></li>
                <li><a href='http://www.ultrascan.uthscsa.edu/manual'>UltraScan Manual</a></li>
                <li><a href='http://ultrascan.uthscsa.edu/AUCuserGuideVolume-1-Hardware.pdf'>
                       AUC Hardware User Guide</a></li>
                <li><a href='contacts.php'>Contacts</a></li>
                <li><a href='mailto:demeler@biochem.uthscsa.edu'>Feedback</a></li>
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
            $contactus_menu
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
            $contactus_menu
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
            $contactus_menu
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
            $contactus_menu
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
            $contactus_menu
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
            $contactus_menu
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
            $contactus_menu
            $help_menu
          </ul>
        </div>

HTML;
}

