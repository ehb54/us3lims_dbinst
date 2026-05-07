<?php
/*
 * job_lifecycle.php
 *
 * Admin lifecycle/history view for HPC jobs.
 *
 * Purpose:
 *   Fill the gap between queue_viewer.php (live queue only) and runID_info.php
 *   (deep per-request detail) by showing recent jobs, last known lifecycle stage,
 *   and where an admin should look next.
 */

include_once 'checkinstance.php';

if ( $_SESSION['userlevel'] < 2 )
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';

// --------------------------------------------------------------------------
// Filters
// --------------------------------------------------------------------------
$requestID = isset($_GET['RequestID']) ? max(0, (int)$_GET['RequestID']) : 0;
$gfacID    = isset($_GET['gfacID'])    ? trim($_GET['gfacID'])            : '';
$cluster   = isset($_GET['cluster'])   ? trim($_GET['cluster'])           : '';
$status    = isset($_GET['status'])    ? trim($_GET['status'])            : '';
$method    = isset($_GET['method'])    ? trim($_GET['method'])            : '';
$days      = isset($_GET['days'])      ? max(1, min(30, (int)$_GET['days'])) : 7;
$limit     = isset($_GET['limit'])     ? max(10, min(500, (int)$_GET['limit'])) : 100;
$refresh   = isset($_GET['refresh'])   ? max(0, min(300, (int)$_GET['refresh'])) : 15;
$show_msgs = !empty($_GET['show_msgs']);

// --------------------------------------------------------------------------
// Page chrome
// --------------------------------------------------------------------------
$page_title = 'HPC Job Lifecycle';
$css        = '';
$js         = '';
$onload     = $refresh > 0 ? "onload='jobLifecycleInit($refresh);'" : '';
include 'header.php';

// --------------------------------------------------------------------------
// Helpers
// --------------------------------------------------------------------------
function h( $s )
{
  return htmlspecialchars( (string)$s, ENT_QUOTES, 'UTF-8' );
}

function request_link( $requestID )
{
  return "runID_info.php?RequestID=" . urlencode( $requestID );
}

function queue_link( $gfacID )
{
  // queue_viewer.php doesn't appear to support deep linking by gfacID yet,
  // but linking there is still useful operationally.
  return "queue_viewer.php";
}

function badge_class( $stage )
{
  $stage = strtolower( $stage );
  if ( strpos($stage, 'fail') !== false || strpos($stage, 'abort') !== false ) return 'bad';
  if ( strpos($stage, 'complete') !== false || strpos($stage, 'import') !== false ) return 'good';
  if ( strpos($stage, 'running') !== false || strpos($stage, 'queue') !== false ) return 'info';
  if ( strpos($stage, 'pending') !== false || strpos($stage, 'request') !== false || strpos($stage, 'submit') !== false ) return 'warn';
  return 'muted';
}

function short_text( $text, $max = 120 )
{
  $text = trim( preg_replace('/\s+/', ' ', (string)$text) );
  if ( strlen($text) <= $max ) return $text;
  return substr( $text, 0, $max - 3 ) . '...';
}

function latest_queue_message( $globaldb, $gfacID )
{
  if ( empty($gfacID) ) return array(null, null);

  $q = "SELECT qm.message, qm.time
        FROM queue_messages qm
        INNER JOIN analysis a ON a.id = qm.analysisID
        WHERE a.gfacID = ?
        ORDER BY qm.time DESC
        LIMIT 1";
  $stmt = mysqli_prepare( $globaldb, $q );
  if ( ! $stmt ) return array(null, null);
  mysqli_stmt_bind_param( $stmt, 's', $gfacID );
  mysqli_stmt_execute( $stmt );
  $res = mysqli_stmt_get_result( $stmt );
  $row = $res ? mysqli_fetch_assoc( $res ) : null;
  mysqli_stmt_close( $stmt );

  if ( ! $row ) return array(null, null);
  return array($row['message'], $row['time']);
}

