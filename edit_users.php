<?php
/*
 * edit_users.php
 *
 * A place to edit/update the people table.
 *
 * This file is the HTTP dispatch layer only.  All action and helper functions
 * live in edit_users_actions.php so that integration tests can include that
 * file directly without triggering the session guard, HTML output, or exit()
 * calls here.
 */
include_once 'checkinstance.php';

if ( !isset($_SESSION['userlevel']) ||
     ( ($_SESSION['userlevel'] != 0) &&
       ($_SESSION['userlevel'] != 4) &&
       ($_SESSION['userlevel'] != 5) ) )   // Super user, admin and super admin only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';
include 'lib/selectboxes.php';
include 'lib/grant_integrity.php';
include 'edit_users_actions.php';
global $link;
// ini_set('display_errors', 'On');

if ( !isset( $enable_GMP ) ) {
  $enable_GMP = false;
}

if ( !isset( $enable_PAM ) ) {
  $enable_PAM = false;
}

// Are we being directed here from a push button?
if (isset($_POST['prior']))
{
  do_prior($link);
  exit();
}

else if (isset($_POST['next']))
{
  do_next($link);
  exit();
}

else if (isset($_POST['delete']))
{
  do_delete($link);
  exit();
}

else if (isset($_POST['update']))
{
  do_update($link);
  exit();
}

else if (isset($_POST['create']))
{
  do_create($link);
  exit();
}

// Start displaying page
$page_title = 'Edit Users';
$js = 'js/edit_users.js';
$css = 'css/edit_users.css';
include 'header.php';
?>
<div id='content'>

  <h1 class="title">Edit Users</h1>
  <?php if ( isset($_SESSION['message']) )
        {
          echo "<p class='message'>{$_SESSION['message']}</p>\n";
          unset($_SESSION['message']);
        } ?>

<?php
// A validation failure on the last update/create redirected here as a
// plain GET (so the browser's back/reload behaves normally), with nothing
// in $_POST to tell this dispatch which form that was. The repost stashed
// before that redirect (_eu_stash_repost()) carries it instead, and is
// read exactly once.
$repost = $_SESSION['edit_users_repost'] ?? null;
unset( $_SESSION['edit_users_repost'] );

// Edit or display a record
if ( $repost !== null && $repost['type'] === 'update' )
  edit_record($link, $repost['post']);

else if ( $repost !== null && $repost['type'] === 'create' )
  do_new($link, $repost['post']);

else if (isset($_POST['edit']))
  edit_record($link);

else if (isset($_POST['new']))
  do_new($link);

else
  display_record($link);

?>

</div>

<?php
include 'footer.php';
exit();
