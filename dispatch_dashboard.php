<?php
/*
 * dispatch_dashboard.php
 *
 * The operator view of metascheduler dispatch for this instance: worker
 * heartbeat, open alert items (the system of record), requests needing
 * action, operational metrics and decision history. Controller-level items
 * show the destination to every instance that uses it but list only this
 * instance's requests.
 */
include_once 'checkinstance.php';

if ( ( $_SESSION['userlevel'] ?? 0 ) < 4 )
{
  header( 'Location: index.php' );
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';
include_once $class_dir . 'dispatch_alerts.php';
include_once $class_dir . 'dispatch_messages.php';

$operator = 'person ' . (int) $_SESSION['loginID'];
$gdb = 'gfac';
$h = function ( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); };

try
{
  $glink = mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname );
}
catch ( mysqli_sql_exception $e )
{
  $glink = false;
}

if ( $glink && $_SERVER['REQUEST_METHOD'] === 'POST'
     && hash_equals( csrf_token(), (string) ( $_POST['csrf_token'] ?? '' ) ) )
{
  $id = (int) ( $_POST['dispatchRequestID'] ?? 0 );
  if ( isset( $_POST['retry'] ) )
    dispatch_operator_retry( $link, $dbname, $id, $operator );
  elseif ( isset( $_POST['resolve_unknown'] ) )
    dispatch_resolve_unknown( $glink, $gdb, $dbname, $id, trim( (string) ( $_POST['job_id'] ?? '' ) ),
                              trim( (string) ( $_POST['evidence'] ?? '' ) ) . " ($operator)" );
  elseif ( isset( $_POST['close_alert'] ) )
  {
    $alert = (int) ( $_POST['alertID'] ?? 0 );
    if ( dispatch_dashboard_alert_visible( $glink, $gdb, $dbname, $alert ) )
      dispatch_alert_close( $glink, $gdb, $alert, "closed by $operator" );
  }
  header( "Location: {$_SERVER['PHP_SELF']}" );
  exit();
}

// An item belongs to this instance, or is controller-level and lists one of its requests.
function dispatch_dashboard_alert_visible( $glink, $gdb, $db, $alert )
{
  return dispatch_rows( $glink, "SELECT 1 FROM `$gdb`.dispatch_alert a WHERE a.alertID = ? AND ( a.instanceDb = ?
      OR ( a.instanceDb IS NULL AND ( a.kind = 'worker_heartbeat' OR EXISTS ( SELECT 1 FROM `$gdb`.dispatch_alert_request q
           WHERE q.alertID = a.alertID AND q.instanceDb = ? ) ) ) )", 'iss', array( (int) $alert, $db, $db ) ) !== array();
}

// The menu's open-item count is cached per session; this page shows it fresh.
unset( $_SESSION['dispatch_open_alerts'] );
$page_title = 'Dispatch Dashboard';
include 'header.php';
$csrf = "<input type='hidden' name='csrf_token' value='" . csrf_token() . "' />";

echo "<div id='content'>\n<h1 class='title'>Dispatch Dashboard</h1>\n";

if ( ! $glink )
{
  echo "<p class='message'>The dispatch database cannot be reached. Alert items are unavailable; "
     . "the worker logs and dispatch-health.log on the LIMS host still record problems.</p>\n</div>\n";
  include $common_dir . 'footer.php';
  exit();
}

$beat = dispatch_heartbeat_status( $glink, $gdb );
echo "<h3>Dispatch worker</h3>\n<p>" . ( $beat['ok']
     ? "Running: last heartbeat {$beat['age']} seconds ago (" . $h( $beat['worker'] ) . ")."
     : "<span class='message text-red'>Not running: " . ( $beat['age'] === null ? 'no heartbeat recorded'
       : "last heartbeat {$beat['age']} seconds ago" ) . ".</span>" ) . "</p>\n";

