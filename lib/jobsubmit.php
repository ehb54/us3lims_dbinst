<?php
/*
 * jobsubmit.php
 *
 * Base class for common elements used to submit an analysis
 *
 */
class jobsubmit
{
   protected $data    = array();   // Global parsed input
   protected $jobfile = "";        // Global string
   protected $message = array();   // Errors and other messages
   protected $grid    = array();   // Information about the clusters
   protected $xmlfile = "";        // Base name of the experiment xml file
 
   function __construct()
   {
      $this->grid[ 'bcf' ] = array 
      (
        "name"       => "bcf.uthscsa.edu",
        "submittype" => "local",
        "workdir"    => "/home/us3/work/",  // Need trailing slash
        "sshport"    => 22,
        "executable" => "us_mpi_analysis.sh",
        "queue"      => "",
        "udpserver"  => "ultrascan3.uthscsa.edu",
        "udpport"    => 12233,
        "database"   => "us3",
        "maxtime"    => 60000,
        "maxproc"    => 20
      );

      $this->grid[ 'alamo' ] = array 
      (
        "name"       => "alamo.uthscsa.edu",
        "submittype" => "local",
        "workdir"    => "/home/us3/work/",  // Need trailing slash
        "sshport"    => 22,
        "executable" => "us_mpi_analysis.sh",
        "queue"      => "",
        "udpserver"  => "ultrascan3.uthscsa.edu",
        "udpport"    => 12233,
        "database"   => "us3",
        "maxtime"    => 60000,
        "maxproc"    => 32
      );

      $this->grid[ 'laredo' ] = array 
      (
        "name"       => "alamo.uthscsa.edu",
        "submittype" => "local",
        "workdir"    => "/home/us3/work/",   // Need trailing slash
        "sshport"    => 22,
        "executable" => "us_mpi_analysis.sh",
        "queue"      => "laredo",
        "udpserver"  => "ultrascan3.uthscsa.edu",
        "udpport"    => 12233,
        "database"   => "us3",
        "maxtime"    => 60000,
        "maxproc"    => 32
      );

      $this->grid[ 'ranger' ] = array 
      (
        "name"       => "ranger.tacc.teragrid.org",
        "submithost" => "http://gw33.quarry.iu.teragrid.org",
        "userdn"     => "/C=US/O=National Center for Supercomputing Applications/CN=Ultrascan3 Community User",
        "submittype" => "http",
        "httpport"   => 8080,
        "workdir"    => "/ogce-rest/job/runjob/async",
        "sshport"    => 22,
        "executable" => "us_mpi_analysis",
        "queue"      => "normal",
        "udpserver"  => "ultrascan3.uthscsa.edu",
        "udpport"    => 12233,
        "database"   => "us3",
        "maxtime"    => 2880,
        "maxproc"    => 64
      );
    
      $this->grid[ 'lonestar' ] = array 
      (
        "name"       => "lonestar.tacc.teragrid.org",
        "submithost" => "http://gw33.quarry.iu.teragrid.org",
        "userdn"     => "/C=US/O=National Center for Supercomputing Applications/CN=Ultrascan3 Community User",
        "submittype" => "http",
        "httpport"   => 8080,
        "workdir"    => "/ogce-rest/job/runjob/async",
        "sshport"    => 22,
        "queue"      => "normal",
        "udpserver"  => "ultrascan3.uthscsa.edu",
        "udpport"    => 12233,
        "database"   => "us3",
        "maxtime"    => 1440,
        "maxproc"    => 36
      );
    
      $this->grid[ 'queenbee' ] = array 
      (
        "name"       => "queenbee.loni-lsu.teragrid.org",
        "submithost" => "http://gw33.quarry.iu.teragrid.org",
        "userdn"     => "/C=US/O=National Center for Supercomputing Applications/CN=Ultrascan3 Community User",
        "submittype" => "http",
        "httpport"   => 8080,
        "workdir"    => "/ogce-rest/job/runjob/async",
        "sshport"    => 22,
        "queue"      => "workq",
        "udpserver"  => "ultrascan3.uthscsa.edu",
        "udpport"    => 12233,
        "database"   => "us3",
        "maxtime"    => 60,
        "maxproc"    => 24
      );
   }
   
   // Deconstructor
   function __destruct()
   {
      $this->clear();
   }

   // Clear out data for another request
   function clear()
   {
      $this->data    = array();
      $this->jobfile = "";
      $this->message = array();
      $this->xmlfile = "";
   }

   // Request status
   function status()
   {
      if ( isset( $this->data['dataset']['status'] ) )
         return $this->data['dataset']['status'];

      return 'Status unavailable';
   }

   // Return any messages
   function get_messages()
   {
      return $this->message;
   }

   // Read and parse submitted xml file
   function parse_input( $xmlfile )
   {
      $this->xmlfile = $xmlfile;          // Save for other methods
      $contents = implode( "", file( $xmlfile ) ); 
 
      $parser = new XMLReader();
      $parser->xml( $contents );
 
      while( $parser->read() )
      {
         if ( $parser->nodeType == XMLReader::ELEMENT ) 
         {
            $tag = $parser->name;

            switch ( $tag )
            {
               case 'US_JobSubmit':
                  $this->parse_submit( $parser );
                  break;
 
               case 'job':
                  $this->parse_job( $parser );
                  break;
 
               case 'dataset':
                  $this->parse_dataset( $parser );
                  break;
            }
         }
      }
   }
 
