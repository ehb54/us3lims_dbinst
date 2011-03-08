<?php
/*
 * submit_gfac.php
 *
 * Submits an analysis using the gfac http method
 *
 */
require_once 'lib/jobsubmit.php';

class submit_gfac extends jobsubmit
{
   // Submits data
   function submit()
   {
      $savedir = getcwd();
      chdir( $this->data['job']['directory'] );
      $this->create_job_xml();
      $this->submit_job    ();
      $this->update_db     ();
      chdir( $savedir );
$this->message[] = "End of submit_gfac.php\n";
   }
 
   // Function to create the job xml 
   function create_job_xml()
   {
      $cluster     = $this->data[ 'job' ][ 'cluster_shortname' ];
      $hostname    = $this->grid[ $cluster ][ 'name' ];
      $httpport    = $this->grid[ $cluster ][ 'httpport' ];
      $workdir     = $this->grid[ $cluster ][ 'workdir' ];
      $userdn      = $this->grid[ $cluster ][ 'userdn' ];
      $queue       = $this->grid[ $cluster ][ 'queue' ];
     
      $cores       = $this->nodes();
      //$cores       = "16";    // override for now
      
      $hostCount   = ceil( $cores / 4 );   // Only bigred
      $maxWallTime = $this->maxwall();
      //$maxWallTime = "60";      // override for now
 
      $this->data[ 'cores'       ] = $cores;
      $this->data[ 'maxWallTime' ] = $maxWallTime;
 
      $writer = new XMLWriter();
      $writer ->openMemory();
      $writer ->setIndent( true );
      $writer ->startDocument( '1.0', 'UTF-8' );
      $writer ->startElement( 'Message' );
         $writer ->startElement( 'Header' );
            $writer ->startElement( 'hostname' );
            $writer ->text( $hostname );
            $writer ->endElement();
 
            $writer ->startElement( 'processorcount' );
            $writer ->text( $cores );
            $writer ->endElement();
 
            if ( $cluster == 'bigred' )
            {
               $writer ->startElement( 'hostcount' );
               $writer ->text( $hostCount );
               $writer ->endElement();
            }
 
            $writer ->startElement( 'queuename' );
            $writer ->text( $queue );
            $writer ->endElement();
 
            $writer ->startElement( 'walltime' );
            $writer ->text( $maxWallTime );
            $writer ->endElement();
 
            $writer ->startElement( 'userdn' );
            $writer ->text( $userdn );
            $writer ->endElement();
         $writer ->endElement();     // hostname
 
         $writer ->startElement( 'Body' );
            $writer ->startElement( 'Method' );
            $writer ->text( 'US3_Run' );
            $writer ->endElement();
 
            $writer ->startElement( 'input' );
            $writer ->text( '' );
            $writer ->endElement();
         $writer ->endElement();     // body
      $writer ->endElement();        // message
      $writer ->endDocument();
 
      $this->data[ 'jobxmlfile' ] = $writer->outputMemory( true );
      unset( $writer );
$this->message[] = "Job xml created";
   }
 
   // Schedule the job
   function submit_job()
   {
      date_default_timezone_set( "America/Chicago" );
 
      $cluster  = $this->data['job']['cluster_shortname'];
      $host     = $this->grid[ $cluster ]['submithost'];
      $port     = $this->grid[ $cluster ]['httpport'];
      $path     = $this->grid[ $cluster ]['workdir'];
      $boundary = "US3:" . basename( $this->data['job']['directory'] );
      $xml      = $this->data['jobxmlfile'];
      $tarFilename = sprintf( "hpcinput-%s-%s-%05d.tar",
                               $this->data['db']['host'],
                               $this->data['db']['name'],
                               $this->data['job']['requestID'] );
      $url     = "$host:$port$path";
      $headers['MIME-Version'] = '1.0';
      $headers['Content-Type'] = "multipart/mixed; boundary=\"$boundary\"";

$this->message[] = "URL: $url";
$this->message[] = "Headers:";
$this->message[] = $headers; 
 
      // Build the data stream
      $httpdata = "--$boundary
Content-Type: text/xml; charset=iso-8859-1

$xml

";
 
      $httpdata .= "--$boundary
Content-Type: application/octet-stream; name=\"$tarFilename\"
Content-Transfer-Encoding: base64

";
 
      // Now the tar file
      $fp = fopen( $tarFilename, "rb" ); //Open it
      $tarfile = fread( $fp, filesize( $tarFilename ) );
      $httpdata .= chunk_split( base64_encode( $tarfile ) );
      fclose( $fp );
 
      // It is important to have the embedded newlines below.
      $httpdata .= "
--$boundary
";

$this->message[] = "Httpdata:
" .  htmlspecialchars( $xml, ENT_QUOTES );
//$this->message[] = $httpdata;
 
      // Now make the request
      $post = new HttpRequest( $url, HTTP_METH_POST );
      
      $post->setHeaders( $headers );
      $post->setBody( $httpdata );
      
      try
      {
        $result = $post->send();
        $this->data['eprfile'] = $result->getBody();  
        $this->data['dataset']['status'] = 'queued';
      }
      catch ( HttpException $e )
      {
        $this->message[] = $e;
        $this->data['dataset']['status'] = 'failed';
      }

$this->message[] = "Job submitted";

      // Process the return info
$this->message[] = "Result text:";
$epr = htmlspecialchars( $this->data['eprfile'], ENT_QUOTES );

$this->message[] = preg_replace( "/&gt;/", "&gt;\n", $epr );
$this->message[] = "End of result text";

   }
 
