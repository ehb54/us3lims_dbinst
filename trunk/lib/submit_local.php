<?php
/*
 * submit_local.php
 *
 * Submits an analysis to a local system (bcf/alamo)
 *
 */
require_once 'lib/jobsubmit.php';

class submit_local extends jobsubmit
{ 
   // Submits data
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
$this->message[] = "End of submit_local.php";
   }
 
   // Copy needed files to supercomputer
   function copy_files()
   {
      $cluster   = $this->data[ 'job' ][ 'cluster_shortname' ];
      $requestID = $this->data[ 'job' ][ 'requestID' ];
      $jobid     = $this->data[ 'db' ][ 'name' ] . sprintf( "-%06d", $requestID );
      $workdir   = $this->grid[ $cluster ][ 'workdir' ] . $jobid;
      $address   = $this->grid[ $cluster ][ 'name' ];
      $port      = $this->grid[ $cluster ][ 'sshport' ]; 
      $tarfile   = sprintf( "hpcinput-%s-%s-%05d.tar",
                         $this->data['db']['host'],
                         $this->data['db']['name'],
                         $this->data['job']['requestID'] );
    
      // Create working directory
      $output = array();
      $cmd    = "ssh -p $port -x us3@$address mkdir -p $workdir 2>&1";
      exec( $cmd, $output, $status );

      // Copy tar file
      $cmd    = "scp -P $port $tarfile us3@$address:$workdir 2>&1";
      exec( $cmd, $output, $status );

      //  Create and copy pbs file
      $pbsfile = $this->create_pbs();
      $cmd     = "scp -P $port $pbsfile us3@$address:$workdir 2>&1";
      exec( $cmd, $output, $status );
      
$this->message[] = "Files copied to $address:$workdir";
   }
 
   // Create a pbs file
   function create_pbs()
   {
      $cluster   = $this->data[ 'job' ][ 'cluster_shortname' ];
      $requestID = $this->data[ 'job' ][ 'requestID' ];
      $jobid     = $this->data[ 'db' ][ 'name' ] . sprintf( "-%06d", $requestID );
      $workdir   = $this->grid[ $cluster ][ 'workdir' ] . $jobid;
      $tarfile   = sprintf( "hpcinput-%s-%s-%05d.tar",
                         $this->data['db']['host'],
                         $this->data['db']['name'],
                         $this->data['job']['requestID'] );

      $pbsfile = "us3.pbs";
      $wall    = $this->maxwall();
      $nodes   = $this->nodes();

      $hours   = (int)( $wall / 60 );
      $mins    = (int)( $wall % 60 );
      $ppn     = $this->grid[ $cluster ][ 'ppn' ]; 

      $walltime = sprintf( "%02.2d:%02.2d:00", $hours, $mins );  // 01:09:00

      switch( $cluster )
      {
        case 'bcf-local':
         $libpath = "/share/apps64/openmpi/lib";
         $path    = "/share/apps64/openmpi/bin";
         break;

        case 'alamo-local':
         $libpath = "/share/apps/openmpi/lib";
         $path    = "/share/apps/openmpi/bin";
         break;

        default:
         $libpath = "/share/apps/openmpi/lib:/share/apps/qt4/lib";
         $path    = "/share/apps/openmpi/bin";
         $ppn     = 2;
         break;
      }

      $procs   = $nodes * $ppn;

      $contents = 
      "#! /bin/bash\n"                                      .
      "#\n"                                                 . 
      "#PBS -N US3_Job\n"                                   .
      "#PBS -l nodes=$nodes:ppn=$ppn,walltime=$walltime\n"           .
      "#PBS -V\n"                                           .
      "#PBS -o $workdir/stdout\n"                     .
      "#PBS -e $workdir/stderr\n"                     .
      "\n"                                                  .
      "export LD_LIBRARY_PATH=$libpath:\$LD_LIBRARY_PATH\n" .
      "export PATH=$path:\$PATH\n"                          .
      "\n"                                                  .
      "# Turn off extraneous mpi debug output\n"            .
      "export OMPI_MCA_mpi_param_check=0\n"                 . 
      "export OMPI_MCA_mpi_show_handle_leaks=0\n"           .
      "export OMPI_MCA_mpi_show_mca_params=0\n"             .
      "\n"                                                  .
      "cd $workdir\n"                                 .
      "mpirun -np $procs /home/us3/bin/us_mpi_analysis $tarfile\n";

      $this->data[ 'pbsfile' ] = $contents;

      $h = fopen( $pbsfile, "w" );
      fwrite( $h, $contents );
      fclose( $h );

      return $pbsfile;
   }

   // Schedule the job
   function submit_job()
   {
      date_default_timezone_set( "America/Chicago" );
 
      $cluster   = $this->data[ 'job' ][ 'cluster_shortname' ];
      $requestID = $this->data[ 'job' ][ 'requestID' ];
      $jobid     = $this->data[ 'db' ][ 'name' ] . sprintf( "-%06d", $requestID );
      $workdir   = $this->grid[ $cluster ][ 'workdir' ] . $jobid;
      $address   = $this->grid[ $cluster ][ 'name' ];
      $port      = $this->grid[ $cluster ][ 'sshport' ]; 
      $tarfile   = sprintf( "hpcinput-%s-%s-%05d.tar",
                         $this->data['db']['host'],
                         $this->data['db']['name'],
                         $this->data['job']['requestID'] );

      // Submit job to the queue
      $cmd   = "ssh -p $port -x us3@$address qsub $workdir/us3.pbs 2>&1";
      $jobid = exec( $cmd, $output, $status );

      // Save the job ID
      $this->data[ 'eprfile' ] = rtrim( $jobid );;

$this->message[] = "Job submitted; ID:" . $this->data[ 'eprfile' ];
   }
 
   function update_db()
   {
      global $globaldbuser;
      global $globaldbpasswd;
      global $globaldbhost;
      global $globaldbname;

      global $dbusername;
      global $dbpasswd;
      global $dbhost;
      global $dbname;

      $cluster   = $this->data['job']['cluster_shortname'];
      $pbs       = mysql_real_escape_string( $this->data[ 'pbsfile' ] );
      $requestID = $this->data[ 'job' ][ 'requestID' ];
      $eprfile   = $this->data['eprfile'];

      $link = mysql_connect( $dbhost, $dbusername, $dbpasswd );
 
      if ( ! $link )
      {
         $this->message[] = "Cannot open database on $dbhost\n";
         return;
      }
 
      if ( ! mysql_select_db( $dbname, $link ) ) 
      {
         $this->message[] = "Cannot change to database $dbname\n";
         return;
      }
 
      $query = "INSERT INTO HPCAnalysisResult SET "  .
               "HPCAnalysisRequestID='$requestID', " .
               "jobfile='$pbs', "                    .
               "gfacID='$eprfile' ";
      
      $result = mysql_query( $query, $link );
 
      if ( ! $result )
      {
         $this->message[] = "Invalid query:\n$query\n" . mysql_error( $link ) . "\n";
         return;
      }
 
      mysql_close( $link );

      // Insert initial data into global DB
      $gfac_link = mysql_connect( $globaldbhost, $globaldbuser, $globaldbpasswd );
 
      if ( ! $gfac_link )
      {
         $this->message[] = "Cannot open database on $globaldbhost\n";
         return;
      }
 
      if ( ! mysql_select_db( $globaldbname, $gfac_link ) ) 
      {
         $this->message[] = "Cannot change to database $globaldbname\n";
         return;
      }

      $query = "INSERT INTO analysis SET " .
               "gfacID='$eprfile', "       .
               "cluster='$cluster', "      .
               "us3_db='$dbname'";

      $result = mysql_query( $query, $gfac_link );
 
      if ( ! $result )
      {
         $this->message[] = "Invalid query:\n$query\n" . mysql_error( $gfac_link ) . "\n";
         return;
      }
 
      mysql_close( $gfac_link );

$this->message[] = "Database $dbname updated: requestID = $requestID";
   }
}
?>
