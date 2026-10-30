<?php
// Metascheduler requests on the queue pages: those not yet handed to the job
// monitor are listed from their dispatch rows, with the state's message and a
// cancel action that follows the dispatch claim rules. Accepted jobs appear
// in the existing job list and are cancelled through its Delete action.

include_once $class_dir . 'dispatch_ledger.php';
include_once $class_dir . 'dispatch_messages.php';

// States listed here; MONITORED and CLOSED jobs are in the job list.
function dispatch_queue_states()
{
  return array( 'PENDING', 'ASSESSING', 'WAITING', 'BLOCKED', 'STAGING', 'SUBMITTING', 'SUBMISSION_UNKNOWN',
                'ACCEPTED', 'FAILED', 'EXPIRED', 'CANCELLED' );
}

// What a state means for the submitter, escaped. Operators (level 4 and up)
// also see the recorded detail, correlated by request and attempt.
function dispatch_queue_message( array $row )
{
  $text = htmlspecialchars( dispatch_public_message( $row ), ENT_QUOTES );
  if ( (int) $_SESSION['userlevel'] >= 4 && (string) $row['reason'] !== '' )
    $text .= "<br/><span class='operator_detail'>Request " . (int) $row['dispatchRequestID']
           . ( ! empty( $row['attemptToken'] ) ? ', attempt ' . htmlspecialchars( $row['attemptToken'], ENT_QUOTES ) : '' )
           . ': ' . htmlspecialchars( (string) $row['reason'], ENT_QUOTES ) . '</span>';
  return $text;
}

// This instance's dispatch requests the viewer may see: their own (level 2)
// or every one in the instance (level 3 and up).
function dispatch_queue_rows( $link, $dbname )
{
  if ( ! dispatch_db_name_ok( $dbname ) || ! dispatch_rows( $link, "SELECT 1 FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'dispatch_request'", 's', array( $dbname ) ) )
    return array();
  $states = "'" . implode( "','", dispatch_queue_states() ) . "'";
  $own = (int) $_SESSION['userlevel'] < 3;
  return dispatch_rows( $link, "SELECT r.dispatchRequestID, r.HPCAnalysisRequestID, r.pool, r.state, r.reason, r.reasonCode,
        r.selectedCluster, r.retryAt, r.deadlineAt, r.createdAt, r.cancelRequested, r.parentBatchKey, r.notifiedAt,
        (SELECT schedulerJobID FROM `$dbname`.dispatch_attempt a WHERE a.dispatchRequestID = r.dispatchRequestID
          AND a.phase = 'ACCEPTED' LIMIT 1) AS schedulerJobID,
        (SELECT attemptToken FROM `$dbname`.dispatch_attempt a WHERE a.dispatchRequestID = r.dispatchRequestID
          ORDER BY attemptID DESC LIMIT 1) AS attemptToken,
        h.analType, p.email
      FROM `$dbname`.dispatch_request r
      LEFT JOIN `$dbname`.HPCAnalysisRequest h ON h.HPCAnalysisRequestID = r.HPCAnalysisRequestID
      LEFT JOIN `$dbname`.people p ON p.personID = r.submitterID
      WHERE r.state IN ($states)" . ( $own ? ' AND r.submitterID = ?' : '' ) . "
      ORDER BY r.createdAt, r.dispatchRequestID", $own ? 'i' : '', $own ? array( (int) $_SESSION['loginID'] ) : array() );
}

function dispatch_queue_may_cancel( array $row )
{
  return ! in_array( $row['state'], array( 'FAILED', 'EXPIRED', 'CANCELLED' ), true ) && (int) $row['cancelRequested'] === 0
         && ( (int) $_SESSION['userlevel'] >= 3 || (string) $row['email'] === (string) $_SESSION['email'] );
}

function dispatch_queue_html( array $rows )
{
  if ( ! $rows )
    return '';
  $html = "<h4>Waiting for dispatch</h4>\n<table class='dispatch_queue'>\n"
        . "<tr><th>Request</th><th>Pool</th><th>Type</th><th>Received (UTC)</th><th>Status</th><th></th></tr>\n";
  foreach ( $rows as $r )
  {
    $id = (int) $r['dispatchRequestID'];
    $button = dispatch_queue_may_cancel( $r )
      ? "<form action='queue_viewer.php' method='post'>"
        . "<input type='hidden' name='dispatchRequestID' value='$id' />"
        . "<input type='hidden' name='csrf_token' value='" . csrf_token() . "' />"
        . "<input type='submit' name='dispatch_cancel' value='Cancel' /></form>"
      : '';
    $html .= "<tr class='dispatch_" . strtolower( $r['state'] ) . "' data-dispatch-id='$id'>"
           . "<td>" . (int) $r['HPCAnalysisRequestID'] . "</td>"
           . "<td>" . htmlspecialchars( $r['pool'], ENT_QUOTES ) . "</td>"
           . "<td>" . htmlspecialchars( (string) $r['analType'], ENT_QUOTES ) . "</td>"
           . "<td>" . htmlspecialchars( $r['createdAt'], ENT_QUOTES ) . "</td>"
           . "<td class='dispatch_message'>" . dispatch_queue_message( $r ) . "</td>"
           . "<td>$button</td></tr>\n";
  }
  return $html . "</table>\n";
}

// The queue page's cancel action for a dispatch request; the viewer must be
// allowed to see and cancel it. Returns the ledger's answer, or 'denied'.
function dispatch_queue_cancel( $link, $dbname, $id )
{
  foreach ( dispatch_queue_rows( $link, $dbname ) as $r )
  {
    if ( (int) $r['dispatchRequestID'] === (int) $id )
      return dispatch_queue_may_cancel( $r ) ? dispatch_request_cancel( $link, $dbname, (int) $id ) : 'denied';
  }
  return 'denied';
}
