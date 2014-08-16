<?php
/*
 * file_writer.php
 *
 * A class that writes predefined files to the
 *  disk
 *
 * Requires session_start();
 */

include_once( "db.php");

abstract class File_writer
{
  // Function to write job parameters for a particular analysis type
  abstract protected function writeJobParameters( $xml, $jobParameters );

  // Takes a structured array of data created by the payload manager
  function write( $job, $HPCAnalysisRequestID )
  {
    // Get the rest of the information we need
    $query  = "SELECT HPCAnalysisRequestGUID FROM HPCAnalysisRequest " .
              "WHERE HPCAnalysisRequestID = $HPCAnalysisRequestID ";
    $result = mysql_query( $query )
              or die( "Query failed : $query<br />" . mysql_error());
    list( $HPCAnalysisRequestGUID ) = mysql_fetch_array( $result );

    // First create a directory with a unique name
    if ( ! ( $current_dir = $this->create_dir( $HPCAnalysisRequestGUID ) ) )
      return false;

    // Write the auc, edit profile, model and noise files
    // Returns all the filenames used
    if ( ! ( $filenames = $this->write_support_files( $job, $current_dir ) ) )
      return false;

    // Now write xml file
    $xml_filename = sprintf( "hpcrequest-%s-%s-%05d.xml",
                             $job['database']['host'],
                             $job['database']['name'],
                             $HPCAnalysisRequestID );

    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent( true );
    $xml->startDocument( '1.0', 'UTF-8', 'yes' );
    $xml->startDTD( 'US_JobSubmit' );
    $xml->endDTD();
    $xml->startElement( 'US_JobSubmit' );
      $xml->writeAttribute( 'method', $job['method'] );
      $xml->writeAttribute( 'version', '1.0' );

      $xml->startElement( 'job' );
        $xml->startElement( 'cluster' );
          $xml->writeAttribute( 'name', $job['cluster']['name'] );
          $xml->writeAttribute( 'shortname', $job['cluster']['shortname'] );
          $xml->writeAttribute( 'queue', $job['cluster']['queue'] );
        $xml->endElement(); // cluster
        $xml->startElement( 'udp' );
          $xml->writeAttribute( 'port', $job['server']['udpport'] );
          $xml->writeAttribute( 'server', $job['server']['ip'] );
        $xml->endElement(); // udp
        $xml->startElement( 'directory' );
          $xml->writeAttribute( 'name', $current_dir );
        $xml->endElement(); // directory
        $xml->startElement( 'datasetCount' );
          $xml->writeAttribute( 'value', $job['datasetCount'] );
        $xml->endElement(); // datasetCount
        $xml->startElement( 'request' );
          $xml->writeAttribute( 'id', $HPCAnalysisRequestID );
          $xml->writeAttribute( 'guid', $HPCAnalysisRequestGUID );
        $xml->endElement(); // request
        $xml->startElement( 'database' );
          $xml->startElement( 'name' );
            $xml->writeAttribute( 'value', $job['database']['name'] );
          $xml->endElement(); // name
          $xml->startElement( 'host' );
            $xml->writeAttribute( 'value', $job['database']['host'] );
          $xml->endElement(); // host
          $xml->startElement( 'user' );
            $xml->writeAttribute( 'email', $job['database']['user_email'] );
          $xml->endElement(); // user
          $xml->startElement( 'submitter' );
            $xml->writeAttribute( 'email', $job['database']['submitter_email'] );
          $xml->endElement(); // submitter
        $xml->endElement(); // database

        // Now we break out and write the job parameters specific to this method
        $this->writeJobParameters( $xml, $job['job_parameters'] );

      $xml->endElement(); // job

      $xml->writeComment( 'the dataset section is repeated for each dataset' );

      foreach ( $job['dataset'] as $dataset_id => $dataset )
      {
        $xml->startElement( 'dataset' );
          $xml->startElement( 'files' );
            $xml->startElement( 'auc' );
              $xml->writeAttribute( 'filename', $filenames[$dataset_id]['auc'] );
            $xml->endElement(); // auc
            $xml->startElement( 'edit' );
              $xml->writeAttribute( 'filename', $filenames[$dataset_id]['edit'] );
            $xml->endElement(); // edit
/*
            $xml->startElement( 'model' );
              $xml->writeAttribute( 'filename', $filenames[$dataset_id]['model'] );
            $xml->endElement(); // model
*/
            if ( isset( $filenames[$dataset_id]['noise'] ) )
            {
              foreach ( $filenames[$dataset_id]['noise'] as $noiseFile )
              {
                $xml->startElement( 'noise' );
                  $xml->writeAttribute( 'filename', $noiseFile );
                $xml->endElement(); // noise
              }
            }
          $xml->endElement(); // files
          $xml->startElement( 'parameters' );
            $xml->startElement( 'simpoints' );
              $xml->writeAttribute( 'value', $dataset['simpoints'] );
            $xml->endElement(); // simpoints
            $xml->startElement( 'band_volume' );
              $xml->writeAttribute( 'value', $dataset['band_volume'] );
            $xml->endElement(); // band_volume
            $xml->startElement( 'radial_grid' );
              $xml->writeAttribute( 'value', $dataset['radial_grid'] );
            $xml->endElement(); // radial_grid
            $xml->startElement( 'time_grid' );
              $xml->writeAttribute( 'value', $dataset['time_grid'] );
            $xml->endElement(); // time_grid
            $xml->startElement( 'rotor_stretch' );
              $xml->writeAttribute( 'value', $dataset['rotor_stretch'] );
            $xml->endElement(); // rotor_stretch
            $xml->startElement( 'centerpiece_shape' );
              $xml->writeAttribute( 'value', $dataset['centerpiece_shape'] );
            $xml->endElement(); // centerpiece_shape
            $xml->startElement( 'centerpiece_bottom' );
              $xml->writeAttribute( 'value', $dataset['centerpiece_bottom'] );
            if (! isset($dataset['centerpiece_angle']) )
              $dataset['centerpiece_angle'] = 0.0;
            if (! isset($dataset['centerpiece_width']) )
              $dataset['centerpiece_width'] = 0.0;
            $xml->endElement(); // centerpiece_bottom
            $xml->startElement( 'centerpiece_angle' );
              $xml->writeAttribute( 'value', $dataset['centerpiece_angle'] );
            $xml->endElement(); // centerpiece_angle
            $xml->startElement( 'centerpiece_pathlength' );
              $xml->writeAttribute( 'value', $dataset['centerpiece_pathlength'] );
            $xml->endElement(); // centerpiece_pathlength
            $xml->startElement( 'centerpiece_width' );
              $xml->writeAttribute( 'value', $dataset['centerpiece_width'] );
            $xml->endElement(); // centerpiece_width

            foreach( $dataset['speedsteps'] as $speedstep )
            {
              $xml->startElement( 'speedstep' );
                $xml->writeAttribute( 'stepID',        $speedstep['stepID']  );
                $xml->writeAttribute( 'rotorspeed',    $speedstep['speed']   );
                $xml->writeAttribute( 'scans',         $speedstep['scans']   );
                $xml->writeAttribute( 'timefirst',     $speedstep['timef']   );
                $xml->writeAttribute( 'timelast',      $speedstep['timel']   );
                $xml->writeAttribute( 'w2tfirst',      $speedstep['w2tf']    );
                $xml->writeAttribute( 'w2tlast',       $speedstep['w2tl']    );
                $xml->writeAttribute( 'duration_hrs',  $speedstep['durhrs']  );
                $xml->writeAttribute( 'duration_mins', $speedstep['durmins'] );
                $xml->writeAttribute( 'delay_hrs',     $speedstep['dlyhrs']  );
                $xml->writeAttribute( 'delay_mins',    $speedstep['dlymins'] );
                $xml->writeAttribute( 'acceleration',  $speedstep['accel']   );
                $xml->writeAttribute( 'accelerflag',   $speedstep['accflag'] );
                $xml->writeAttribute( 'expID',         $speedstep['expID']   );
              $xml->endElement(); // speedstep
            }

            $xml->startElement( 'solution' );
              $xml->startElement( 'buffer' );
                $xml->writeAttribute( 'density', $dataset['density'] );
                $xml->writeAttribute( 'viscosity', $dataset['viscosity'] );
                $xml->writeAttribute( 'manual', $dataset['manual'] );
              $xml->endElement(); // buffer
              foreach( $dataset['analytes'] as $analyte )
              {
                $xml->startElement( 'analyte' );
                  $xml->writeAttribute( 'vbar20', $analyte['vbar']   );
                  $xml->writeAttribute( 'amount', $analyte['amount'] );
                  $xml->writeAttribute( 'mw',     $analyte['mw']     );
                  $xml->writeAttribute( 'type',   $analyte['type']   );
                $xml->endElement(); // analyte
              }
        
            $xml->endElement(); //solution
          $xml->endElement(); // parameters
        $xml->endElement(); // dataset
      }

    $xml->endElement(); // US_JobSubmit
    $xml->endDocument();

    $fp = fopen( $current_dir . $xml_filename, 'w');
    fwrite( $fp, $xml->outputMemory() );
    fclose( $fp );

    // Update database with xml file content
    $xml_data = file_get_contents( $current_dir . $xml_filename );

    // Create tar file including all files
    $files = array();
    foreach ( $filenames as $filename )
    {
      $files[] = $filename['auc'];
      $files[] = $filename['edit'];
      // $files[] = $filename['model'];

      if ( isset( $filename['noise'] ) )
      {
        foreach ( $filename['noise'] as $noiseFile )
          $files[] = $noiseFile;
      }

      if ( isset( $filename['CG_model'] ) )
         $files[] = $filename['CG_model'];

      if ( isset( $filename['DC_model'] ) )
         $files[] = $filename['DC_model'];

      $files[] = $xml_filename;
    }

    $save_cwd = getcwd();         // So we can come back to the current 
                                  // working directory later

    chdir( $current_dir );

    $fileList = implode( " ", $files );
    $tarFilename = sprintf( "hpcinput-%s-%s-%05d.tar",
                             $job['database']['host'],
                             $job['database']['name'],
                             $HPCAnalysisRequestID );
    shell_exec( "/bin/tar -cf $tarFilename " . $fileList );

    chdir( $save_cwd );

    return( $current_dir . $xml_filename );
  }

