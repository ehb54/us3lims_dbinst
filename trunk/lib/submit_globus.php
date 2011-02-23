<?php
/*
 * submit_globus.php
 *
 * Submits an analysis using the globus mpi method
 *
 */
require_once 'lib/jobsubmit.php';

class submit_globus extends jobsubmit
{
   protected static $globusbin = "/share/apps/tigre/globus/bin/";

   // Submit the this->data
   function submit()
   {
      // a preliminary test to see if data is still defined
      if ( ! isset( $this->data['job']['cluster_shortname'] ) )
      {
        $this->message[] = "Data profile is not defined. Return to Queue Setup.\n";
        return;
      }

      $savedir = getcwd();
      chdir( $this->data['job']['directory'] );
      $this->copy_files    ();
      $this->write_job_file();
      $this->submit_job    ();
      $this->update_db     ();
      chdir( $savedir );
$this->message[] = "End of submit_globus.php\n";
   }
 
   // Copy needed files to supercomputer
   function copy_files()
   {
      $globusbin = self::$globusbin;

      $cluster   = $this->data[ 'job' ][ 'cluster_shortname' ];
      $requestID = $this->data[ 'job' ][ 'requestID' ];
      $jobid     = $cluster . sprintf( "-%06d", $requestID );
      $workdir   = $this->grid[ $cluster ][ 'workdir' ] . "/work/" . $jobid;
      $address   = $this->grid[ $cluster ][ 'name' ];
      $port      = $this->grid[ $cluster ][ 'sshport' ]; 
      $tarFilename = sprintf( "hpcinput-%s-%s-%05d.tar",
                               $this->data['db']['host'],
                               $this->data['db']['name'],
                               $this->data['job']['requestID'] );
      
      // Create working directory
      $cmd = "{$globusbin}gsissh -p $port -x $address mkdir -p $workdir";
      shell_exec( $cmd );
 
      // Copy tar file
      $cmd = "{$globusbin}gsiscp -P 22 $tarFilename bcf.uthscsa.edu:" . $workdir;
      shell_exec( $cmd );
$this->message[] = "Files copied";
   }
 
   // Create job xml file
   function write_job_file()
   {
      $dir         = $this->data[ 'job' ][ 'directory' ];
      if ( $dir[strlen( $dir ) - 1] != '/' )
         $dir .= '/';
      $cluster     = $this->data[ 'job' ][ 'cluster_shortname' ];
      $requestID   = $this->data[ 'job' ][ 'requestID' ];
      $jobid       = $cluster . sprintf( "-%06d", $requestID );
      $workdir     = $this->grid[ $cluster ][ 'workdir' ] . "/work/" . $jobid;
 
      $this->jobfile     = $dir . $jobid . ".xml";
      $executable  = $this->grid[ $cluster ][ 'workdir' ] . '/bin/' .$this->grid[ $cluster ][ 'executable' ];
      $directory   = $workdir;
      $stdout      = $workdir . "/" . $jobid . ".stdout";
      $stderr      = $workdir . "/" . $jobid . ".stderr";
 
      $xmlfile     = $this->xmlfile;
 
      $cores       = $this->nodes();
      $cores       = "18";    // override for now
      
      $hostCount   = ceil( $cores / 4 );   // Only bigred
      $maxWallTime = $this->maxwall();
      $maxWallTime = "5";      // override for now
 
      $this->data[ 'cores'       ] = $cores;
      $this->data[ 'maxWallTime' ] = $maxWallTime;
 
      $tarFilename = sprintf( "hpcinput-%s-%s-%05d.tar",
                               $this->data['db']['host'],
                               $this->data['db']['name'],
                               $this->data['job']['requestID'] );

      $wrt = xmlwriter_open_memory();
      xmlwriter_set_indent( $wrt, true );
      xmlwriter_start_document( $wrt );
      xmlwriter_start_element( $wrt, "job");
 
      xmlwriter_start_element( $wrt, "executable" );
      xmlwriter_text( $wrt, $executable );
      xmlwriter_end_element( $wrt );
 
      xmlwriter_start_element( $wrt, "directory" );
      xmlwriter_text( $wrt, $directory );
      xmlwriter_end_element( $wrt );
 
      xmlwriter_start_element( $wrt, "argument" );
      //xmlwriter_text( $wrt, $xmlfile );
      xmlwriter_text( $wrt, $tarFilename );
      xmlwriter_end_element( $wrt );
 
      xmlwriter_start_element( $wrt, "stdout" );
      xmlwriter_text( $wrt, $stdout );
      xmlwriter_end_element( $wrt );
 
      xmlwriter_start_element( $wrt, "stderr" );
      xmlwriter_text( $wrt, $stderr );
      xmlwriter_end_element( $wrt );
 
      xmlwriter_start_element( $wrt, "count" );
      xmlwriter_text( $wrt, $cores );
      xmlwriter_end_element( $wrt );
 
      if ( $cluster == 'bigred' )
      {
         xmlwriter_start_element( $wrt, "hostCount" );
         xmlwriter_text( $wrt, $hostCount );
         xmlwriter_end_element( $wrt );
      }
 
      xmlwriter_start_element( $wrt, "queue" );
      xmlwriter_text( $wrt, "normal" );
      //xmlwriter_text( $wrt, "spoolq@bcf:36001" );
      xmlwriter_end_element( $wrt );
 
      xmlwriter_start_element( $wrt, "maxWallTime" );
      xmlwriter_text( $wrt, $maxWallTime );
      xmlwriter_end_element( $wrt );
 
      xmlwriter_start_element( $wrt, "jobType" );
      xmlwriter_text( $wrt, "mpi" );
      xmlwriter_end_element( $wrt );
 
      xmlwriter_end_element( $wrt );  // job
      xmlwriter_end_document( $wrt ); 
 
      $fp = fopen( $this->jobfile, 'w');
      fwrite( $fp, xmlwriter_output_memory( $wrt ) );
      fclose( $fp ); 
      
      $this->data[ 'jobxmlfile' ] = file_get_contents( $this->jobfile );
$this->message[] = "Job file written";
$this->message[] = $this->data['jobxmlfile'];
   }
 