   function parse_submit( &$parser )
   {
      $this->data[ 'method'  ] = $parser->getAttribute( 'method'  );
      $this->data[ 'version' ] = $parser->getAttribute( 'version' );
   }
 
   function parse_job( &$parser )
   {
      $job = array();
 
      while ( $parser->read() )
      {
         if ( $parser->nodeType == XMLReader::END_ELEMENT && 
              $parser->name     == 'job' ) 
              break;
 
         if ( $parser->nodeType == XMLReader::ELEMENT ) 
         {
            $tag = $parser->name;
 
            switch ( $tag )
            {
               case 'cluster':
                  $job[ 'cluster_name'      ] = $parser->getAttribute( 'name' );
                  $job[ 'cluster_shortname' ] = $parser->getAttribute( 'shortname' );
                  break;
 
               case 'udp':
                  $job[ 'udp_server' ] = $parser->getAttribute( 'server' );
                  $job[ 'udp_port'   ] = $parser->getAttribute( 'port' );
                  break;
 
               case 'directory':
                  $job[ 'directory' ] = $parser->getAttribute( 'name' );
                  break;
 
               case 'datasetCount':
                  $job[ 'datasetCount' ] = $parser->getAttribute( 'value' );
                  break;
 
               case 'request':
                  $job[ 'requestID' ] = $parser->getAttribute( 'id' );
                  break;
 
               case 'database':
                  $this->parse_db( $parser );
                  break;
 
               case 'jobParameters':
                  $this->parse_jobParameters( $parser, $job );
                  break;
            }
         }
      }
 
      $this->data[ 'job' ] = $job;
   }
   function parse_db( &$parser )
   {
      $db = array();
 
      while ( $parser->read() )
      {
         if ( $parser->nodeType == XMLReader::END_ELEMENT && 
              $parser->name     == 'database' ) 
              break;
 
         if ( $parser->nodeType == XMLReader::ELEMENT ) 
         {
            $tag = $parser->name;
            
            switch ( $tag )
            {
               case 'name':
                  $db[ 'name' ] = $parser->getAttribute( 'value' );
                  break;
 
               case 'host':
                  $db[ 'host' ] = $parser->getAttribute( 'value' );
                  break;
 
               case 'user':
                  $db[ 'user' ] = $parser->getAttribute( 'email' );
                  break;
 
               case 'submitter':
                  $db[ 'submitter' ] = $parser->getAttribute( 'email' );
                  break;
            }
         }
      }
 
      $this->data[ 'db' ] = $db;
   }
 
   function parse_jobParameters( &$parser, &$job )
   {
      $parameters = array();
 
      while ( $parser->read() )
      {
         if ( $parser->nodeType == XMLReader::END_ELEMENT &&
              $parser->name     == 'jobParameters' )
              break;
 
         $tag = $parser->name;
         if ( $tag == "#text" ) continue;
 
         $parameters[ $tag ] = $parser->getAttribute( 'value' );
      }
 
      $job[ 'jobParameters' ] = $parameters;
   }
 
   function parse_dataset( &$parser )
   {
      $dataset = array();
 
      if ( ! isset( $this->data[ 'dataset' ] ) ) $this->data[ 'dataset' ] = array();
 
      while ( $parser->read() )
      {
         if ( $parser->nodeType == XMLReader::END_ELEMENT &&
              $parser->name     == 'dataset' )
              break;
 
         $tag = $parser->name;
 
         switch ( $tag )
         {
            case 'files':
              $this->parse_files( $parser, $dataset );
              break;
 
            case 'parameters':
              $this->parse_parameters( $parser, $dataset );
              break;
         }
      }
 
      array_push( $this->data[ 'dataset' ], $dataset ); 
   }

   function parse_files( &$parser, &$dataset )
   {
      $files = array();
 
      while ( $parser->read() )
      {
         if ( $parser->nodeType == XMLReader::END_ELEMENT &&
              $parser->name     == 'files' )
              break;
 
         $tag = $parser->name;
 
         switch ( $tag )
         {
            case 'experiment':
            case 'auc'       :
            case 'edit'      :
            case 'model'     :
            case 'noise'     :
               array_push( $files, $parser->getAttribute( 'filename' ) );
              break;
         }
      }
      $dataset[ 'files' ] = $files;
   }
 
   function parse_parameters( &$parser, &$dataset )
   {
      $parameters = array();
 
      while ( $parser->read() )
      {
         if ( $parser->nodeType == XMLReader::END_ELEMENT &&
              $parser->name     == 'parameters' )
              break;
 
         $tag = $parser->name;
         if ( $tag == "#text" ) continue;
 
         $parameters[ $tag ] = $parser->getAttribute( 'value' );
      }
 
      $dataset[ 'parameters' ] = $parameters;
   }

