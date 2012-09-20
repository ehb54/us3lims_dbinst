<?php
/*
 * globaldb_stats.php
 *
 * A home page for generating/viewing/exporting global database stats
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( ( $_SESSION['userlevel'] != 4 ) &&
     ( $_SESSION['userlevel'] != 5 ) )  // global admins only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'current_db_list.php';

// Specific DB setup for functions in this file
$conn1   = mysql_connect($v1_host, $v1_user, $v1_pass)
           or die("Could not connect to $v1_host\n");

$conn2   = mysql_connect($v2_host, $v2_user, $v2_pass)
           or die("Could not connect to $v2_host\n");

// Make sure output buffering is off and cleared for this page
while (ob_get_level())
  ob_end_flush();

if (ob_get_length() === false)
  ob_start();

$cluster_list = array(
                'lonestar.tacc.teragrid.org'          => 'lonestar.tacc',
                'lonestar'                            => 'lonestar',
                'bcf.uthscsa.edu'                     => 'bcf',
                'alamo.uthscsa.edu'                   => 'alamo',
                'ranger.tacc.teragrid.edu'            => 'ranger',
                'queenbee.loni-lsu.teragrid.org'      => 'queenbee',
                'gatekeeper.ranger.tacc.teragrid.edu' => 'gatekeeper.ranger',
                'stampede.tacc.teragrid.org'          => 'stampede',
                'gordon.sdsc.edu'                     => 'gordon',
                'trestles.sdsc.edu'                   => 'trestles',
                'alamo.biochemistry.uthscsa.edu'      => 'alamo.biochemistry'
                );

// Figure out which clusters were selected
$selected_clusters = array();
if ( $_POST )
{
  foreach ( $_POST as $key => $value )
  {
    // HTML translates the periods into underscores
    $key = implode( '.', explode( '_', $key ) );
    if ( array_key_exists( $key, $cluster_list ) )
      $selected_clusters[$key] = $cluster_list[$key];
  }
}

else
  $selected_clusters = $cluster_list;

// Start displaying page
$page_title = 'Global DB Statistics';
$js  = 'js/export.js';
include 'header.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Global DB Statistics</h1>
  <!-- Place page content here -->

<?php
// Generate global data or view/export report
if (isset($_POST['generate']))
  generate();

else if ( isset($_POST['by_quarter']) )
  by_quarter();

// Menu and general stuff
$start_year_menu = start_year_menu();

// Develop cluster checkboxes
$cluster_boxes = '';
foreach ( $cluster_list as $key => $value )
{
  $checked = ( array_key_exists( $key, $selected_clusters ) ) 
           ? " checked='checked'" : "" ;
  $cluster_boxes .= "  <tr><td><input type='checkbox' name='$key' \n" .
                    "                $checked />$key</td></tr>\n";
}
$count_boxes = count( $cluster_list ) + 1;

echo <<<HTML
<form action="{$_SERVER['PHP_SELF']}" method='post'>
  <fieldset>
  <label>Select from the following information</label>

  <table cellspacing='10' cellpadding='0' class='noborder'>
  <tr><th>Starting year for report:</th>
      <td>$start_year_menu</td>
  </tr>

  <tr><th rowspan='$count_boxes'>Clusters to include:</th></tr>
$cluster_boxes

  <tr><th rowspan='2'>Actions:</th>
      <td><input type='submit' name='generate' value='Generate Global DB' />
          (Just do this once.)</td>
  </tr>
  <tr><td><input type='submit' name='by_quarter' value='Quarterly Report' /></td>
  </tr>
  
  </table>
  </fieldset>
</form>

HTML;

?>
</div>

<?php
include 'footer.php';
exit();

// Function to create a starting year dropdown list box
function start_year_menu()
{
  $earliest_year = 2011;
  $current_year  = date( 'Y' );

  // Keep previously-selected year, if possible
  if ( isset( $_POST['start_year'] ) )
    $start_year = $_POST['start_year'];

  else
    $start_year = $current_year - 1;

  $menu = "<select name='start_year' size='1'>\n";
  for ( $year = $earliest_year; $year <= $current_year; $year++ )
  {
    $selected = ( $year == $start_year ) ? " selected='selected'" : "";
    $menu    .= "  <option value='$year'$selected>$year</option>\n";
  }

  $menu .= "</select>\n";

  return $menu;
}

/*
 * Function to copy selected db information from different individual lims
 *  tables into a temporary global database
 */
