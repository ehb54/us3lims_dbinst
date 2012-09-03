<?php
/*
 * HPC_analysis.php
 *
 * A class that writes predefined $_SESSION variables to the
 *  HPCAnalysisRequest and related tables
 *
 * Requires session_start();
 */

include_once 'db.php';
include_once 'lib/utility.php';

abstract class HPC_analysis
{
  abstract protected function HPCJobParameters( $HPCAnalysisRequestID, $job_parameters );

  // Takes a structured array of data created by the payload manager
  function writeDB( $data )
  {
    // First the main HPCAnalysisRequest table entry
    $HPCAnalysisRequestID = $this->HPCAnalysisRequest( $data );

    // The job parameters specific to the analysis type
    $this->HPCJobParameters( $HPCAnalysisRequestID, $data['job_parameters']  );

    // Finally the HPCDataset and HPCRequestData tables
    $this->HPCDataset( $HPCAnalysisRequestID, $data['dataset'] );

    if ( DEBUG )
    {
      // echo "From HPC_analysis...\n";
      $this->email_log( $data );
      // $this->debug_out( $data );
      // echo "End of DB update\n";
    }

    // Return the original analysis ID
    return $HPCAnalysisRequestID;
  }

  // Function to create the main HPCAnalysisRequest table entry
  function HPCAnalysisRequest( $job )
  {
    global $link;

    // Get any remaining information we need
    // investigatorGUID
    $query  = "SELECT personGUID FROM people " .
              "WHERE personID = {$_SESSION['id']} ";
    $result = mysql_query( $query, $link )
              or die( "Query failed : $query<br />" . mysql_error());
    list( $investigatorGUID ) = mysql_fetch_array( $result );

    // submitterGUID
    $query  = "SELECT personGUID FROM people " .
              "WHERE personID = {$_SESSION['loginID']} ";
    $result = mysql_query( $query, $link )
              or die( "Query failed : $query<br />" . mysql_error());
    list( $submitterGUID ) = mysql_fetch_array( $result );

    $guid = uuid();
    // What about $job['cluster']['shortname'] and $job['cluster']['queue']?
    $query  = "INSERT INTO HPCAnalysisRequest SET " .
              "HPCAnalysisRequestGUID = '$guid', " .
              "investigatorGUID = '$investigatorGUID', " .
              "submitterGUID = '$submitterGUID', " .
              "email = '{$job['database']['submitter_email']}', " .
              "experimentID = '{$job['job_parameters']['experimentID']}', " .
              "submitTime =  now(), " .
              "clusterName = '{$job['cluster']['name']}', " .
              "method = '{$job['method']}' " ;
    mysql_query( $query )
          or die( "Query failed : $query<br />" . mysql_error());

    // Return the generated ID
    return ( mysql_insert_id() );
  }

  // Function to create the HPCDataset and HPCRequestData table entries
  function HPCDataset( $HPCAnalysisRequestID, $datasets )
  {
    foreach ( $datasets as $dataset_id => $dataset )
    {
      $query  = "INSERT INTO HPCDataset SET " .
                "HPCAnalysisRequestID = $HPCAnalysisRequestID,      " .
                "editedDataID         = {$dataset['editedDataID']}, " .
                "simpoints            = {$dataset['simpoints']},    " .
                "band_volume          = {$dataset['band_volume']},  " .
                "radial_grid          = {$dataset['radial_grid']},  " .
                "time_grid            = {$dataset['time_grid']},    " .
                "rotor_stretch        = '{$dataset['rotor_stretch']}' " ;
      mysql_query( $query )
            or die( "Query failed : $query<br />" . mysql_error());

      $HPCDatasetID = mysql_insert_id();

      // Now for the HPCRequestData table
      if ( isset( $dataset['noiseIDs'][ 0 ] ) && $dataset['noiseIDs'][ 0 ] > 0 )
      {
        foreach ( $dataset['noiseIDs'] as $noiseID )
        {
          $query  = "INSERT INTO HPCRequestData SET      " .
                    "HPCDatasetID       = $HPCDatasetID, " .
                    "noiseID             = $noiseID       " ;
          mysql_query( $query )
                or die( "Query failed : $query<br />" . mysql_error());
        }
      }
    }
  }

  // Some debug functions
  function debug_out( $job )
  {
    echo "<pre>\n";
    echo "Array data\n";
    print_r( $job );
    echo "Session variables: \n";
    print_r( $_SESSION );
    echo "</pre>\n";
  }

  // This function provides email logging without interrupting
  //  the jobs themselves
  function email_log( $job )
  {
    $to = "dzollars@gmail.com";
    $subject = "Logging from {$job['database']['name']} ";
    $message = "job files---\n";
    $message .= $this->__multiarray( $job );
    $message .= "\nSESSION variables---\n";
    $message .= $this->__multiarray( $_SESSION );
    mail($to, $subject, $message);
  }

  // This function parses values in an array, calling itself recursively
  //  as needed for multilevel arrays
  function __multiarray( $job )
  {
    $msg = "";
    static $level = 0;       // to keep track of some indentation

    foreach ($job as $key => $value)
    {
      if (is_array($value))
      {
        $level++;
        $msg .= "$key data:\n";
        $msg .= $this->__multiarray( $value );
      }
      else
      {
        for ($x = 0; $x < $level; $x++)
          $msg .= "  ";
        $msg .= "$key: $value\n";
      }
    }
    $level--;
    return $msg;
  }

}

