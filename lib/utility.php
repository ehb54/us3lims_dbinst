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

// A class to keep track of what clusters are available
class cluster_info
{
   public $name;
   public $short_name;
   public $queue;

   public function __construct( $n, $s, $q )
   {
      $this->name  = $n;
      $this->short_name = $s;
      $this->queue = $q;
   }
}

$clusters = array( new cluster_info( "bcf.uthscsa.edu",            "bcf",      "normal" ),
                   new cluster_info( "ranger.tacc.utexas.edu",     "ranger",   "normal" ),
                   new cluster_info( "ranger.tacc.utexas.edu",     "ranger",   "long"   ),
                   new cluster_info( "queenbee.loni-lsu.teragrid.org", "queenbee", "workq"  ),
                   new cluster_info( "lonestar.tacc.teragrid.org", "lonestar", "normal" ) );

// Function to return appropriate clusters
function tigre()
{
  global $clusters;

  if ( $_SESSION['userlevel'] < 2 )
    return( "" );

  $cmd = "/share/apps64/ultrascan/etc/us_get_tigre_sysstat 2> /dev/null";
  exec( $cmd, $output);

  $text = "    <fieldset style='margin-top:1em' id='clusters'>\n" .
          "      <legend>Select TIGRE Cluster</legend>\n";

  // Double check cluster authorizations
  if ( $_SESSION['userlevel'] >= 4         ||
       ( isset($_SESSION['clusterAuth'])   &&
         count($_SESSION['clusterAuth']) > 0) )
  {
    $text .= <<<HTML
    <table>
    <tr><th>Cluster</th><th>Status</th><th>Queue Name</th> <th>running/queued</th> </tr>

    <!--<tr class='subheader'><th></th><th></th><th>running/queued</th></tr>-->

HTML;

    $checked  = " checked='checked'";      // check the first one
    $disabled = "";                        // set these as constants for now
    $status   = "up";
    $jobs     = 0;
    $load     = 0;
  
    foreach ( $clusters as $cluster )
    {
      // Userlevel 4 users can go to all systems; otherwise we check 
      //  authorizations
      if ( $_SESSION['userlevel'] >= 4         ||
           in_array( $cluster->name, $_SESSION['clusterAuth']) )
      {
        $value = "$cluster->name:$cluster->short_name:$cluster->queue";
        $text .= "     <tr><td class='cluster'>" .
                 "<input type='radio' name='cluster' " .
                 "value='$value'$checked$disabled />" .
                 "$cluster->name</td>\n         <td>$status</td> <td>$cluster->queue</td>" .
                 "<td>$jobs / $load</td></tr>\n";

        $checked = "";
      }
    } 
    $text .= <<<HTML
    </table>
    <p><input class='submit' type='submit' name='TIGRE' value='Submit via TIGRE'/></p>

HTML;
  }

  else
  {
    $text .= <<<HTML
    <p class='message'>Cluster authorizations cannot be found. Try 
       logging in again, and if that doesn&rsquo;t work contact 
       the system administrator.</p>
    <p><a href='login_form.php'>Login form</a></p>

HTML;

  }

  $text .= "     </fieldset>\n";

  return( $text );
}

/**
 * Generates a Universally Unique IDentifier, version 4.
 *
 * RFC 4122 (http://www.ietf.org/rfc/rfc4122.txt) defines a special type of Globally
 * Unique IDentifiers (GUID), as well as several methods for producing them. One
 * such method, described in section 4.4, is based on truly random or pseudo-random
 * number generators, and is therefore implementable in a language like PHP.
 *
 * We choose to produce pseudo-random numbers with the Mersenne Twister, and to always
 * limit single generated numbers to 16 bits (ie. the decimal value 65535). That is
 * because, even on 32-bit systems, PHP's RAND_MAX will often be the maximum *signed*
 * value, with only the equivalent of 31 significant bits. Producing two 16-bit random
 * numbers to make up a 32-bit one is less efficient, but guarantees that all 32 bits
 * are random.
 *
 * The algorithm for version 4 UUIDs (ie. those based on random number generators)
 * states that all 128 bits separated into the various fields (32 bits, 16 bits, 16 bits,
 * 8 bits and 8 bits, 48 bits) should be random, except : (a) the version number should
 * be the last 4 bits in the 3rd field, and (b) bits 6 and 7 of the 4th field should
 * be 01. We try to conform to that definition as efficiently as possible, generating
 * smaller values where possible, and minimizing the number of base conversions.
 *
 * @copyright   Copyright (c) CFD Labs, 2006. This function may be used freely for
 *              any purpose ; it is distributed without any form of warranty whatsoever.
 * @author      David Holmes <dholmes@cfdsoftware.net>
 *
 * @return  string  A UUID, made up of 32 hex digits and 4 hyphens.
 */

function uuid() {
   
    // The field names refer to RFC 4122 section 4.1.2

    return sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
        mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
        mt_rand(0, 65535), // 16 bits for "time_mid"
        mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
        bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
            // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
            // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
            // 8 bits for "clk_seq_low"
        mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node" 
    ); 
}

?>
