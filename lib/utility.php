<?php
/*
 * utility.php
 *
 * A place to store a few common functions
 *
 */

## collect config info

function collect_config_info() {
    global $class_dir;
    global $admin_list;  ##  can be set in global or dbinst specific config
    global $clusters;
    global $global_cluster_details;

    $debug = false;
    
    ## anonymous error message function - local in scope
    $error_msg = function( $msg ) {
        $emsg = "ERROR: lib/utility.php : $msg";
        echo "$emsg<br>";
        error_log( $emsg );
    };

    ## anonymous info message function - local in scope

    $debug_msg = function( $msg, $debug ) {
        if ( $debug ) {
            $emsg = "info: lib/utility.php : $msg";
            echo "$emsg<br>";
        }
    };
    
    if ( !isset( $class_dir ) ) {
        $error_msg( "\$class_dir is not set" );
        return;
    }

    if ( !is_dir( $class_dir ) ) {
        $error_msg( "\$class_dir [$class_dir] is not a directory" );
        return;
    }

    ## dbinst specific configs

    $dbinst_config_file = '../cluster_config.php';

    if ( !file_exists( $dbinst_config_file ) ) {
        $dbinst_config_file = '../uslims3_newlims/cluster_config.php';
        if ( !file_exists( $dbinst_config_file ) ) {
            $error_msg( "no cluster_config.php file found" );
            return;
        }
    }

    ## global configs

    $global_config_file = "$class_dir/../global_config.php";

    if ( !file_exists( $global_config_file ) ) {
        $error_msg("\$global_config_file_dir [$global_config_file] does not exist");
        return;
    }
        
    ## read global config first, so dbinst overrides

    try {
        include( $global_config_file );
    } catch ( Exception $e ) {
        $error_msg( "including $global_config_file " . $e->getMessage() );
        return;
    }

    try {
        include( $dbinst_config_file );
    } catch ( Exception $e ) {
        $error_msg ( "including $dbinst_config_file " . $e->getMessage() );
        return;
    }
        
    if ( !isset( $cluster_configuration ) || !is_array( $cluster_configuration ) ) {
        $error_msg( "\$cluster_configuration not set or is not an array" );
        return;
    }

    if ( !isset( $cluster_details ) || !is_array( $cluster_details ) ) {
        $error_msg( "\$cluster_details not set or is not an array" );
        return;
    }

    ## if inactive or corrupt in $cluster_configuration remove from $cluster_details

    foreach ( $cluster_details as $k => $v ) {
        ## echo json_encode( $v, JSON_PRETTY_PRINT ) . "<br>";
        if ( !array_key_exists( $k, $cluster_configuration ) ) {
            ## disable cluster not present in $cluster_configuration
            unset( $cluster_details[$k] );
            continue;
        }
        if ( !is_array( $cluster_configuration[$k] ) ) {
            $error_msg( "cluster configuratin for cluster $k is not an array" );
            ## disable cluster corrupt in $cluster_configuration
            unset( $cluster_details[$k] );
            continue;
        }
        if ( !array_key_exists( 'active', $cluster_configuration[$k] ) ||
             $cluster_configuration[$k]['active'] == false
            ) {
            ## disable active==false cluster in $cluster_configuration
            unset( $cluster_details[$k] );
            continue;
        }
    }

    $global_cluster_details = $cluster_details;

    $clusters = [];
    foreach ( $cluster_details as $k => $v ) {
        ## echo json_encode( $v, JSON_PRETTY_PRINT ) . "<br>";
        if ( !array_key_exists( 'active', $v ) ) {
            $error_msg( "cluster details missing 'active' flag for cluster $k" );
            continue;
        }
        if ( !$v['active'] ) {
            $debug_msg( "cluster $k not active", $debug );
            continue;
        }
        if ( !array_key_exists( $k, $cluster_configuration ) ) {
            $debug_msg( "cluster $k would be active, but not present in \$cluster_configuration", $debug );
            continue;
        }
        if ( !array_key_exists( 'active', $cluster_configuration[$k] ) ) {
            $debug_msg( "cluster $k missing 'active' in \$cluster_configuration", $debug );
            continue;
        }
        if ( !$cluster_configuration[$k]['active'] ) {
            $debug_msg( "cluster $k would be active, but inactive in \$cluster_configuration", $debug );
            continue;
        }
        if ( !array_key_exists( 'name', $v ) ) {
            $error_msg( " cluster details missing 'name' for cluster $k" );
            continue;
        }
        if ( array_key_exists( 'clusters', $v ) ) {
            $v['queue'] = 'n/a';
            if ( count( $v['clusters'] ) == 0 ) {
                $error_msg( " metascheduling clusters is empty for cluster $k" );
                continue;
            }
        }
        if ( !array_key_exists( 'queue', $v ) ) {
            $error_msg( "cluster details missing 'queue' for cluster $k" );
            continue;
        }
        $debug_msg( "cluster $k adding", $debug );
        $clusters[] = new cluster_info( $v['name'], $k, $v['queue'] );
    }
}

