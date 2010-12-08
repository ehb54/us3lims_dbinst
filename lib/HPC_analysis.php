<?php
/*
 * HPC_analysis.php
 *
 * A class that writes predefined $_SESSION variables to the
 *  HPCAnalysisRequest and related tables
 *
 * Requires session_start();
 */

include_once( "db.php");

class HPC_analysis
{
  function HPC_analysis() {}

  // Takes a structured array of data created by the payload manager
  function writeDB( $data )
  {
    switch( $data['method'] )
    {
      case '2DSA' :
        return $this->HPCRequest_2DSA( $data );
        break;

      case '2DSA_MW' :
        break;

      case 'GA' :
        break;

      case 'GA_MW' :
        break;

      case 'GA_SC' :
        break;

      default :
        die( "Invalid Selection type: " . __FILE__ . " " . __LINE__ );
        break;

    }
  }

  // Function to create the HPC Analysis DB entries for the 2DSA analysis
  function HPCRequest_2DSA( $job )
  {
    $HPCAnalysisRequestID = $this->HPCAnalysisRequest( $job );

    // Now the specific 2DSA settings
    $job_parameters = array();
    $job_parameters = $job['job_parameters'];
    $query  = "INSERT INTO 2DSA_Settings SET " .
              "HPCAnalysisRequestID = $HPCAnalysisRequestID, " .
              "s_min                = {$job_parameters['s_min']},            " .
              "s_max                = {$job_parameters['s_max']},            " .
              "s_resolution         = {$job_parameters['s_resolution']},     " .
              "ff0_min              = {$job_parameters['ff0_min']},          " .
              "ff0_max              = {$job_parameters['ff0_max']},          " .
              "ff0_resolution       = {$job_parameters['ff0_resolution']},   " .
              "uniform_grid         = {$job_parameters['uniform_grid']},     " .
              "mc_iterations        = {$job_parameters['mc_iterations']}, " .
              "tinoise_option       = {$job_parameters['tinoise_option']},   " .
              "regularization       = {$job_parameters['regularization']},   " .
              "meniscus_range       = {$job_parameters['meniscus_range']},   " .
              "meniscus_points      = {$job_parameters['meniscus_points']},  " .
              "max_iterations       = {$job_parameters['max_iterations']}, " .
              "rinoise_option       = {$job_parameters['rinoise_option']}    ";
    mysql_query( $query )
          or die( "Query failed : $query<br />" . mysql_error());

    // Finally the HPCDataset and HPCRequestData tables
    $this->HPCDataset( $HPCAnalysisRequestID, $job['dataset'] );

    if ( DEBUG )
    {
      // echo "From HPC_analysis_2DSA...\n";
      $this->email_log( $job );
      // $this->debug_out( $job );
      // echo "End of DB update - 2DSA\n";
    }

    // Return the original analysis ID
    return( $HPCAnalysisRequestID );
  }

  // Function to create the main HPCAnalysisRequest table entry
  function HPCAnalysisRequest( $job )
  {
    // Get any remaining information we need
    // investigatorGUID
    $query  = "SELECT personGUID FROM people " .
              "WHERE personID = {$_SESSION['id']} ";
    $result = mysql_query( $query )
              or die( "Query failed : $query<br />" . mysql_error());
    list( $investigatorGUID ) = mysql_fetch_array( $result );

    // submitterGUID
    $query  = "SELECT personGUID FROM people " .
              "WHERE personID = {$_SESSION['loginID']} ";
    $result = mysql_query( $query )
              or die( "Query failed : $query<br />" . mysql_error());
    list( $submitterGUID ) = mysql_fetch_array( $result );

    $query  = "INSERT INTO HPCAnalysisRequest SET                                    " .
              "HPCAnalysisRequestGUID = UUID(),                                      " .
              "investigatorGUID       = '$investigatorGUID',                         " .
              "submitterGUID          = '$submitterGUID',                            " .
              "experimentID           = '{$job['job_parameters']['experimentID']}',  " .
              "submitTime             =  NOW(),                                      " .
              "rotor_stretch          = '{$job['job_parameters']['rotor_stretch']}', " .
              "clusterName            = '{$job['cluster']['name']}',                 " .
              "method                 = '{$job['method']}' ";
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
                "time_grid            = {$dataset['time_grid']}     " ;
      mysql_query( $query )
            or die( "Query failed : $query<br />" . mysql_error());

      // Now for the HPCRequestData table
      $HPCDatasetID = mysql_insert_id();
      if ( isset( $dataset['modelID'] ) && $dataset['modelID'] > 0 )
      {
        $query  = "INSERT INTO HPCRequestData SET      " .
                  "HPCDatasetID       = $HPCDatasetID, " .
                  "dataType           = 'model',       " .
                  "dataID             = {$dataset['modelID']} " ;
        mysql_query( $query )
              or die( "Query failed : $query<br />" . mysql_error());
      }

      if ( isset( $dataset['noiseIDs'][ 0 ] ) && $dataset['noiseIDs'][ 0 ] > 0 )
      {
        foreach ( $dataset['noiseIDs'] as $noiseID )
        {
          $query  = "INSERT INTO HPCRequestData SET      " .
                    "HPCDatasetID       = $HPCDatasetID, " .
                    "dataType           = 'noise',       " .
                    "dataID             = $noiseID       " ;
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
?>