function generate()
{
  global $dblist;
  global $conn1, $conn2;
  global $v1_host, $v2_host;

  // We probably need more time for this one
  set_time_limit(600);

  $global_db =    'uslims3_global';

  // Delete existing records from global DB
  mysql_select_db($global_db, $conn2)
            or die("Could not select database $global_db on $v2_host.");

  $query  = "DELETE FROM submissions ";
  mysql_query($query)
        or die("Query failed : $query\n" . mysql_error());

  $query  = "DELETE FROM investigators ";
  mysql_query($query)
        or die("Query failed : $query\n" . mysql_error());

  echo "<pre>\n";

  // Now get info from each database and add it
  foreach ( $dblist as $db )
  {
    echo "Using $db.";

    $query  = "SELECT q.HPCAnalysisRequestID, startTime, endTime, " .
              "CPUTime, clusterName, CPUCount, " .
              "i.personID AS investigatorID, s.personID AS submitterID, " .
              "CONCAT(i.lname, ', ', i.fname) AS investigatorName, " .
              "CONCAT(s.lname, ', ', s.fname) AS submitterName " .
              "FROM HPCAnalysisRequest q, HPCAnalysisResult r, " .
              "people i, people s " .
              "WHERE q.HPCAnalysisRequestID = r.HPCAnalysisRequestID " .
              "AND investigatorGUID = i.personGUID " .
              "AND submitterGUID = s.personGUID " .
              "AND clusterName IS NOT NULL ";          // These are canceled jobs
    mysql_select_db($db, $conn1)
              or die("Could not select database $db on $v1_host.");
    $result = mysql_query($query, $conn1)
              or die("Query failed : $query\n" . mysql_error());
    echo ".";
    ob_flush();
    flush();

    // Now update the global database
    mysql_select_db($global_db, $conn2)
              or die("Could not select database $global_db on $v2_host.");
    while ( $row = mysql_fetch_array($result) )
    {
      // Make variables
      foreach ($row as $key => $value )
      {
        $$key = $value;
      }

      $query  = "INSERT INTO submissions " .
                "SET HPCAnalysis_ID = $HPCAnalysisRequestID, " .
                "db = '$db', " .
                "DateTime = '$startTime', " .
                "EndDateTime = '$endTime', " .
                "CPUTime = '$CPUTime', " .
                "Cluster_Name = '$clusterName', " .
                "CPU_Number = $CPUCount, " .
                "InvestigatorID = $investigatorID, " .
                "Investigator_Name = '$investigatorName', " .
                "SubmitterID = $submitterID, " .
                "Submitter_Name = '$submitterName' ";
      mysql_query($query)
            or die("Query failed : $query\n" . mysql_error());
    }
    echo ".";
    ob_flush();
    flush();

    // Now a table of investigator statistics
    $query  = "SELECT personID, " .
              "CONCAT(lname, ', ', fname) AS investigatorName, " .
              "email, signup, lastLogin, userlevel " .
              "FROM people " .
              "ORDER BY lname, fname ";
    mysql_select_db($db, $conn1)
              or die("Could not select database $db on $v1_host.");
    $result = mysql_query($query, $conn1)
              or die("Query failed : $query\n" . mysql_error());
    echo ".";
    ob_flush();
    flush();

    // Now the global database
    mysql_select_db($global_db, $conn2)
              or die("Could not select database $global_db on $v2_host.");
    while ( $row = mysql_fetch_array($result) )
    {
      // Make variables
      foreach ($row as $key => $value )
      {
        $$key = $value;
      }

      $query  = "INSERT INTO investigators " .
                "SET InvestigatorID = $personID, " .
                "Investigator_Name = '$investigatorName', " .
                "db = '$db', " .
                "Email = '$email', " .
                "Signup = '$signup', " .
                "LastLogin = '$lastLogin', " .
                "Userlevel = '$userlevel' ";
      mysql_query($query)
            or die("Query failed : $query\n" . mysql_error());

      echo ".";
      ob_flush();
      flush();
    }

    echo "\n";
  }

  echo "</pre>\n";
}

/*
 * Produces a table with quarterly statistics report information
 *  on individual cluster usage. Uses HPC result tables
 *  across all databases
 * Be sure to use the generate() function first, to create a global 
 *  database with all the information we need.
 *
 */
