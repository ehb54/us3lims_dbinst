<?php
/*
 * lost_password.php
 *
 * A form to allow user to create a new password
 *
 */

include 'config.php';

if ( !isset( $enable_PAM ) ) {
  $enable_PAM = false;
}

$page_title = 'Lost Password';
include 'header.php';


if ( $enable_PAM ) {
echo<<<HTML
<div id='content' class='account-page'>
<div class="account-card">

  <p class="account-eyebrow">UltraScan III &middot; LIMS</p>
  <h1 class="title">Password recovery</h1>

  <p>Please contact your administrator for instructions</p>
  <p class="account-new"><a href="login.php">&larr; Back to sign in</a></p>
</div>
</div>
HTML;
} else {
  $echo_msg =
    isset( $message )
    ? "<p class='message'>$message</p>"
    : ""
  ;
echo<<<HTML
<div id='content' class='account-page'>
<div class="account-card">

  <p class="account-eyebrow">UltraScan III &middot; LIMS</p>
  <h1 class="title">Password recovery</h1>

  $echo_msg
  


  <form action="recover_password.php" method="post">
    <p>Enter your email address to recover your password.</p>

    <p><label for="recovery-email">Email address:</label> <input id="recovery-email" name="email_address" type="text" size='30'/></p>

    <p><input type="submit" name="Submit" value="Reset password"/></p>
  </form>

  <p class="account-new"><a href="login.php">&larr; Back to sign in</a></p>
</div>
</div>
HTML;
}

include $common_dir . 'footer.php';
?>
