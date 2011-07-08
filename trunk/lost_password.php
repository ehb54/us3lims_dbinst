<?php
/*
 * lost_password.php
 *
 * A form to allow user to create a new password
 *
 */

include 'config.php';

$page_title = 'Create Password';
include 'header.php';
?>
<div id='content'>

  <h1 class="title">Lost Password</h1>

  <?php if ( isset($message) ) echo "<p class='message'>$message</p>\n"; ?>

  <h3>Recover Password Form</h3>

  <form action="recover_password.php" method="post">
    <p>Please enter your E-mail address below:</p>

    <p>E-mail address: <input name="email_address" type="text" size='30'/></p>

    <p><input type="submit" name="Submit" value="Reset My Password!"/></p>
  </form>

</div>

<?php
include 'footer.php';
?>