   // Schedule the job
   function submit_job()
   {
      $globusbin = self::$globusbin;

      date_default_timezone_set( "America/Chicago" );
      $term = '-term ' . date( 'm/d/Y', strtotime( '+1 month' ) );
 
      $cluster = $this->data[ 'job' ][ 'cluster_shortname' ];
      $name    = $this->grid[ $cluster ][ 'name' ];
      $port    = $this->grid[ $cluster ][ 'globusport' ];
 
      $factory = "-F https://" . $name . ":" . $port .
                 "/wsrf/services/ManagedJobFactoryService";
 
      $factory_type = "-factory-type " . $this->grid[ $cluster ][ 'factorytype' ];
      
      $cmd = "{$globusbin}globusrun-ws -submit -batch $term $factory $factory_type -f $this->jobfile" .
             " 2> $this->jobfile.status";
      $this->data[ 'eprfile' ] = shell_exec( $cmd );
      // Check submit status
      $status = file( "$this->jobfile.status" );
 
      // Line 1 should be "Submitting job...Done."
      if ( preg_match( "/Done/i", $status[ 0 ] ) )
         $this->data[ 'dataset' ][ 'status' ] = "queued";
      else
      {
         $this->data[ 'dataset' ][ 'status' ] = "failed";
      }
$this->message[] = "Job submitted";
$this->message[] = "Status:";
$this->message[] = $status;
$this->message[] = "Result file:";
$this->message[] = $this->data['eprfile'];
   }
 
   function update_db()
   {
      $requestID = $this->data[ 'job' ][ 'requestID' ];
      $dbname    = $this->data[ 'db' ][ 'name' ];
      $host      = $this->data[ 'db' ][ 'host' ];
      $user      = $this->data[ 'db' ][ 'user' ];
      $status    = $this->data[ 'dataset' ][ 'status' ];
      $epr       = $this->data[ 'eprfile' ];
      $xml       = $this->data[ 'jobxmlfile' ];
 
      $db = mysql_connect( $host, "us3php", "us3" );
 
      if ( ! $db )
      {
         $this->message[] = "Cannot open database on $host\n";
         exit( 1 );
      }
 
      if ( ! mysql_select_db( $dbname, $db ) ) 
      {
         $this->message[] = "Cannot change to database $dbname\n";
         exit( 2 );
      }
 
      $query = "insert into HPCAnalysisResult set "                   .
               "HPCAnalysisRequestID='$requestID', "                  .
               "queueStatus='$status', "                              .
               "updateTime=now(), "                                   .
               "jobfile='" . mysql_real_escape_string( $xml ) . "', " .
               "eprfile='" . mysql_real_escape_string( $epr ) . "'";
      
      $result = mysql_query( $query, $db );
 
      if ( ! $result )
      {
         $this->message[] = "Invalid query: " . mysql_error( $db ) . "\n";
         exit( 4 );
      }
 
      mysql_close( $db );
$this->message[] = "Database $dbname updated: requestID = $requestID";
   }
}
?>
