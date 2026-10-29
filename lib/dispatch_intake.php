<?php
// Metascheduler intake for the submit pages. A submission to an active pool
// records each HPC request it creates together with its dispatch row in one
// instance-database transaction; the dispatch worker submits them later.
// require_cluster.php decides which submissions come here; a manually chosen
// cluster never does, and every function here is a no-op for it.

include_once $class_dir . 'dispatch_ledger.php';

function dispatch_intake_active()
{
  return ! empty( $_SESSION['cluster']['dispatch'] );
}

// Whether this page recorded and committed a dispatch submission.
function dispatch_intake_received()
{
  return ! empty( $GLOBALS['dispatch_intake_committed'] );
}

// The submission's key, fixed before any request ID exists: an autoflow stage
// occurrence set by submitone.php, else a nonce made once per selection.
function dispatch_intake_nonce()
{
  if ( ! empty( $_SESSION['dispatch_intake_key'] ) )
    return $_SESSION['dispatch_intake_key'];
  if ( empty( $_SESSION['cluster']['nonce'] ) )
    $_SESSION['cluster']['nonce'] = bin2hex( random_bytes( 16 ) );
  return $_SESSION['cluster']['nonce'];
}

// Start this page's intake for the analysis $input (its payload, which
// identifies it apart from request IDs). Returns array( 'receipt' => rows )
// for a submission already received with the same input, array( 'error' => )
// when its key was used for different input, else array() after opening the
// transaction that will hold every request this page creates.
function dispatch_intake_begin( $link, $dbname, array $input )
{
  unset( $input['cluster'] );
  $checksum = hash( 'sha256', json_encode( $input ) );
  $GLOBALS['dispatch_intake_pool'] = $_SESSION['cluster']['shortname'];
  $prior = dispatch_intake_existing( $link, $dbname, $_SESSION['loginID'], dispatch_intake_nonce() );
  if ( $prior )
  {
    unset( $_SESSION['cluster'] );
    if ( $prior[0]['intakeChecksum'] !== $checksum )
      return array( 'error' => 'This submission key was already used for a different analysis; nothing was submitted.' );
    $GLOBALS['dispatch_intake_committed'] = true;
    return array( 'receipt' => $prior );
  }
  $GLOBALS['dispatch_intake_rows'] = array();
  $GLOBALS['dispatch_intake_errors'] = array();
  $GLOBALS['dispatch_intake_checksum'] = $checksum;
  mysqli_begin_transaction( $link );
  return array();
}

// Whether the service account can read a file the web server wrote and write
// its directory, where staging finalizes the archive and the job script.
function dispatch_intake_worker_can_read( $path )
{
  if ( ! is_file( $path ) || ! is_readable( $path ) )
    return false;
  $us3 = function_exists( 'posix_getpwnam' ) ? posix_getpwnam( 'us3' ) : false;
  if ( ! $us3 )
    return true;
  foreach ( array( $path, dirname( $path ) ) as $p )
  {
    $mode = fileperms( $p );
    $group = function_exists( 'posix_getgrgid' ) ? posix_getgrgid( filegroup( $p ) ) : false;
    $in_group = filegroup( $p ) === $us3['gid'] || ( $group && in_array( 'us3', $group['members'], true ) );
    $need = $p === $path ? 4 : 3;
    $have = fileowner( $p ) === $us3['uid'] ? ( $mode >> 6 ) & 7
          : ( $in_group ? ( $mode >> 3 ) & 7 : $mode & 7 );
    if ( ( $have & $need ) !== $need )
      return false;
  }
  return true;
}

// Record one request the page created from its XML $filename. A failure
// clears $files_ok and is added to the page's $missit_msg.
function dispatch_intake_note( $link, $dbname, $HPCAnalysisRequestID, $filename, &$files_ok, &$missit_msg )
{
  if ( ! dispatch_intake_active() || ! $files_ok )
    return;
  $error = dispatch_intake_add( $link, $dbname, $HPCAnalysisRequestID, $filename );
  if ( $error !== '' )
  {
    $files_ok = false;
    $missit_msg .= '<br/>' . htmlspecialchars( $error, ENT_QUOTES );
  }
}

