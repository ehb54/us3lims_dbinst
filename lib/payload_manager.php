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

    function get_ds_range( $index = 0, $count = 1 )
    {
        $dataset = $this->payload['queue']['payload'];
        $indexi  = $index;
        $dsetds  = $dataset['dataset'];
        $dataset['dataset'] = array();

        for ( $k = 0; $k < $count; $k++ )
        {
           $temp    = array();
           $temp    = $dsetds[$indexi];
           $dataset['dataset'][$k] = $temp;
           $indexi++;
        }

        $dataset['datasetCount'] = $count;

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
      global $link;
      $timelast  = 0;
      $rawDataID = $_SESSION['request'][$dataset_id]['rawDataID'];
      
      // we need the stretch function from the rotor table
      $rotor_stretch = "0 0";
      $cellname      = "1";
      $channel       = "A";
      $chanindex     = 0;
      $query  = "SELECT coeff1, coeff2, filename, rawData.experimentID " .
                "FROM rawData, experiment, rotorCalibration " .
                "WHERE rawData.rawDataID =  ? " .
                "AND rawData.experimentID = experiment.experimentID " .
                "AND experiment.rotorCalibrationID = rotorCalibration.rotorCalibrationID ";
      $stmt   = mysqli_prepare( $link, $query );
      $stmt->bind_param( "i", $rawDataID );
      $stmt->execute();
      $result = $stmt->get_result() or die( "Query failed : $query<br />$rawDataID<br />" . $stmt->error );
      if ( $result->num_rows > 0 )
      {
        list( $coeff1, $coeff2, $filename, $experID ) = mysqli_fetch_array( $result );   // should be 1
        $rotor_stretch = "$coeff1 $coeff2";
        list( $run, $dtype, $cellname, $channel, $waveln, $ftype ) = explode( ".", $filename );
        $chanindex = strpos( "ABCDEFGH", $channel ) / 2;
      }
      $result->close();
      $stmt->close();

      // We may need speedsteps information
      $speedsteps = array();
      $query  = "SELECT speedstepID, speedstep.experimentID, scans, durationhrs, durationmins, " .
                "delayhrs, delaymins, rotorspeed, acceleration, accelerflag, " .
                " w2tfirst, w2tlast, timefirst, timelast " .
                "FROM rawData, speedstep " .
                "WHERE rawData.rawDataID = ? " .
                "AND rawData.experimentID = speedstep.experimentID ";
      $stmt  = mysqli_prepare( $link, $query );
      $stmt->bind_param( "i", $rawDataID );
      $stmt->execute();
      $result = $stmt->get_result() or die( "Query failed : $query<br />$rawDataID<br />" . $stmt->error );
      while ( list( $stepID, $expID, $scans, $durhrs, $durmins, $dlyhrs, $dlymins,
                    $speed, $accel, $accflag, $w2tf, $w2tl, $timef, $timel ) = mysqli_fetch_array( $result ) )
      {
        $speedstep['stepID']  = $stepID;
        $speedstep['expID']   = $expID;
        $speedstep['scans']   = $scans;
        $speedstep['durhrs']  = $durhrs;
        $speedstep['durmins'] = $durmins;
        $speedstep['dlyhrs']  = $dlyhrs;
        $speedstep['dlymins'] = $dlymins;
        $speedstep['speed']   = $speed;
        $speedstep['accel']   = $accel;
        $speedstep['accflag'] = $accflag;
        $speedstep['w2tf']    = $w2tf;
        $speedstep['w2tl']    = $w2tl;
        $speedstep['timef']   = $timef;
        $speedstep['timel']   = $timel;

        $speedsteps[] = $speedstep;

        if ( $timel > $timelast )
          $timelast     = $timel;
      }
      $result->close();
      $stmt->close();

      // We need the centerpiece bottom
      $centerpiece_bottom      = 7.3;
      $centerpiece_shape       = 'standard';
      $centerpiece_angle       = 2.5;
      $centerpiece_pathlength  = 1.2;
      $centerpiece_width       = 0.0;
      $query  = "SELECT shape, bottom, angle, pathLength, width " .
                "FROM rawData, cell, abstractCenterpiece " .
                "WHERE rawData.rawDataID = ? " .
                "AND rawData.experimentID = cell.experimentID " .
                "AND cell.name = ? " .
                "AND cell.abstractCenterpieceID = abstractCenterpiece.abstractCenterpieceID ";
      $args   = array( $rawDataID, $cellname );
      $stmt  = mysqli_prepare( $link, $query );
      $stmt->bind_param( "is", $rawDataID, $cellname );
      $stmt->execute();
      $result = $stmt->get_result()
                or die( "Query failed : $query<br />" . print_r( $args, true ) . "<br />" . $stmt->error );
      if ( mysqli_num_rows ( $result ) > 0 )
        list( $centerpiece_shape, $centerpiece_bottom, $centerpiece_angle, $centerpiece_pathlength, $centerpiece_width )
          = mysqli_fetch_array( $result );      // should be 1
       if ( strpos( $centerpiece_bottom, ":" ) !== false )
       { // Parse multiple bottoms and get the one for the channel set
          $bottoms            = explode( ":", $centerpiece_bottom );
          $centerpiece_bottom = $bottoms[ $chanindex ];
       }
      $result->close();
      $stmt->close();
      // We also need some information about the analytes in this cell
      $analytes = array();
      // language=MariaDB
      $query  = "SELECT type, vbar, molecularWeight, amount " .
                "FROM rawData, solutionAnalyte, analyte " .
                "WHERE rawData.rawDataID = ? " .
                "AND rawData.solutionID = solutionAnalyte.solutionID " .
                "AND solutionAnalyte.analyteID = analyte.analyteID ";
      $stmt  = mysqli_prepare( $link, $query );
      $stmt->bind_param( "i", $rawDataID );
      $stmt->execute();
      $result = $stmt->get_result()
                or die( "Query failed : $query<br />". "$rawDataID<br />" . $stmt->error );
      while ( list( $type, $vbar, $mw, $amount ) = mysqli_fetch_array( $result ) )
      {
        $analyte['type']   = $type;
        $analyte['vbar']   = $vbar;
        $analyte['mw']     = $mw;
        $analyte['amount'] = $amount;

        $analytes[] = $analyte;
      }
      $result->close();
      $stmt->close();
      // Finally, some buffer information
      $density     = 0.0;
      $viscosity   = 0.0;
      $compress    = 0.0;
      $manual      = 0;
      $smanual     = 0;
      $description = '';
      // language=MariaDB
      $query  = "SELECT viscosity, density, description, compressibility, manual " .
                "FROM rawData, solutionBuffer, buffer " .
                "WHERE rawData.rawDataID = ? " .
                "AND rawData.solutionID = solutionBuffer.solutionID " .
                "AND solutionBuffer.bufferID = buffer.bufferID ";
      $stmt  = mysqli_prepare( $link, $query );
      $stmt->bind_param( "i", $rawDataID );
      $stmt->execute();
      $result = $stmt->get_result()
                or die( "Query failed : $query<br />$rawDataID<br />" . $stmt->error );
      if ( mysqli_num_rows ( $result ) > 0 )
        list( $viscosity, $density, $description, $compress, $manual ) = mysqli_fetch_array( $result ); // should be 1


      $result->close();
      $stmt->close();

      // Turn on 'manual' flag where '  [M]' is present in buffer description
      str_replace( '  [M]', '', $description, $smanual );
      $manual      = ( $smanual != 0 ) ? $smanual : $manual;


      // Save the simulation parameters looked up in the db
      $params['rotor_stretch'] = $rotor_stretch;
      $params['centerpiece_bottom'] = $centerpiece_bottom;
      $params['centerpiece_shape']  = $centerpiece_shape;
      $params['centerpiece_angle']  = $centerpiece_angle;
      $params['centerpiece_pathlength']  = $centerpiece_pathlength;
      $params['centerpiece_width']  = $centerpiece_width;
      $params['density']      = $density;
      $params['viscosity']    = $viscosity;
      $params['compress']     = $compress;
      $params['manual' ]      = $manual;
      $params['analytes']     = $analytes;
      $params['speedsteps']   = $speedsteps;
      $params['rawDataID']    = $rawDataID;
      $params['experimentID'] = $experID;
      $params['timelast']     = $timelast;

      $_SESSION['request'][$dataset_id]['experimentID'] = $experID;

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

    // Function to parse a model XML in order to return total concentration
    function total_concentration( $xml )
    {
      $tot_conc = 0.0;
      $name     = "";

      $parser   = new XMLReader();
      $parser->xml( $xml );

      while( $parser->read() )
      {
        $type       = $parser->nodeType;

        if ( $type == XMLReader::ELEMENT )
        {
          $name       = $parser->name;

          if ( $name == "analyte" )
          {
            $parser->moveToAttribute( 'signal' );
            $concen     = $parser->value;
            $tot_conc  += $concen;
          }
        }
      }
      $parser->close();

      return $tot_conc;
    }

    // Function to read a 2DSA-IT or 2DSA-CG-IT model for an edit
    //   and return the model's total concentration
    function model_concentration( $editedDataID )
    {
      global $link;
      $tot_conc = 0.0;
      $modelXML = "";
      $query    = "SELECT xml FROM model " .
                  "WHERE editedDataID = ? " .
                  "AND description LIKE '%2DSA%IT%' " .
                  "AND description NOT LIKE '%-GL-%' " .
                  "ORDER BY modelID DESC";
      $stmt     = mysqli_prepare( $link, $query );
      $stmt->bind_param( "i", $editedDataID );
      $stmt->execute();
      $result   = $stmt->get_result()
            or die( "Query failed : $query<br/>$editedDataID<br/>\n" . $stmt->error );

      if ( $result->num_rows > 0 )
      {
        list( $modelXML ) = mysqli_fetch_array( $result );

        if ( $modelXML != "" )
        {
          $tot_conc = $this->total_concentration( $modelXML );
        }
      }
      else
      {
        $tot_conc = -1;   // Mark no 2DSA-IT found
      }
      $result->close();
      $stmt->close();
      return $tot_conc;
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
    global $udpport, $ipaddr, $ipa_ext;
    global $ipad_a, $ipae_a;

    // These will be done every time
    $parameters                 = array();
    $this->getDBParams( $dataset_id, $parameters );   // DB parameters

    // A lot of this only gets posted the first time through
    if ( $dataset_id == 0 )
    {
      $method               = $this->analysisType();
      $udp['ip']            = $ipaddr;
      $this->add( 'method', $method );
      $analType             = $method;
      if ( $num_datasets > 1  &&
           $_SESSION['separate_datasets'] == 0 )
         $analType            .= '-GL';


      $udp                  = array();
      $udp['udpport']       = $udpport;
      $clusname             = $_SESSION['cluster']['shortname'];
      $gwhostid             = $_SESSION['gwhostid'];
      if ( preg_match( '/alamo/', $clusname )  &&
           preg_match( '/alamo/', $gwhostid ) )
      {  // Use alternate IP addresses for UDP if host,cluster both 'alamo'
         $ipaddr   = $ipad_a;
         $ipa_ext  = $ipae_a;
      }
      $udp['ip']            = $ipaddr;
      $udp['ip_ext']        = $ipa_ext;
      $this->add( 'server', $udp );

      $this->add( 'directory', $_SESSION['request'][$dataset_id]['path'] );
      $this->add( 'datasetCount', $num_datasets );

      $database             = array();
      $database['name']     = $dbname;
      $database['host']     = $dbhost;
      $database['user_email']      = $_SESSION['email'];
      $database['submitter_email'] = $_SESSION['submitter_email'];
      $database['user_id']         = $_SESSION['user_id'];
      $database['gwhostid']        = $_SESSION['gwhostid'];
      $this->add( 'database', $database );

      $job_parameters       = array();
      $s_min_in             = $_POST['s_value_min'];
      $s_max_in             = $_POST['s_value_max'];
      $k_min_in             = $_POST['ff0_min'];
      $k_max_in             = $_POST['ff0_max'];

      // Compute 'uniform_grid' (grid repetitions)

      $gpoints_s            = $_POST['s_grid_points'];
      $gpoints_k            = $_POST['ff0_grid_points'];
      if ( $gpoints_s < 10 )
         $gpoints_s = 10;
      if ( $gpoints_s > 2100 )
         $gpoints_s = 2100;
      if ( $gpoints_k < 10 )
         $gpoints_k = 10;
      if ( $gpoints_k > 2100 )
         $gpoints_k = 2100;
      // Accumulate a list of grid repetition evenly dividing into S points
      $greps_s   = array();
      $count_grs = 0;
      for ( $jreps = 2; $jreps < 41; $jreps++ )
      {
         $testp     = (int)( $gpoints_s / $jreps ) * $jreps;
         if ( $testp == $gpoints_s )
         {  // Save a repetition that divides evenly into S grid points
            $greps_s[] = $jreps;
            $count_grs++;
         }
      }
      // Find the repetitions and K grid points that work best
      $kdiff     = 99999;
      $kreps     = $greps_s[ 0 ];
      $kgridp_k  = $gpoints_k;
      for ( $jrx = 0; $jrx < $count_grs; $jrx++ )
      {  // Examine each grid repetition from the S list
         $jreps     = $greps_s[ $jrx ];
         $subpts_s  = (int)( $gpoints_s / $jreps );
         $subpts_k  = (int)( $gpoints_k / $jreps );
         $jgridp_k  = $subpts_k * $jreps;
         $nsubgs    = $jreps * $jreps;
         $subgsz    = $subpts_s * $subpts_k;
         $jdiff     = $nsubgs - $subgsz;
         if ( $jdiff < 0 )
            $jdiff     = 0 - $jdiff;
         if ( $jdiff < $kdiff )
         {  // Count and size of subgrid are closely matched
            $kdiff     = $jdiff;
            $kgridp_k  = $jgridp_k;
            $kreps     = $jreps;
         }
      }

      $gridreps  = $kreps;
      $gpoints_k = $kgridp_k;
      $subpts_s  = (int)( $gpoints_s / $gridreps );
      $subpts_k  = (int)( $gpoints_k / $gridreps );
      $gpoints_s = $subpts_s * $gridreps;
      $gpoints_k = $subpts_k * $gridreps;
      $subg_size = $subpts_s * $subpts_k;
      while( $subg_size > 200  ||  $gridreps < 2 )
      {
         $gridreps++;
         $subpts_s  = (int)( $gpoints_s / $gridreps );
         $subpts_k  = (int)( $gpoints_k / $gridreps );
         $subg_size = $subpts_s * $subpts_k;
      }
      while( $subg_size < 40  ||  $gridreps > 160 )
      {
         $gridreps--;
         $subpts_s  = (int)( $gpoints_s / $gridreps );
         $subpts_k  = (int)( $gpoints_k / $gridreps );
         $subg_size = $subpts_s * $subpts_k;
      }
      $gpoints_s = $subpts_s * $gridreps;
      $gpoints_k = $subpts_k * $gridreps;

      $job_parameters['s_grid_points']    = $gpoints_s;
      $job_parameters['ff0_grid_points']  = $gpoints_k;
      $job_parameters['uniform_grid']     = $gridreps;
      $job_parameters['s_min']            = $s_min_in;
      $job_parameters['s_max']            = $s_max_in;
      $job_parameters['ff0_min']          = $k_min_in;
      $job_parameters['ff0_max']          = $k_max_in;

      $job_parameters['mc_iterations']    = $_POST['mc_iterations'];

      if ( isset( $_POST['req_mgroupcount'] ) )
      {
         if ( $job_parameters['mc_iterations'] > 1 )
            $job_parameters['req_mgroupcount'] = $_POST['req_mgroupcount'];
         else if ( $num_datasets > 1 )
            $job_parameters['req_mgroupcount'] = $_POST['req_mgroupcount'];
         else
            $job_parameters['req_mgroupcount'] = 1;
      }

      else
         $job_parameters['req_mgroupcount'] = 1;

      $job_parameters['tinoise_option']   = $_POST['tinoise_option'];
      $job_parameters['rinoise_option']   = $_POST['rinoise_option'];

      $fit_mb_select                      = $_POST['fit_mb_select'];
      $job_parameters['fit_mb_select']    = $fit_mb_select;
      if ( $fit_mb_select > 0 )
      {
         $job_parameters['meniscus_range']   = $_POST['meniscus_range'];
         $job_parameters['meniscus_points']  = $_POST['meniscus_points'];
      }
      else
      {
         $job_parameters['meniscus_range']   = 0.0;
         $job_parameters['meniscus_points']  = 1;
      }
//$job_parameters['MR_posted'] = isset( $_POST['meniscus_range'] ) ? 1 : 0;
//$job_parameters['MR_postval']=$_POST['meniscus_range'];

      $job_parameters['max_iterations']   = ( $_POST['iterations_option'] == 1 )
                                          ? $_POST['max_iterations'] : 1;
      $job_parameters['debug_timings']    = ( isset( $_POST['debug_timings'] ) &&
                                                     $_POST['debug_timings']   == 'on' )
                                          ? 1 : 0;
      $job_parameters['debug_level']      = $_POST['debug_level-value'];
      $job_parameters['debug_text']       = $_POST['debug_text-value'];
      $job_parameters['experimentID']     = $parameters['experimentID'];
      $job_parameters['timelast']         = $parameters['timelast'];
      $this->add( 'job_parameters', $job_parameters );
      if ( $fit_mb_select == 1 )
         $analType            .= '-FM';
      else if ( $fit_mb_select == 2 )
         $analType            .= '-FB';
      else if ( $fit_mb_select == 3 )
         $analType            .= '-FMB';
      if ( $job_parameters['max_iterations' ] > 1 )
         $analType            .= '-IT';
      if ( $job_parameters['mc_iterations'  ] > 1 )
         $analType            .= '-MC';
      $this->add( 'analType', $analType );

      $dataset = array();
        $dataset[ 0 ]['files']      = array();   // This will be done later
        $dataset[ 0 ]['parameters'] = array();
    }

    // These will be done every time
    $centerpiece_shape = $parameters['centerpiece_shape'];

    // Create new elements for this dataset
    //?? $parameters                 = $dataset['parameters'];
    $editedDataID   = $_SESSION['request'][$dataset_id]['editedDataID'];
    $parameters['rawDataID']    = $_SESSION['request'][$dataset_id]['rawDataID'];
    $parameters['auc']          = $_SESSION['request'][$dataset_id]['filename'];
    $parameters['editedDataID'] = $editedDataID;
    $parameters['edit']         = $_SESSION['request'][$dataset_id]['editFilename'];
    $parameters['noiseIDs']     = array();
    $parameters['noiseIDs']     = $_SESSION['request'][$dataset_id]['noiseIDs'];
    
    $parameters['simpoints']    = $_POST['simpoints-value'];
    $parameters['band_volume']  = ( $centerpiece_shape == 'band forming' )
                                ? $_POST['band_volume-value']
                                : 0.0;
    $parameters['radial_grid']  = $_POST['radial_grid'];
    $parameters['time_grid']    = $_POST['time_grid'];
    $parameters['total_concentration']
                                = $this->model_concentration( $editedDataID );

    // Get arrays with multiple dataset data
    $dataset                    = $this->get('dataset');
    // Add new datasets
    $dataset[$dataset_id]       = $parameters;
    $this->add( 'dataset', $dataset );

$this->show( 0, "Payload manager 2DSA\n" );
  }

}

