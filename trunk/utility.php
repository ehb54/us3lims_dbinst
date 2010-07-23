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

// Function to return appropriate clusters
function tigre()
{
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
    <tr><th>Cluster</th><th>Status</th><th>High Priority<br/>Queue</th>
        <th>Low Priority<br/>Queue</th></tr>

    <tr class='subheader'><th></th><th></th><th>running/queued</th><th>running/queued</th></tr>
HTML;

    $checked = " checked='checked'";      // check the first one
    foreach ( $output as $line )
    {
      list($cluster, $status, $jobs, $load) = explode(" ", $line);
      $cluster_shortname = substr( $cluster, 0, strpos($cluster, '.') );

      // Make allowance for bigred
      if ( strncmp( $cluster, "gatekeeper.bigred", 17) == 0 )
        $cluster_shortname = "bigred";

      // Userlevel 4 users can go to all systems; otherwise we check 
      //  authorizations
      if ( $_SESSION['userlevel'] >= 4         ||
           in_array( $cluster_shortname, $_SESSION['clusterAuth']) )
      {
        $text .= "     <tr><td class='cluster'>" .
                 "<input type='radio' name='cluster' value='$cluster:$cluster_shortname'$checked />\n" .
                 "  $cluster</td><td>$status</td><td>$jobs</td><td>$load</td></tr>\n";
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
?>
