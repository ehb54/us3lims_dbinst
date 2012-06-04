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
      global $globaldbname;

      if ( $globaldbname == 'gfac2' )
          ; // Add logic to change port from 8080 to 8081

      $this->grid[ 'bcf' ] = array 
      (
        "name"       => "bcf.uthscsa.edu",
        "submithost" => "http://gf5.ucs.indiana.edu",
        "userdn"     => "/C=US/O=National Center for Supercomputing Applications/CN=Ultrascan3 Community User",
        "submittype" => "http",
        "httpport"   => 8080,
        "workdir"    => "/ogce-rest/job/runjob/async",
        "sshport"    => 22,
        "queue"      => "default",
        "maxtime"    => 60000,
        "ppn"        => 2,
        "maxproc"    => 26
      );
    
      $this->grid[ 'bcf-local' ] = array 
      (
        "name"       => "bcf.uthscsa.edu",
        "submittype" => "local",
        "workdir"    => "/home/us3/work/",  // Need trailing slash
        "sshport"    => 22,
        "queue"      => "default",
        "maxtime"    => 60000,
        "ppn"        => 2,
        "maxproc"    => 26
      );

      $this->grid[ 'alamo' ] = array 
      (
        "name"       => "alamo.uthscsa.edu",
        "submithost" => "http://gf5.ucs.indiana.edu",
        "userdn"     => "/C=US/O=National Center for Supercomputing Applications/CN=Ultrascan3 Community User",
        "submittype" => "http",
        "httpport"   => 8080,
        "workdir"    => "/ogce-rest/job/runjob/async",
        "sshport"    => 22,
        "queue"      => "default",
        "maxtime"    => 90000,
        "ppn"        => 4,
        "maxproc"    => 52
      );
    
      $this->grid[ 'alamo-local' ] = array 
      (
        "name"       => "alamo.uthscsa.edu",
        "submittype" => "local",
        "workdir"    => "/home/us3/work/",  // Need trailing slash
        "sshport"    => 22,
        "queue"      => "",
        "maxtime"    => 90000,
        "ppn"        => 4,
        "maxproc"    => 52
      );

      $this->grid[ 'ranger' ] = array 
      (
        "name"       => "gatekeeper.ranger.tacc.teragrid.org",
        "submithost" => "http://gf5.ucs.indiana.edu",
        "userdn"     => "/C=US/O=National Center for Supercomputing Applications/CN=Ultrascan3 Community User",
        "submittype" => "http",
        "httpport"   => 8080,
        "workdir"    => "/ogce-rest/job/runjob/async",
        "sshport"    => 22,
        "queue"      => "normal",
        "maxtime"    => 1440,   // This is overridden in maxwall() if using the long queue
        "ppn"        => 16,
        "maxproc"    => 64
      );
    
      $this->grid[ 'lonestar' ] = array 
      (
        "name"       => "lonestar.tacc.teragrid.org",
        "submithost" => "http://gf5.ucs.indiana.edu",
        "userdn"     => "/C=US/O=National Center for Supercomputing Applications/CN=Ultrascan3 Community User",
        "submittype" => "http",
        "httpport"   => 8080,
        "workdir"    => "/ogce-rest/job/runjob/async",
        "sshport"    => 22,
        "queue"      => "normal",
        "maxtime"    => 1440,
        "ppn"        => 12,
        "maxproc"    => 36
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
                  $job[ 'cluster_queue'     ] = $parser->getAttribute( 'queue' );
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
      $queue      = $this->data[ 'job' ][ 'cluster_queue' ];
      $max_time   = $this->grid[ $cluster ][ 'maxtime' ];
 
      // Override max time in the case of the long queue on ranger
      if ( $cluster == 'ranger' &&
           $queue   == 'long'   )
         $max_time = 2880;

      if ( preg_match( "/^GA/", $this->data[ 'method' ] ) )
      {
         // Assume 1 sec a basic unit

         $generations = $parameters[ 'generations' ];
         $population  = $parameters[ 'population' ];

         // The constant 125 is an empirical value from doing a Hessian
         // minimization

         $time        = ( 125 + $population ) * $generations;
 
         $time *= 1.5;  // Pad things a bit
         $time  = (int)( ($time + 59) / 60 ); // Round up to minutes
      }
      else // 2DSA
      {
         $ti_noise   = isset( $parameters[ 'tinoise_option' ] )
                       ? $parameters[ 'tinoise_option' ] > 0 
                       : false;
    
         $ri_noise   = isset( $parameters[ 'rinoise_option' ] )
                       ? $parameters[ 'rinoise_option' ] > 0
                       : false;
 
         $time       = 20;  // Base time in minutes

         if ( isset( $parameters[ 'meniscus_points' ] ) )
         {
            $points = $parameters[ 'meniscus_points' ];
            if ( $points > 0 )  $time *= $points;
         }
    
         if ( $ti_noise || $ri_noise ) $time *= 2;
      }
 
      if ( isset( $parameters[ 'mc_iterations' ] ) )
      {
         $montecarlo = $parameters[ 'mc_iterations' ];
         if ( $montecarlo > 0 )  $time *= $montecarlo;
      }

      if ( isset( $parameters[ 'max_iterations' ] ) )
      {
         $mxiters = $parameters[ 'max_iterations' ];
         if ( $mxiters > 0 )  $time *= $mxiters;
      }

      $time *= 1.5;  // Padding
 
      // Account for parallel group count in max walltime
      $mgroupcount = $this->data[ 'job' ][ 'mgroupcount' ];

      if ( $cluster == 'alamo' || $cluster == 'alamo-local' )
      {
         // For alamo, $max_time is hardwired to 90000, and no PMG
         $time = $max_time;
         $mgroupcount = 1;
      }

      else if ( $cluster == 'bcf' || $cluster == 'bcf-local' )
      {
         // For bcf, hardwire $max_time to 120 (2 hours), and no PMG
         $time = 120;
         $mgroupcount = 1;
      }

      switch ( $mgroupcount )
      {
         case 2  :
            $time = (int)( $time * 10 / 15 );
            break;

         case 4  :
            $time = (int)( $time * 10 / 35 );
            break;

         case 8  :
            $time = (int)( $time * 10 / 75 );
            break;

         case 1  :
         default :
            break;
      }

      $time = max( $time, 5 );         // Minimum time is 5 minutes
      $time = min( $time, $max_time ); // Maximum time is defined for each cluster
 
      return (int)$time;
   }
 
   function nodes()
   {
      $cluster    = $this->data[ 'job' ][ 'cluster_shortname' ];
      $parameters = $this->data[ 'job' ][ 'jobParameters' ];
      $max_procs  = $this->grid[ $cluster ][ 'maxproc' ];
      $ppn        = $this->grid[ $cluster ][ 'ppn'     ];
 
      if ( preg_match( "/^GA/", $this->data[ 'method' ] ) )
      {  // GA: procs is demes+1 rounded to procs-per-node
         $procs = $parameters[ 'demes' ] + $ppn;  // Procs = demes+1
         $procs = (int)( $procs / $ppn ) * $ppn;  // Rounded to procs-per-node
      }
      else  // 2DSA
      {  // 2DSA:  procs is max_procs, but no more than subgrid count
         $gsize = $parameters[ 'uniform_grid' ];
         $gsize = $gsize * $gsize;           // Subgrid count
         $procs = min( $max_procs, $gsize ); // Procs = max or subgrid count
      }

      $procs = max( $procs, 4 );             // Minimum procs is 4
      $procs = min( $procs, $max_procs );    // Maximum procs depends on cluster

      $nodes = $procs / $ppn;    // Return nodes, procs divided by procs-per-node
      return (int)$nodes;
   }

   function max_mgroupcount()
   {
      $cluster    = $this->data[ 'job' ][ 'cluster_shortname' ];
      $max_procs  = $this->grid[ $cluster ][ 'maxproc' ];
      $max_time   = $this->grid[ $cluster ][ 'maxtime' ];
      $nodes      = $this->nodes();

      $groups = $max_procs / $nodes;

      if ( $max_time > 50000 )
      {  // "Unlimited" max time (bcf,alamo) means no PMG
        $groups = 1;
      }

      // Convert to 1, 2, 4, or 8
      $power      = (int) floor( log( $groups, 2 ) );
      $max_groups = min( 8, pow( 2, $power ) );

      return $max_groups;
   }
}
?>