function derive_stage_and_hint( $row )
{
  $hasResult   = ! empty( $row['HPCAnalysisResultID'] );
  $hasGfac     = ! empty( $row['gfacID'] );
  $qStatus     = strtolower( (string)($row['queueStatus'] ?? '') );
  $gStatus     = strtoupper( trim( (string)($row['gfac_status'] ?? '') ) );
  $hasStdout   = ! empty( $row['stdout'] );
  $hasStderr   = ! empty( $row['stderr'] );
  $resultCount = (int)($row['result_data_count'] ?? 0);
  $lastMessage = (string)($row['lastMessage'] ?? '');
  $queueMsg    = (string)($row['queue_msg'] ?? '');

  if ( ! $hasResult )
    return array('Request only', 'Check submit path: no HPCAnalysisResult row yet.');

  if ( $hasResult && ! $hasGfac )
    return array('Submit pending', 'Result row exists but no job ID. Check submit_slurm.php / submit logging.');

  if ( in_array($qStatus, array('aborted','failed'), true) || in_array($gStatus, array('FAILED','ERROR','CANCELED','CANCELLED','RUN_TIMEOUT','SUBMIT_TIMEOUT','DATA_TIMEOUT','FAILED_DATA'), true) )
  {
    $hint = 'Check run info first';
    if ( $hasStderr ) $hint = 'Check stderr in run info first.';
    else if ( ! empty($queueMsg) || ! empty($lastMessage) ) $hint = 'Check queue/last message in run info.';
    return array('Failed', $hint);
  }

  if ( in_array($qStatus, array('queued'), true) || in_array($gStatus, array('SUBMITTED'), true) )
    return array('Queued', 'Check queue viewer / cluster queue state.');

  if ( in_array($qStatus, array('running'), true) || in_array($gStatus, array('RUNNING','DATA'), true) )
    return array('Running', 'Check queue viewer for live scheduler state.');

  if ( in_array($qStatus, array('completed'), true) || in_array($gStatus, array('COMPLETE'), true) )
  {
    if ( $resultCount > 0 )
      return array('Imported/Completed', 'Open run info for detailed results.');

    if ( $hasStdout || $hasStderr )
      return array('Cleanup/import pending', 'Job finished but no imported result rows. Check cleanup/import path.');

    return array('Completed, detail incomplete', 'Job appears complete. Open run info and check cleanup/message history.');
  }

  if ( $hasGfac && empty($gStatus) )
    return array('Submitted', 'Job ID recorded. Check queue viewer / global analysis state.');

  return array('Unknown/Inconsistent', 'Open run info and compare request/result/global analysis state.');
}

// --------------------------------------------------------------------------
// Open global db for analysis / queue_messages
// --------------------------------------------------------------------------
$globaldb = mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname );
if ( ! $globaldb )
{
  echo "<div id='content'><p>Cannot open global database on " . h($globaldbhost) . "</p></div>";
  include 'footer.php';
  exit();
}

// --------------------------------------------------------------------------
// Build main query
// --------------------------------------------------------------------------
$userFilter = '';
$params     = array();
$types      = '';
$where      = array();

if ( $requestID > 0 ) {
  $where[] = 'r.HPCAnalysisRequestID = ?';
  $params[] = $requestID;
  $types   .= 'i';
}
if ( $gfacID !== '' ) {
  $where[] = 'res.gfacID = ?';
  $params[] = $gfacID;
  $types   .= 's';
}
if ( $cluster !== '' ) {
  $where[] = 'r.clusterName = ?';
  $params[] = $cluster;
  $types   .= 's';
}
if ( $status !== '' ) {
  $where[] = '(res.queueStatus = ? OR ga.status = ?)';
  $params[] = $status;
  $params[] = strtoupper($status);
  $types   .= 'ss';
}
if ( $method !== '' ) {
  $where[] = 'r.method = ?';
  $params[] = $method;
  $types   .= 's';
}
$where[] = 'r.submitTime >= DATE_SUB(NOW(), INTERVAL ? DAY)';
$params[] = $days;
$types   .= 'i';

if ( $_SESSION['userlevel'] == 2 )
{
  $submitterGUID = preg_replace( '/^.*_/', '', $_SESSION['user_id'] );
  $where[] = '(r.submitterGUID = ? OR r.investigatorGUID = ?)';
  $params[] = $submitterGUID;
  $params[] = $submitterGUID;
  $types   .= 'ss';
}

$sql = "SELECT
          r.HPCAnalysisRequestID,
          r.HPCAnalysisRequestGUID,
          r.experimentID,
          r.submitTime,
          r.clusterName,
          r.method,
          r.editXMLFilename,
          r.submitterGUID,
          r.investigatorGUID,
          res.HPCAnalysisResultID,
          res.gfacID,
          res.queueStatus,
          res.lastMessage,
          res.updateTime,
          res.startTime,
          res.endTime,
          res.stdout,
          res.stderr,
          res.jobfile,
          ga.status AS gfac_status,
          ga.queue_msg,
          ga.time AS gfac_update,
          ( SELECT COUNT(*)
            FROM HPCAnalysisResultData rd
            WHERE rd.HPCAnalysisResultID = res.HPCAnalysisResultID
          ) AS result_data_count
        FROM HPCAnalysisRequest r
        LEFT JOIN HPCAnalysisResult res
          ON res.HPCAnalysisResultID = (
            SELECT MAX(res2.HPCAnalysisResultID)
            FROM HPCAnalysisResult res2
            WHERE res2.HPCAnalysisRequestID = r.HPCAnalysisRequestID
          )
        LEFT JOIN gfac.analysis ga
          ON ga.gfacID = res.gfacID";

