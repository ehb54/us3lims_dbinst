<?php
/*
 * submit_local.php
 *
 * Submits an analysis to a local system (bcf/alamo/laredo)
 *
 */
require_once 'lib/jobsubmit.php';

class submit_local extends jobsubmit
{
   // Submit the this->data
   function submit()
   {
      // a preliminary test to see if data is still defined
      if ( ! isset( $this->data[ 'job' ][ 'cluster_shortname' ] ) )
      {
        $this->message[] = "Data profile is not defined. Return to Queue Setup.\n";
        return;
      }

      $savedir = getcwd();
      chdir( $this->data[ 'job' ][ 'directory' ] );
      $this->copy_files    ();
      $this->submit_job    ();
      $this->update_db     ();
      chdir( $savedir );
$this->message[] = "End of submit_local.php\n";
   }
 
   // Copy needed files to supercomputer
   function copy_files()
   {
      $cluster   = $this->data[ 'job' ][ 'cluster_shortname' ];
      $requestID = $this->data[ 'job' ][ 'requestID' ];
      $jobid     = $cluster . sprintf( "-%06d", $requestID );
      $workdir   = $this->grid[ $cluster ][ 'workdir' ] . $jobid;
      $address   = $this->grid[ $cluster ][ 'name' ];
      $port      = $this->grid[ $cluster ][ 'sshport' ]; 
      $tarFilename = sprintf( "hpcinput-%s-%s-%05d.tar",
                               $this->data['db']['host'],
                               $this->data['db']['name'],
                               $this->data['job']['requestID'] );
      
      // Create working directory
      $cmd = "ssh -p $port -x $address mkdir -p $workdir";
      shell_exec( $cmd );
 
      // Copy tar file
      $cmd = "scp -P 22 $tarFilename $address:" . $workdir;
      shell_exec( $cmd );
$this->message[] = "Files copied";
   }
 
   // Schedule the job
   function submit_job()
   {
      date_default_timezone_set( "America/Chicago" );
      $term = '-term ' . date( 'm/d/Y', strtotime( '+1 month' ) );
 
      $cluster = $this->data[ 'job' ][ 'cluster_shortname' ];
      $name    = $this->grid[ $cluster ][ 'name' ];
      $port    = $this->grid[ $cluster ][ 'localport' ];
 
      $factory = "-F https://" . $name . ":" . $port .
                 "/wsrf/services/ManagedJobFactoryService";
 
      $factory_type = "-factory-type " . $this->grid[ $cluster ][ 'factorytype' ];
      
      $cmd = "qsub -submit -batch $term $factory $factory_type -f $this->jobfile" .
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
