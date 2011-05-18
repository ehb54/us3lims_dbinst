<?php
/*
 * contactus.php
 *
 * A page to allow people to contact us
 *
 */
session_start();

include 'config.php';

// Start displaying page
$page_title = "Contact Us";
$js  = 'js/contactus.js';
include 'top.php';
include 'links.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Contact Us</h1>

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

</div>

<?php
include 'bottom.php';
exit();
?>
