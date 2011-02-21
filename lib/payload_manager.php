<?php
/*
 * A class to encapsulate the payload data
 *
 */
include_once 'config.php';
include_once 'db.php';

// Base class, to be extended by different analysis methods
abstract class Payload_manager 
{
    var $payload;

    abstract public function analysisType();

    function __construct( $session ) 
    {
        $this->payload = $session;
    }

    function add( $key, $val ) 
    {
        $this->payload['queue']['payload'][$key] = $val;
    }

    function get( $key = null ) 
    {
        if ( ! isset( $this->payload['queue']['payload'] ) )
            return false;

        if ( $key == null ) 
        {
            return $this->payload['queue']['payload'];
        }

        else if ( ! array_key_exists( $key, $this->payload['queue']['payload'] ) )
            return false;

        else
        {
            return $this->payload['queue']['payload'][$key];
        }
    }

    function get_dataset( $index = 0 )
    {
        $dataset = $this->payload['queue']['payload'];

        // Save only one dataset
        $temp    = array();
        $temp    = $dataset['dataset'][$index];
        $dataset['dataset'] = array();
        $dataset['dataset'][0] = $temp;

        // Change the count to reflect a single dataset
        $dataset['datasetCount'] = 1;

        return $dataset;
    }

    function remove( $key ) 
    {
        unset($this->payload['queue']['payload'][$key]);
    }

    function clear() 
    {
        unset( $this->payload['queue']['payload'] );
        unset( $_SESSION['payload_mgr'] );
    }

    function save()
    {
        unset( $_SESSION['payload_mgr'] );

        if ( ! isset( $this->payload['queue']['payload'] ) )
          return;

        foreach ( $this->payload['queue']['payload'] as $key => $value )
          $_SESSION['payload_mgr'][$key] = $value;
    }

    function restore()
    {
        if ( isset($_SESSION['payload_mgr']) )
        {
            foreach( $_SESSION['payload_mgr'] as $key => $value )
              $this->add( $key, $value );
        }

        unset( $_SESSION['payload_mgr'] );
    }

    // Function to look up certain info from the db
    function getDBParams( $dataset_id, &$params )
    {
      $rawDataID = $_SESSION['request'][$dataset_id]['rawDataID'];
      
      // we need the stretch function from the rotor table
      $rotor_stretch = "0 0";
      $query  = "SELECT coeff1, coeff2 " .
                "FROM rawData, experiment, rotorCalibration " .
                "WHERE rawData.rawDataID = $rawDataID " .
                "AND rawData.experimentID = experiment.experimentID " .
                "AND experiment.rotorCalibrationID = rotorCalibration.rotorCalibrationID ";
      $result = mysql_query( $query )
                or die( "Query failed : $query<br />" . mysql_error());
      if ( mysql_num_rows( $result ) > 0 )
      {
        list( $coeff1, $coeff2 ) = mysql_fetch_array( $result );      // should be 1
        $rotor_stretch = "$coeff1 $coeff2";
      }
      
      // We need the centerpiece bottom
      $centerpiece_bottom = 7.3;
      $centerpiece_shape  = 'standard';
      $query  = "SELECT shape, bottom " .
                "FROM rawData, cell, abstractCenterpiece " .
                "WHERE rawData.rawDataID = $rawDataID " .
                "AND rawData.experimentID = cell.experimentID " .
                "AND cell.abstractCenterpieceID = abstractCenterpiece.abstractCenterpieceID ";
      $result = mysql_query( $query )
                or die( "Query failed : $query<br />" . mysql_error());
      if ( mysql_num_rows ( $result ) > 0 )
        list( $centerpiece_shape, $centerpiece_bottom ) = mysql_fetch_array( $result );      // should be 1
      
      // We also need some information about the solution in this cell
      $vbar20 = 0.0;
      $query  = "SELECT commonVbar20 " .
                "FROM rawData, solution " .
                "WHERE rawData.rawDataID = $rawDataID " .
                "AND rawData.solutionID = solution.solutionID ";
      $result = mysql_query( $query )
                or die( "Query failed : $query<br />" . mysql_error());
      if ( mysql_num_rows( $result ) > 0 )
        list( $vbar20 ) = mysql_fetch_array( $result );      // should be 1
      
      // Finally, some buffer information
      $density = 0.0;
      $viscosity = 0.0;
      $query  = "SELECT viscosity, density " .
                "FROM rawData, solutionBuffer, buffer " .
                "WHERE rawData.rawDataID = $rawDataID " .
                "AND rawData.solutionID = solutionBuffer.solutionID " .
                "AND solutionBuffer.bufferID = buffer.bufferID ";
      $result = mysql_query( $query )
                or die( "Query failed : $query<br />" . mysql_error());
      if ( mysql_num_rows ( $result ) > 0 )
        list( $viscosity, $density ) = mysql_fetch_array( $result );      // should be 1

      // Save the simulation parameters looked up in the db
      $params['rotor_stretch'] = $rotor_stretch;
      $params['centerpiece_bottom'] = $centerpiece_bottom;
      $params['centerpiece_shape']  = $centerpiece_shape;
      $params['vbar20']       = $vbar20;
      $params['density']      = $density;
      $params['viscosity']    = $viscosity;

    }