collect_config_info();

if ( !isset( $admin_list ) || count( $admin_list ) == 0 ) {
    ## revert to hard coded defaults
    $admin_list = array( 'gegorbet@gmail.com',
                         'demeler@umontana.edu',
                         'alexsav.science@gmail.com',
                         'emre.brookes@umontana.edu' );
}

function emailsyntax_is_valid($email)
{
  $array = explode("@", $email);
  if ( count($array) != 2 ) {
    return FALSE;
  }

  list($local, $domain) = $array;

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

function PAM_name_is_valid($name)
{
        
  if ( emailsyntax_is_valid( $name ) ) {
    return TRUE;
  }

  $pattern  = '/^([0-9a-zA-Z]*([-|_]?[0-9a-zA-Z]+)*)' .
              '(([-|_]?)\.([-|_]?)[0-9a-zA-Z]*([-|_]?[0-9a-zA-Z]+)+)*([-|_]?)$/i';
  $match    = preg_match( $pattern, $name );
  if ( $match ) {
     return TRUE;
  }
  return FALSE;
}

// Random Password generator. 
// http://www.phpfreaks.com/quickcode/Random_Password_Generator/56.php
/**
 * Generates a random password of either a fixed length or a variable length.
 *
 * The password consists of alphanumeric characters excluding easily confused ones.
 * If a fixed length is provided, the password will be exactly that length; otherwise,
 * it will be a random length between 6 and 12 characters.
 *
 * @param int $fixed_length The fixed length for the password. If zero or not provided,
 *                          a random length between 6 and 12 will be used.
 * @return string           A randomly generated password.
 */
function makeRandomPassword($fixed_length = 0 ): string
{
  $salt = "abchefghjkmnpqrstuvwxyz0123456789";
  srand( (double)microtime() * 1234567 ); 
  $i    = 0;
  $pass = '';
  // length of random password
  if ( $fixed_length > 0 ) {
    $max_len = $fixed_length;
  } else {
    $max_len = rand(6, 12);
  }
  while ( $i <= $max_len )
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

if ( !isset( $clusters ) || count( $clusters ) == 0 ) {
    ## fall back to hard coded defaults
    $clusters = array( 
        new cluster_info( "dev1-linux",               "us3iab-devel",   "normal"  )
        ,new cluster_info( "ls5.tacc.utexas.edu",      "lonestar5",      "normal"  )
        ,new cluster_info( "stampede2.tacc.xsede.org", "stampede2",      "skx-normal" )
        ,new cluster_info( "comet.sdsc.xsede.org",     "comet",          "compute" )
        ,new cluster_info( "bridges2.psc.edu",         "bridges2",       "RM-shared"  )
        ,new cluster_info( "expanse.sdsc.edu",         "expanse",        "shared"  )
        ,new cluster_info( "expanse.sdsc.edu",         "expanse-gamc",   "compute" )
        ,new cluster_info( "juwels.fz-juelich.de",     "juwels",         "batch"   )
        ,new cluster_info( "js-169-137.jetstream-cloud.org", "jetstream",       "batch" )
        ,new cluster_info( "js-169-137.jetstream-cloud.org", "jetstream-local", "batch" )
        ,new cluster_info( "taito.csc.fi",             "taito-local",    "serial"  )
        ,new cluster_info( "puhti.csc.fi",             "puhti-local",    "serial"  )
        ,new cluster_info( "chinook.hs.umt.edu",       "chinook-local",  "batch"   )
        ,new cluster_info( "login.gscc.umt.edu",       "umontana-local", "griz_partition" )
        ,new cluster_info( "demeler9.uleth.ca",        "demeler9-local", "batch"   )
        ,new cluster_info( "demeler1.uleth.ca",        "demeler1-local", "batch"   )
        ,new cluster_info( "us3iab-node0.localhost",   "us3iab-node0",   "batch"   )
        ,new cluster_info( "us3iab-node1.localhost",   "us3iab-node1",   "normal"  )
        );
}

global $svcport;
$gfac_serviceURL = "http://gridfarm005.ucs.indiana.edu:" . $svcport . "/ogce-rest/job";

// Change for sandbox testing
//global $globaldbname;
global $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname;

if ( file_exists('../down_clusters.php') ) include '../down_clusters.php';

$gfac_link = mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname );
$result    = mysqli_select_db( $gfac_link, $globaldbname );

$query     = "SELECT cluster, running, queued, status FROM cluster_status";
$result    = mysqli_query( $gfac_link, $query );

while ( list( $cluster, $running, $queued, $status ) = mysqli_fetch_row( $result ) )
{
   if ( isset( $down_clusters ) && in_array( $cluster, $down_clusters ) )
     $status = 'down';
//if($cluster == 'jureca')
// $status = 'up';
//if($cluster == 'alamo')
// $status = 'up';
//if($cluster == 'lonestar5')
// $status = 'up';

   if ( isset( $draining_clusters ) && in_array( $cluster, $draining_clusters ) )
     $status = 'draining';

   for ( $ii = 0; $ii < count( $clusters ); $ii++ )
   {
     if ( $clusters[$ii]->short_name == $cluster )
     {
       $clusters[$ii]->running = $running;
       $clusters[$ii]->queued  = $queued;
       $clusters[$ii]->status  = $status;
     }
   }
}

mysqli_close( $gfac_link );

// Reset default db
include "db.php";

// Function to show appropriate clusters
function showClusters()
{
  global $clusters;
  global $org_site;

  if ( $_SESSION['userlevel'] < 2 )
    return( "" );

  $text = "    <fieldset class='margin_d_top_c_1em' id='clusters'>\n" .
          "      <legend>Available Clusters:</legend>\n";

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
    $gamcnms  = "";
    $ngamc    = 0;
    foreach ( $clusters as $cluster )
    {
      // Only list clusters that are authorized for the user
      if (  in_array( $cluster->short_name, $_SESSION['clusterAuth'] ) )
      {
        $disabled = ( $cluster->status == 'down' ) ? " disabled='disabled'" : "";
        $disabled = ( $cluster->status == 'draining' ) ? " disabled='disabled'" : $disabled;
        $clload   = "<td>n/a</td>";
	$clstat   = "<td>$cluster->status</td>";

        // Color-code entry based on status and queue counts
        if ( $cluster->status != 'down'  &&  $cluster->status != 'draining' )
        {
          $clstat   = "<td class='color_c__green'>$cluster->status</td>";
          $cque     = $cluster->queued;
          $crun     = $cluster->running;
  
          if ( $cque != 0  &&   $crun != 0 )
          {
            $qrrat     = (int)( ( $crun * 100 ) / $cque );
            if ( $qrrat < 80 )
              $clload     = "<td width=70 BGCOLOR='red'>long</td>";
            else if ( $qrrat > 120 )
              $clload     = "<td width=70 BGCOLOR='green'>short</td>";
            else
              $clload     = "<td width=70 BGCOLOR='yellow'>medium</td>";
          }
  
          else if ( $cque == 0 )
            $clload     = "<td BGCOLOR='green'>short</td>";
  
          else
            $clload     = "<td BGCOLOR='red'>long</td>";
        }
  
        else if ( $cluster->status == 'down' )
        {
          $clstat   = "<td class='color_c__red'>$cluster->status</td>";
        }
  
        else if ( $cluster->status == 'draining' )
        {
          $clstat   = "<td class='color_c__DarkViolet'>$cluster->status</td>";
        }

        $clname = $cluster->name;
        if ( preg_match( '/localhost/', $clname ) )
        {  // Form local cluster name
          $parts  = explode( "/", $org_site );
          $lohost = $parts[ 0 ];
          $clname = preg_replace( '/uslims3/', $cluster->short_name, $lohost );
        }
        if ( preg_match( '/-gamc/', $cluster->short_name ) )
        {  // Keep track of "-gamc" type names
           if ( $ngamc == 0 )
              $gamcnms  = $cluster->short_name;
           else
              $gamcnms .= "|$cluster->short_name";
           $ngamc++;
        }

        $value = "$clname:$cluster->short_name:$cluster->queue";
        $text .= "     <tr><td class='cluster' width=150 >" .
                 "$cluster->short_name</td>\n" .
                 "$clstat " .
                 "<td>$cluster->queue</td>"   .
                 "<td>$cluster->running / $cluster->queued</td> " .
                 "$clload</tr>\n";

        if ( $disabled == "" )
           $checked = "";
      }
    }
    $mctext = "";
    if ( $ngamc > 0 )
    {  // Add note about choosing "-gamc" cluster
       $mctext .= <<<HTML
       </table>
       <table>
HTML;
    }

    $text .= <<<HTML
    $mctext
    </table>

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

// Function to return appropriate clusters
function tigre( $force_pmg = false )
{
  global $clusters;
  global $org_site;
  global $global_cluster_details;

  if ( $_SESSION['userlevel'] < 2 )
    return( "" );

  $text = "    <fieldset class='margin_d_top_c_1em' id='clusters'>\n" .
          "      <legend>Select Cluster</legend>\n";

  ## do we have mgroupcount && mc_iterations > 1?
  $pmg_job =
      $force_pmg
      || (
          array_key_exists( 'payload_mgr', $_SESSION )
          && array_key_exists( 'job_parameters',  $_SESSION[ 'payload_mgr' ] )
          && array_key_exists( 'req_mgroupcount', $_SESSION[ 'payload_mgr' ][ 'job_parameters' ] )
          && $_SESSION[ 'payload_mgr' ][ 'job_parameters' ][ 'req_mgroupcount' ] > 1
          && array_key_exists( 'mc_iterations', $_SESSION[ 'payload_mgr' ][ 'job_parameters' ] )
          && $_SESSION[ 'payload_mgr' ][ 'job_parameters' ][ 'mc_iterations' ] > 1
      )
      ;

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
    $gamcnms  = "";
    $ngamc    = 0;
  
    foreach ( $clusters as $cluster )
    {
        ## Only list clusters that are authorized for the user
        if (
            in_array( $cluster->short_name, $_SESSION['clusterAuth'] )
            && array_key_exists( $cluster->short_name, $global_cluster_details )
            && (
                (
                 ## pmg job and pmj true
                 $pmg_job
                 && array_key_exists( 'pmg', $global_cluster_details[$cluster->short_name] )
                 && $global_cluster_details[$cluster->short_name]['pmg'] == true
                )
                ||
                (
                 ## !pmg job and pmjonly not set or false
                 !$pmg_job
                 && (
                      !array_key_exists( 'pmgonly', $global_cluster_details[$cluster->short_name] )
                      || $global_cluster_details[$cluster->short_name]['pmgonly'] == false
                 )
                )
            )
            )
        {
        $disabled = ( $cluster->status == 'down' ) ? " disabled='disabled'" : "";
        $disabled = ( $cluster->status == 'draining' ) ? " disabled='disabled'" : $disabled;
        $clload   = "<td>n/a</td>";
        $clstat   = "<td>$cluster->status</td>";

        // Color-code entry based on status and queue counts
        if ( $cluster->status != 'down'  &&  $cluster->status != 'draining' )
        {
          $clstat   = "<td class='color_c__green'>$cluster->status</td>";
          $cque     = $cluster->queued;
          $crun     = $cluster->running;
  
          if ( $cque != 0  &&   $crun != 0 )
          {
            $qrrat     = (int)( ( $crun * 100 ) / $cque );
            if ( $qrrat < 80 )
              $clload     = "<td width=70 BGCOLOR='red'>long</td>";
            else if ( $qrrat > 120 )
              $clload     = "<td width=70 BGCOLOR='green'>short</td>";
            else
              $clload     = "<td width=70 BGCOLOR='yellow'>medium</td>";
          }
  
          else if ( $cque == 0 )
            $clload     = "<td BGCOLOR='green'>short</td>";
  
          else
            $clload     = "<td BGCOLOR='red'>long</td>";
        }
  
        else if ( $cluster->status == 'down' )
        {
          $clstat   = "<td class='color_c__red'>$cluster->status</td>";
        }
  
        else if ( $cluster->status == 'draining' )
        {
          $clstat   = "<td class='color_c__DarkViolet'>$cluster->status</td>";
        }

        $clname = $cluster->name;
        if ( preg_match( '/localhost/', $clname ) )
        {  // Form local cluster name
          $parts  = explode( "/", $org_site );
          $lohost = $parts[ 0 ];
          $clname = preg_replace( '/uslims3/', $cluster->short_name, $lohost );
        }
        if ( preg_match( '/-gamc/', $cluster->short_name ) )
        {  // Keep track of "-gamc" type names
           if ( $ngamc == 0 )
              $gamcnms  = $cluster->short_name;
           else
              $gamcnms .= "|$cluster->short_name";
           $ngamc++;
        }

        $value = "$clname:$cluster->short_name:$cluster->queue";
        $text .= "     <tr><td class='cluster' width=150 >" .
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
    $mctext = "";
##    if ( $ngamc > 0 )
##    {  // Add note about choosing "-gamc" cluster
##       $mctext .= <<<HTML
##       </table><table><tr><td class='color_c__DarkViolet'>
##<b>N.B.</b> For GA-MC jobs, select any existing "-gamc" variation of a chosen cluster.</td></tr>
## HTML;
##    }
    $text .= <<<HTML
    $mctext
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

  ## debugging
  ## file_put_contents( "/tmp/debug2", json_encode( $_SESSION[ 'payload_mgr' ], JSON_PRETTY_PRINT ) );
  ## $text .= $pmg_job ? "is pmg_job" : "not pmg_job";

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
  $servname = $_SERVER['SERVER_NAME'];
  if ( preg_match( "/novalo/", $servname ) )
     $servname = "uslims3.aucsolutions.com";
  else if ( preg_match( "/scyld/", $servname ) )
     $servname = "alamo.uthscsa.edu";

  //$headers = "From: $org_name Admin<$admin_email>"     . "\n";
  //$headers = "From: us3@uslims3.aucsolutions.com"     . "\n";       
   $headers = "From: us3@" . $servname . "\n";       
  

  // Set the reply address
  $headers .= "Reply-To: $org_name<$admin_email>"      . "\n";
  $headers .= "Return-Path: $org_name<$admin_email>"   . "\n";

  // Try to avoid spam filters
  $headers .= "Message-ID: <" . $now . "info@" . $servname . ">\n";
  $headers .= "X-Mailer: PHP v" . phpversion()         . "\n";
  $headers .= "MIME-Version: 1.0"                      . "\n";
  $headers .= "Content-Transfer-Encoding: 8bit"        . "\n";

  mail($email, "$subject - $now", $message, $headers);
}
