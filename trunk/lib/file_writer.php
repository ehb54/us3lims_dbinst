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

class file_writer
{
  function file_writer() {}

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
        $xml->endElement(); // cluster
        $xml->startElement( 'udp' );
          $xml->writeAttribute( 'port', '12335' );
          $xml->writeAttribute( 'server', '129.111.140.167' );
        $xml->endElement(); // udp
        $xml->startElement( 'directory' );
          $xml->writeAttribute( 'name', $current_dir );
        $xml->endElement(); // directory
        $xml->startElement( 'datasetCount' );
          $xml->writeAttribute( 'value', $job['datasetCount'] );
        $xml->endElement(); // datasetCount
        $xml->startElement( 'requestID' );
          $xml->writeAttribute( 'id', $HPCAnalysisRequestID );
        $xml->endElement(); // requestID
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
        $this->writeJobParameters( $xml, $job['method'], $job['job_parameters'] );

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
            $xml->startElement( 'model' );
              $xml->writeAttribute( 'filename', $filenames[$dataset_id]['model'] );
            $xml->endElement(); // model
            foreach ( $filenames[$dataset_id]['noise'] as $noiseFile )
            {
              $xml->startElement( 'noise' );
                $xml->writeAttribute( 'filename', $noiseFile );
              $xml->endElement(); // noise
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

      // model
      $query  = "SELECT contents FROM model " .
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

      // noise
      foreach ( $dataset['noiseIDs'] as $ndx => $noiseID )
      {
        $query  = "SELECT noiseType, noiseVector FROM noise " .
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

    return( $filenames );
  }

  // Function to decide which job parameters to write
  function writeJobParameters( $xml, $method, $jobParameters )
  {
    switch( $method )
    {
      case '2DSA' :
        $this->write2DSAParameters( $xml, $jobParameters );
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

  // Function to write the XML job parameters for the 2DSA analysis
  function write2DSAParameters( $xml, $parameters )
  {
    $xml->startElement( 'jobParameters' );
      $xml->startElement( 's_min' );
        $xml->writeAttribute( 'value', $parameters['s_min'] );
      $xml->endElement(); // s_min
      $xml->startElement( 's_max' );
        $xml->writeAttribute( 'value', $parameters['s_max'] );
      $xml->endElement(); // s_max
      $xml->startElement( 's_resolution' );
        $xml->writeAttribute( 'value', $parameters['s_resolution'] );
      $xml->endElement(); // s_resolution
      $xml->startElement( 'ff0_min' );
        $xml->writeAttribute( 'value', $parameters['ff0_min'] );
      $xml->endElement(); // ff0_min
      $xml->startElement( 'ff0_max' );
        $xml->writeAttribute( 'value', $parameters['ff0_max'] );
      $xml->endElement(); // ff0_max
      $xml->startElement( 'ff0_resolution' );
        $xml->writeAttribute( 'value', $parameters['ff0_resolution'] );
      $xml->endElement(); // ff0_resolution
      $xml->startElement( 'uniform_grid' );
        $xml->writeAttribute( 'value', $parameters['uniform_grid'] );
      $xml->endElement(); // uniform_grid
      $xml->startElement( 'montecarlo_value' );
        $xml->writeAttribute( 'value', $parameters['montecarlo_value'] );
      $xml->endElement(); // montecarlo_value
      $xml->startElement( 'tinoise_option' );
        $xml->writeAttribute( 'value', $parameters['tinoise_option'] );
      $xml->endElement(); // tinoise_option
      $xml->startElement( 'regularization' );
        $xml->writeAttribute( 'value', $parameters['regularization'] );
      $xml->endElement(); // regularization
      $xml->startElement( 'meniscus_value' );
        $xml->writeAttribute( 'value', $parameters['meniscus_value'] );
      $xml->endElement(); // meniscus_value
      $xml->startElement( 'meniscus_points' );
        $xml->writeAttribute( 'value', $parameters['meniscus_points'] );
      $xml->endElement(); // meniscus_points
      $xml->startElement( 'iterations_value' );
        $xml->writeAttribute( 'value', $parameters['iterations_value'] );
      $xml->endElement(); // iterations_value
      $xml->startElement( 'rinoise_option' );
        $xml->writeAttribute( 'value', $parameters['rinoise_option'] );
      $xml->endElement(); // rinoise_option
      $xml->startElement( 'rotor_stretch' );
        $xml->writeAttribute( 'value', $parameters['rotor_stretch'] );
      $xml->endElement(); // rotor_stretch
    $xml->endElement(); // jobParameters
  }

  // Function to create the data subdirectory to write files into
  function create_dir( $dir_name )
  {
    global $data_dir;

    $dirPath = $data_dir . $dir_name;
    if ( ! mkdir( $dirPath, 0770 ) )
      return false;

    return( $dirPath . "/" );
  }

  // Function to create and open a file, and write data to it if possible
  function create_file( $filename, $dir, $data )
  {
    $dataFile = $dir . $filename;

    if ( ! $fp = fopen( $dataFile, "a" ) )
      return false;

    if ( ! is_writable( $dataFile ) )
      return false;

    if ( fwrite( $fp, $data ) === false )
    {
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
?>
