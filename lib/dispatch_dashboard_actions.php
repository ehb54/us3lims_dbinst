<?php
// The dispatch dashboard's operator actions: retry a blocked request, resolve or close an
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
  if ( isset( $post['close_unknown'] ) )
  {
    $evidence = trim( (string) ( $post['evidence'] ?? '' ) );
    return dispatch_close_unknown( $glink, $dbname, $id, $evidence !== '' ? "$evidence ($operator)" : '' );
  }
  if ( isset( $post['close_alert'] ) )
  {
    $alert = (int) ( $post['alertID'] ?? 0 );
    return dispatch_dashboard_alert_visible( $glink, $gdb, $dbname, $alert )
        && dispatch_alert_close( $glink, $gdb, $alert, "closed by $operator" );
  }
  return false;
}

// Why a Resolve or Close changed nothing, read after the refusal.
function dispatch_dashboard_refusal( $link, $dbname, $id, array $post )
{
  $req = dispatch_get( $link, $dbname, $id );
  if ( ! $req )
    return "Request $id does not exist in this instance.";
  if ( $req['state'] !== DISPATCH_SUBMISSION_UNKNOWN )
    return "Request $id is no longer held; it is {$req['state']}.";
  if ( trim( (string) ( $post['evidence'] ?? '' ) ) === '' )
    return "Request $id was not changed: evidence is required.";
  if ( isset( $post['resolve_unknown'] ) && trim( (string) ( $post['job_id'] ?? '' ) ) === '' )
    return "Request $id was not changed: Resolve needs the job ID. If you cannot find a job, use Close.";
  return "Request $id was not changed: an automatic check is running. Try again in a minute.";
}

// Whether a worker should be running here: an active pool, or a request of this instance not yet settled.
function dispatch_dashboard_worker_expected( $link, $dbname, array $cluster_details )
{
  foreach ( $cluster_details as $entry )
    if ( is_array( $entry ) && is_array( $entry['clusters'] ?? null ) && ( ! array_key_exists( 'active', $entry ) || $entry['active'] ) )
      return true;
  return dispatch_rows_checked( $link, "SELECT 1 FROM `$dbname`.dispatch_request
      WHERE state NOT IN ('CLOSED','FAILED','EXPIRED','CANCELLED') LIMIT 1" ) !== array();
}

// An item belongs to this instance, or is controller-level and lists one of its requests.
function dispatch_dashboard_alert_visible( $glink, $gdb, $db, $alert )
{
  return dispatch_rows( $glink, "SELECT 1 FROM `$gdb`.dispatch_alert a WHERE a.alertID = ? AND ( a.instanceDb = ?
      OR ( a.instanceDb IS NULL AND ( a.kind IN ('worker_heartbeat','pool_list') OR EXISTS ( SELECT 1 FROM `$gdb`.dispatch_alert_request q
           WHERE q.alertID = a.alertID AND q.instanceDb = ? ) ) ) )", 'iss', array( (int) $alert, $db, $db ) ) !== array();
}

// What is missing for dispatch to work here: this instance's tables, and gfac's, which a hand
// upgrade leaves out until the gfac account's grant is fixed.
function dispatch_dashboard_schema_messages( $link, $glink, $dbname, $gdb )
{
  $messages = array();
  $instance = dispatch_schema_problems( $link, $dbname, dispatch_instance_tables() );
  if ( $instance )
    $messages[] = 'This instance cannot record dispatch requests: ' . implode( '; ', $instance )
                . '. Run uslims_upgrade.php --apply on the LIMS host to create the tables.';
  $gfac = $glink ? dispatch_schema_problems( $glink, $gdb, dispatch_gfac_tables() ) : array();
  if ( $gfac )
    $messages[] = 'The dispatch worker cannot run: ' . implode( '; ', $gfac )
                . '. Give the gfac account CREATE on gfac and run uslims_upgrade.php --apply on the LIMS host.';
  return $messages;
}