    // Function to display some debugging info
    function show( $HPCAnalysisRequestID = 0, $filenames = array() )
    {
      if ( DEBUG )
      {
        $now = date( "m/d/Y h:i:s a" );
        echo "<pre>SessionID = " . session_id() . "\n";
        echo "From {$_SERVER['PHP_SELF']}\n";
        echo "Time: $now\n</pre>\n";
        echo "<pre>\n" .
             "HPCAnalysisRequestID = $HPCAnalysisRequestID\n\n" .
             "Payload... "; 
        if ( $_SESSION['separate_datasets'] )
        {
          $dataset_count = $this->get( 'datasetCount' );
          for ( $i = 0; $i < $dataset_count; $i++ )
          {
            echo "Payload dataset $i ...\n";
            print_r( $this->get_dataset( $i ) );
          }
        }
  
        else
          print_r( $this->get() );
  
        echo "Session variables...";
        print_r( $_SESSION );
        echo "</pre>\n";

        if ( count( $filenames ) > 0 )
        {
            echo "<pre>Filenames:\n";
            foreach ( $filenames as $filename )
              echo "* $filename\n";
            echo "</pre>\n";
        }
      }
    }
}

/*
 * A place to encapsulate the 2DSA payload data
 * Inherits from payload_manager.php
 *
 */
class Payload_2DSA extends Payload_manager
{
  public function analysisType()
  {
    return '2DSA';
  }

  // Function to save all the data on the screen
  function acquirePostedData( $dataset_id, $num_datasets )
  {
    // From config.php
    global $dbname, $dbhost;
    global $udpport, $ipaddr;

    // A lot of this only gets posted the first time through
    if ( $dataset_id == 0 )
    {
      $this->add( 'method', $this->analysisType() );

      $udp                  = array();
      $udp['udpport']       = $udpport;
      $udp['ip']            = $ipaddr;
      $this->add( 'server', $udp );

      $this->add( 'directory', $_SESSION['request'][$dataset_id]['path'] );
      $this->add( 'datasetCount', $num_datasets );

      $database             = array();
      $database['name']     = $dbname;
      $database['host']     = $dbhost;
      $database['user_email'] = $_SESSION['email'];
      $database['submitter_email'] = $_SESSION['submitter_email'];
      $this->add( 'database', $database );

      $job_parameters                     = array();
      $job_parameters['s_min']            = $_POST['s_value_min'];
      $job_parameters['s_max']            = $_POST['s_value_max'];
      $job_parameters['s_resolution']     = $_POST['s_resolution'];
      $job_parameters['ff0_min']          = $_POST['ff0_min'];
      $job_parameters['ff0_max']          = $_POST['ff0_max'];
      $job_parameters['ff0_resolution']   = $_POST['ff0_resolution'];
      $job_parameters['uniform_grid']     = $_POST['uniform_grid'];
      $job_parameters['mc_iterations']    = $_POST['mc_iterations'];
      $job_parameters['tinoise_option']   = $_POST['tinoise_option'];
      $job_parameters['regularization']   = $_POST['regularization'];
      $job_parameters['meniscus_range']   = ( $_POST['meniscus_option'] == 1 )
                                          ? $_POST['meniscus_range'] : 0.0;
      $job_parameters['meniscus_points']  = ( $_POST['meniscus_option'] == 1 )
                                          ? $_POST['meniscus_points'] : 1;
      $job_parameters['max_iterations']   = ( $_POST['iterations_option'] == 1 )
                                          ? $_POST['max_iterations'] : 1;
      $job_parameters['rinoise_option']   = $_POST['rinoise_option'];
      $job_parameters['experimentID']     = $_SESSION['experimentID'];
      $this->add( 'job_parameters', $job_parameters );

      $dataset = array();
        $dataset[ 0 ]['files']      = array();   // This will be done later
        $dataset[ 0 ]['parameters'] = array();
    }

    // These will be done every time
    $parameters                 = array();
    $this->getDBParams( $dataset_id, $parameters );   // DB parameters
    $centerpiece_shape = $parameters['centerpiece_shape'];

    // Create new elements for this dataset
    //?? $parameters                 = $dataset['parameters'];
    $parameters['rawDataID']    = $_SESSION['request'][$dataset_id]['rawDataID'];
    $parameters['auc']          = $_SESSION['request'][$dataset_id]['filename'];
    $parameters['editedDataID'] = $_SESSION['request'][$dataset_id]['editedDataID'];
    $parameters['edit']         = $_SESSION['request'][$dataset_id]['editFilename'];
  //  $parameters['modelID']      = $_SESSION['request'][$dataset_id]['modelID'];
    $parameters['noiseIDs']     = array();
    $parameters['noiseIDs']     = $_SESSION['request'][$dataset_id]['noiseIDs'];
    
    $parameters['simpoints']    = $_POST['simpoints-value'];
    $parameters['band_volume']  = ( $centerpiece_shape == 'band_forming' )
                                ? $_POST['band_volume-value']
                                : 0.0;
    $parameters['radial_grid']  = $_POST['radial_grid'];
    $parameters['time_grid']    = $_POST['time_grid'];

    // Get arrays with multiple dataset data
    $dataset                    = $this->get('dataset');
    // Add new datasets
    $dataset[$dataset_id]       = $parameters;
    $this->add( 'dataset', $dataset );

  }

}

