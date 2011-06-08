<?php
/*
 * links.php
 *
 * Include file that contains links
 *  Needs session_start(), config.php
 *
 */

$userlevel = ( isset( $_SESSION['userlevel'] ) ) ? $_SESSION['userlevel'] : -1;

$home_menu = <<<HTML
    <li class='section'><a href="http://$org_site/index.php">Welcome!</a></li>

HTML;

$admin5_menu = <<<HTML
    <li><a href='http://$org_site/mysql_admin.php'>MySQL</a></li>
    <li><a href='http://$org_site/runID_info.php'>Info by Run ID</a></li>
    <li class='section'><a href='http://$org_site/check_db.php'>Check DB Linkage</a></li>

HTML;

$admin4_menu = <<<HTML
    <li><a href='http://$org_site/runID_info.php'>Info by Run ID</a></li>
    <li class='section'><a href='http://$org_site/check_db.php'>Check DB Linkage</a></li>

HTML;

$viewuser_menu = <<<HTML
      <li><a href='http://$org_site/view_users.php'>View User Info</a></li>
      <li class='section'><a href='http://$org_site/view_all.php'>View All Users</a></li>

HTML;

$edituser_menu = <<<HTML
      <li><a href='http://$org_site/edit_users.php'>Edit User Info</a></li>
      <li><a href='http://$org_site/view_users.php'>View User Info</a></li>
      <li class='section'><a href='http://$org_site/view_all.php'>View All Users</a></li>

HTML;

$hardware_menu = <<<HTML
      <li><a href='edit_labs.php'>Edit Labs</a></li>
      <li class='section'><a href='edit_instruments.php'>Edit Instruments</a></li>

HTML;

$myinfo_menu = <<<HTML
      <li><a href='http://$org_site/profile.php?edit=12'>Change My Info</a></li>
      <li><a href='http://$org_site/logout.php'>Logout</a></li>

HTML;

if ( $userlevel == 5 )  // level 5 = super admin ( developer )
{
  $sidebar_menu = <<<HTML
  <ul>
    $home_menu
    $admin5_menu
    $edituser_menu
    $hardware_menu
    $myinfo_menu
  </ul>

HTML;
}

else if ( $userlevel == 4 )  // userlevel 4 = admin
{
  $sidebar_menu = <<<HTML
  <ul>
    $home_menu
    $admin4_menu
    $edituser_menu
    $hardware_menu
    $myinfo_menu
  </ul>

HTML;
}

else if ( $userlevel == 3 )  // userlevel 3 = superuser
{
  $sidebar_menu = <<<HTML
  <ul>
    $home_menu
    $viewuser_menu
    $hardware_menu
    $myinfo_menu
  </ul>

HTML;
}

else if ( $userlevel == 2 )  // userlevel 2 = Data analyst
{
  $sidebar_menu = <<<HTML
  <ul>
    $home_menu
    $myinfo_menu
  </ul>

HTML;
}

else if ( $userlevel == 1 )  // level 1 = privileged user
{
  $sidebar_menu = <<<HTML
  <ul>
    $home_menu
    $myinfo_menu
  </ul>

HTML;
}

else if ( $userlevel == 0 )  // level 0 = regular user
{
  $sidebar_menu = <<<HTML
  <ul>
    $home_menu
    $myinfo_menu
  </ul>

HTML;
}

else // not logged in
{
  $sidebar_menu = <<<HTML
  <ul>
    $home_menu
    <li><a href='https://$org_site/login.php'>Login</a></li>
  </ul>

HTML;
}

echo<<<HTML
      
<div id='sidebar'>

  $sidebar_menu

  <!-- A spacer -->
  <div style='padding-bottom:20em;'></div>

</div>
HTML;
?>
