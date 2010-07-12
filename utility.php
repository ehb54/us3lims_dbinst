<?php
/*
 * utility.php
 *
 * A place to store a few common functions
 *
 */

function emailsyntax_is_valid($email)
{
  list($local, $domain) = explode("@", $email);

  $pattern_local  = '^([0-9a-z]*([-|_]?[0-9a-z]+)*)' .
                    '(([-|_]?)\.([-|_]?)[0-9a-z]*([-|_]?[0-9a-z]+)+)*([-|_]?)$';

  $pattern_domain = '^([0-9a-z]+([-]?[0-9a-z]+)*)' .
                    '(([-]?)\.([-]?)[0-9a-z]*([-]?[0-9a-z]+)+)*\.[a-z]{2,4}$';

  $match_local  = eregi($pattern_local, $local);
  $match_domain = eregi($pattern_domain, $domain);

  if ( $match_local && $match_domain )
  {
    return TRUE;
  }

  return FALSE;
}

// Random Password generator. 
// http://www.phpfreaks.com/quickcode/Random_Password_Generator/56.php
function makeRandomPassword() {
  $salt = "abchefghjkmnpqrstuvwxyz0123456789";
  srand( (double)microtime() * 1234567 ); 
  $i    = 0;
  $pass = '';
  while ( $i <= 7 ) 
  {
    $num  = rand() % 33;
    $tmp  = substr($salt, $num, 1);
    $pass = $pass . $tmp;
    $i++;
  }
  return $pass;
}
?>