if ( ! empty($where) )
  $sql .= "\nWHERE " . implode("\n  AND ", $where);

$sql .= "\nORDER BY r.submitTime DESC
         LIMIT " . (int)$limit;

$stmt = mysqli_prepare( $link, $sql );
if ( ! $stmt )
{
  echo "<div id='content'><p>Query prepare failed: " . h(mysqli_error($link)) . "</p></div>";
  include 'footer.php';
  exit();
}
if ( ! empty($params) )
  mysqli_stmt_bind_param( $stmt, $types, ...$params );
mysqli_stmt_execute( $stmt );
$result = mysqli_stmt_get_result( $stmt );
$rows   = array();
while ( $row = mysqli_fetch_assoc($result) )
{
  list($msg, $msg_time) = latest_queue_message( $globaldb, $row['gfacID'] ?? '' );
  $row['latest_queue_message'] = $msg;
  $row['latest_queue_message_time'] = $msg_time;
  list($stage, $hint) = derive_stage_and_hint( $row );
  $row['lifecycle_stage'] = $stage;
  $row['debug_hint']      = $hint;
  $rows[] = $row;
}
mysqli_stmt_close( $stmt );
mysqli_close( $globaldb );

$filter_clusters = array();
$methods  = array('2DSA','2DSA_CG','2DSA_MW','GA','GA_MW','GA_SC','DMGA','PCSA');
$cr = mysqli_query( $link, "SELECT DISTINCT clusterName FROM HPCAnalysisRequest WHERE clusterName IS NOT NULL AND clusterName <> '' ORDER BY clusterName" );
if ( $cr ) while ( list($c) = mysqli_fetch_array($cr) ) $filter_clusters[] = $c;