// Open alert items.
$alerts = dispatch_rows( $glink, "SELECT a.* FROM `$gdb`.dispatch_alert a WHERE a.openFlag = 1 AND ( a.instanceDb = ?
    OR ( a.instanceDb IS NULL AND ( a.kind = 'worker_heartbeat' OR EXISTS ( SELECT 1 FROM `$gdb`.dispatch_alert_request q
         WHERE q.alertID = a.alertID AND q.instanceDb = ? ) ) ) ) ORDER BY a.urgency = 'urgent' DESC, a.firstAt",
  'ss', array( $dbname, $dbname ) );
echo "<h3>Open alerts (" . count( $alerts ) . ")</h3>\n";
if ( ! $alerts )
  echo "<p>No open alert items.</p>\n";
else
{
  echo "<table><tr><th>Kind</th><th>Urgency</th><th>Destination</th><th>First / last (UTC)</th><th>Detail</th>"
     . "<th>This instance's requests</th><th></th></tr>\n";
  foreach ( $alerts as $a )
  {
    $ids = array_column( dispatch_rows( $glink, "SELECT dispatchRequestID FROM `$gdb`.dispatch_alert_request
                                                 WHERE alertID = ? AND instanceDb = ?", 'is',
                                        array( (int) $a['alertID'], $dbname ) ), 'dispatchRequestID' );
    echo "<tr><td>" . $h( $a['kind'] ) . "</td><td>" . $h( $a['urgency'] ) . ( $a['emailedAt'] ? ' (emailed)' : '' )
       . "</td><td>" . $h( $a['destination'] ) . "</td><td>" . $h( $a['firstAt'] ) . "<br/>" . $h( $a['lastAt'] )
       . "</td><td>" . $h( $a['detail'] ) . "</td><td>" . $h( implode( ', ', $ids ) ) . "</td><td>"
       . "<form method='post'>$csrf<input type='hidden' name='alertID' value='" . (int) $a['alertID'] . "' />"
       . "<input type='submit' name='close_alert' value='Close' /></form></td></tr>\n";
  }
  echo "</table>\n";
}

// Requests needing action.
$attention = dispatch_rows( $link, "SELECT r.*, (SELECT attemptToken FROM `$dbname`.dispatch_attempt a
      WHERE a.dispatchRequestID = r.dispatchRequestID ORDER BY attemptID DESC LIMIT 1) AS attemptToken
    FROM `$dbname`.dispatch_request r
    WHERE r.state IN ('BLOCKED','SUBMISSION_UNKNOWN','ACCEPTED') OR (r.state = 'WAITING' AND r.notifiedAt IS NOT NULL)
    ORDER BY r.createdAt" );
echo "<h3>Requests needing attention (" . count( $attention ) . ")</h3>\n";
if ( $attention )
{
  echo "<table><tr><th>Request</th><th>State</th><th>Status shown to the submitter</th><th>Detail</th><th>Action</th></tr>\n";
  foreach ( $attention as $r )
  {
    $id = (int) $r['dispatchRequestID'];
    $form = "<form method='post'>$csrf<input type='hidden' name='dispatchRequestID' value='$id' />";
    if ( $r['state'] === 'SUBMISSION_UNKNOWN' )
      $action = $form . "Job ID <input type='text' name='job_id' size='8' /> Evidence <input type='text' name='evidence' size='24' />"
              . "<input type='submit' name='resolve_unknown' value='Resolve' /></form>"
              . "<small>A job ID records acceptance; no job ID records that nothing was submitted.</small>";
    elseif ( in_array( $r['state'], array( 'BLOCKED', 'WAITING' ), true ) )
      $action = $form . "<input type='submit' name='retry' value='Retry assessment' /></form>";
    else
      $action = 'Tracking is restored automatically.';
    echo "<tr><td>$id (HPC " . (int) $r['HPCAnalysisRequestID'] . ")</td><td>" . $h( $r['state'] ) . "</td><td>"
       . $h( dispatch_public_message( $r ) ) . "</td><td>" . $h( $r['reason'] )
       . ( $r['attemptToken'] ? "<br/><small>attempt " . $h( $r['attemptToken'] ) . "</small>" : '' )
       . "</td><td>$action</td></tr>\n";
  }
  echo "</table>\n";
}