   function maxwall()
   {
      $parameters = $this->data[ 'job' ][ 'jobParameters' ];
      $cluster    = $this->data[ 'job' ][ 'cluster_shortname' ];
      $max_time   = $this->grid[ $cluster ][ 'maxtime' ];
 
      if ( preg_match( "/^GA/", $this->data[ 'method' ] ) )
      {
         // Assume 1 sec a basic unit

         $generations = $parameters[ 'generations' ];
         $population  = $parameters[ 'population' ];

         // The constant 400 is an empirical value from doing a Hessian
         // minimumization

         $time        = ( 400 + $population ) * $generations;
 
         $time *= 1.5;  // Pad things a bit
         $time  = (int)( ($time + 59) / 60 ); // Round up to minutes
      }
      else // 2DSA
      {
//         $iterations = $parameters[ 'iterations_value' ];
 
         $ti_noise   = isset( $parameters[ 'tinoise_option' ] )
                       ? $parameters[ 'tinoise_option' ] > 0 
                       : false;
    
         $ri_noise   = isset( $parameters[ 'rinoise_option' ] )
                       ? $parameters[ 'rinoise_option' ] > 0
                       : false;
 
         $time       = 10;  // Base time in minutes

//         if ( $iterations > 0 ) $time *= $iterations;

         if ( isset( $parameters[ 'meniscus_points' ] ) )
         {
            $points = $parameters[ 'meniscus_points' ];
            if ( $points > 0 )  $time *= $points;
         }
    
         if ( $ti_noise || $ri_noise ) $time *= 2;
      }
 
      if ( isset( $parameters[ 'montecarlo_value' ] ) )
      {
         $montecarlo = $parameters[ 'montecarlo_value' ];
         if ( $montecarlo > 0 )  $time *= $montecarlo;
      }

      $time *= 1.5;  // Padding
 
      $time = max( $time, 5 );         // Minimum time is 5 minutes
      $time = min( $time, $max_time ); // Maximum time is defined for each cluster
 
      return (int)$time;
   }
 
   function nodes()
   {
      $cluster    = $this->data[ 'job' ][ 'cluster_shortname' ];
      $parameters = $this->data[ 'job' ][ 'jobParameters' ];
      $max_nodes  = $this->grid[ $cluster ][ 'maxproc' ];
 
      if ( preg_match( "/^GA/", $this->data[ 'method' ] ) )
      {
         $nodes = $parameters[ 'demes' ] + 1;

         switch ( $cluster )
         {
            case 'lonestar':
            case 'queenbee':  
               $nodes = (int)( ( $nodes + 11 ) / 12 ) * 12;   // 12 nodes per processor
               break;

            case 'ranger':
               $nodes = (int)( ( $nodes * 15 ) / 16 ) * 16;   // 16 nodes per processor
               break;

            default:
               break;
         }
      }
      else  // 2DSA
      {
         /*
         // $k is the number of grid repetitions (equivalently, grid movements) 
         
         $k = pow( $parameters[ 'uniform_grid' ], 2 );
         if ( $k < 2 ) $k++;
 
         $target_util = 0.5;
         $cluster     = $this->data[ 'job' ][ 'cluster_shortname' ];
         
         if ( $cluster == 'hlrb2' ) $target_util = 0.1;
 
         // We are setting $x, the "expected # of solutes from a single application
         // of NNLS to a grid" to be 0.03.  This is not really known, a priori, and
         // is simply an average taken over a variety of problems.  I have a new
         // method based upon sampling (it's in the TG paper soon to be published)
         // - but let's just take .03 to be ok for now.
 
         $x     = 0.03;
         $logx  = log( $x ); // Make efficient: about -3.50655789732
 
         // $r is the number of stages, equation developed in 2DSA paper 
 
         $r     = (int)( ceil( log( $k ) / -$logx ) );
         $nodes = 2;
 
         for ( $p = 2; $p <= $max_nodes; $p++ )
         {
            // $L is the number of stages where there is sufficient 
            // work to occupy $p processors, (2DSA paper),
            // needed for speedup & utilization calcs  
 
            $L     = (int)( log( $k / $p ) / -$logx );
            $util  = ( ( $k / $p ) / ( 1 - $x ) ) / 
                     ( ( $k / $p ) / ( 1 - $x ) + $r - $L );
 
            if ( $util >= $target_util ) $nodes = $p;
         }
         */

         $procs =  ( $parameters[ 'uniform_grid' ] < 12 ) ? 1 : 2;

         switch ( $cluster )
         {
            case 'lonestar':
            case 'queenbee':  
               $nodes = $procs * 12;   // 12 nodes per processor
               break;

            case 'ranger':
               $nodes = $procs * 16;   // 16 nodes per processor
               break;

            default:
               $nodes = $max_nodes;    // bcf
               break;
         }
      }
 
      $nodes = max( $nodes, 2 );             // Minimum nodes is 2
      $nodes = min( $nodes, $max_nodes );    // Maximum nodes depends on cluster
 
      return (int)$nodes;
   }
}
?>
