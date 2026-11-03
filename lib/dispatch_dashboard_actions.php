<?php
// The dispatch dashboard's operator actions: retry a blocked request, resolve an
// uncertain submission, close an alert item. The page includes common's dispatch_alerts.php.

// One POSTed action, already checked for level and CSRF by the page.
function dispatch_dashboard_post( $link, $glink, $gdb, $dbname, array $post, $operator )
{
  $id = (int) ( $post['dispatchRequestID'] ?? 0 );
  if ( isset( $post['retry'] ) )
    return dispatch_operator_retry( $link, $dbname, $id, $operator );
  ## The operator's name is added only to evidence they gave, so empty fields still fail the resolver's check.
  if ( isset( $post['resolve_unknown'] ) )
  {
    $evidence = trim( (string) ( $post['evidence'] ?? '' ) );
    return dispatch_resolve_unknown( $glink, $dbname, $id, trim( (string) ( $post['job_id'] ?? '' ) ),
                                     $evidence !== '' ? "$evidence ($operator)" : '' );
  }
  if ( isset( $post['close_alert'] ) )
  {
    $alert = (int) ( $post['alertID'] ?? 0 );
    return dispatch_dashboard_alert_visible( $glink, $gdb, $dbname, $alert )
        && dispatch_alert_close( $glink, $gdb, $alert, "closed by $operator" );
  }
  return false;
}

// An item belongs to this instance, or is controller-level and lists one of its requests.
function dispatch_dashboard_alert_visible( $glink, $gdb, $db, $alert )
{
  return dispatch_rows( $glink, "SELECT 1 FROM `$gdb`.dispatch_alert a WHERE a.alertID = ? AND ( a.instanceDb = ?
      OR ( a.instanceDb IS NULL AND ( a.kind = 'worker_heartbeat' OR EXISTS ( SELECT 1 FROM `$gdb`.dispatch_alert_request q
           WHERE q.alertID = a.alertID AND q.instanceDb = ? ) ) ) )", 'iss', array( (int) $alert, $db, $db ) ) !== array();
}