   function update_db()
   {
      global $dbusername;
      global $dbpasswd;
      global $globaldbname;
      global $globaldbuser;
      global $globaldbpasswd;

      $requestID  = $this->data[ 'job' ][ 'requestID' ];
      $dbname     = $this->data[ 'db' ][ 'name' ];
      $host       = $this->data[ 'db' ][ 'host' ];
      $user       = $this->data[ 'db' ][ 'user' ];
      $status     = $this->data[ 'dataset' ][ 'status' ];
      $epr        = $this->data[ 'eprfile' ];
      $analysisID = $this->getAnalysisID( $epr );
      $xml        = $this->data[ 'jobxmlfile' ];
 
      $db = mysql_connect( $host, $dbusername, $dbpasswd );
 
      if ( ! $db )
      {
         $this->message[] = "Cannot open database on $host\n";
         //exit( 1 );
      }
 
      if ( ! mysql_select_db( $dbname, $db ) ) 
      {
         $this->message[] = "Cannot change to database $dbname\n";
         //exit( 2 );
      }
 
      $query = "insert into HPCAnalysisResult set "                   .
               "HPCAnalysisRequestID='$requestID', "                  .
               "queueStatus='$status', "                              .
               "updateTime=now(), "                                   .
               "jobfile='" . mysql_real_escape_string( $xml ) . "', " .
               "gfacID='$analysisID'";
      
      $result = mysql_query( $query, $db );
 
      if ( ! $result )
      {
         $this->message[] = "Invalid query: " . mysql_error( $db ) . "\n";
      }
 
      mysql_close( $db );
$this->message[] = "Database $dbname updated: requestID = $requestID";

      // Update global db
      $db = mysql_connect( $host, $globaldbuser, $globaldbpasswd );

      if ( ! $db )
      {
         $this->message[] = "Cannot open global database on $host\n";
      }
 
      if ( ! mysql_select_db( $globaldbname, $db ) ) 
      {
         $this->message[] = "Cannot change to global database $globaldbname\n";
      }

      $analysisID = $this->getAnalysisID( $epr );
$this->message[] = "ExperimentID extracted from EPR file = '$analysisID'\n";

      $cluster = $this->data['job']['cluster_shortname'];
      $query   = "INSERT INTO analysis SET gfacID='$analysisID', cluster='$cluster'"; 
      $result  = mysql_query( $query, $db );
 
      if ( ! $result )
      {
         $this->message[] = "Invalid query: " . mysql_error( $db ) . "\n";
         //exit( 4 );
      }
 
      mysql_close( $db );
$this->message[] = "Global database $globaldbname updated: analysisID = $analysisID";
   }

   // function to extract the experimentID from the EPR file
   function getAnalysisID( $epr )
   {
      $analysisID = '';

      $parser = new XMLReader();
      $parser->xml( $epr );

      while( $parser->read() )
      {
         $type = $parser->nodeType;

         if ( $type == XMLReader::TEXT )
         {
            $analysisID = $parser->value;
            break;
         }
      }

      $parser->close();      
      return $analysisID;
   }
}
?>