/*
 * A place to encapsulate the 2DSA payload data for custom grid
 * Inherits from payload_manager.php
 *
 */
class Payload_2DSA_CG extends Payload_manager
{
  public function analysisType()
  {
    return '2DSA_CG';
  }

  // Function to save all the data on the screen
  function acquirePostedData( $dataset_id, $num_datasets )
  {
    // From config.php
    global $dbname, $dbhost;
    global $udpport, $ipaddr, $ipa_ext;
    global $ipad_a, $ipae_a;

    // These will be done every time
    $parameters                 = array();
    $this->getDBParams( $dataset_id, $parameters );   // DB parameters

    // A lot of this only gets posted the first time through
    if ( $dataset_id == 0 )
    {
      $method               = $this->analysisType();
      $this->add( 'method', $method );
      $analType             = $method;
      if ( $num_datasets > 1  &&
           $_SESSION['separate_datasets'] == 0 )
         $analType            .= '-GL';

      $udp                  = array();
      $udp['udpport']       = $udpport;
      $clusname             = $_SESSION['cluster']['shortname'];
      $gwhostid             = $_SESSION['gwhostid'];
      if ( preg_match( '/alamo/', $clusname )  &&
           preg_match( '/alamo/', $gwhostid ) )
      {  // Use alternate IP addresses for UDP if host,cluster both 'alamo'
         $ipaddr   = $ipad_a;
         $ipa_ext  = $ipae_a;
      }
      $udp['ip']            = $ipaddr;
      $udp['ip_ext']        = $ipa_ext;
      $this->add( 'server', $udp );

      $this->add( 'directory', $_SESSION['request'][$dataset_id]['path'] );
      $this->add( 'datasetCount', $num_datasets );

      $database             = array();
      $database['name']     = $dbname;
      $database['host']     = $dbhost;
      $database['user_email'] = $_SESSION['email'];
      $database['submitter_email'] = $_SESSION['submitter_email'];
      $database['user_id']         = $_SESSION['user_id'];
      $database['gwhostid']        = $_SESSION['gwhostid'];
      $this->add( 'database', $database );

      $job_parameters                     = array();
      $job_parameters['CG_modelID']       = $_POST['CG_modelID'];
      $job_parameters['uniform_grid']     = $_POST['uniform_grid'];
      $job_parameters['mc_iterations']    = $_POST['mc_iterations'];

      if ( isset( $_POST['req_mgroupcount'] ) )
      {
         if ( $job_parameters['mc_iterations'] > 1 )
            $job_parameters['req_mgroupcount'] = $_POST['req_mgroupcount'];
         else if ( $num_datasets > 1 )
            $job_parameters['req_mgroupcount'] = $_POST['req_mgroupcount'];
         else
            $job_parameters['req_mgroupcount'] = 1;
      }

      else
         $job_parameters['req_mgroupcount'] = 1;

      $job_parameters['tinoise_option']   = $_POST['tinoise_option'];
      $job_parameters['rinoise_option']   = $_POST['rinoise_option'];

      $fit_mb_select                      = $_POST['fit_mb_select'];
      $job_parameters['fit_mb_select']    = $fit_mb_select;
      if ( $fit_mb_select > 0 )
      {
         $job_parameters['meniscus_range']   = $_POST['meniscus_range'];
         $job_parameters['meniscus_points']  = $_POST['meniscus_points'];
      }
      else
      {
         $job_parameters['meniscus_range']   = 0.0;
         $job_parameters['meniscus_points']  = 1;
      }
//$job_parameters['MR_posted']  = isset( $_POST['meniscus_range'] ) ? 1 : 0;
//$job_parameters['MR_postval'] = $_POST['meniscus_range'];

      $job_parameters['max_iterations']   = ( $_POST['iterations_option'] == 1 )
                                          ? $_POST['max_iterations'] : 1;
      $job_parameters['debug_timings']    = ( isset( $_POST['debug_timings'] ) &&
                                                     $_POST['debug_timings']   == 'on' )
                                          ? 1 : 0;
      $job_parameters['debug_level']      = $_POST['debug_level-value'];
      $job_parameters['debug_text']       = $_POST['debug_text-value'];
      $job_parameters['experimentID']     = $parameters['experimentID'];
      $this->add( 'job_parameters', $job_parameters );

      $dataset = array();
        $dataset[ 0 ]['files']      = array();   // This will be done later
        $dataset[ 0 ]['parameters'] = array();
      if ( $fit_mb_select == 1  ||
           $fit_mb_select == 3 )
         $analType            .= '-FM';
      if ( $fit_mb_select == 2 )
         $analType            .= '-FB';
      if ( $job_parameters['max_iterations' ] > 1 )
         $analType            .= '-IT';
      if ( $job_parameters['mc_iterations'  ] > 1 )
         $analType            .= '-MC';
      $this->add( 'analType', $analType );
    }

    // These will be done every time
    $centerpiece_shape = $parameters['centerpiece_shape'];

    // Create new elements for this dataset
    //?? $parameters                 = $dataset['parameters'];
    $editedDataID   = $_SESSION['request'][$dataset_id]['editedDataID'];
    $parameters['rawDataID']    = $_SESSION['request'][$dataset_id]['rawDataID'];
    $parameters['auc']          = $_SESSION['request'][$dataset_id]['filename'];
    $parameters['editedDataID'] = $editedDataID;
    $parameters['edit']         = $_SESSION['request'][$dataset_id]['editFilename'];
    $parameters['noiseIDs']     = array();
    $parameters['noiseIDs']     = $_SESSION['request'][$dataset_id]['noiseIDs'];
    
    $parameters['simpoints']    = $_POST['simpoints-value'];
    $parameters['band_volume']  = ( $centerpiece_shape == 'band forming' )
                                ? $_POST['band_volume-value']
                                : 0.0;
    $parameters['radial_grid']  = $_POST['radial_grid'];
    $parameters['time_grid']    = $_POST['time_grid'];
    $parameters['total_concentration']
                                = $this->model_concentration( $editedDataID );

    // Get arrays with multiple dataset data
    $dataset                    = $this->get('dataset');
    // Add new datasets
    $dataset[$dataset_id]       = $parameters;
    $this->add( 'dataset', $dataset );

$this->show( 0, "Payload manager 2DSA-CG\n" );
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
    global $udpport, $ipaddr, $ipa_ext;
    global $ipad_a, $ipae_a;

    // These will be done every time
    $parameters                 = array();
    $this->getDBParams( $dataset_id, $parameters );   // DB parameters

    // These items only get posted the first time
    if ( $dataset_id == 0 )
    {
      $method               = $this->analysisType();
      $udp['ip']            = $ipaddr;
      $this->add( 'method', $method );
      $analType             = $method;
      $analType             = $method;
      if ( $num_datasets > 1  &&
           $_SESSION['separate_datasets'] == 0 )
         $analType            .= '-GL';

      $udp                  = array();
      $udp['udpport']       = $udpport;
      $clusname             = $_SESSION['cluster']['shortname'];
      $gwhostid             = $_SESSION['gwhostid'];
      if ( preg_match( '/alamo/', $clusname )  &&
           preg_match( '/alamo/', $gwhostid ) )
      {  // Use alternate IP addresses for UDP if host,cluster both 'alamo'
         $ipaddr   = $ipad_a;
         $ipa_ext  = $ipae_a;
      }
      $udp['ip']            = $ipaddr;
      $udp['ip_ext']        = $ipa_ext;
      $udp['ip_ext_aa']     = $ipae_a;
      $this->add( 'server', $udp );

      $this->add( 'directory', $_SESSION['request'][$dataset_id]['path'] );
      $this->add( 'datasetCount', $num_datasets );

      $database             = array();
      $database['name']     = $dbname;
      $database['host']     = $dbhost;
      $database['user_email'] = $_SESSION['email'];
      $database['submitter_email'] = $_SESSION['submitter_email'];
      $database['user_id']         = $_SESSION['user_id'];
      $database['gwhostid']        = $_SESSION['gwhostid'];
      $this->add( 'database', $database );

      $job_parameters                     = array();
      $job_parameters['mc_iterations']    = $_POST['mc_iterations'];
      // Adjust demes so procs (demes+1) is a multiple of 4 (or demes is "1")
      $demes                              = $_POST['demes-value'];
      $demes                = (int)( ( $demes + 1 ) / 4 ) * 4 - 1;
      $demes                = ( $demes < 4 ) ? 1 : $demes;
      $job_parameters['demes']            = $demes;

      if ( isset( $_POST['req_mgroupcount'] ) )
      {
         if ( $job_parameters['mc_iterations'] > 1 )
            $job_parameters['req_mgroupcount'] = $_POST['req_mgroupcount'];
         else if ( $num_datasets > 1 )
            $job_parameters['req_mgroupcount'] = $_POST['req_mgroupcount'];
         else
            $job_parameters['req_mgroupcount'] = 1;
      }

      else
         $job_parameters['req_mgroupcount'] = 1;

      $job_parameters['population']       = $_POST['genes-value'];
      $job_parameters['generations']      = $_POST['generations-value'];
      $job_parameters['crossover']        = $_POST['crossover-value'];
      $job_parameters['mutation']         = $_POST['mutation-value'];
      $job_parameters['plague']           = $_POST['plague-value'];
      $job_parameters['elitism']          = $_POST['elitism-value'];
      $job_parameters['migration']        = $_POST['migration-value'];

      // This one is in %, so it needs to be divided by 100
      $job_parameters['regularization']   = $_POST['regularization-value'] / 100.0;

      $job_parameters['seed']             = $_POST['seed-value'];
      $job_parameters['conc_threshold']   = $_POST['conc_threshold-value'];
      $job_parameters['s_grid']           = $_POST['s_grid-value'];
      $job_parameters['k_grid']           = $_POST['k_grid-value'];
      $job_parameters['mutate_sigma']     = $_POST['mutate_sigma-value'];
      $job_parameters['p_mutate_s']       = $_POST['mutate_s-value'];
      $job_parameters['p_mutate_k']       = $_POST['mutate_k-value'];
      $job_parameters['p_mutate_sk']      = $_POST['mutate_sk-value'];
      $job_parameters['debug_timings']    = ( isset( $_POST['debug_timings'] ) &&
                                                     $_POST['debug_timings']   == 'on' )
                                          ? 1 : 0;
      $job_parameters['debug_level']      = $_POST['debug_level-value'];
      $job_parameters['debug_text']       = $_POST['debug_text-value'];
      $job_parameters['bucket_fixed']     = $_POST['z-fixed'];
      $job_parameters['x-type']           = $_POST['x-type'];
      $job_parameters['y-type']           = $_POST['y-type'];
      $job_parameters['z-type']           = $_POST['z-type'];
      $job_parameters['experimentID']     = $parameters['experimentID'];
      // buckets
      $this->add( 'job_parameters', $job_parameters );

      $dataset = array();
        $dataset[ 0 ]['files']      = array();   // This will be done later
        $dataset[ 0 ]['parameters'] = array();
      if ( $job_parameters['mc_iterations'  ] > 1 )
         $analType            .= '-MC';
      $this->add( 'analType', $analType );
    }

    // These will be done every time
    $centerpiece_shape = $parameters['centerpiece_shape'];

    // Create new elements for this dataset
    //?? $parameters                 = $dataset['parameters'];
    $editedDataID   = $_SESSION['request'][$dataset_id]['editedDataID'];
    $parameters['rawDataID']    = $_SESSION['request'][$dataset_id]['rawDataID'];
    $parameters['auc']          = $_SESSION['request'][$dataset_id]['filename'];
    $parameters['editedDataID'] = $editedDataID;
    $parameters['edit']         = $_SESSION['request'][$dataset_id]['editFilename'];
    $parameters['noiseIDs']     = array();
    $parameters['noiseIDs']     = $_SESSION['request'][$dataset_id]['noiseIDs'];
    
    $parameters['simpoints']    = $_POST['simpoints-value'];
    $parameters['band_volume']  = ( $centerpiece_shape == 'band forming' )
                                ? $_POST['band_volume-value']
                                : 0.0;
    $parameters['radial_grid']  = $_POST['radial_grid'];
    $parameters['time_grid']    = $_POST['time_grid'];
    $parameters['total_concentration']
                                = $this->model_concentration( $editedDataID );

    // Get arrays with multiple dataset data
    $dataset                    = $this->get('dataset');
    // Add new datasets
    $dataset[$dataset_id]       = $parameters;
    $this->add( 'dataset', $dataset );

  }

