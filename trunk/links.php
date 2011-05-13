<?php
/*
 * links.php
 *
 * Include file that contains links
 *  Needs session_start(), config.php
 *
 */

echo<<<HTML
<div id='sidebar'>

  <ul><li class='section'><a href='http://$org_site/index.php'>Welcome!</a></li>

HTML;

  // level 1 = privileged user
  if ( isset($_SESSION['userlevel']) &&
             $_SESSION['userlevel'] == 1 )
  {
    echo <<<HTML

HTML;
  }

  // userlevel 2 = Data analyst
  if ( isset($_SESSION['userlevel']) &&
             $_SESSION['userlevel'] == 2 )
  {
    echo <<<HTML

HTML;
  }

  // userlevel 3 = superuser
  if ( isset($_SESSION['userlevel']) &&
             $_SESSION['userlevel'] == 3 )
  {
    echo <<<HTML
      <li><a href='http://$org_site/view_users.php'>View User Info</a></li>
      <li class='section'><a href='http://$org_site/view_all.php'>View All Users</a></li>
      <li><a href='edit_labs.php'>Edit Labs</a></li>
      <li class='section'><a href='edit_instruments.php'>Edit Instruments</a></li>

HTML;
  }

  // userlevel 4 = admin
  if ( isset($_SESSION['userlevel']) &&
             $_SESSION['userlevel'] == 4 )
  {
    echo <<<HTML
      <li><a href='http://$org_site/edit_users.php'>Edit User Info</a></li>
      <li><a href='http://$org_site/view_users.php'>View User Info</a></li>
      <li class='section'><a href='http://$org_site/view_all.php'>View All Users</a></li>
      <li><a href='edit_labs.php'>Edit Labs</a></li>
      <li class='section'><a href='edit_instruments.php'>Edit Instruments</a></li>

HTML;
  }

  // level 5 = super admin ( developer )
  if ( isset($_SESSION['userlevel']) &&
             $_SESSION['userlevel'] == 5 )
  {
    echo <<<HTML
      <li><a href='http://$org_site/mysql_admin.php'>MySQL</a></li>
      <li><a href='http://$org_site/edit_users.php'>Edit User Info</a></li>
      <li><a href='http://$org_site/view_users.php'>View User Info</a></li>
      <li class='section'><a href='http://$org_site/view_all.php'>View All Users</a></li>
      <li><a href='edit_labs.php'>Edit Labs</a></li>
      <li class='section'><a href='edit_instruments.php'>Edit Instruments</a></li>

HTML;
  }

  // Links for all logged in users
  if ( isset($_SESSION['id']) )
  {
    echo <<<HTML
      <li><a href='http://$org_site/profile.php?edit=12'>Change My Info</a></li>
      <li><a href='http://$org_site/logout.php'>Logout</a></li>

HTML;
  }

  // Links for non-logged in users
  else
  {
      echo <<<HTML
      <li><a href='https://$org_site/login.php'>Login</a></li>

HTML;
  }

  // Now finish out the list
?>
  </ul>

  <!-- A spacer -->
  <div style='height:20em;'></div>

</div>
