<?php
/*
 * submit_gfac.php
 *
 * Submits an analysis using the gfac http method
 *
 */
require_once 'config.php';
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
      global $globaldbname, $globaldbhost;

      $cluster     = $this->data[ 'job' ][ 'cluster_shortname' ];
      $hostname    = $this->grid[ $cluster ][ 'name' ];
      $httpport    = $this->grid[ $cluster ][ 'httpport' ];
      $workdir     = $this->grid[ $cluster ][ 'workdir' ];
      $userdn      = $this->grid[ $cluster ][ 'userdn' ];
      $queue       = $this->grid[ $cluster ][ 'queue' ];
      $gfacID      = "US3-" . basename( $this->data['job']['directory'] );
      $dbname      = $this->data[ 'db' ][ 'name' ];

      $ppn         = $this->grid[ $cluster ][ 'ppn' ];
      $nodes       = $this->nodes();
      $cores       = $nodes * $ppn;
      
      $maxWallTime = $this->maxwall();
      //$maxWallTime = "5";      // override for now
 
      $this->data[ 'cores'       ] = $cores;
      $this->data[ 'nodes'       ] = $nodes;
      $this->data[ 'ppn'         ] = $ppn;
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
 
            $writer ->startElement( 'cluster' );
            $writer ->text( $cluster );
            $writer ->endElement();

            $writer ->startElement( 'us3_db' );
            $writer ->text( $dbname );
            $writer ->endElement();

            $writer ->startElement( 'global_db' );
            $writer ->text( $globaldbname );
            $writer ->endElement();

            $writer ->startElement( 'global_host' );
            $writer ->text( $globaldbhost );
            $writer ->endElement();

            $writer ->writeComment( 'experimentid is the same as gfacID' );
            $writer ->startElement( 'experimentid' );
            $writer ->text( $gfacID );
            $writer ->endElement();

            $writer ->startElement( 'processorcount' );
            $writer ->text( $cores );
            $writer ->endElement();
 
            $writer ->startElement( 'hostcount' );
            $writer ->text( $nodes );
            $writer ->endElement();
 
            $writer ->startElement( 'queuename' );
            $writer ->text( $queue );
            $writer ->endElement();
 
            $writer ->startElement( 'walltime' );
            $writer ->text( $maxWallTime );
            $writer ->endElement();
 
            $writer ->startElement( 'userdn' );
            $writer ->text( $userdn );
            $writer ->endElement();
         $writer ->endElement();     // Header
 
         $writer ->startElement( 'Body' );
            $writer ->startElement( 'Method' );
            $writer ->text( 'US3_Run' );
            $writer ->endElement();
 
            $writer ->startElement( 'input' );
            $writer ->text( '' );
            $writer ->endElement();
         $writer ->endElement();     // Body
      $writer ->endElement();        // Message
      $writer ->endDocument();
 
      $this->data[ 'jobxmlfile' ] = $writer->outputMemory( true );
      unset( $writer );

      // Let's output the file while we're in the work directory
      $outputfile = "$cluster-" . basename( $this->data['job']['directory'] ) . "-jobxmlfile.xml";
      file_put_contents( "$outputfile", $this->data['jobxmlfile'] );

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
      $boundary = 'US3-' . basename( $this->data['job']['directory'] );
      $xml      = $this->data['jobxmlfile'];
      $tarFilename = sprintf( "hpcinput-%s-%s-%05d.tar",
                               $this->data['db']['host'],
                               $this->data['db']['name'],
                               $this->data['job']['requestID'] );
      $url     = "$host:$port$path";
      $headers['MIME-Version'] = '1.0';
      $headers['Content-Type'] = "multipart/mixed; boundary=\"$boundary\"";

//$this->message[] = "Headers:";
//$this->message[] = $headers; 
 
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

//$this->message[] = "Httpdata:
//" .  htmlspecialchars( $xml, ENT_QUOTES );
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
      $gfacID     = $this->getGfacID( $epr );
      $xml        = $this->data[ 'jobxmlfile' ];
 
      $link = mysql_connect( $host, $dbusername, $dbpasswd );
 
      if ( ! $link )
      {
         $this->message[] = "Cannot open database on $host\n";
         return;
      }
 
      if ( ! mysql_select_db( $dbname, $link ) ) 
      {
         $this->message[] = "Cannot change to database $dbname\n";
         return;
      }
 
      $gfacID = $this->getGfacID( $epr );

      if ( $gfacID == '' ) // Nothing returned
         $gfacID      = "US3-" . basename( $this->data['job']['directory'] );

      $query = "INSERT INTO HPCAnalysisResult SET "                   .
               "HPCAnalysisRequestID='$requestID', "                  .
               "queueStatus='$status', "                              .
               "jobfile='" . mysql_real_escape_string( $xml ) . "', " .
               "gfacID='$gfacID'";
      
      $result = mysql_query( $query, $link );
 
      if ( ! $result )
      {
         $this->message[] = "Invalid query:\n$query\n" . mysql_error( $result ) . "\n";
         return;
      }
 
      mysql_close( $link );

      // Update global db
      $gfac_link = mysql_connect( $host, $globaldbuser, $globaldbpasswd );

      if ( ! $gfac_link )
      {
         $this->message[] = "Cannot open global database on $host\n";
         return;
      }
 
      if ( ! mysql_select_db( $globaldbname, $gfac_link ) ) 
      {
         $this->message[] = "Cannot change to global database $globaldbname\n";
         return;
      }

      $cluster = $this->data['job']['cluster_shortname'];

      $query   = "INSERT INTO analysis SET " .
                 "gfacID='$gfacID', "        .
                 "cluster='$cluster', "      .
                 "us3_db='$dbname'"; 
      $result  = mysql_query( $query, $gfac_link );
 
      if ( ! $result )
      {
         $this->message[] = "Invalid query:\n$query\n" . mysql_error( $result ) . "\n";
         return;
      }
 
      mysql_close( $gfac_link );
$this->message[] = "Global database $globaldbname updated: gfacID = $gfacID";
   }

   // function to extract the experimentID from the EPR file
   function getGfacID( $epr )
   {
      $gfacID = '';

      $parser = new XMLReader();
      $parser->xml( $epr );

      while( $parser->read() )
      {
         $type = $parser->nodeType;

         if ( $type == XMLReader::TEXT )
         {
            $gfacID = $parser->value;
            break;
         }
      }

      $parser->close();      
      return $gfacID;
   }
}
?>