function by_quarter()
{
  global $conn2, $v2_host;
  global $page_title;
  global $selected_clusters;

  $end_year  = date( 'Y' );

  // Keep selected year, if possible
  if ( isset( $_POST['start_year'] ) )
    $start_year = $_POST['start_year'];

  else
    $start_year = $end_year - 1;

  $cluster_comma_list = "'" . implode( "', '", array_keys( $selected_clusters ) ) . "'";

  $quarters = array( '', '1st', '2nd', '3rd', '4th' );

  $super_tot = 0;

  $column_count = count( $selected_clusters ) + 5;
  echo <<<HTML
  <table cellpadding='10' cellspacing='0' class='style1'>
    <thead>
    <tr><th colspan='$column_count'>Cluster Usage Report ($start_year - $end_year)</th></tr>
    </thead>

    <tfoot>
      <tr><td colspan='$column_count'><input type='button' value='Export'
                     onclick='export_file();' />
          </td></tr>
    </tfoot>

    <tbody>

HTML;
    
  // For export file
  $export  = array();
  $counter = 0;

  for ( $year = $start_year; $year <= $end_year; $year++ )
  {
    // Column headings
    echo "  <tr style='background-color:#9CC4E4;'><th>Quarter/<br />\n" .
         "          $year</th>\n";
    $export[$counter]['Quarter'] = $year;
    foreach ( $selected_clusters as $cluster => $shortname )
    {
      echo "      <th>$shortname</th>\n";
      $export[$counter][$shortname] = '';
    }

    // The last few columns
    echo <<<HTML
      <th>Total</th>
      <th>#Investigators</th>
      <th>#Submitters</th>
      <th>#Jobs</th>
  </tr>

HTML;
    
    $export[$counter]['Total']         = '';
    $export[$counter]['Investigators'] = '';
    $export[$counter]['Submitters']    = '';
    $export[$counter]['Jobs']          = '';
    $counter++;

    $grand_tot = 0;

    for ( $quarter = 1; $quarter <= 4; $quarter++)
    {
      switch ( $quarter )
      {
          case 1 :
            $start = "$year-01-01";
            $end   = "$year-03-31";
            break;

          case 2 :
            $start = "$year-04-01";
            $end   = "$year-06-30";
            break;

          case 3 :
            $start = "$year-07-01";
            $end   = "$year-09-30";
            break;

          case 4 :
            $start = "$year-10-01";
            $end   = "$year-12-31";
            break;

          default :
            break;
      }

      // Start a row
      $global_shortname =    'uslims3_global';

      mysql_select_db($global_shortname, $conn2)
                or die("Could not select database $global_shortname on $v2_host.");
      
      $quarter_sum = 0;
      echo "  <tr><th>{$quarters[$quarter]}</th>\n";
      $export[$counter]['Quarter'] = $quarters[$quarter];
      foreach ( $selected_clusters as $cluster => $shortname )
      {
        // CPUTime is in seconds, so 60 * 60 = hours
        $query  = "SELECT SUM(CPUTime*CPU_Number)/3600.0 " .
                  "FROM submissions " .
                  "WHERE Cluster_Name = '$cluster' " .
                  "AND DateTime >= '$start 00:00:00' " .
                  "AND DateTime <= '$end 23:59:59' ";
        $result = mysql_query($query)
                  or die("Query failed : $query\n" . mysql_error());
   
        list($time) = mysql_fetch_array($result);
        $time = ( $time == NULL ) ? 0 : $time;
        $quarter_sum += $time;

        echo "      <td>" . round($time, 1) . "</td>\n";
        $export[$counter][$shortname] = round($time, 1);
      }
   
      // Now let's get investigator and submitter stats
      $query  = "SELECT COUNT(DISTINCT Investigator_Name), " .
                "COUNT(DISTINCT Submitter_Name), " .
                "COUNT(DISTINCT HPCAnalysis_ID) " .
                "FROM submissions " .
                "WHERE Cluster_Name IN ( $cluster_comma_list ) " .
                "AND DateTime >= '$start 00:00:00' " .
                "AND DateTime <= '$end 23:59:59' ";
      $result = mysql_query($query)
                or die("Query failed : $query\n" . mysql_error());
   
      $grand_tot += $quarter_sum;
      $quarter_sum_rounded = round( $quarter_sum, 1 );
   
      list($inv_count, $sub_count, $job_count) = mysql_fetch_array($result);
      echo <<<HTML
      <td>$quarter_sum_rounded</td>
      <td>$inv_count</td>
      <td>$sub_count</td>
      <td>$job_count</td>
  </tr>

HTML;
      $export[$counter]['Total']         = $quarter_sum_rounded;
      $export[$counter]['Investigators'] = $inv_count;
      $export[$counter]['Submitters']    = $sub_count;
      $export[$counter]['Jobs']          = $job_count;

      $counter++;
    }

    // In this case it's probably easier just to do another query for totals
    $start   = "$year-01-01";
    $end     = "$year-12-31";

    echo " <tr><th>Tot</th>\n";
    $export[$counter]['Quarter'] = 'Tot';
    foreach ( $selected_clusters as $cluster => $shortname)
    {
      // CPUTime is in seconds, so 60 * 60 = hours
      $query  = "SELECT SUM(CPUTime*CPU_Number)/3600.0 " .
                "FROM submissions " .
                "WHERE Cluster_Name = '$cluster' " .
                "AND DateTime >= '$start 00:00:00' " .
                "AND DateTime <= '$end 23:59:59' ";
      $result = mysql_query($query)
                or die("Query failed : $query\n" . mysql_error());
   
      list($time) = mysql_fetch_array($result);
      $time = ( $time == NULL ) ? 0 : $time;
      echo "      <th>" . round($time, 1) . "</th>\n";
      $export[$counter][$shortname] = round($time, 1);
    }

    // Now let's get investigator and submitter stats
    $query  = "SELECT COUNT(DISTINCT Investigator_Name), " .
              "COUNT(DISTINCT Submitter_Name), " .
              "COUNT(DISTINCT HPCAnalysis_ID) " .
              "FROM submissions " .
              "WHERE Cluster_Name IN ( $cluster_comma_list ) " .
              "AND DateTime >= '$start 00:00:00' " .
              "AND DateTime <= '$end 23:59:59' ";
    $result = mysql_query($query)
              or die("Query failed : $query\n" . mysql_error());

    list($inv_count, $sub_count, $job_count) = mysql_fetch_array($result);
    $grand_tot_rounded = round( $grand_tot, 1 );
    echo <<<HTML
      <th>$grand_tot_rounded</th>
      <th>$inv_count</th>
      <th>$sub_count</th>
      <th>$job_count</th>
  </tr>

HTML;

    $export[$counter]['Total']         = $grand_tot_rounded;
    $export[$counter]['Investigators'] = $inv_count;
    $export[$counter]['Submitters']    = $sub_count;
    $export[$counter]['Jobs']          = $job_count;

    $super_tot += $grand_tot;

    $counter++;
  }

  // Now let's get a super-grant total from all years
  $start   = "$start_year-01-01";
  $end     = "$end_year-12-31";

  echo "  <tr><th>All</th>\n";
  $export[$counter]['Quarter'] = 'All';

  foreach ( $selected_clusters as $cluster => $shortname )
  {
    // CPUTime is in seconds, so 60 * 60 = hours
    $query  = "SELECT SUM(CPUTime*CPU_Number)/3600.0 " .
              "FROM submissions " .
              "WHERE Cluster_Name = '$cluster' " .
              "AND DateTime >= '$start 00:00:00' " .
              "AND DateTime <= '$end 23:59:59' ";
    $result = mysql_query($query)
              or die("Query failed : $query\n" . mysql_error());

    list($time) = mysql_fetch_array($result);
    $time = ( $time == NULL ) ? 0 : $time;
    echo "      <th>" . round($time, 1) . "</th>\n";
    $export[$counter][$shortname] = round($time, 1);
  }

  // Now let's get investigator and submitter stats
  $query  = "SELECT COUNT(DISTINCT Investigator_Name), " .
            "COUNT(DISTINCT Submitter_Name), " .
            "COUNT(DISTINCT HPCAnalysis_ID) " .
            "FROM submissions " .
            "WHERE Cluster_Name IN ( $cluster_comma_list ) " .
            "AND DateTime >= '$start 00:00:00' " .
            "AND DateTime <= '$end 23:59:59' ";
  $result = mysql_query($query)
            or die("Query failed : $query\n" . mysql_error());

  list($inv_count, $sub_count, $job_count) = mysql_fetch_array($result);
  $super_tot_rounded = round( $super_tot, 1 );
  echo <<<HTML
      <th>$super_tot_rounded</th>
      <th>$inv_count</th>
      <th>$sub_count</th>
      <th>$job_count</th>
  </tr>

HTML;

  echo "</tbody>\n" .
       "</table>\n";

  $export[$counter]['Total']         = $super_tot_rounded;
  $export[$counter]['Investigators'] = $inv_count;
  $export[$counter]['Submitters']    = $sub_count;
  $export[$counter]['Jobs']          = $job_count;

  $notes = <<<HTML

  <h4>Notes:</h4>

  <ul><li>Time is in CPU - Hours</li>

      <li>The totals at the bottom of the investigators and submitters columns 
          don&rsquo;t necessarily add up to the total of the numbers in the 
          columns. This is because the total is the number of unique individuals. 
          In other words, some people might have submitted jobs in more than 
          one quarter, but the total would only count them once.</li>
  </ul>


HTML;

  echo $notes;
  //$export[$counter]['Notes'] = strip_tags( $notes );

  $_SESSION['exporttitle'] = $page_title;
  $_SESSION['exportfile']  = $export;

}
?>
