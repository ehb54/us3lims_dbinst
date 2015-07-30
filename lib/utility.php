<?php
/*
 * utility.php
 *
 * A place to store a few common functions
 *
 */

$admin_list = array( 'gegorbet@gmail.com',
                     'demeler@biochem.uthscsa.edu',
                     'dzollars@gmail.com' );

function emailsyntax_is_valid($email)
{
  list($local, $domain) = explode("@", $email);

  $pattern_local  = '/^([0-9a-z]*([-|_]?[0-9a-z]+)*)' .
                    '(([-|_]?)\.([-|_]?)[0-9a-z]*([-|_]?[0-9a-z]+)+)*([-|_]?)$/i';

  $pattern_domain = '/^([0-9a-z]+([-]?[0-9a-z]+)*)' .
                    '(([-]?)\.([-]?)[0-9a-z]*([-]?[0-9a-z]+)+)*\.[a-z]{2,4}$/i';

  $match_local  = preg_match($pattern_local, $local);
  $match_domain = preg_match($pattern_domain, $domain);

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

// A class to keep track of cluster information, and what clusters are available
class cluster_info
{
   public $name;
   public $short_name;
   public $queue;
   public $running;
   public $queued;
   public $status;

   public function __construct( $n, $s, $q )
   {
      $this->name       = $n;
      $this->short_name = $s;
      $this->queue      = $q;
      $this->running    = "*";
      $this->queued     = "*";
      $this->status     = "*";
   }
}

$clusters = array( 
  new cluster_info( "lonestar.tacc.teragrid.org",  "lonestar",    "normal"  ), 
  new cluster_info( "stampede.tacc.xsede.org",     "stampede",    "normal"  ), 
  new cluster_info( "comet.sdsc.edu",              "comet",       "compute" ), 
  new cluster_info( "gordon.sdsc.edu",             "gordon",      "normal"  ), 
  new cluster_info( "alamo.uthscsa.edu",           "alamo",       "batch"   ),
  new cluster_info( "alamo.uthscsa.edu",           "alamo-local", "batch"   ),
  new cluster_info( "jacinto.uthscsa.edu",         "jacinto",     "default" ),
  new cluster_info( "jacinto.uthscsa.edu",       "jacinto-local", "default" ),
  new cluster_info( "jureca.fz-juelich.de",        "jureca",      "batch"   )
  );

global $svcport;
//$gfac_serviceURL = "http://gridfarm005.ucs.indiana.edu:8080/ogce-rest/job";
//$gfac_serviceURL = "http://gridfarm005.ucs.indiana.edu:9090/ogce-rest/job";
$gfac_serviceURL = "http://gridfarm005.ucs.indiana.edu:" . $svcport . "/ogce-rest/job";

// Change for sandbox testing
global $globaldbname;

include '../down_clusters.php';

$gfac_link = mysql_connect( $globaldbhost, $globaldbuser, $globaldbpasswd );
$result    = mysql_select_db( $globaldbname, $gfac_link );

$query     = "SELECT cluster, running, queued, status FROM cluster_status";
$result    = mysql_query( $query, $gfac_link );

while ( list( $cluster, $running, $queued, $status ) = mysql_fetch_row( $result ) )
{
   if ( in_array( $cluster, $down_clusters ) )
     $status = 'down';
//if($cluster == 'comet')
// $status = 'up';

   if ( in_array( $cluster, $draining_clusters ) )
     $status = 'draining';

   for ( $i = 0; $i < count( $clusters ); $i++ )
   {
     if ( $clusters[$i]->short_name == $cluster )
     {
       $clusters[$i]->running = $running;
       $clusters[$i]->queued  = $queued;
       $clusters[$i]->status  = $status;
     }
   }
}

mysql_close( $gfac_link );

// Reset default db
include "db.php";

// Function to return appropriate clusters
function tigre()
{
  global $clusters;

  if ( $_SESSION['userlevel'] < 2 )
    return( "" );

  $text = "    <fieldset style='margin-top:1em' id='clusters'>\n" .
          "      <legend>Select Cluster</legend>\n";

  // Double check cluster authorizations
  if ( $_SESSION['userlevel'] >= 4         ||
       ( isset($_SESSION['clusterAuth'])   &&
         count($_SESSION['clusterAuth']) > 0) )
  {
    $text .= <<<HTML
    <table>
    <tr><th>Cluster</th><th>Status</th><th>Queue Name</th> <th>Running / Queued</th> <th>Likely Run Wait</th></tr>

HTML;

    $checked  = " checked='checked'";      // check the first one
  
    foreach ( $clusters as $cluster )
    {
      // Only list clusters that are authorized for the user
      if (  in_array( $cluster->short_name, $_SESSION['clusterAuth'] ) )
      {
        $disabled = ( $cluster->status == 'down' ) ? " disabled='disabled'" : "";
        $disabled = ( $cluster->status == 'draining' ) ? " disabled='disabled'" : $disabled;
        $clload   = "<td>n/a</td>";
        $clstat   = "<td>$cluster->status</td>";
        if ( $cluster->status != 'down'  &&  $cluster->status != 'draining' )
        {
          $clstat   = "<td STYLE='color: green'>$cluster->status</td>";
          $cque     = $cluster->queued;
          $crun     = $cluster->running;
          if ( $cque != 0  &&   $crun != 0 )
          {
            $qrrat     = (int)( ( $crun * 100 ) / $cque );
            if ( $qrrat < 80 )
              $clload     = "<td BGCOLOR='red'>long</td>";
            else if ( $qrrat > 120 )
              $clload     = "<td BGCOLOR='green'>short</td>";
            else
              $clload     = "<td BGCOLOR='yellow'>medium</td>";
          }
          else if ( $cque == 0  &&   $crun == 0 )
            $clload     = "<td BGCOLOR='green'>short</td>";
          if ( preg_match( "/^alamo/", $cluster->short_name ) )
          {
            if ( $cque == 0 )
              $clload     = "<td BGCOLOR='green'>short</td>";
            else
              $clload     = "<td BGCOLOR='red'>long</td>";
          }
        }
        else if ( $cluster->status == 'down' )
        {
          $clstat   = "<td STYLE='color: red'>$cluster->status</td>";
        }
        else if ( $cluster->status == 'draining' )
        {
          $clstat   = "<td STYLE='color: DarkViolet'>$cluster->status</td>";
        }

        $value = "$cluster->name:$cluster->short_name:$cluster->queue";
        $text .= "     <tr><td class='cluster'>" .
                 "<input type='radio' name='cluster' " .
                 "value='$value'$checked$disabled />" .
                 "$cluster->short_name</td>\n" .
                 "$clstat " .
                 "<td>$cluster->queue</td>"   .
                 "<td>$cluster->running / $cluster->queued</td> " .
                 "$clload</tr>\n";

        if ( $disabled == "" )
           $checked = "";
      }
    }
    $text .= <<<HTML
    </table>
    <p><input class='submit' type='submit' name='TIGRE' value='Submit'/></p>

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

// Function to get the jobstatus xml and parse for important items
function getJobstatus( $gfacID )
{
  global $gfac_serviceURL;

  $url = "$gfac_serviceURL/jobstatus/$gfacID";

  $hex = "[0-9a-fA-F]";
  if ( ! preg_match( "/^US3-Experiment/", $gfacID ) &&
       ! preg_match( "/^US3-$hex{8}-$hex{4}-$hex{4}-$hex{4}-$hex{12}$/", $gfacID ) )
     return "Not a GFAC ID";

  $r = new HttpRequest( $url, HttpRequest::METH_GET );

  $time   = date( "F d, Y H:i:s", time() );

  try
  {
     $result = $r->send();
     $xml    = $result->getBody();
  }
  catch ( HttpException $e )
  {
    return "Job status unavailable at $time\n" .
           " ( $e )\n" .
           "URL: $url\n";
  }

  $status  = "GFAC status request submitted at $time\n";
  $status .= "<table>\n";

  $parser = new XMLReader();
  $parser->xml( $xml );

  while( $parser->read() )
  {
     $type = $parser->nodeType;

     if ( $type == XMLReader::ELEMENT )
        $name = $parser->name;

     else if ( $type == XMLReader::TEXT )
     {
        if ( $name == "status" )
           $status .= "<tr><th>status:</th><td>$parser->value</td></tr>\n";
        else if ( $name = "message" )
           $status .= "<tr><th>message:</th><td>" . wordwrap( $parser->value ) . "</td></tr>\n";
     }
  }
  $status .= "</table>\n";

  $parser->close();
  return $status;

}

// Function to send out an arbitrary email message
function LIMS_mailer( $email, $subject, $message )
{
  global $org_name, $admin_email;     // From config.php

  $now = time();
  $headers = "From: $org_name Admin<$admin_email>"     . "\n";

  // Set the reply address
  $headers .= "Reply-To: $org_name<$admin_email>"      . "\n";
  $headers .= "Return-Path: $org_name<$admin_email>"   . "\n";

  // Try to avoid spam filters
  $headers .= "Message-ID: <" . $now . "info@" . $_SERVER['SERVER_NAME'] . ">\n";
  $headers .= "X-Mailer: PHP v" . phpversion()         . "\n";
  $headers .= "MIME-Version: 1.0"                      . "\n";
  $headers .= "Content-Transfer-Encoding: 8bit"        . "\n";

  mail($email, "$subject - $now", $message, $headers);
}
?>