  // Function to get the posted bucket data on the screen
  function getBuckets( $count, &$buckets )
  {
    $xtype = $_POST['x-type'] ?? 's';
    $ytype = $_POST['y-type'] ?? 'ff0';
    $ztype = $_POST['z-type'] ?? 'vbar';
    $xtlo  = 'x_min';
    $xthi  = 'x_max';
    $ytlo  = 'y_min';
    $ythi  = 'y_max';
    $buckets = array();
    
    for ( $i = 1; $i <= $count; $i++ )
    {
      $buckets[$i][$xtlo] = $_POST[$i.'_xmin'];
      $buckets[$i][$xthi] = $_POST[$i.'_xmax'];
      $buckets[$i][$ytlo] = $_POST[$i.'_ymin'];
      $buckets[$i][$ythi] = $_POST[$i.'_ymax'];
    }

    $parameters['bucket_fixed'] = $_POST['z-fixed'] ?? '0.0';
    $parameters['x-type'] = $xtype;
    $parameters['y-type'] = $ytype;
    $parameters['z-type'] = $ztype;
  }
}

/*
 * A place to encapsulate the DMGA payload data
 * Inherits from payload_manager.php
 *
 */
class Payload_DMGA extends Payload_manager
{
  public function analysisType()
  {
    return 'DMGA';
  }

