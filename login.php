<?php
/*
 * login.php
 *
 * Login page
 *
 */
include_once 'checkinstance.php';

$page_title = "Login";
include 'header.php';

if (! isset($message) ) $message = "";


if ( !isset( $enable_PAM ) ) {
  $enable_PAM = false;
}

if ( $enable_PAM ) {
    echo<<<HTML

<div id='content' class='account-page'>

  <div class="account-card">
  <p class="account-eyebrow">UltraScan III &middot; LIMS</p>
  <h1 class="title">Sign in to LIMS</h1>
  <p class="account-intro">Sign in to access your laboratory workspace.</p>
  <p class='message'>$message</p>

  <form method='post' action='checkuser.php'>
    <table cellspacing='0' cellpadding='7'>
      <tr><td><label for='login-email'>Username or E-Mail Address:</label></td>
          <td><input id='login-email' type='text' name='email' maxlength='64' size='20'
                     class='w-20em' autocomplete='username' autofocus required /></td></tr>

      <tr><td><label for='login-password'>Password:</label></td>
          <td><input id='login-password' type='password' name='password' maxlength='127'
                     size='20' class='w-20em' autocomplete='current-password' required /></td></tr>

      <tr><td><input type='submit' name='Submit' value='Sign In'/></td></tr>
    </table>
  </form>

  <p class='account-recovery'><a href='lost_password.php'>Forgot your password?</a></p>

  <p class='account-new'>Need an account? Please contact your administrator.</p>
  </div>

</div>

HTML;
} else {
    echo<<<HTML

<div id='content' class='account-page'>

  <div class="account-card">
  <p class="account-eyebrow">UltraScan III &middot; LIMS</p>
  <h1 class="title">Sign in to LIMS</h1>
  <p class="account-intro">Sign in to access your laboratory workspace.</p>
  <p class='message'>$message</p>

  <form method='post' action='checkuser.php'>
    <table cellspacing='0' cellpadding='7'>
      <tr><td><label for='login-email'>E-Mail Address:</label></td>
          <td><input id='login-email' type='text' name='email' maxlength='64' size='20'
                     class='w-20em' autocomplete='username' autofocus required /></td></tr>

      <tr><td><label for='login-password'>Password:</label></td>
          <td><input id='login-password' type='password' name='password' maxlength='127'
                     size='20' class='w-20em' autocomplete='current-password' required /></td></tr>

      <tr><td><input type='submit' name='Submit' value='Sign In'/></td></tr>
    </table>
  </form>

  <p class='account-recovery'><a href='lost_password.php'>Forgot your password?</a></p>

  <p class='account-new'>New to UltraScan LIMS? <a href='newaccount.php'>Create an account &rarr;</a></p>
  </div>

</div>
HTML;
}

include $common_dir . 'footer.php';
