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
   private $workdir;
   private $tarfile;
   private $requestID;
   private $port;
   private $address;
   private $cluster;
   
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
      $this->cluster   = $this->data[ 'job' ][ 'cluster_shortname' ];
      $this->requestID = $this->data[ 'job' ][ 'requestID' ];
      $jobid           = $this->data[ 'db' ][ 'name' ] . sprintf( "-%06d", $this->requestID );
      $this->workdir   = $this->grid[ $this->cluster ][ 'workdir' ] . $jobid;
      $this->address   = $this->grid[ $this->cluster ][ 'name' ];
      $this->port      = $this->grid[ $this->cluster ][ 'sshport' ]; 
      $this->tarfile   = sprintf( "hpcinput-%s-%s-%05d.tar",
                               $this->data['db']['host'],
                               $this->data['db']['name'],
                               $this->data['job']['requestID'] );
      
      // Create working directory
      $output = array();
      $cmd    = "ssh -p $this->port -x us3@$this->address mkdir -p $this->workdir 2>&1";

$this->message[] = "cmd: $cmd\n";

      exec( $cmd, $output, $status );

$this->message[] = "cmd output: " . implode( "\n", $output ) . "\n";
 
      // Copy tar file
      $cmd    = "scp -P $this->port $this->tarfile us3@$this->address:$this->workdir 2>&1";

$this->message[] = "cmd: $cmd\n";
      exec( $cmd, $output, $status );
$this->message[] = "cmd output: " . implode( "\n", $output ) . "\n";

      //  Create pbs file
      $pbsfile = $this->create_pbs();
      $cmd     = "scp -P $this->port $pbsfile us3@$this->address:$this->workdir 2>&1";

$this->message[] = "cmd: $cmd\n";
      exec( $cmd, $output, $status );
$this->message[] = "cmd output: " . implode( "\n", $output ) . "\n";
      
$this->message[] = "Files copied";
   }
 
   // Create a pbs file
   function create_pbs()
   {
      $pbsfile = "us3.pbs";
      $wall    = $this->maxwall();

      $hours   = (int)( $wall / 60 );
      $mins    = (int)( $wall % 60 );

      $walltime = sprintf( "%02.2d:%02.2d:00", $hours, $mins );  // 01:09:00

      switch( $this->cluster )
      {
        case 'bcf-local':
         $libpath = "/share/apps64/openmpi/lib";
         $nodes   = $this->grid[ $this->cluster ][ 'maxproc' ];
         $path    = "/share/apps64/openmpi/bin";
         break;

        case 'alamo-local':
         $libpath = "/share/apps/openmpi/lib";
         $nodes   = $this->grid[ $this->cluster ][ 'maxproc' ];
         $path    = "/share/apps/openmpi/bin";
         break;

        default:
         $libpath = "/share/apps/openmpi/lib:/share/apps/qt4/lib";
         $nodes   = "8:ppn=4";
         $path    = "/share/apps/openmpi/bin";
         break;
      }

      $contents = 
      "#! /bin/bash\n"                                      .
      "#\n"                                                 . 
      "#PBS -N US3_Job\n"                                   .
      "#PBS -l nodes=$nodes,walltime=$walltime\n"           .
      "#PBS -V\n"                                           .
      "#PBS -o $this->workdir/stdout\n"                     .
      "#PBS -e $this->workdir/stderr\n"                     .
      "\n"                                                  .
      "export LD_LIBRARY_PATH=$libpath:\$LD_LIBRARY_PATH\n" .
      "export PATH=$path:\$PATH\n"                          .
      "\n"                                                  .
      "# Turn off extraneous mpi debug output\n"            .
      "export OMPI_MCA_mpi_param_check=0\n"                 . 
      "export OMPI_MCA_mpi_show_handle_leaks=0\n"           .
      "export OMPI_MCA_mpi_show_mca_params=0\n"             .
      "\n"                                                  .
      "cd $this->workdir\n"                                 .
      "mpirun -np $nodes /home/us3/bin/us_mpi_analysis $this->tarfile\n";

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
 
      $cmd   = "ssh -p $this->port -x us3@$this->address qsub $this->workdir/us3.pbs 2>&1";
$this->message[] = "cmd: $cmd\n";
      $jobid = exec( $cmd, $output, $status );
$this->message[] = "cmd output: " . implode( "\n", $output ) . "\n";
      $this->data[ 'eprfile' ] = rtrim( $jobid );;

$this->message[] = "Job submitted";
$this->message[] = "Job ID:" . $this->data[ 'eprfile' ];
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

      $db = mysql_connect( $dbhost, $dbusername, $dbpasswd );
 
      if ( ! $db )
      {
         $this->message[] = "Cannot open database on $dbhost\n";
         return;
      }
 
      if ( ! mysql_select_db( $dbname, $db ) ) 
      {
         $this->message[] = "Cannot change to database $dbname\n";
         return;
      }
 
      $pbs = mysql_real_escape_string( $this->data[ 'pbsfile' ] );

      $query = "INSERT INTO HPCAnalysisResult SET "        .
               "HPCAnalysisRequestID='$this->requestID', " .
               "jobfile='$pbs', "                          .
               "gfacID='" . $this->data[ 'eprfile' ]      . "'";
      
      $result = mysql_query( $query, $db );
 
      if ( ! $result )
      {
         $this->message[] = "Invalid query:\n$query\n" . mysql_error( $db ) . "\n";
         return;
      }
 
      mysql_close( $db );

      // Insert initial data into global DB
      $db = mysql_connect( $globaldbhost, $globaldbuser, $globaldbpasswd );
 
      if ( ! $db )
      {
         $this->message[] = "Cannot open database on $globaldbhost\n";
         return;
      }
 
      if ( ! mysql_select_db( $globaldbname, $db ) ) 
      {
         $this->message[] = "Cannot change to database $globaldbname\n";
         return;
      }
 
      $pbs = mysql_real_escape_string( $this->data[ 'pbsfile' ] );

      $query = "INSERT INTO analysis SET "                    .
               "gfacID='"  . $this->data[ 'eprfile' ] . "', " .
               "cluster='" . $this->cluster           . "', " .
               "us3_db='$dbname'";

      $result = mysql_query( $query, $db );
 
      if ( ! $result )
      {
         $this->message[] = "Invalid query:\n$query\n" . mysql_error( $db ) . "\n";
         return;
      }
 
      mysql_close( $db );

$this->message[] = "Database $dbname updated: requestID = $this->requestID";
   }
}
?>
