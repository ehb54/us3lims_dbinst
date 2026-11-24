<?php
/*
 * supporting_files_proc.php
 *
 * Server side of supporting_files.php: a user's own documents
 */
include_once 'checkinstance.php';

if ( ! isset( $_SESSION['id'], $_SESSION['userlevel'] ) || $_SESSION['userlevel'] < 1 )
{
  http_response_code( 403 );
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';

const SF_MAX_SIZE = 52428800;   // The limit in js/supporting_files.js

$PID    = (int) $_SESSION['id'];
$action = $_POST['action'] ?? ( isset( $_GET['docID'] ) ? 'SEND_DOC' : '' );

switch ( $action )
{
  case 'GET_BLOB'       : sf_receive_chunk( $PID );               break;
  case 'DEL_FILE'       : sf_drop_upload( $PID );                 break;
  case 'NEW_DOC'        : echo json_encode( sf_new( $link, $PID ) );     break;
  case 'UPDATE_DOC'     : echo json_encode( sf_update( $link, $PID ) );  break;
  case 'DEL_DOC'        : echo json_encode( sf_delete( $link, $PID ) );  break;
  case 'GIVE_BLOB'      : echo json_encode( sf_locate( $link, $PID ) );  break;
  case 'SEND_DOC'       : sf_send( $link, $PID );                 break;
  case 'GIVE_DOC_INFO'  : echo json_encode( sf_documents( $link, $PID ) ); break;
  case 'GIVE_INIT_INFO' :
    $info = array();
    foreach ( array( 'project', 'solution', 'buffer', 'analyte' ) as $class )
      $info[ $class ] = sf_choices( $link, $PID, $class );
    $info['image_info'] = sf_documents( $link, $PID );
    echo json_encode( $info );
    break;
}

sf_clean_uploads();
exit();

// Function to run a prepared statement; returns array( error or null, rows, insert ID )
function sf_query( $link, $query, $types, ...$args )
{
  $stmt = mysqli_prepare( $link, $query );
  if ( ! $stmt ) return array( $link->error, array(), 0 );
  $stmt->bind_param( $types, ...$args );
  if ( ! $stmt->execute() ) return array( $stmt->error, array(), 0 );
  $result = $stmt->get_result();
  $rows   = $result ? $result->fetch_all( MYSQLI_ASSOC ) : array();
  $ID     = $stmt->insert_id;
  $stmt->close();
  return array( null, $rows, $ID );
}

// Function to return a request value, or null where the page sends none
function sf_post( $name )
{
  $value = $_POST[ $name ] ?? 'null';
  return ( is_string( $value ) && $value !== 'null' ) ? $value : null;
}

// Function to split an "old_new" request value into two values, either of them null
function sf_pair( $value )
{
  $parts = array_pad( explode( '_', (string) $value, 2 ), 2, 'null' );
  foreach ( $parts as $i => $part )
    if ( $part === 'null' ) $parts[ $i ] = null;
  return $parts;
}

// The link table and ID column of each class a document can be attached to
function sf_link_table( $class )
{
  $tables = array( 'project'  => array( 'imageProject',  'projectID',  'projectPerson' ),
                   'solution' => array( 'imageSolution', 'solutionID', 'solutionPerson' ),
                   'buffer'   => array( 'imageBuffer',   'bufferID',   'bufferPerson' ),
                   'analyte'  => array( 'imageAnalyte',  'analyteID',  'analytePerson' ) );
  return $tables[ $class ] ?? null;
}

function sf_owns_document( $link, $PID, $imageID )
{
  list( , $rows ) = sf_query( $link, "SELECT 1 FROM imagePerson WHERE imageID = ? AND personID = ? ",
                              'ii', $imageID, $PID );
  return count( $rows ) > 0;
}

// Function to add or remove a link; only to something the page offers this user. Returns OK or an error
function sf_set_link( $link, $PID, $imageID, $class, $oldID, $newID )
{
  $table = sf_link_table( $class );
  if ( $table === null ) return "Error : unknown category";
  list( $linkTable, $column, $personTable ) = $table;

  if ( $newID !== null )
  {
    list( , $rows ) = sf_query( $link, "SELECT 1 FROM $personTable WHERE $column = ? AND personID = ? ",
                                'ii', $newID, $PID );
    if ( ! $rows ) return "Error : not permitted";
  }
  if ( $oldID !== null )
  {
    list( $error ) = sf_query( $link, "DELETE FROM $linkTable WHERE imageID = ? AND $column = ? ",
                               'ii', $imageID, $oldID );
    if ( $error !== null ) return "Error : $error";
  }
  if ( $newID !== null )
  {
    list( $error ) = sf_query( $link, "INSERT INTO $linkTable SET imageID = ?, $column = ? ",
                               'ii', $imageID, $newID );
    if ( $error !== null ) return "Error : $error";
  }
  return "OK";
}

function sf_choices( $link, $PID, $class )
{
  list( , $column, $personTable ) = sf_link_table( $class );
  list( $error, $rows ) = sf_query( $link,
      "SELECT t.$column, t.description FROM $class AS t, $personTable AS p " .
      "WHERE p.$column = t.$column AND p.personID = ? ", 'i', $PID );
  return array( 'error' => $error, 'data' => $rows ? $rows : null );
}

function sf_documents( $link, $PID )
{
  list( $error, $rows ) = sf_query( $link,
      "SELECT I.imageID, I.imageGUID, I.description, I.filename " .
      "FROM image AS I, imagePerson AS IP " .
      "WHERE I.imageID = IP.imageID AND IP.personID = ? ", 'i', $PID );

  foreach ( array( 'project', 'solution', 'buffer', 'analyte' ) as $class )
  {
    list( $table, $column ) = sf_link_table( $class );
    list( , $links ) = sf_query( $link,
        "SELECT L.imageID, L.$column FROM $table AS L, imagePerson AS IP " .
        "WHERE L.imageID = IP.imageID AND IP.personID = ? ", 'i', $PID );
    $linked = array_column( $links, $column, 'imageID' );
    foreach ( $rows as $i => $row )
      $rows[ $i ][ $column ] = $linked[ $row['imageID'] ] ?? null;
  }

  return array( 'error' => $error, 'data' => $rows ? $rows : null );
}

// Uploads wait outside the web folder, under a name that carries their user
// Returns null if the folder is a link or open to other accounts
function sf_upload_dir()
{
  global $dbname;
  $dir = sys_get_temp_dir() . '/us3_supporting_files_' . $dbname;
  if ( ! is_dir( $dir ) ) @mkdir( $dir, 0700 );
  if ( is_link( $dir ) || ! is_dir( $dir ) || ( fileperms( $dir ) & 0077 ) ) return null;
  return $dir;
}

// The page names an upload with digits only; anything else is refused
function sf_upload_path( $PID, $name )
{
  if ( ! is_string( $name ) || ! preg_match( '/^[0-9]{1,40}$/D', $name ) ) return null;
  $dir = sf_upload_dir();
  return ( $dir === null ) ? null : "$dir/{$PID}_$name";
}

function sf_receive_chunk( $PID )
{
  $path = sf_upload_path( $PID, $_POST['filename'] ?? null );
  $data = is_string( $_POST['data'] ?? null ) ? base64_decode( $_POST['data'], true ) : false;
  if ( $path === null || $data === false ||
       ( is_file( $path ) ? filesize( $path ) : 0 ) + strlen( $data ) > SF_MAX_SIZE ||
       file_put_contents( $path, $data, FILE_APPEND ) === false )
  {
    http_response_code( 400 );
    echo "Not accepted";
    return;
  }
  echo "OK";
}

function sf_drop_upload( $PID )
{
  $path = sf_upload_path( $PID, $_POST['filename'] ?? null );
  if ( $path === null || ! is_file( $path ) )
  {
    echo "Not found";
    return;
  }
  unlink( $path );
  echo "OK";
}

// Function to read the user's finished upload and remove it, or null
function sf_take_upload( $PID, $name )
{
  $path = sf_upload_path( $PID, $name );
  if ( $path === null || ! is_file( $path ) ) return null;
  $blob = file_get_contents( $path );
  unlink( $path );
  return $blob;
}

// Function to remove uploads that were never finished
function sf_clean_uploads()
{
  $dir = sf_upload_dir();
  if ( $dir === null ) return;
  foreach ( glob( "$dir/*" ) ?: array() as $file )
    if ( time() - (int) @filemtime( $file ) >= 1800 ) @unlink( $file );
}

function sf_new( $link, $PID )
{
  $output = array( "blob" => "OK", "image" => "OK", "imagePerson" => "OK",
                   "imageProject" => "OK", "imageClass" => "OK" );

  $blob = sf_take_upload( $PID, sf_post( 'local_filename' ) );
  if ( $blob === null )
  {
    $output["blob"] = "Error: File not found !";
    return $output;
  }

  $imageGUID   = uuid();
  $description = (string) sf_post( 'description' );
  $filename    = (string) sf_post( 'filename' );
  list( $error, , $imageID ) = sf_query( $link,
      "INSERT INTO image SET imageGUID = ?, description = ?, gelPicture = ?, filename = ? ",
      'ssss', $imageGUID, $description, $blob, $filename );
  if ( $error !== null )
  {
    $output["image"] = "Error : $error";
    return $output;
  }

  list( $error ) = sf_query( $link, "INSERT INTO imagePerson SET imageID = ?, personID = ? ", 'ii', $imageID, $PID );
  if ( $error !== null ) $output["imagePerson"] = "Error : $error";

  if ( sf_post( 'projectID' ) !== null )
    $output["imageProject"] = sf_set_link( $link, $PID, $imageID, 'project', null, sf_post( 'projectID' ) );
  if ( sf_post( 'class' ) !== null && sf_post( 'subclassID' ) !== null )
    $output["imageClass"] = sf_set_link( $link, $PID, $imageID, sf_post( 'class' ), null, sf_post( 'subclassID' ) );

  return $output;
}

function sf_update( $link, $PID )
{
  $output  = array( "blob" => "OK", "image" => "OK", "imageProject" => "OK", "imageClass" => "OK" );
  $imageID = (int) sf_post( 'docID' );

  if ( ! sf_owns_document( $link, $PID, $imageID ) )
  {
    $output["image"] = "Error : not permitted";
    return $output;
  }

  if ( sf_post( 'local_filename' ) !== null )
  {
    $blob = sf_take_upload( $PID, sf_post( 'local_filename' ) );
    if ( $blob === null )
    {
      $output["blob"] = "Error: File not found !";
      return $output;
    }
    $filename = (string) sf_post( 'filename' );
    list( $error ) = sf_query( $link, "UPDATE image SET filename = ?, gelPicture = ? WHERE imageID = ? ",
                               'ssi', $filename, $blob, $imageID );
    if ( $error !== null ) $output["image"] = "Error : $error";
  }

  $description = sf_post( 'description' );
  if ( $description !== null )
  {
    list( $error ) = sf_query( $link, "UPDATE image SET description = ? WHERE imageID = ? ", 'si', $description, $imageID );
    if ( $error !== null ) $output["image"] = "Error : $error";
  }

  if ( sf_post( 'projectID' ) !== null )
  {
    list( $oldID, $newID ) = sf_pair( sf_post( 'projectID' ) );
    $output["imageProject"] = sf_set_link( $link, $PID, $imageID, 'project', $oldID, $newID );
  }

  if ( sf_post( 'class' ) !== null && sf_post( 'subclassID' ) !== null )
  {
    list( $oldClass, $newClass ) = sf_pair( sf_post( 'class' ) );
    $subclassID = sf_post( 'subclassID' );
    if ( $oldClass === null )
      list( $oldID, $newID ) = array( null, $subclassID );
    else if ( $newClass === null )
      list( $oldID, $newID ) = array( $subclassID, null );
    else
      list( $oldID, $newID ) = sf_pair( $subclassID );

    if ( $oldClass === $newClass )
      $output["imageClass"] = sf_set_link( $link, $PID, $imageID, $newClass, $oldID, $newID );
    else
    {
      // Add before removing, so a refused new link leaves the old one
      if ( $newClass !== null )
        $output["imageClass"] = sf_set_link( $link, $PID, $imageID, $newClass, null, $newID );
      if ( $oldClass !== null && $output["imageClass"] === "OK" )
        $output["imageClass"] = sf_set_link( $link, $PID, $imageID, $oldClass, $oldID, null );
    }
  }

  return $output;
}

function sf_delete( $link, $PID )
{
  $output  = array( "image" => "OK", "imagePerson" => "OK", "imageProject" => "OK", "imageClass" => "OK" );
  $imageID = (int) sf_post( 'docID' );

  if ( ! sf_owns_document( $link, $PID, $imageID ) )
  {
    $output["image"] = "Error : not permitted";
    return $output;
  }

  list( $error ) = sf_query( $link,
      "UPDATE image SET imageGUID = NULL, description = '', gelPicture = '', filename = '' " .
      "WHERE imageID = ? ", 'i', $imageID );
  if ( $error !== null ) $output["image"] = "Error : $error";

  list( $error ) = sf_query( $link, "DELETE FROM imagePerson WHERE imageID = ? AND personID = ? ", 'ii', $imageID, $PID );
  if ( $error !== null ) $output["imagePerson"] = "Error : $error";

  if ( sf_post( 'projectID' ) !== null )
    $output["imageProject"] = sf_set_link( $link, $PID, $imageID, 'project', sf_post( 'projectID' ), null );
  if ( sf_post( 'class' ) !== null && sf_post( 'subclassID' ) !== null )
    $output["imageClass"] = sf_set_link( $link, $PID, $imageID, sf_post( 'class' ), sf_post( 'subclassID' ), null );

  return $output;
}

// Function to tell the page where to fetch a document; this file sends it
function sf_locate( $link, $PID )
{
  $imageID = (int) sf_post( 'docID' );
  $doc     = array( "error" => null, "path" => null, "size" => null );
  if ( ! sf_owns_document( $link, $PID, $imageID ) ) return $doc;

  list( $error, $rows ) = sf_query( $link, "SELECT LENGTH( gelPicture ) AS size FROM image WHERE imageID = ? ", 'i', $imageID );
  $doc["error"] = $error;
  if ( $rows && $rows[0]['size'] )
  {
    $doc["path"] = "supporting_files_proc.php?docID=$imageID";
    $doc["size"] = (int) $rows[0]['size'];
  }
  return $doc;
}

function sf_send( $link, $PID )
{
  $imageID = is_string( $_GET['docID'] ) ? (int) $_GET['docID'] : 0;
  list( , $rows ) = sf_owns_document( $link, $PID, $imageID )
      ? sf_query( $link, "SELECT gelPicture FROM image WHERE imageID = ? ", 'i', $imageID )
      : array( null, array() );
  if ( ! $rows )
  {
    http_response_code( 403 );
    return;
  }
  header( "Content-Type: application/octet-stream" );
  header( "Content-Disposition: attachment" );
  header( "X-Content-Type-Options: nosniff" );
  header( "Cache-Control: private, no-store" );
  echo $rows[0]['gelPicture'];
}