/*
 * A place to encapsulate the GA payload data
 * Inherits from payload_manager.php
 *
 */
class Payload_GA extends Payload_manager
{
  public function analysisType()
  {
    return 'GA';
  }

  // Function to save all the data on the screen
  function acquirePostedData( $dataset_id, $num_datasets )
  {
    // From config.php
    global $dbname, $dbhost;
    global $udpport, $ipaddr;

    // These items only get posted the first time
    if ( $dataset_id == 0 )
    {
      $this->add( 'method', $this->analysisType() );

      $udp                  = array();
      $udp['udpport']       = $udpport;
      $udp['ip']            = $ipaddr;
      $this->add( 'server', $udp );

      $this->add( 'directory', $_SESSION['request'][$dataset_id]['path'] );
      $this->add( 'datasetCount', $num_datasets );

      $database             = array();
      $database['name']     = $dbname;
      $database['host']     = $dbhost;
      $database['user_email'] = $_SESSION['email'];
      $database['submitter_email'] = $_SESSION['submitter_email'];
      $this->add( 'database', $database );

      $job_parameters                     = array();
      $job_parameters['mc_iterations']    = $_POST['mc_iterations'];
      $job_parameters['demes']            = $_POST['demes-value'];
      $job_parameters['population']       = $_POST['genes-value'];
      $job_parameters['generations']      = $_POST['generations-value'];
      $job_parameters['crossover']        = $_POST['crossover-value'];
      $job_parameters['mutation']         = $_POST['mutation-value'];
      $job_parameters['plague']           = $_POST['plague-value'];
      $job_parameters['elitism']          = $_POST['elitism-value'];
      $job_parameters['migration']        = $_POST['migration-value'];
      $job_parameters['regularization']   = $_POST['regularization-value'];
      $job_parameters['seed']             = $_POST['seed-value'];
      $job_parameters['conc_threshold']   = $_POST['conc_threshold-value'];
      $job_parameters['s_grid']           = $_POST['s_grid-value'];
      $job_parameters['k_grid']           = $_POST['k_grid-value'];
      $job_parameters['mutate_sigma']     = $_POST['mutate_sigma-value'];
      $job_parameters['p_mutate_s']       = $_POST['mutate_s_value'];
      $job_parameters['p_mutate_k']       = $_POST['mutate_k_value'];
      $job_parameters['p_mutate_sk']      = $_POST['mutate_sk_value'];
      $job_parameters['experimentID']     = $_SESSION['experimentID'];
      // buckets
      $this->add( 'job_parameters', $job_parameters );

      $dataset = array();
        $dataset[ 0 ]['files']      = array();   // This will be done later
        $dataset[ 0 ]['parameters'] = array();
    }

    // These will be done every time
    $parameters                 = array();
    $this->getDBParams( $dataset_id, $parameters );   // DB parameters
    $centerpiece_shape = $parameters['centerpiece_shape'];

    // Create new elements for this dataset
    //?? $parameters                 = $dataset['parameters'];
    $parameters['rawDataID']    = $_SESSION['request'][$dataset_id]['rawDataID'];
    $parameters['auc']          = $_SESSION['request'][$dataset_id]['filename'];
    $parameters['editedDataID'] = $_SESSION['request'][$dataset_id]['editedDataID'];
    $parameters['edit']         = $_SESSION['request'][$dataset_id]['editFilename'];
  //  $parameters['modelID']      = $_SESSION['request'][$dataset_id]['modelID'];
    $parameters['noiseIDs']     = array();
    $parameters['noiseIDs']     = $_SESSION['request'][$dataset_id]['noiseIDs'];
    
    $parameters['simpoints']    = $_POST['simpoints-value'];
    $parameters['band_volume']  = ( $centerpiece_shape == 'band_forming' )
                                ? $_POST['band_volume-value']
                                : 0.0;
    $parameters['radial_grid']  = $_POST['radial_grid'];
    $parameters['time_grid']    = $_POST['time_grid'];

    // Get arrays with multiple dataset data
    $dataset                    = $this->get('dataset');
    // Add new datasets
    $dataset[$dataset_id]       = $parameters;
    $this->add( 'dataset', $dataset );

  }

}
?>
