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
<div id='content'>

  <h1 class="title">Lost Password</h1>

  <p>Please contact your administrator for instructions</p>
</div>
HTML;
} else {
  $echo_msg =
    isset( $message )
    ? "<p class='message'>$message</p>"
    : ""
  ;
echo<<<HTML
<div id='content'>

  <h1 class="title">Lost Password</h1>

  $echo_msg
  
  <h3>Recover Password Form</h3>

  <form action="recover_password.php" method="post">
    <p>Please enter your E-mail address below:</p>

    <p>E-mail address: <input name="email_address" type="text" size='30'/></p>

    <p><input type="submit" name="Submit" value="Reset My Password!"/></p>
  </form>

</div>
HTML;
}

include 'footer.php';
?>
