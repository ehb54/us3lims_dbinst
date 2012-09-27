<?php
/*
 * admin_links.php
 *
 * A page of links for the admin
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( ($_SESSION['userlevel'] != 3) &&
     ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) )    // super, admin and super admin only
{
  header('Location: index.php');
  exit();
} 

include 'config.php';

$userlevel = ( isset( $_SESSION['userlevel'] ) ) ? $_SESSION['userlevel'] : -1;

$admin5_menu = <<<HTML
    <li><a href='https://$org_site/mysql_admin.php'>MySQL</a></li>
    <li><a href='https://$org_site/runID_info.php'>Info by Run ID</a></li>
    <li><a href='https://$org_site/orphans.php'>Orphan Report</a></li>
    <li><a href='https://$org_site/HPC_requests.php'>HPC Report</a></li>
    <li><a href='https://$org_site/check_db.php'>Check DB Linkage</a></li>
    <li><a href='https://$org_site/globaldb_stats.php'>Global DB Stats</a></li>

HTML;

$admin4_menu = <<<HTML
    <li><a href='https://$org_site/runID_info.php'>Info by Run ID</a></li>
    <li><a href='https://$org_site/orphans.php'>Orphan Report</a></li>
    <li><a href='https://$org_site/HPC_requests.php'>HPC Report</a></li>
    <li><a href='https://$org_site/check_db.php'>Check DB Linkage</a></li>
    <li><a href='https://$org_site/globaldb_stats.php'>Global DB Stats</a></li>

HTML;

$admin3_menu = <<<HTML
    <li><a href='https://$org_site/runID_info.php'>Info by Run ID</a></li>
    <li><a href='https://$org_site/orphans.php'>Orphan Report</a></li>
    <li><a href='https://$org_site/HPC_requests.php'>HPC Report</a></li>
    <li><a href='https://$org_site/check_db.php'>Check DB Linkage</a></li>

HTML;

$edituser_menu = <<<HTML
      <li><a href='https://$org_site/edit_users.php'>Edit User Info</a></li>
      <li><a href='https://$org_site/view_users.php'>View User Info</a></li>
      <li><a href='https://$org_site/view_all.php'>View All Users</a></li>
      <li><a href='https://$org_site/admin_view_projects.php'>View Users&rsquo; Projects</a></li>
      <li><a href='https://$org_site/select_user.php'>Select Data from Another User</a></li>

HTML;

$hardware_menu = <<<HTML
      <li><a href='https://$org_site/edit_labs.php'>Edit Labs</a></li>
      <li><a href='https://$org_site/edit_instruments.php'>Edit Instruments</a></li>

HTML;

// Start building different link menus for different userlevels
$admin_menu = "<ol>\n";

if ( $userlevel == 5 )  // level 5 = super admin ( developer )
{
  $admin_menu .= <<<HTML
    <li>Administrator Functions<br />
      <ul>
        $admin5_menu
      </ul>
    </li>

HTML;
}

else if ( $userlevel == 4 )  // userlevel 4 = admin
{
  $admin_menu .= <<<HTML
    <li>Administrator Functions<br />
      <ul>
        $admin4_menu
      </ul>
    </li>

HTML;
}

else if ( $userlevel == 3 )  // userlevel 3 = local admin
{
  $admin_menu .= <<<HTML
    <li>Administrator Functions<br />
      <ul>
        $admin3_menu
      </ul>
    </li>

HTML;
}

if ( $userlevel == 4 || $userlevel == 5 )
{
  $admin_menu .= <<<HTML
    <li>User Management<br />
      <ul>
        $edituser_menu
      </ul>
    </li>

HTML;
}

else // $userlevel = 3
{
  $admin_menu .= <<<HTML
    <li>User Information<br />
      <ul>
        $edituser_menu
      </ul>
    </li>

HTML;
}

  // For all $userlevel = 3, 4, 5 users
  $admin_menu .= <<<HTML
    <li>Facilities Management<br />
      <ul>
        $hardware_menu
      </ul>
    </li>

HTML;

// Close out the list
$admin_menu .= "</ol>\n";

// Start displaying page
$page_title = "Administrator Links";
include 'header.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Administrator Links</h1>
  <!-- Place page content here -->

  <p>The links on this page are provided for the administrator. Use with care.</p>

  <?php echo $admin_menu; ?>

</div>

<?php
include 'footer.php';
exit();
?>
