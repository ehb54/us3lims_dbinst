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
            <li><a href="http://$org_site/index.php">Home</a></li>
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='#'>New Project</a></li>
                <li class='separator'></li>
                <li><a href='#'>Enter Peptide Sequence</a></li>
                <li><a href='#'>Enter Nucleic Acid Sequence</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Analysis</a>
              <ul class='level2'>
                <li><a href='queue_setup_1.php'>Queue Setup</a></li>
                <li class='separator'></li>
                <li><a href='2DSA_1.php'>2DSA Analysis</a></li>
              </ul></li>
            <li><a href="#">Contact Us</a></li>
            <li><a href='#'>Help</a></li>
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 4 ) // admin
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li><a href="http://$org_site/index.php">Home</a></li>
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='#'>New Project</a></li>
                <li class='separator'></li>
                <li><a href='#'>Enter Peptide Sequence</a></li>
                <li><a href='#'>Enter Nucleic Acid Sequence</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Analysis</a>
              <ul class='level2'>
                <li><a href='queue_setup_1.php'>Queue Setup</a></li>
                <li class='separator'></li>
                <li><a href='2DSA_1.php'>2DSA Analysis</a></li>
              </ul></li>
            <li><a href="#">Contact Us</a></li>
            <li><a href='#'>Help</a></li>
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 3 ) // super user
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li><a href="http://$org_site/index.php">Home</a></li>
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='#'>New Project</a></li>
                <li class='separator'></li>
                <li><a href='#'>Enter Peptide Sequence</a></li>
                <li><a href='#'>Enter Nucleic Acid Sequence</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Analysis</a>
              <ul class='level2'>
                <li><a href='queue_setup_1.php'>Queue Setup</a></li>
                <li class='separator'></li>
                <li><a href='2DSA_1.php'>2DSA Analysis</a></li>
              </ul></li>
            <li><a href="#">Contact Us</a></li>
            <li><a href='#'>Help</a></li>
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 2 ) // analyst
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li><a href="http://$org_site/index.php">Home</a></li>
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='#'>New Project</a></li>
                <li class='separator'></li>
                <li><a href='#'>Enter Peptide Sequence</a></li>
                <li><a href='#'>Enter Nucleic Acid Sequence</a></li>
              </ul></li>
            <li class='submenu'><a href='#'>Analysis</a>
              <ul class='level2'>
                <li><a href='queue_setup_1.php'>Queue Setup</a></li>
                <li class='separator'></li>
                <li><a href='2DSA_1.php'>2DSA Analysis</a></li>
              </ul></li>
            <li><a href="#">Contact Us</a></li>
            <li><a href='#'>Help</a></li>
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 1 ) // privileged user
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li><a href="http://$org_site/index.php">Home</a></li>
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='#'>New Project</a></li>
                <li class='separator'></li>
                <li><a href='#'>Enter Peptide Sequence</a></li>
                <li><a href='#'>Enter Nucleic Acid Sequence</a></li>
              </ul></li>
            <li><a href="#">Contact Us</a></li>
            <li><a href='#'>Help</a></li>
          </ul>
        </div>

HTML;
}

else if ( $userlevel == 0 ) // regular user
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li><a href="http://$org_site/index.php">Home</a></li>
            <li class='submenu'><a href='#'>Project</a>
              <ul class='level2'>
                <li><a href='#'>New Project</a></li>
                <li class='separator'></li>
                <li><a href='#'>Enter Peptide Sequence</a></li>
                <li><a href='#'>Enter Nucleic Acid Sequence</a></li>
              </ul></li>
            <li><a href="#">Contact Us</a></li>
            <li><a href='#'>Help</a></li>
          </ul>
        </div>

HTML;
}

else // not logged in, userlevel not 0-5 for some reason, etc.
{
$global_menu = <<<HTML
        <div id="globalnav">
          <ul class='level1'>
            <li><a href="http://$org_site/index.php">Home</a></li>
            <li><a href="#">Contact Us</a></li>
          </ul>
        </div>

HTML;
}

