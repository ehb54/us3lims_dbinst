<?php
/*
 * global_menu.php
 *
 * Writes a different global menu depending on userlevel, or if logged in
 *
 * Requires session to be started already
 */

$userlevel = ( isset( $_SESSION['userlevel'] ) ) ? $_SESSION['userlevel'] : -1;

if ( $userlevel == 5 ) // super admin
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li class='submenu'><a href="http://$org_site/index.php">Home</a>
              <ul class='level2'>
                <li><a href='http://www.ultrascan.uthscsa.edu'>UltraScan Software</a></li>
                <li><a href='http://www.ultrascan.uthscsa.edu/manual'>UltraScan Manual</a></li>
                <li><a href='http://ultrascan.uthscsa.edu/AUCuserGuideVolume-1-Hardware.pdf'>
                       AUC Hardware User Guide</a></li>
                <li><a href='contacts.php'>Contacts</a></li>
                <li><a href='mailto:demeler@biochem.uthscsa.edu'>Feedback</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='view_projects.php'>View/Edit Projects</a></li>
                <li class='separator'></li>
                <li><a href='#' onclick='construction();'>Enter Peptide Sequence</a></li>
                <li><a href='#' onclick='construction();'>Enter Nucleic Acid Sequence</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Analysis</a>
              <ul class='level2'>
                <li><a href='queue_setup_1.php'>Queue Setup</a></li>
                <li><a href='queue_viewer.php'>Queue Viewer</a></li>
                <li class='separator'></li>
                <li><a href='2DSA_1.php'>2DSA Analysis</a></li>
                <li><a href='GA_1.php'>GA Analysis</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Unused Menu</a>
              <ul class='level2'>
                <li><a href='#' onclick='construction();'>Menu Item</a></li>
              </ul></li>
            <li><a href="contactus.php">Contact Us</a></li>
            <li><a href='#' onclick='construction();'>Help</a></li>
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 4 ) // admin
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li class='submenu'><a href="http://$org_site/index.php">Home</a>
              <ul class='level2'>
                <li><a href='http://www.ultrascan.uthscsa.edu'>UltraScan Software</a></li>
                <li><a href='http://www.ultrascan.uthscsa.edu/manual'>UltraScan Manual</a></li>
                <li><a href='http://ultrascan.uthscsa.edu/AUCuserGuideVolume-1-Hardware.pdf'>
                       AUC Hardware User Guide</a></li>
                <li><a href='contacts.php'>Contacts</a></li>
                <li><a href='mailto:demeler@biochem.uthscsa.edu'>Feedback</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='view_projects.php'>View/Edit Projects</a></li>
                <li class='separator'></li>
                <li><a href='#' onclick='construction();'>Enter Peptide Sequence</a></li>
                <li><a href='#' onclick='construction();'>Enter Nucleic Acid Sequence</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Analysis</a>
              <ul class='level2'>
                <li><a href='queue_setup_1.php'>Queue Setup</a></li>
                <li><a href='queue_viewer.php'>Queue Viewer</a></li>
                <li class='separator'></li>
                <li><a href='2DSA_1.php'>2DSA Analysis</a></li>
                <li><a href='GA_1.php'>GA Analysis</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Unused Menu</a>
              <ul class='level2'>
                <li><a href='#' onclick='construction();'>Menu Item</a></li>
              </ul></li>
            <li><a href="contactus.php">Contact Us</a></li>
            <li><a href='#' onclick='construction();'>Help</a></li>
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 3 ) // super user
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li class='submenu'><a href="http://$org_site/index.php">Home</a>
              <ul class='level2'>
                <li><a href='http://www.ultrascan.uthscsa.edu'>UltraScan Software</a></li>
                <li><a href='http://www.ultrascan.uthscsa.edu/manual'>UltraScan Manual</a></li>
                <li><a href='http://ultrascan.uthscsa.edu/AUCuserGuideVolume-1-Hardware.pdf'>
                       AUC Hardware User Guide</a></li>
                <li><a href='contacts.php'>Contacts</a></li>
                <li><a href='mailto:demeler@biochem.uthscsa.edu'>Feedback</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='view_projects.php'>View/Edit Projects</a></li>
                <li class='separator'></li>
                <li><a href='#' onclick='construction();'>Enter Peptide Sequence</a></li>
                <li><a href='#' onclick='construction();'>Enter Nucleic Acid Sequence</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Analysis</a>
              <ul class='level2'>
                <li><a href='queue_setup_1.php'>Queue Setup</a></li>
                <li><a href='queue_viewer.php'>Queue Viewer</a></li>
                <li class='separator'></li>
                <li><a href='2DSA_1.php'>2DSA Analysis</a></li>
                <li><a href='GA_1.php'>GA Analysis</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Unused Menu</a>
              <ul class='level2'>
                <li><a href='#' onclick='construction();'>Menu Item</a></li>
              </ul></li>
            <li><a href="contactus.php">Contact Us</a></li>
            <li><a href='#' onclick='construction();'>Help</a></li>
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 2 ) // analyst
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li class='submenu'><a href="http://$org_site/index.php">Home</a>
              <ul class='level2'>
                <li><a href='http://www.ultrascan.uthscsa.edu'>UltraScan Software</a></li>
                <li><a href='http://www.ultrascan.uthscsa.edu/manual'>UltraScan Manual</a></li>
                <li><a href='http://ultrascan.uthscsa.edu/AUCuserGuideVolume-1-Hardware.pdf'>
                       AUC Hardware User Guide</a></li>
                <li><a href='contacts.php'>Contacts</a></li>
                <li><a href='mailto:demeler@biochem.uthscsa.edu'>Feedback</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='view_projects.php'>View/Edit Projects</a></li>
                <li class='separator'></li>
                <li><a href='#' onclick='construction();'>Enter Peptide Sequence</a></li>
                <li><a href='#' onclick='construction();'>Enter Nucleic Acid Sequence</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Analysis</a>
              <ul class='level2'>
                <li><a href='queue_setup_1.php'>Queue Setup</a></li>
                <li><a href='queue_viewer.php'>Queue Viewer</a></li>
                <li class='separator'></li>
                <li><a href='2DSA_1.php'>2DSA Analysis</a></li>
                <li><a href='GA_1.php'>GA Analysis</a></li>
              </ul></li>
            <li><a href="contactus.php">Contact Us</a></li>
            <li><a href='#' onclick='construction();'>Help</a></li>
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 1 ) // privileged user
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li class='submenu'><a href="http://$org_site/index.php">Home</a>
              <ul class='level2'>
                <li><a href='http://www.ultrascan.uthscsa.edu'>UltraScan Software</a></li>
                <li><a href='http://www.ultrascan.uthscsa.edu/manual'>UltraScan Manual</a></li>
                <li><a href='http://ultrascan.uthscsa.edu/AUCuserGuideVolume-1-Hardware.pdf'>
                       AUC Hardware User Guide</a></li>
                <li><a href='contacts.php'>Contacts</a></li>
                <li><a href='mailto:demeler@biochem.uthscsa.edu'>Feedback</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='view_projects.php'>View/Edit Projects</a></li>
                <li class='separator'></li>
                <li><a href='#' onclick='construction();'>Enter Peptide Sequence</a></li>
                <li><a href='#' onclick='construction();'>Enter Nucleic Acid Sequence</a></li>
              </ul></li>
            <li><a href="contactus.php">Contact Us</a></li>
            <li><a href='#' onclick='construction();'>Help</a></li>
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 0 ) // regular user
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li class='submenu'><a href="http://$org_site/index.php">Home</a>
              <ul class='level2'>
                <li><a href='http://www.ultrascan.uthscsa.edu'>UltraScan Software</a></li>
                <li><a href='http://www.ultrascan.uthscsa.edu/manual'>UltraScan Manual</a></li>
                <li><a href='http://ultrascan.uthscsa.edu/AUCuserGuideVolume-1-Hardware.pdf'>
                       AUC Hardware User Guide</a></li>
                <li><a href='contacts.php'>Contacts</a></li>
                <li><a href='mailto:demeler@biochem.uthscsa.edu'>Feedback</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='view_projects.php'>View/Edit Projects</a></li>
                <li class='separator'></li>
                <li><a href='#' onclick='construction();'>Enter Peptide Sequence</a></li>
                <li><a href='#' onclick='construction();'>Enter Nucleic Acid Sequence</a></li>
              </ul></li>
            <li><a href="contactus.php">Contact Us</a></li>
            <li><a href='#' onclick='construction();'>Help</a></li>
          </ul>
        </div>

HTML;
}

else // not logged in, userlevel not 0-5 for some reason, etc.
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li class='submenu'><a href="http://$org_site/index.php">Home</a>
              <ul class='level2'>
                <li><a href='http://www.ultrascan.uthscsa.edu'>UltraScan Software</a></li>
                <li><a href='http://www.ultrascan.uthscsa.edu/manual'>UltraScan Manual</a></li>
                <li><a href='http://ultrascan.uthscsa.edu/AUCuserGuideVolume-1-Hardware.pdf'>
                       AUC Hardware User Guide</a></li>
                <li><a href='contacts.php'>Contacts</a></li>
                <li><a href='mailto:demeler@biochem.uthscsa.edu'>Feedback</a></li>
              </ul></li>
            <li><a href="contactus.php">Contact Us</a></li>
          </ul>
        </div>

HTML;
}