  // Function to save all the data on the screen
  function acquirePostedData( $dataset_id, $num_datasets )
  {
    // From config.php
    global $dbname, $dbhost;
    global $udpport, $ipaddr, $ipa_ext;
    global $ipad_a, $ipae_a;

    // These will be done every time
    $parameters                 = array();
    $this->getDBParams( $dataset_id, $parameters );   // DB parameters

    // These items only get posted the first time
    if ( $dataset_id == 0 )
    {
      $method               = $this->analysisType();
      $this->add( 'method', $method );
      $analType             = $method;

      $udp                  = array();
      $udp['udpport']       = $udpport;
      $clusname             = $_SESSION['cluster']['shortname'];
      $gwhostid             = $_SESSION['gwhostid'];
      if ( preg_match( '/alamo/', $clusname )  &&
           preg_match( '/alamo/', $gwhostid ) )
      {  // Use alternate IP addresses for UDP if host,cluster both 'alamo'
         $ipaddr   = $ipad_a;
         $ipa_ext  = $ipae_a;
      }
      $udp['ip']            = $ipaddr;
      $udp['ip_ext']        = $ipa_ext;
      $udp['ip_ext_aa']     = $ipae_a;
      $this->add( 'server', $udp );

      $this->add( 'directory', $_SESSION['request'][$dataset_id]['path'] );
      $this->add( 'datasetCount', $num_datasets );

      $database             = array();
      $database['name']     = $dbname;
      $database['host']     = $dbhost;
      $database['user_email'] = $_SESSION['email'];
      $database['submitter_email'] = $_SESSION['submitter_email'];
      $database['user_id']         = $_SESSION['user_id'];
      $database['gwhostid']        = $_SESSION['gwhostid'];
      $this->add( 'database', $database );

      $job_parameters                     = array();
      $job_parameters['DC_modelID']       = $_POST['DC_modelID'];
      $job_parameters['mc_iterations']    = $_POST['mc_iterations'];
      $job_parameters['demes']            = $_POST['demes-value'];

      if ( isset( $_POST['req_mgroupcount'] ) )
      {
         if ( $job_parameters['mc_iterations'] > 1 )
            $job_parameters['req_mgroupcount'] = $_POST['req_mgroupcount'];
         else if ( $num_datasets > 1 )
            $job_parameters['req_mgroupcount'] = $_POST['req_mgroupcount'];
         else
            $job_parameters['req_mgroupcount'] = 1;
      }

      else
         $job_parameters['req_mgroupcount'] = 1;

      $job_parameters['population']       = $_POST['genes-value'];
      $job_parameters['generations']      = $_POST['generations-value'];
      $job_parameters['crossover']        = $_POST['crossover-value'];
      $job_parameters['mutation']         = $_POST['mutation-value'];
      $job_parameters['plague']           = $_POST['plague-value'];
      $job_parameters['elitism']          = $_POST['elitism-value'];
      $job_parameters['migration']        = $_POST['migration-value'];

      // This one is in %, so it needs to be divided by 100
      $job_parameters['regularization']   = $_POST['regularization-value'] / 100.0;

      $job_parameters['seed']             = $_POST['seed-value'];
      $job_parameters['conc_threshold']   = $_POST['conc_threshold-value'];
      $job_parameters['p_grid']           = $_POST['p_grid-value'];
      $job_parameters['mutate_sigma']     = $_POST['mutate_sigma-value'];
      $job_parameters['minimize_opt']     = $_POST['minimize_opt-value'];
      $job_parameters['debug_timings']    = ( isset( $_POST['debug_timings'] ) &&
                                                     $_POST['debug_timings']   == 'on' )
                                          ? 1 : 0;
      $job_parameters['debug_level']      = $_POST['debug_level-value'];
      $job_parameters['debug_text']       = $_POST['debug_text-value'];
      $job_parameters['experimentID']     = $parameters['experimentID'];
      $this->add( 'job_parameters', $job_parameters );

      $dataset = array();
        $dataset[ 0 ]['files']      = array();   // This will be done later
        $dataset[ 0 ]['parameters'] = array();
      if ( $job_parameters['mc_iterations'  ] > 1 )
         $analType            .= '-MC';
      $this->add( 'analType', $analType );
    }

    // These will be done every time
    $centerpiece_shape = $parameters['centerpiece_shape'];

    // Create new elements for this dataset
    //?? $parameters                 = $dataset['parameters'];
    $editedDataID   = $_SESSION['request'][$dataset_id]['editedDataID'];
    $parameters['rawDataID']    = $_SESSION['request'][$dataset_id]['rawDataID'];
    $parameters['auc']          = $_SESSION['request'][$dataset_id]['filename'];
    $parameters['editedDataID'] = $editedDataID;
    $parameters['edit']         = $_SESSION['request'][$dataset_id]['editFilename'];
    $parameters['noiseIDs']     = array();
    $parameters['noiseIDs']     = $_SESSION['request'][$dataset_id]['noiseIDs'];
    
    $parameters['simpoints']    = $_POST['simpoints-value'];
    $parameters['band_volume']  = ( $centerpiece_shape == 'band forming' )
                                ? $_POST['band_volume-value']
                                : 0.0;
    $parameters['radial_grid']  = $_POST['radial_grid'];
    $parameters['time_grid']    = $_POST['time_grid'];
    $parameters['total_concentration']
                                = $this->model_concentration( $editedDataID );

    // Get arrays with multiple dataset data
    $dataset                    = $this->get('dataset');
    // Add new datasets
    $dataset[$dataset_id]       = $parameters;
    $this->add( 'dataset', $dataset );

  }
}

