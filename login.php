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

$loginusertext =
    ( isset( $enable_PAM ) && $enable_PAM )
    ? "Username or E-Mail Address:"
    : "E-Mail Address:"
    ;

echo<<<HTML

<div id='content'>

  <h1 class="title">Login</h1>

  <h3>Registered users please log in:</h3>
  <p class='message'>$message</p>

  <form method='post' action='https://$org_site/checkuser.php'>
    <table cellspacing='0' cellpadding='7'>
      <tr><td>$loginusertext</td>
          <td><input type='text' name='email' maxlength='64' size='20'
                     style='width:20em;' /></td></tr>

      <tr><td>Password:</td>
          <td><input type='password' name='password' maxlength='32'
                     size='20' style='width:20em;'/></td></tr>

      <tr><td><input type='submit' name='Submit' value='Sign In'/></td></tr>
    </table>
  </form>

  <p><a href='https://$org_site/lost_password.php'>Forget your password?</a></p>

  <h3>New Users:</h3>
  <p><a href='https://$org_site/newaccount.php'>Sign up for a new account</a></p>

</div>

HTML;

include 'footer.php';
