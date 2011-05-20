<?php
/*
 * contactus.php
 *
 * A page to allow people to contact us
 *
 */
session_start();

include 'config.php';
include 'lib/utility.php';

// Start displaying page
$page_title = "Contact Us";
$js  = 'js/contactus.js';
include 'top.php';
include 'links.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Contact Us</h1>
  <!-- Place page content here -->

<?php
// Have we logged in already?
if ( isset( $_SESSION['userlevel'] ) )
  enter_comment();

// No, have we entered the captcha text?
else if ( ! isset( $_POST['captcha'] ) )
  do_captcha();

// Yes, so do they match?
else if ( $_POST['captcha'] == $_SESSION['captcha'] )
  enter_comment();

// Ok, they don't match
else
  do_captcha( "Entered text doesn&rsquo;t match." );

?>
</div>

<?php
include 'bottom.php';
exit();

// Function to request comment information from the user
function enter_comment()
{
  echo <<<HTML
    <fieldset><legend>Send us your comments:</legend> 
    <form action="sendcomments.php" method="post"
		      onsubmit="return validate(this);" >
    <table cellspacing='0' cellpadding='10' class='style1'>

    <thead>
      <tr><th colspan='2'>Comments</th>
      </tr>
    </thead>

    <tbody>
      <tr><th>Name:</th>
          <td><input type="text" name="fname" id="fname" size="20" /> 
		          <input type="text" name="lname" id="lname" size="30" /></td>
      </tr>

      <tr><th>Phone:</th>
          <td><input type="text" name="phone" id="phone" size="14" /></td>
      </tr>

      <tr><th>Email:</th>
          <td><input type="text" name="email" id="email" size="35" /></td>
      </tr>

      <tr><th valign="top">Comments:</th>
          <td><textarea name="comments" id="comments" rows="8" cols="60"></textarea></td>
      </tr>

      <tr><!-- buttons -->
          <td valign="top" colspan="4" align="center">
            <input type="submit" value="Send it!" />
            <input type="reset" value="Reset" /> </td>
      </tr>
    </tbody>

    </table>
    </fieldset>
    </form>

HTML;
}

// Function to display a captcha and request human input
function do_captcha( $msg = "" )
{
  $message = ( empty( $msg ) ) ? "" : "<p style='color:red;'>$msg</p>";

  // Let's just use the random password function we already have
  $pw = makeRandomPassword();
  $_SESSION['captcha'] = $pw;

echo<<<HTML
  <div id='captcha'>

  $message

  <img src='create_captcha.php' alt='Captcha image' />

  <form action="{$_SERVER['PHP_SELF']}" method="post">
    <h3>Please enter the code above to proceed to the comment form</h3>

    <p><input type='text' name='captcha' size='40' maxlength='10' /></p>

    <p><input type='submit' name='enter_request' value='Enter Request' />
       <input type='reset' /></p>

  </form>

  </div>

HTML;
}

?>