/*
 * A class that writes the 2DSA portion of the data to the DB
 *  Inherits from HPC_analysis
 */
class HPC_2DSA extends HPC_analysis
{
  // Function to create the HPC Analysis DB entries for the 2DSA analysis
  protected function HPCJobParameters( $HPCAnalysisRequestID, $job_parameters )
  {
    $query  = "INSERT INTO 2DSA_Settings SET " .
              "HPCAnalysisRequestID = $HPCAnalysisRequestID, " .
              "s_min                = {$job_parameters['s_min']},            " .
              "s_max                = {$job_parameters['s_max']},            " .
              "s_resolution         = {$job_parameters['s_grid_points']},    " .
              "ff0_min              = {$job_parameters['ff0_min']},          " .
              "ff0_max              = {$job_parameters['ff0_max']},          " .
              "ff0_resolution       = {$job_parameters['ff0_grid_points']},  " .
              "uniform_grid         = {$job_parameters['uniform_grid']},     " .
              "mc_iterations        = {$job_parameters['mc_iterations']}, " .
              "tinoise_option       = {$job_parameters['tinoise_option']},   " .
              "meniscus_range       = {$job_parameters['meniscus_range']},   " .
              "meniscus_points      = {$job_parameters['meniscus_points']},  " .
              "max_iterations       = {$job_parameters['max_iterations']}, " .
              "rinoise_option       = {$job_parameters['rinoise_option']}    ";

    mysql_query( $query )
          or die( "Query failed : $query<br />" . mysql_error());

  }
}

/*
 * A class that writes the 2DSA_CG portion of the data to the DB
 *  Inherits from HPC_analysis
 */
class HPC_2DSA_CG extends HPC_analysis
{
  // Function to create the HPC Analysis DB entries for the 2DSA_CG analysis
  protected function HPCJobParameters( $HPCAnalysisRequestID, $job_parameters )
  {
    $query  = "INSERT INTO 2DSA_CG_Settings SET " .
              "HPCAnalysisRequestID = $HPCAnalysisRequestID, " .
              "CG_modelID           = {$job_parameters['CG_modelID']},       " .
              "uniform_grid         = {$job_parameters['uniform_grid']},     " .
              "mc_iterations        = {$job_parameters['mc_iterations']},    " .
              "tinoise_option       = {$job_parameters['tinoise_option']},   " .
              "meniscus_range       = {$job_parameters['meniscus_range']},   " .
              "meniscus_points      = {$job_parameters['meniscus_points']},  " .
              "max_iterations       = {$job_parameters['max_iterations']},   " .
              "rinoise_option       = {$job_parameters['rinoise_option']}    ";

    mysql_query( $query )
          or die( "Query failed : $query<br />" . mysql_error());

  }
}

/*
 * A class that writes the GA portion of the data to the DB
 *  Inherits from HPC_analysis
 */
class HPC_GA extends HPC_analysis
{
  // Function to create the HPC Analysis DB entries for the GA analysis
  protected function HPCJobParameters( $HPCAnalysisRequestID, $job_parameters )
  {
    $query  = "INSERT INTO GA_Settings SET " .
              "HPCAnalysisRequestID = $HPCAnalysisRequestID, " .
              "montecarlo_value     = {$job_parameters['mc_iterations']},  " .
              "demes_value          = {$job_parameters['demes']},          " .
              "genes_value          = {$job_parameters['population']},     " .
              "generations_value    = {$job_parameters['generations']},    " .
              "crossover_value      = {$job_parameters['crossover']},      " .
              "mutation_value       = {$job_parameters['mutation']},       " .
              "plague_value         = {$job_parameters['plague']},         " .
              "elitism_value        = {$job_parameters['elitism']},        " .
              "migration_value      = {$job_parameters['migration']},      " .
              "regularization_value = {$job_parameters['regularization']}, " .
              "seed_value           = {$job_parameters['seed']},           " .
              "conc_threshold       = {$job_parameters['conc_threshold']}, " .
              "s_grid               = {$job_parameters['s_grid']},         " .
              "k_grid               = {$job_parameters['k_grid']},         " .
              "mutate_sigma         = {$job_parameters['mutate_sigma']},   " .
              "mutate_s             = {$job_parameters['p_mutate_s']},     " .
              "mutate_k             = {$job_parameters['p_mutate_k']},     " .
              "mutate_sk            = {$job_parameters['p_mutate_sk']}     " ;
    mysql_query( $query )
          or die( "Query failed : $query<br />" . mysql_error());
    $settingsID = mysql_insert_id();

    // Now save the buckets
    for ( $i = 1; $i <= sizeof( $job_parameters['buckets'] ); $i++ )
    {
      $bucket = $job_parameters['buckets'][$i];
      $query  = "INSERT INTO HPCSoluteData SET " .
                "GA_SettingsID = $settingsID, " .
                "s_min         = {$bucket['s_min']}, " .
                "s_max         = {$bucket['s_max']}, " .
                "ff0_min       = {$bucket['f_min']}, " .
                "ff0_max       = {$bucket['f_max']}  " ;
      mysql_query( $query )
            or die( "Query failed : $query<br />" . mysql_error());
    }
  }
}
?>