?>
<style>
#content .small-note { color:#666; font-size:0.92em; }
#content .table-wrap { width:100%; overflow-x:auto; }
#content table.admin.history { width:100%; border-collapse:collapse; table-layout:fixed; }
#content table.admin.history th, #content table.admin.history td { padding:6px 8px; vertical-align:top; overflow-wrap:anywhere; word-break:break-word; }
#content table.admin.history tbody tr:nth-child(even) { background:#fafafa; }
#content .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:0.85em; font-weight:600; white-space:nowrap; }
#content .badge.good { background:#dff3e2; color:#196127; }
#content .badge.bad { background:#fbe3e4; color:#9b1c1c; }
#content .badge.warn { background:#fff4d6; color:#8a5a00; }
#content .badge.info { background:#d9ecff; color:#0d4f8b; }
#content .badge.muted { background:#e9ecef; color:#495057; }
#content .filters table td, #content .filters table th { padding:4px 8px; }
#content .mono { font-family: monospace; }
#content .hint { max-width: 18em; }
#content .msg { max-width: 18em; }
#content .small-note { overflow-wrap:anywhere; word-break:break-word; }
#content input[type=text] { max-width: 100%; }
#content .filters table { width:100%; }
#content .filters table th { white-space:nowrap; }
@media (max-width: 1200px) {
  #content .filters table,
  #content .filters tbody,
  #content .filters tr,
  #content .filters th,
  #content .filters td { display:block; width:100%; box-sizing:border-box; }
  #content .filters table th { padding-top:10px; }
  #content .filters table td { padding-bottom:4px; }
  #content .filters table td[colspan] { padding-top:10px; }
}
</style>
<script>
function jobLifecycleInit(seconds) {
  if (!seconds || seconds <= 0) return;
  window.setTimeout(function() { window.location.reload(); }, seconds * 1000);
}
</script>
<div id='content'>
  <h1 class="title">HPC Job Lifecycle</h1>
  <p class="small-note">
    Recent HPC jobs across submit, queue, cleanup, and import stages.
    Use this page to find the last known stage and then jump to queue viewer or run info.
  </p>

  <div class="filters">
    <form method="get" action="<?php echo h($_SERVER['PHP_SELF']); ?>">
      <table>
        <tr>
          <th>RequestID</th>
          <td><input type="text" name="RequestID" value="<?php echo $requestID > 0 ? h($requestID) : ''; ?>" size="8"></td>
          <th>gfacID</th>
          <td><input type="text" name="gfacID" value="<?php echo h($gfacID); ?>" size="18"></td>
          <th>Cluster</th>
          <td>
            <select name="cluster">
              <option value="">All</option>
              <?php foreach ( $filter_clusters as $c ): ?>
              <option value="<?php echo h($c); ?>"<?php echo $cluster === $c ? " selected='selected'" : ''; ?>><?php echo h($c); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th>Method</th>
          <td>
            <select name="method">
              <option value="">All</option>
              <?php foreach ( $methods as $m ): ?>
              <option value="<?php echo h($m); ?>"<?php echo $method === $m ? " selected='selected'" : ''; ?>><?php echo h($m); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <th>Status</th>
          <td><input type="text" name="status" value="<?php echo h($status); ?>" size="12" placeholder="queued / FAILED / ..."></td>
          <th>Days / Limit / Refresh</th>
          <td>
            <input type="number" name="days" value="<?php echo h($days); ?>" min="1" max="30" style="width:4.5em;">
            <input type="number" name="limit" value="<?php echo h($limit); ?>" min="10" max="500" style="width:5em;">
            <select name="refresh">
              <?php foreach ( array(0,5,15,30,60) as $opt ): ?>
              <option value="<?php echo $opt; ?>"<?php echo $refresh === $opt ? " selected='selected'" : ''; ?>><?php echo $opt === 0 ? 'Off' : ($opt . 's'); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <td colspan="6">
            <label><input type="checkbox" name="show_msgs" value="1"<?php echo $show_msgs ? " checked='checked'" : ''; ?>> show message snippets</label>
            <input type="submit" value="Filter">
            <a href="<?php echo h($_SERVER['PHP_SELF']); ?>">Reset</a>
          </td>
        </tr>
      </table>
    </form>
  </div>

  <div class="table-wrap">
  <table cellspacing="0" cellpadding="0" class="admin history">
    <caption>Recent Jobs (<?php echo count($rows); ?> shown)</caption>
    <thead>
      <tr>
        <th>Request</th>
        <th>Submit</th>
        <th>Cluster / Method</th>
        <th>Job ID</th>
        <th>Stored Status</th>
        <th>Global Status</th>
        <th>Lifecycle</th>
        <th>Last Update</th>
        <th>Hint</th>
        <th>Links</th>
      </tr>
    </thead>
    <tbody>
      <?php if ( empty($rows) ): ?>
      <tr><td colspan="10">No jobs found for the current filter.</td></tr>
      <?php else: ?>
      <?php foreach ( $rows as $row ): ?>
      <tr>
        <td>
          <div><strong><?php echo h($row['HPCAnalysisRequestID']); ?></strong></div>
          <?php if ( !empty($row['experimentID']) ): ?><div class="small-note">Exp <?php echo h($row['experimentID']); ?></div><?php endif; ?>
          <?php if ( !empty($row['editXMLFilename']) ): ?><div class="small-note"><?php echo h($row['editXMLFilename']); ?></div><?php endif; ?>
        </td>
        <td>
          <?php echo h($row['submitTime']); ?>
        </td>
        <td>
          <div><?php echo h($row['clusterName']); ?></div>
          <div class="small-note"><?php echo h($row['method']); ?></div>
        </td>
        <td class="mono">
          <?php echo !empty($row['gfacID']) ? h($row['gfacID']) : '<span class="small-note">none</span>'; ?>
        </td>
        <td>
          <?php echo !empty($row['queueStatus']) ? h($row['queueStatus']) : '<span class="small-note">none</span>'; ?>
          <?php if ( !empty($row['lastMessage']) ): ?><div class="small-note msg"><?php echo h(short_text($row['lastMessage'], 100)); ?></div><?php endif; ?>
        </td>
        <td>
          <?php echo !empty($row['gfac_status']) ? h($row['gfac_status']) : '<span class="small-note">n/a</span>'; ?>
          <?php if ( !empty($row['queue_msg']) ): ?><div class="small-note msg"><?php echo h(short_text($row['queue_msg'], 100)); ?></div><?php endif; ?>
        </td>
        <td>
          <span class="badge <?php echo h(badge_class($row['lifecycle_stage'])); ?>"><?php echo h($row['lifecycle_stage']); ?></span>
          <div class="small-note">
            Result row: <?php echo !empty($row['HPCAnalysisResultID']) ? 'yes' : 'no'; ?>,
            imported: <?php echo (int)$row['result_data_count']; ?>
          </div>
        </td>
        <td>
          <?php echo h($row['updateTime'] ?: $row['gfac_update']); ?>
          <?php if ( $show_msgs && !empty($row['latest_queue_message']) ): ?>
            <div class="small-note msg"><?php echo h(short_text($row['latest_queue_message'], 120)); ?></div>
          <?php endif; ?>
        </td>
        <td class="hint">
          <?php echo h($row['debug_hint']); ?>
        </td>
        <td>
          <a href="<?php echo h(request_link($row['HPCAnalysisRequestID'])); ?>">run info</a><br>
          <?php if ( !empty($row['gfacID']) ): ?>
          <a href="<?php echo h(queue_link($row['gfacID'])); ?>">queue viewer</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php include 'footer.php'; ?>