// Record one request; returns an error message, or '' on success.
function dispatch_intake_add( $link, $dbname, $HPCAnalysisRequestID, $filename )
{
  global $is_cli, $ID;

  if ( ! dispatch_intake_worker_can_read( $filename ) )
    return "The analysis input $filename cannot be used by the submission service; nothing was submitted.";
  $nonce = dispatch_intake_nonce();
  list( $id, $created ) = dispatch_intake( $link, $dbname, array(
    'logicalKey'           => dispatch_intake_key( $dbname, $_SESSION['loginID'], $nonce,
                                                   count( $GLOBALS['dispatch_intake_rows'] ) ),
    'HPCAnalysisRequestID' => $HPCAnalysisRequestID,
    'autoflowAnalysisID'   => ( $is_cli && ! empty( $ID ) ) ? (int) $ID : null,
    'stageName'            => $is_cli ? 'autoflow' : 'interactive',
    'parentBatchKey'       => dispatch_intake_batch_key( $dbname, $_SESSION['loginID'], $nonce ),
    'batchSealed'          => 0,
    'submitterID'          => $_SESSION['loginID'],
    'investigatorID'       => $_SESSION['id'],
    'pool'                 => $_SESSION['cluster']['shortname'],
    'inputRef'             => $filename,
    'inputChecksum'        => hash_file( 'sha256', $filename ),
    'intakeChecksum'       => $GLOBALS['dispatch_intake_checksum'],
    'priorityDirective'    => function_exists( 'priority_nice_string' ) ? priority_nice_string() : '',
  ) + dispatch_intake_wait_settings() );
  if ( $id <= 0 )
    return 'The request could not be recorded for dispatch; nothing was submitted.';
  if ( ! $created )
    return 'This submission was already received.';
  $GLOBALS['dispatch_intake_rows'][] = array( 'dispatchRequestID' => $id, 'HPCAnalysisRequestID' => $HPCAnalysisRequestID );
  return '';
}

// The installation's wait notification and maximum dispatch wait, in seconds.
function dispatch_intake_wait_settings()
{
  return array(
    'notify_seconds'   => isset( $GLOBALS['global_dispatch_notify_seconds'] )
                          ? (int) $GLOBALS['global_dispatch_notify_seconds'] : 3600,
    'deadline_seconds' => isset( $GLOBALS['global_dispatch_max_wait_seconds'] )
                          ? (int) $GLOBALS['global_dispatch_max_wait_seconds'] : 86400,
  );
}

// Finish the page's intake: with $files_ok, seal the requests as one
// submission (a batch when there are several), point an autoflow stage at its
// request, and commit; otherwise roll every one of them back. Returns
// $files_ok as it stands after the intake. A no-op for a direct submission.
function dispatch_intake_close( $link, $dbname, $files_ok, &$missit_msg )
{
  global $is_cli, $ID;

  if ( ! dispatch_intake_active() )
    return $files_ok;
  $rows = $GLOBALS['dispatch_intake_rows'];
  $ok = $files_ok && $rows;
  $nonce = dispatch_intake_nonce();
  if ( $ok && count( $rows ) === 1 )
    $ok = dispatch_affected( $link, "UPDATE `$dbname`.dispatch_request SET parentBatchKey = NULL WHERE parentBatchKey = ?",
                             's', array( dispatch_intake_batch_key( $dbname, $_SESSION['loginID'], $nonce ) ) ) === 1;
  if ( $ok )
  {
    $prefix = dispatch_intake_prefix( $dbname, $_SESSION['loginID'], $nonce );
    $ok = dispatch_affected( $link, "UPDATE `$dbname`.dispatch_request SET batchSealed = 1 WHERE LEFT(logicalKey, ?) = ?",
                             'is', array( strlen( $prefix ), $prefix ) ) === count( $rows );
  }
  ## The stage holds as READY until the worker reports acceptance or failure.
  if ( $ok && $is_cli && ! empty( $ID ) )
    $ok = dispatch_affected( $link, "UPDATE `$dbname`.autoflowAnalysis SET currentHPCARID = ?, status = 'READY',
                                     statusMsg = 'Analysis received; waiting for dispatch' WHERE requestID = ?",
                             'ii', array( (int) $rows[0]['HPCAnalysisRequestID'], (int) $ID ) ) >= 0;
  $ok ? mysqli_commit( $link ) : mysqli_rollback( $link );
  if ( ! $ok && $files_ok )
    $missit_msg .= '<br/>The analysis could not be recorded for dispatch; nothing was submitted.';
  $GLOBALS['dispatch_intake_committed'] = $ok;
  unset( $_SESSION['cluster'] );
  return $ok;
}

function dispatch_intake_title( $title )
{
  return dispatch_intake_received() ? str_replace( 'Submitted', 'Received', $title ) : $title;
}

// The receipt shown once the submission is committed: received, not yet submitted to a scheduler.
function dispatch_intake_receipt_html( array $rows, $pool )
{
  $ids = array();
  foreach ( $rows as $r )
    $ids[] = (int) $r['dispatchRequestID'];
  $pool = htmlspecialchars( (string) $pool, ENT_QUOTES );
  $which = count( $ids ) === 1 ? "Dispatch request {$ids[0]}"
         : count( $ids ) . ' dispatch requests (' . implode( ', ', $ids ) . ')';
  return "<pre>\n  Analysis received. Checking available resources in '$pool'.\n"
       . "  $which. The queue page shows where it is submitted.\n";
}
