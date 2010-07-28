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
  function write( $data, $HPCAnalysisRequestID )
  {
    // Get the rest of the information we need
    $query  = "SELECT HPCAnalysisRequestGUID FROM HPCAnalysisRequest " .
              "WHERE HPCAnalysisRequestID = $HPCAnalysisRequestID ";
    $result = mysql_query( $query )
              or die( "Query failed : $query<br />" . mysql_error());
    list( $HPCAnalysisRequestGUID ) = mysql_fetch_array( $result );
    $request = array();
    $request['id']   = $HPCAnalysisRequestID;
    $request['guid'] = $HPCAnalysisRequestGUID;

    switch( $data['method'] )
    {
      case '2DSA' :
        return $this->write_2DSA( $data, $request );
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
  function write_2DSA( $job, $request )
  {
    // First create a directory with a unique name
    if ( ! ( $current_dir = $this->create_dir( $request['guid'] ) ) )
      return false;

    // Write the auc, edit profile, model and noise files
    if ( ! ( $filenames = $this->write_support_files( $job, $current_dir ) ) )
      return false;

    echo "<pre>\n";
    echo "Current directory = $current_dir\n";
    print_r( $filenames );
    echo "</pre>\n";

    // Now write xml file
    $xml_filename = sprintf( "hpcrequest-%s-%s-%05d.xml",
                             $job['database']['host'],
                             $job['database']['name'],
                             $request['id'] );

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
