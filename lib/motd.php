<?php
/*
 * motd.php
 *
 * Functions to implement a message of the day
 */

// function to read the contents of a file into a string   
function motd_readfile($fn)
{
  $maxlen = 255;
  $filetext = "";


  if ( file_exists($fn) && is_readable($fn) )
  {
    if ( $fp = fopen($fn, "r") )
    {
      $line = fgets($fp, $maxlen);
      while (!feof($fp))
      {
        $filetext .= $line;
        $line = fgets($fp, $maxlen);
      }

      fclose($fp);
    }

  }
  return( $filetext );
}

// function to display the contents of $ULTRASCAN/etc/motd_submit
function motd_submit()
{
  $fn = "/share/apps64/ultrascan/etc/motd_submit";

  // Job submission is normal --- just display a php/html message
  if ( file_exists($fn) ) include $fn;


}

// function to check if job submission is blocked
function motd_isblocked()
{
  $fn = "/share/apps64/ultrascan/etc/motd_block";
  $blocked = false;

  if ( file_exists($fn) )
    $blocked = true;

  return($blocked);
}

// function to block job submission and display message
function motd_block()
{
  // First, check to see if job submission is blocked
  if ( ! motd_isblocked() )
    return;

  $fn = "/share/apps64/ultrascan/etc/motd_block";
  $message = motd_readfile($fn);

  // If userlevel < 4, display message and stop
  if ( ! isset( $_SESSION['userlevel']) ) header('Location: login_form.php');
  if ( $_SESSION['userlevel'] < 4)
  {
    // Display message
    if ($message)
      echo "<p class='message'>$message</p>\n";
    else
      echo "<p class='message'>Job submission is blocked.</p>\n";

    echo "</div>\n" .
         "</body>\n" .
         "</html>\n";
    exit;
  }

  // Userlevel == 4, 5
  if ($message)
    echo "<p class='message'>$message</p>\n";
  
  echo "<p class='message'>Job submission is blocked for ordinary users, " .
       "however you may continue.</p>\n";
  return;

}