  // Function to write the edit profile, model and noise files
  // If successful, returns a data structure with all the filenames in it
  function write_support_files( $job, $dir )
  {
    $filenames = array();
    foreach ( $job['dataset'] as $dataset_id => $dataset )
    {
      // auc files
      $query  = "SELECT data FROM rawData " .
                "WHERE rawDataID = {$dataset['rawDataID']} ";
      $result = mysql_query( $query )
                or die( "Query failed : $query<br />" . mysql_error());
      list( $aucdata ) = mysql_fetch_array( $result );
      if ( ! $this->create_file( $dataset['auc'], $dir, $aucdata ) )
        return false;
      $filenames[$dataset_id]['auc'] = $dataset['auc'];

      // edit profile
      $query  = "SELECT data FROM editedData " .
                "WHERE editedDataID = {$dataset['editedDataID']} ";
      $result = mysql_query( $query )
                or die( "Query failed : $query<br />" . mysql_error());
      list( $edit_profile ) = mysql_fetch_array( $result );
      if ( ! $this->create_file( $dataset['edit'], $dir, $edit_profile ) )
        return false;
      $filenames[$dataset_id]['edit'] = $dataset['edit'];

/*
      // model
      $query  = "SELECT xml FROM model " .
                "WHERE modelID = {$dataset['modelID']} ";
      $result = mysql_query( $query )
                or die( "Query failed : $query<br />" . mysql_error());
      list( $model_contents ) = mysql_fetch_array( $result );
      if ( ! ( $model_file = $this->my_tmpname( '.model', '', $dir ) ) )
        return false;
      $model_file = basename( $model_file );
      if ( ! $this->create_file( $model_file, $dir, $model_contents ) )
        return false;
      $filenames[$dataset_id]['model'] = $model_file;
*/

      // noise
      foreach ( $dataset['noiseIDs'] as $ndx => $noiseID )
      {
        $query  = "SELECT noiseType, xml FROM noise " .
                  "WHERE noiseID = $noiseID ";
        $result = mysql_query( $query )
                  or die( "Query failed : $query<br />" . mysql_error());
        list( $type, $vector ) = mysql_fetch_array( $result );
        if ( ! ($noise_file = $this->my_tmpname( ".$type", '', $dir ) ) )
          return false;
        $noise_file = basename( $noise_file );
        if ( ! $this->create_file( $noise_file, $dir, $vector ) )
          return false;
        $filenames[$dataset_id]['noise'][$ndx] = $noise_file;
      }
    }

    // In the case of 2DSA_CG files, the CG_model
    if ( isset( $job['job_parameters']['CG_modelID'] ) )
    {
      $CG_modelID = $job['job_parameters']['CG_modelID'];
      $query  = "SELECT description, xml " .
                "FROM model " .
                "WHERE modelID = $CG_modelID ";
      $result = mysql_query( $query )
                or die( "Query failed : $query<br />" . mysql_error());
      list( $fn, $contents ) = mysql_fetch_array( $result );
      if ( ! $this->create_file( $fn, $dir, $contents ) )
        return false;
      $filenames[0]['CG_model'] = $fn;  // put it in with other dataset[0] files so
                                        //  as not to confuse the tar file creation

    }

    // In the case of DMGA_Constr files, the DC_model
    if ( isset( $job['job_parameters']['DC_modelID'] ) )
    {
      $DC_modelID = $job['job_parameters']['DC_modelID'];
      $query  = "SELECT description, xml " .
                "FROM model " .
                "WHERE modelID = $DC_modelID ";
      $result = mysql_query( $query )
                or die( "Query failed : $query<br />" . mysql_error());
      list( $fn, $contents ) = mysql_fetch_array( $result );
      if ( ! $this->create_file( $fn, $dir, $contents ) )
        return false;
      $filenames[0]['DC_model'] = $fn;  // put it in with other dataset[0] files so
                                        //  as not to confuse the tar file creation

    }

    return( $filenames );
  }