/*
 * A place to encapsulate the PCSA payload data
 * Inherits from payload_manager.php
 *
 */
class Payload_PCSA extends Payload_manager
{
  public function analysisType()
  {
    return 'PCSA';
  }

  // Function to save all the data on the screen
  function acquirePostedData( $dataset_id, $num_datasets )
  {
    // From config.php
    global $dbname, $dbhost;
    global $udpport, $ipaddr, $ipa_ext;
    global $ipad_a, $ipae_a;

    // These will be done every time
    $parameters                 = array();
    $this->getDBParams( $dataset_id, $parameters );   // DB parameters

    // A lot of this only gets posted the first time through
    if ( $dataset_id == 0 )
    {
      $method               = $this->analysisType();
      $this->add( 'method', $method );
      $analType             = $method;
      if ( $num_datasets > 1  &&
           $_SESSION['separate_datasets'] == 0 )
         $analType            .= '-GL';

      $udp                  = array();
      $udp['udpport']       = $udpport;
      $clusname             = $_SESSION['cluster']['shortname'];
      $gwhostid             = $_SESSION['gwhostid'];
      if ( preg_match( '/alamo/', $clusname )  &&
           preg_match( '/alamo/', $gwhostid ) )
      {  // Use alternate IP addresses for UDP if host,cluster both 'alamo'
         $ipaddr   = $ipad_a;
         $ipa_ext  = $ipae_a;
      }
      $udp['ip']            = $ipaddr;
      $udp['ip_ext']        = $ipa_ext;
      $this->add( 'server', $udp );

      $this->add( 'directory', $_SESSION['request'][$dataset_id]['path'] );
      $this->add( 'datasetCount', $num_datasets );

      $database             = array();
      $database['name']     = $dbname;
      $database['host']     = $dbhost;
      $database['user_email'] = $_SESSION['email'];
      $database['submitter_email'] = $_SESSION['submitter_email'];
      $database['user_id']         = $_SESSION['user_id'];
      $database['gwhostid']        = $_SESSION['gwhostid'];
      $this->add( 'database', $database );

      $job_parameters                     = array();
      $job_parameters['curve_type']       = $_POST['curve_type'];
      $job_parameters['solute_type']      = $_POST['solute_type'];
      $job_parameters['x_min']            = $_POST['x_min'];
      $job_parameters['x_max']            = $_POST['x_max'];
      $job_parameters['y_min']            = $_POST['y_min'];
      $job_parameters['y_max']            = $_POST['y_max'];
      $job_parameters['z_value']          = $_POST['z_value'];

      if ( $job_parameters['curve_type'] != 'HL' )
         $job_parameters['vars_count']       = $_POST['vars_count'];
      else
         $job_parameters['vars_count']       = $_POST['hl_vars_count'];

      $job_parameters['gfit_iterations']  = $_POST['gfit_iterations'];
      $job_parameters['thr_deltr_ratio']  = $_POST['thr_deltr_ratio'];
      $job_parameters['curves_points']    = $_POST['curves_points'];
      $job_parameters['tikreg_option']    = $_POST['tikreg_option'];
      $job_parameters['tikreg_alpha']     = $_POST['tikreg_alpha'];

      $job_parameters['mc_iterations']    = $_POST['mc_iterations'];

      if ( isset( $_POST['req_mgroupcount'] ) )
      {
         if ( $job_parameters['mc_iterations'] > 1 )
            $job_parameters['req_mgroupcount'] = $_POST['req_mgroupcount'];
         else if ( $num_datasets > 1 )
            $job_parameters['req_mgroupcount'] = $_POST['req_mgroupcount'];
         else
            $job_parameters['req_mgroupcount'] = 1;
      }

      else
         $job_parameters['req_mgroupcount'] = 1;

      $job_parameters['tinoise_option']   = $_POST['tinoise_option'];
      $job_parameters['rinoise_option']   = $_POST['rinoise_option'];
      $job_parameters['debug_timings']    = ( isset( $_POST['debug_timings'] ) &&
                                                     $_POST['debug_timings']   == 'on' )
                                          ? 1 : 0;
      $job_parameters['debug_level']      = $_POST['debug_level-value'];
      $job_parameters['debug_text']       = $_POST['debug_text-value'];
      $job_parameters['experimentID']     = $parameters['experimentID'];
      $this->add( 'job_parameters', $job_parameters );

      $dataset = array();
        $dataset[ 0 ]['files']      = array();   // This will be done later
        $dataset[ 0 ]['parameters'] = array();

      $subType              = '-IS';
      $curve_type           = $job_parameters['curve_type'];
      if ( $curve_type != 'All' )
         $subType              = '-' . $curve_type;
      $analType            .= $subType;
      if ( $job_parameters['tikreg_option'  ] > 0 )
         $analType            .= '-TR';
      if ( $job_parameters['mc_iterations'  ] > 1 )
         $analType            .= '-MC';
      $this->add( 'analType', $analType );
    }

    // These will be done every time
    $centerpiece_shape = $parameters['centerpiece_shape'];

    // Create new elements for this dataset
    //?? $parameters                 = $dataset['parameters'];
    $editedDataID   = $_SESSION['request'][$dataset_id]['editedDataID'];
    $parameters['rawDataID']    = $_SESSION['request'][$dataset_id]['rawDataID'];
    $parameters['auc']          = $_SESSION['request'][$dataset_id]['filename'];
    $parameters['editedDataID'] = $editedDataID;
    $parameters['edit']         = $_SESSION['request'][$dataset_id]['editFilename'];
    $parameters['noiseIDs']     = array();
    $parameters['noiseIDs']     = $_SESSION['request'][$dataset_id]['noiseIDs'];
    
    $parameters['simpoints']    = $_POST['simpoints-value'];
    $parameters['band_volume']  = ( $centerpiece_shape == 'band forming' )
                                ? $_POST['band_volume-value']
                                : 0.0;
    $parameters['radial_grid']  = $_POST['radial_grid'];
    $parameters['time_grid']    = $_POST['time_grid'];
    $parameters['total_concentration']
                                = $this->model_concentration( $editedDataID );

    // Get arrays with multiple dataset data
    $dataset                    = $this->get('dataset');
    // Add new datasets
    $dataset[$dataset_id]       = $parameters;
    $this->add( 'dataset', $dataset );

$this->show( 0, "Payload manager PCSA\n" );
  }

}

?>
