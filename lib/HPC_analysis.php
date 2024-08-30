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
              "WHERE personID = ? ";
    $stmt = mysqli_prepare( $link, $query );
    $stmt->bind_param("i", $_SESSION['id'] );
    $stmt->execute();
    $result = $stmt->get_result()
              or die( "Query failed : $query<br />" . mysqli_error($link));
    list( $investigatorGUID ) = mysqli_fetch_array( $result );
    $result->close();
    $stmt->close();

    // submitterGUID
    $query  = "SELECT personGUID FROM people " .
              "WHERE personID = ? ";
    $stmt = mysqli_prepare( $link, $query );
    $stmt->bind_param("i", $_SESSION['loginID'] );
    $stmt->execute();
    $result = $stmt->get_result()
              or die( "Query failed : $query<br />" . mysqli_error($link));
    list( $submitterGUID ) = mysqli_fetch_array( $result );
    $result->close();
    $stmt->close();
    $guid = uuid();
    $analType = $job['method'];
    if ( isset( $job['analType'] ) )
      $analType = $job['analType'];
    // What about $job['cluster']['shortname'] and $job['cluster']['queue']?
    $query  = "INSERT INTO HPCAnalysisRequest SET " .
              "HPCAnalysisRequestGUID = ?, " .
              "investigatorGUID = ?, " .
              "submitterGUID = ?, " .
              "email = ?, " .
              "experimentID = ?, " .
              "submitTime =  now(), " .
              "clusterName = ?, " .
              "analType = ?, " .
              "method = ? " ;
    $stmt = mysqli_prepare( $link, $query );
    $args = [ $guid, $investigatorGUID, $submitterGUID,
              $job['database']['submitter_email'], $job['job_parameters']['experimentID'], $job['cluster']['name'],
              $analType, $job['method'] ];
    $stmt->bind_param("ssssisss", ...$args );
    $stmt->execute()
          or die( "Query failed : $query<br />" . print_r($args, true) . "<br />"  . $stmt->error);
    $stmt->close();
    echo $stmt->insert_id;
    if (!isset($stmt->insert_id))
    {
      die("INSERT FAILED apparently:" . print_r($stmt->insert_id, true);
      $stmt = mysqli_prepare($link, "SELECT HPCAnalysisRequestID from HPCAnalysisRequest where HPCAnalysisRequestGUID = ?");
      $stmt->bind_param("s", $guid);
      $stmt->execute();
      $result = $stmt->get_result() or die("Query fauled:");
      $result->close();
      $stmt->close();
    }

    // Return the generated ID
    return  $stmt->insert_id ;
  }

  // Function to create the HPCDataset and HPCRequestData table entries
  function HPCDataset( $HPCAnalysisRequestID, $datasets )
  {
    global $link;
    foreach ( $datasets as $dataset_id => $dataset )
    {
      $query  = "INSERT INTO HPCDataset SET " .
                "HPCAnalysisRequestID = ?,      " .
                "editedDataID         = ?, " .
                "simpoints            = ?,    " .
                "band_volume          = ?,  " .
                "radial_grid          = ?,  " .
                "time_grid            = ?,    " .
                "rotor_stretch        = ? " ;
      $args = [ $HPCAnalysisRequestID, $dataset['editedDataID'], $dataset['simpoints'], $dataset['band_volume'],
                $dataset['radial_grid'], $dataset['time_grid'], $dataset['rotor_stretch'] ];
      $stmt = mysqli_prepare( $link, $query );
      $stmt->bind_param("iiidiis", ...$args );
      $stmt->execute()
            or die( "Query failed : $query<br />" . print_r($args, true) . "<br />" . $stmt->error);

      $HPCDatasetID = $stmt->insert_id;
      $stmt->close();


      // Now for the HPCRequestData table
      if ( isset( $dataset['noiseIDs'][ 0 ] ) && $dataset['noiseIDs'][ 0 ] > 0 )
      {
        $query  = "INSERT INTO HPCRequestData SET      " .
            "HPCDatasetID       = ?, " .
            "noiseID             = ?       " ;
        $stmt = mysqli_prepare( $link, $query );
        foreach ( $dataset['noiseIDs'] as $noiseID )
        {
          $args = [ $HPCDatasetID, $noiseID ];
          $stmt->bind_param("ii", ...$args );
          $stmt->execute() or die( "Query failed : $query<br />". print_r($args, true) . "<br />". $stmt->error);
        }
        $stmt->close();
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
    global $link;
    $query  = "INSERT INTO 2DSA_Settings SET " .
              "HPCAnalysisRequestID = ?," .
              "s_min                = ?," .
              "s_max                = ?," .
              "s_resolution         = ?," .
              "ff0_min              = ?," .
              "ff0_max              = ?," .
              "ff0_resolution       = ?," .
              "uniform_grid         = ?," .
              "mc_iterations        = ?," .
              "tinoise_option       = ?," .
              "meniscus_range       = ?," .
              "meniscus_points      = ?," .
              "max_iterations       = ?," .
              "rinoise_option       = ?";
    $stmt = mysqli_prepare( $link, $query );
    $args = [ $HPCAnalysisRequestID, $job_parameters['s_min'], $job_parameters['s_max'],
        $job_parameters['s_grid_points'], $job_parameters['ff0_min'], $job_parameters['ff0_max'],
        $job_parameters['ff0_grid_points'], $job_parameters['uniform_grid'], $job_parameters['mc_iterations'],
        $job_parameters['tinoise_option'], $job_parameters['meniscus_range'], $job_parameters['meniscus_points'],
        $job_parameters['max_iterations'], $job_parameters['rinoise_option'] ];
    $stmt->bind_param("iddiddiiiidiii", ...$args );
    $stmt->execute() or die( "Query failed : $query<br />" . print_r($args, true) . "<br />" . $stmt->error);
    $stmt->close();

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
    global $link;
    $query  = "INSERT INTO 2DSA_CG_Settings SET " .
              "HPCAnalysisRequestID = ?, " .
              "CG_modelID           = ?, " .
              "uniform_grid         = ?, " .
              "mc_iterations        = ?, " .
              "tinoise_option       = ?, " .
              "meniscus_range       = ?, " .
              "meniscus_points      = ?, " .
              "max_iterations       = ?, " .
              "rinoise_option       = ?   ";
    $stmt = mysqli_prepare( $link, $query );
    $args = [ $HPCAnalysisRequestID, $job_parameters['CG_modelID'], $job_parameters['uniform_grid'],
        $job_parameters['mc_iterations'], $job_parameters['tinoise_option'], $job_parameters['meniscus_range'],
        $job_parameters['meniscus_points'], $job_parameters['max_iterations'], $job_parameters['rinoise_option'] ];
    $stmt->bind_param("iiiiidiii", ...$args );

    $stmt->execute() or die( "Query failed : $query<br />" . print_r($args, true) . "<br />" . $stmt->error);
    $stmt->close();

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
    global $link;
    $query  = "INSERT INTO GA_Settings SET " .
              "HPCAnalysisRequestID = ?, " .
              "montecarlo_value     = ?, " .
              "demes_value          = ?, " .
              "genes_value          = ?, " .
              "generations_value    = ?, " .
              "crossover_value      = ?, " .
              "mutation_value       = ?, " .
              "plague_value         = ?, " .
              "elitism_value        = ?, " .
              "migration_value      = ?, " .
              "regularization_value = ?, " .
              "seed_value           = ?, " .
              "conc_threshold       = ?, " .
              "s_grid               = ?, " .
              "k_grid               = ?, " .
              "mutate_sigma         = ?, " .
              "mutate_s             = ?, " .
              "mutate_k             = ?, " .
              "mutate_sk            = ? " ;
    $stmt = mysqli_prepare( $link, $query );
    $args = [ $HPCAnalysisRequestID, $job_parameters['mc_iterations'], $job_parameters['demes'],
        $job_parameters['population'], $job_parameters['generations'], $job_parameters['crossover'],
        $job_parameters['mutation'], $job_parameters['plague'], $job_parameters['elitism'],
        $job_parameters['migration'], $job_parameters['regularization'], $job_parameters['seed'],
        $job_parameters['conc_threshold'], $job_parameters['s_grid'], $job_parameters['k_grid'],
        $job_parameters['mutate_sigma'], $job_parameters['p_mutate_s'], $job_parameters['p_mutate_k'],
        $job_parameters['p_mutate_sk'] ];
    $stmt->bind_param("iiiiiiiiiididiidiii", ...$args );
    $stmt->execute() or die( "Query failed : $query<br />" . print_r($args, true) . "<br />" . $stmt->error);
    $settingsID = $stmt->insert_id;
    $stmt->close();
    $bucket     = $job_parameters['buckets'][1];
    $xtype      = $job_parameters['x-type'];
    $ytype      = $job_parameters['y-type'];

    $xtlo       = 'x_min';
    $xthi       = 'x_max';
    $ytlo       = 'y_min';
    $ythi       = 'y_max';

    // Now save the buckets
    $query  = "INSERT INTO HPCSoluteData SET " .
              "GA_SettingsID = ?, " .
              "s_min         = ?, " .
              "s_max         = ?, " .
              "ff0_min       = ?, " .
              "ff0_max       = ?  " ;
    $stmt = mysqli_prepare( $link, $query );
    for ( $i = 1; $i <= sizeof( $job_parameters['buckets'] ); $i++ )
    {
      $bucket = $job_parameters['buckets'][$i];
      $args = [ $settingsID, $bucket[$xtlo], $bucket[$xthi], $bucket[$ytlo], $bucket[$ythi] ];
      $stmt->bind_param("idddd", ...$args );
      $stmt->execute() or die( "Query failed : $query<br />" . print_r($args, true) . "<br />" . $stmt->error);
    }
    $stmt->close();
  }
}

/*
 * A class that writes the DMGA portion of the data to the DB
 *  Inherits from HPC_analysis
 */
class HPC_DMGA extends HPC_analysis
{
  // Function to create the HPC Analysis DB entries for the DMGA analysis
  protected function HPCJobParameters( $HPCAnalysisRequestID, $job_parameters )
  {
    global $link;
    $query  = "INSERT INTO DMGA_Settings SET " .
              "HPCAnalysisRequestID = ?, " .
              "DC_modelID           = ?, " .
              "mc_iterations        = ?, " .
              "demes                = ?, " .
              "population           = ?, " .
              "generations          = ?, " .
              "mutation             = ?, " .
              "crossover            = ?, " .
              "plague               = ?, " .
              "elitism              = ?, " .
              "migration            = ?, " .
              "p_grid               = ?, " .
              "seed                 = ? " ;
    $stmt = mysqli_prepare( $link, $query );
    $args = [ $HPCAnalysisRequestID, $job_parameters['DC_modelID'], $job_parameters['mc_iterations'],
        $job_parameters['demes'], $job_parameters['population'], $job_parameters['generations'],
        $job_parameters['mutation'], $job_parameters['crossover'], $job_parameters['plague'],
        $job_parameters['elitism'], $job_parameters['migration'], $job_parameters['p_grid'],
        $job_parameters['seed'] ];
    $stmt->bind_param("iiiiiiiiiiiii", ...$args );
    $stmt->execute() or die( "Query failed : $query<br />" . print_r($args, true) . "<br />" . $stmt->error);
    $settingsID = $stmt->insert_id;
    $stmt->close();
  }
}

/*
 * A class that writes the PCSA portion of the data to the DB
 *  Inherits from HPC_analysis
 */
class HPC_PCSA extends HPC_analysis
{
  // Function to create the HPC Analysis DB entries for the PCSA analysis
  protected function HPCJobParameters( $HPCAnalysisRequestID, $job_parameters )
  {
    global $link;
    $query  = "INSERT INTO PCSA_Settings SET " .
              "HPCAnalysisRequestID = $HPCAnalysisRequestID, " .
              "curve_type           = '{$job_parameters['curve_type']}',     " .
              "s_min                = {$job_parameters['x_min']},            " .
              "s_max                = {$job_parameters['x_max']},            " .
              "ff0_min              = {$job_parameters['y_min']},            " .
              "ff0_max              = {$job_parameters['y_max']},            " .
              "vars_count           = {$job_parameters['vars_count']},       " .
              "gfit_iterations      = {$job_parameters['gfit_iterations']},  " .
              "curves_points        = {$job_parameters['curves_points']},    " .
              "thr_deltr_ratio      = {$job_parameters['thr_deltr_ratio']},  " .
              "tikreg_option        = {$job_parameters['tikreg_option']},    " .
              "tikreg_alpha         = {$job_parameters['tikreg_alpha']},     " .
              "mc_iterations        = {$job_parameters['mc_iterations']},    " .
              "tinoise_option       = {$job_parameters['tinoise_option']},   " .
              "rinoise_option       = {$job_parameters['rinoise_option']}    ";
    $stmt = mysqli_prepare( $link, $query );
    $args = [ $HPCAnalysisRequestID, $job_parameters['curve_type'], $job_parameters['x_min'],
        $job_parameters['x_max'], $job_parameters['y_min'], $job_parameters['y_max'],
        $job_parameters['vars_count'], $job_parameters['gfit_iterations'], $job_parameters['curves_points'],
        $job_parameters['thr_deltr_ratio'], $job_parameters['tikreg_option'], $job_parameters['tikreg_alpha'],
        $job_parameters['mc_iterations'], $job_parameters['tinoise_option'], $job_parameters['rinoise_option'] ];
    $stmt->bind_param("isddddiiididiii", ...$args );
    $stmt->execute() or die( "Query failed : $query<br />" . print_r($args, true) . "<br />" . $stmt->error);
  }
}

?>