  // Function to create the data subdirectory to write files into
  function create_dir( $dir_name )
  {
    global $submit_dir;

    $dirPath = $submit_dir . $dir_name;
    if ( ! mkdir( $dirPath, 0770 ) )
    {
      echo "\nmkdir failed\n";
      return false;
    }

    // Ensure that group write permmissions are set for us3 user in listen
    // mkdir is influenced by umask, which is system wide and should
    // not be reset for one process
    chmod( $dirPath, 0770 );
    return( $dirPath . "/" );
  }

  // Function to create and open a file, and write data to it if possible
  function create_file( $filename, $dir, $data )
  {
    //echo "\nIn create_file: filename =  $filename\n";
    $dataFile = $dir . $filename;

    if ( ! $fp = fopen( $dataFile, "w" ) )
    {
      echo "fopen failed\n";
      return false;
    }

    if ( ! is_writable( $dataFile ) )
    {
      echo "is_writable failed\n";
      return false;
    }

    if ( fwrite( $fp, $data ) === false )
    {
      echo "fwrite failed\n";
      fclose( $fp );
      return false;
    }

    fclose( $fp );
    return( $dataFile );
  }

  // Function to create a unique filename with given extension
  function my_tmpname( $postfix = '.tmp', $prefix = '', $dir = null )
  {
    // validate arguments
    if ( ! (isset($postfix) && is_string($postfix) ) )
      return false;

    if (! (isset($prefix) && is_string($prefix) ) )
      return false;

    if (! isset($dir) )
      $dir = getcwd();

    // find a temporary name
    $tries = 1;
    while ( $tries <= 5 )
    {
      // get a known, unique temporary file name
      $sysFileName = tempnam($dir, $prefix);
      if ( $sysFileName === false )
        return false;

      // tack on the extension
      $newFileName = $sysFileName . $postfix;
      if ($sysFileName == $newFileName)
        return $sysFileName;

      // move or point the created temporary file to the new filename
      // NOTE: this fails if the new file name exists
      if ( rename( $sysFileName, $newFileName ) )
        return $newFileName;

      $tries++;
    }

    // failed 5 times.
    return false;
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
 * A class that writes the 2DSA portion of a control xml file
 * Inherits from File_writer
 */
class File_2DSA extends File_writer
{
  // Function to write the XML job parameters for the 2DSA analysis
  function writeJobParameters( $xml, $parameters )
  {
    $xml->startElement( 'jobParameters' );
      $xml->startElement( 's_min' );
        $xml->writeAttribute( 'value', $parameters['s_min'] );
      $xml->endElement(); // s_min
      $xml->startElement( 's_max' );
        $xml->writeAttribute( 'value', $parameters['s_max'] );
      $xml->endElement(); // s_max
      $xml->startElement( 's_resolution' );
        $xml->writeAttribute( 'value', $parameters['s_grid_points'] / 
                                       $parameters['uniform_grid']  );
      $xml->endElement(); // old-style s_resolution
      $xml->startElement( 's_grid_points' );
        $xml->writeAttribute( 'value', $parameters['s_grid_points'] );
      $xml->endElement(); // s_grid_points
      $xml->startElement( 'ff0_min' );
        $xml->writeAttribute( 'value', $parameters['ff0_min'] );
      $xml->endElement(); // ff0_min
      $xml->startElement( 'ff0_max' );
        $xml->writeAttribute( 'value', $parameters['ff0_max'] );
      $xml->endElement(); // ff0_max
      $xml->startElement( 'ff0_resolution' );
        $xml->writeAttribute( 'value', $parameters['ff0_grid_points'] /
                                       $parameters['uniform_grid']    );
      $xml->endElement(); // old-style ff0_resolution
      $xml->startElement( 'ff0_grid_points' );
        $xml->writeAttribute( 'value', $parameters['ff0_grid_points'] );
      $xml->endElement(); // ff0_grid_points
      $xml->startElement( 'uniform_grid' );
        $xml->writeAttribute( 'value', $parameters['uniform_grid'] );
      $xml->endElement(); // uniform_grid
      $xml->startElement( 'mc_iterations' );
        $xml->writeAttribute( 'value', $parameters['mc_iterations'] );
      $xml->endElement(); // mc_iterations
      $xml->startElement( 'req_mgroupcount' );
        $xml->writeAttribute( 'value', $parameters['req_mgroupcount'] );
      $xml->endElement(); // req_mgroupcount
      $xml->startElement( 'tinoise_option' );
        $xml->writeAttribute( 'value', $parameters['tinoise_option'] );
      $xml->endElement(); // tinoise_option
      $xml->startElement( 'meniscus_range' );
        $xml->writeAttribute( 'value', $parameters['meniscus_range'] );
      $xml->endElement(); // meniscus_range
      $xml->startElement( 'meniscus_points' );
        $xml->writeAttribute( 'value', $parameters['meniscus_points'] );
      $xml->endElement(); // meniscus_points
      $xml->startElement( 'max_iterations' );
        $xml->writeAttribute( 'value', $parameters['max_iterations'] );
      $xml->endElement(); // max_iterations
      $xml->startElement( 'rinoise_option' );
        $xml->writeAttribute( 'value', $parameters['rinoise_option'] );
      $xml->endElement(); // rinoise_option
      $xml->startElement( 'debug_timings' );
        $xml->writeAttribute( 'value', $parameters['debug_timings'] );
      $xml->endElement(); // debug_timings
      $xml->startElement( 'debug_level' );
        $xml->writeAttribute( 'value', $parameters['debug_level'] );
      $xml->endElement(); // debug_level
    $xml->endElement(); // jobParameters
  }

}

/*
 * A class that writes the 2DSA portion of a control xml file
 * Inherits from File_writer
 */
class File_2DSA_CG extends File_writer
{
  // Function to write the XML job parameters for the 2DSA analysis
  function writeJobParameters( $xml, $parameters )
  {
    $CG_modelID = $parameters['CG_modelID'];

    $query  = "SELECT description FROM model " .
              "WHERE  modelID = $CG_modelID ";
    $result = mysql_query( $query )
              or die( "Query failed : $query<br />" . mysql_error());
    list( $fn ) = mysql_fetch_array( $result );

    $xml->startElement( 'jobParameters' );
      $xml->startElement( 'CG_model' );
        $xml->writeAttribute( 'id', $CG_modelID );
        $xml->writeAttribute( 'filename', $fn );
      $xml->endElement(); // CG_model
      $xml->startElement( 'uniform_grid' );
        $xml->writeAttribute( 'value', $parameters['uniform_grid'] );
      $xml->endElement(); // uniform_grid
      $xml->startElement( 'mc_iterations' );
        $xml->writeAttribute( 'value', $parameters['mc_iterations'] );
      $xml->endElement(); // mc_iterations
      $xml->startElement( 'req_mgroupcount' );
        $xml->writeAttribute( 'value', $parameters['req_mgroupcount'] );
      $xml->endElement(); // req_mgroupcount
      $xml->startElement( 'tinoise_option' );
        $xml->writeAttribute( 'value', $parameters['tinoise_option'] );
      $xml->endElement(); // tinoise_option
      $xml->startElement( 'meniscus_range' );
        $xml->writeAttribute( 'value', $parameters['meniscus_range'] );
      $xml->endElement(); // meniscus_range
      $xml->startElement( 'meniscus_points' );
        $xml->writeAttribute( 'value', $parameters['meniscus_points'] );
      $xml->endElement(); // meniscus_points
      $xml->startElement( 'max_iterations' );
        $xml->writeAttribute( 'value', $parameters['max_iterations'] );
      $xml->endElement(); // max_iterations
      $xml->startElement( 'rinoise_option' );
        $xml->writeAttribute( 'value', $parameters['rinoise_option'] );
      $xml->endElement(); // rinoise_option
      $xml->startElement( 'debug_timings' );
        $xml->writeAttribute( 'value', $parameters['debug_timings'] );
      $xml->endElement(); // debug_timings
      $xml->startElement( 'debug_level' );
        $xml->writeAttribute( 'value', $parameters['debug_level'] );
      $xml->endElement(); // debug_level
    $xml->endElement(); // jobParameters
  }

}

/*
 * A class that writes the GA portion of a control xml file
 * Inherits from File_writer
 */
class File_GA extends File_writer
{
  // Function to write the XML job parameters for the GA analysis
  function writeJobParameters( $xml, $parameters )
  {
    $xml->startElement( 'jobParameters' );
      $xml->startElement( 'mc_iterations' );
        $xml->writeAttribute( 'value', $parameters['mc_iterations'] );
      $xml->endElement(); // mc_iterations
      $xml->startElement( 'req_mgroupcount' );
        $xml->writeAttribute( 'value', $parameters['req_mgroupcount'] );
      $xml->endElement(); // req_mgroupcount
      $xml->startElement( 'demes' );
        $xml->writeAttribute( 'value', $parameters['demes'] );
      $xml->endElement(); // demes
      $xml->startElement( 'population' );
        $xml->writeAttribute( 'value', $parameters['population'] );
      $xml->endElement(); // population
      $xml->startElement( 'generations' );
        $xml->writeAttribute( 'value', $parameters['generations'] );
      $xml->endElement(); // generations
      $xml->startElement( 'crossover' );
        $xml->writeAttribute( 'value', $parameters['crossover'] );
      $xml->endElement(); // crossover
      $xml->startElement( 'mutation' );
        $xml->writeAttribute( 'value', $parameters['mutation'] );
      $xml->endElement(); // mutation
      $xml->startElement( 'plague' );
        $xml->writeAttribute( 'value', $parameters['plague'] );
      $xml->endElement(); // plague
      $xml->startElement( 'elitism' );
        $xml->writeAttribute( 'value', $parameters['elitism'] );
      $xml->endElement(); // elitism
      $xml->startElement( 'migration' );
        $xml->writeAttribute( 'value', $parameters['migration'] );
      $xml->endElement(); // migration
      $xml->startElement( 'regularization' );
        $xml->writeAttribute( 'value', $parameters['regularization'] );
      $xml->endElement(); // regularization
      $xml->startElement( 'seed' );
        $xml->writeAttribute( 'value', $parameters['seed'] );
      $xml->endElement(); // seed
      $xml->startElement( 'conc_threshold' );
        $xml->writeAttribute( 'value', $parameters['conc_threshold'] );
      $xml->endElement(); // conc_threshold
      $xml->startElement( 's_grid' );
        $xml->writeAttribute( 'value', $parameters['s_grid'] );
      $xml->endElement(); // s_grid
      $xml->startElement( 'k_grid' );
        $xml->writeAttribute( 'value', $parameters['k_grid'] );
      $xml->endElement(); // k_grid
      $xml->startElement( 'mutate_sigma' );
        $xml->writeAttribute( 'value', $parameters['mutate_sigma'] );
      $xml->endElement(); // mutate_sigma
      $xml->startElement( 'p_mutate_s' );
        $xml->writeAttribute( 'value', $parameters['p_mutate_s'] );
      $xml->endElement(); // p_mutate_s
      $xml->startElement( 'p_mutate_k' );
        $xml->writeAttribute( 'value', $parameters['p_mutate_k'] );
      $xml->endElement(); // p_mutate_k
      $xml->startElement( 'p_mutate_sk' );
        $xml->writeAttribute( 'value', $parameters['p_mutate_sk'] );
      $xml->endElement(); // p_mutate_sk
      $xml->startElement( 'debug_timings' );
        $xml->writeAttribute( 'value', $parameters['debug_timings'] );
      $xml->endElement(); // debug_timings
      $xml->startElement( 'debug_level' );
        $xml->writeAttribute( 'value', $parameters['debug_level'] );
      $xml->endElement(); // debug_level
      $xml->startElement( 'bucket_fixed' );
        $xml->writeAttribute( 'value',     $parameters['bucket_fixed'] );
        $xml->writeAttribute( 'fixedtype', $parameters['z-type'] );
        $xml->writeAttribute( 'xtype',     $parameters['x-type'] );
        $xml->writeAttribute( 'ytype',     $parameters['y-type'] );
      $xml->endElement(); // bucket-fixed

      $xtlo = 'x_min';
      $xthi = 'x_max';
      $ytlo = 'y_min';
      $ythi = 'y_max';
      // Now write out the buckets
      for ( $i = 1; $i <= sizeof( $parameters['buckets'] ); $i++ )
      {
        $bucket = $parameters['buckets'][$i];
        $xml->startElement( 'bucket' );
          $xml->writeAttribute( $xtlo, $bucket[$xtlo] );
          $xml->writeAttribute( $xthi, $bucket[$xthi] );
          $xml->writeAttribute( $ytlo, $bucket[$ytlo] );
          $xml->writeAttribute( $ythi, $bucket[$ythi] );
        $xml->endElement(); // bucket
      }

    $xml->endElement(); // jobParameters
  }

}

/*
 * A class that writes the DMGA portion of a control xml file
 * Inherits from File_writer
 */
class File_DMGA extends File_writer
{
  // Function to write the XML job parameters for the GA analysis
  function writeJobParameters( $xml, $parameters )
  {
    $DC_modelID = $parameters['DC_modelID'];

    $query  = "SELECT description FROM model " .
              "WHERE  modelID = $DC_modelID ";
    $result = mysql_query( $query )
              or die( "Query failed : $query<br />" . mysql_error());
    list( $fn ) = mysql_fetch_array( $result );

    $xml->startElement( 'jobParameters' );
      $xml->startElement( 'DC_model' );
        $xml->writeAttribute( 'id', $DC_modelID );
        $xml->writeAttribute( 'filename', $fn );
      $xml->endElement(); // DC_model
      $xml->startElement( 'mc_iterations' );
        $xml->writeAttribute( 'value', $parameters['mc_iterations'] );
      $xml->endElement(); // mc_iterations
      $xml->startElement( 'req_mgroupcount' );
        $xml->writeAttribute( 'value', $parameters['req_mgroupcount'] );
      $xml->endElement(); // req_mgroupcount
      $xml->startElement( 'demes' );
        $xml->writeAttribute( 'value', $parameters['demes'] );
      $xml->endElement(); // demes
      $xml->startElement( 'population' );
        $xml->writeAttribute( 'value', $parameters['population'] );
      $xml->endElement(); // population
      $xml->startElement( 'generations' );
        $xml->writeAttribute( 'value', $parameters['generations'] );
      $xml->endElement(); // generations
      $xml->startElement( 'crossover' );
        $xml->writeAttribute( 'value', $parameters['crossover'] );
      $xml->endElement(); // crossover
      $xml->startElement( 'mutation' );
        $xml->writeAttribute( 'value', $parameters['mutation'] );
      $xml->endElement(); // mutation
      $xml->startElement( 'plague' );
        $xml->writeAttribute( 'value', $parameters['plague'] );
      $xml->endElement(); // plague
      $xml->startElement( 'elitism' );
        $xml->writeAttribute( 'value', $parameters['elitism'] );
      $xml->endElement(); // elitism
      $xml->startElement( 'migration' );
        $xml->writeAttribute( 'value', $parameters['migration'] );
      $xml->endElement(); // migration
      $xml->startElement( 'seed' );
        $xml->writeAttribute( 'value', $parameters['seed'] );
      $xml->endElement(); // seed
      $xml->startElement( 'p_grid' );
        $xml->writeAttribute( 'value', $parameters['p_grid'] );
      $xml->endElement(); // p_grid
      $xml->startElement( 'mutate_sigma' );
        $xml->writeAttribute( 'value', $parameters['mutate_sigma'] );
      $xml->endElement(); // mutate_sigma
      $xml->startElement( 'debug_timings' );
        $xml->writeAttribute( 'value', $parameters['debug_timings'] );
      $xml->endElement(); // debug_timings
      $xml->startElement( 'debug_level' );
        $xml->writeAttribute( 'value', $parameters['debug_level'] );
      $xml->endElement(); // debug_level

    $xml->endElement(); // jobParameters
  }

}

?>