// Metrics.
$m = dispatch_metrics( $glink, $gdb, $dbname );
echo "<h3>Last 7 days</h3>\n<ul>"
   . "<li>Accepted submissions: {$m['acceptance']['count']}; time from request to acceptance: average "
   . $h( $m['acceptance']['avg_seconds'] ) . " s, longest " . $h( $m['acceptance']['max_seconds'] ) . " s.</li>"
   . "<li>Assessments: READY {$m['assessments']['READY']}, DELAYED {$m['assessments']['DELAYED']}, REJECTED "
   . "{$m['assessments']['REJECTED']}, UNKNOWN {$m['assessments']['UNKNOWN']} (share unknown: "
   . $h( $m['assessments']['unknown_share'] ) . ").</li>"
   . "<li>Reconciliation: {$m['reconciliation']['accepted']} found accepted, {$m['reconciliation']['not_accepted']} found not "
   . "submitted, {$m['reconciliation']['unresolved_checks']} checks without a result.</li></ul>\n";
echo "<p>Open requests by state and destination:</p><table><tr><th>State</th><th>Destination</th><th>Requests</th></tr>";
foreach ( $m['open_by_state_and_destination'] as $r )
  echo "<tr><td>" . $h( $r['state'] ) . "</td><td>" . $h( $r['destination'] ) . "</td><td>" . (int) $r['n'] . "</td></tr>";
echo "</table>\n<p>Waiting by reason:</p><table><tr><th>Reason</th><th>Requests</th><th>Average age (s)</th></tr>";
foreach ( $m['waiting_by_reason'] as $r )
  echo "<tr><td>" . $h( $r['reason'] ) . "</td><td>" . (int) $r['n'] . "</td><td>" . (int) $r['avg_age_s'] . "</td></tr>";
echo "</table>\n";

// Decision history.
$decisions = dispatch_rows( $glink, "SELECT * FROM `$gdb`.dispatch_decision WHERE instanceDb = ?
                                     ORDER BY decisionID DESC LIMIT 25", 's', array( $dbname ) );
echo "<h3>Recent decisions</h3>\n<p><small>Weights are shares of accepted submissions among members eligible for each"
   . " request, not guaranteed shares of allocation or compute time.</small></p>\n";
if ( $decisions )
{
  echo "<table><tr><th>Time (UTC)</th><th>Request</th><th>Pool / policy</th><th>Outcome</th><th>Chosen</th>"
     . "<th>Members</th></tr>\n";
  foreach ( $decisions as $d )
  {
    $policy = json_decode( (string) $d['policyJson'], true );
    $how = is_array( $policy ) ? 'admission ' . $h( $policy['admission'] ) : '';
    if ( is_array( $policy ) && ! empty( $policy['groups'] ) )
    {
      foreach ( $policy['groups'] as $g => $grp )
        $how .= "<br/>$g: " . $h( $grp['strategy'] ) . ( $grp['weights'] ? ' weights ' . $h( json_encode( $grp['weights'] ) ) : '' );
    }
    $members = '';
    foreach ( (array) json_decode( (string) $d['candidatesJson'], true ) as $name => $c )
      $members .= $h( $name ) . ': ' . ( ! empty( $c['eligible'] ) ? 'eligible' : 'skipped' )
                . ( ! empty( $c['verdict'] ) ? ' (' . $h( $c['verdict'] ) . ')' : '' )
                . ( empty( $c['eligible'] ) ? ' - ' . $h( $c['reason'] ) : '' ) . '<br/>';
    echo "<tr><td>" . $h( $d['createdAt'] ) . "</td><td>" . (int) $d['dispatchRequestID'] . "</td><td>" . $h( $d['pool'] )
       . " v" . $h( $d['policyVersion'] ) . "<br/>$how</td><td>" . $h( $d['outcome'] ) . "<br/><small>"
       . $h( $d['reason'] ) . "</small></td><td>" . $h( $d['chosen'] ) . "</td><td>$members</td></tr>\n";
  }
  echo "</table>\n";
}

echo "</div>\n";
include $common_dir . 'footer.php';
