<?php
// Included at the top level of a submit page before anything is written: with
// no cluster selected, renders a "not submitted" page and sets $submit_stopped.
//
// lib/utility.php's cluster fieldset lists only clusters in the user's
// clusterAuthorizations, so an account authorized for no configured cluster
// posts no 'cluster' and the job would otherwise be reported as accepted.

// Needed unconditionally below for pick_cluster_is_metascheduler_entry() in
// the very condition that decides whether a metascheduler resolution is
// needed at all -- cheap (function/const definitions only, no DB or SSH).
require_once '/home/us3/lims/bin/gridctl/pick_cluster_reserve.php';

$submit_stopped = false;
if ( ! isset( $_SESSION['cluster'] ) )
{
  $submit_stop_msg = "<b>No cluster was selected, so nothing was submitted.</b><br/>"
                   . "If no clusters were offered on the previous page, this account is not "
                   . "authorized for any configured cluster. Ask your administrator to check "
                   . "its cluster authorizations.";
  include __DIR__ . '/submit_stop.php';
  $submit_stopped = true;
}

// A posted cluster may be a metascheduler pseudo-entry (its own $cluster_details
// row carries a 'clusters' list rather than a real sshport/workdir/queue --
// lib/utility.php's collect_config_info() deliberately lists these for
// selection, see METASCHEDULER-DESIGN.md). Nothing downstream can submit
// directly to one, so it is resolved to a real member cluster here, before any
// files are written -- a resolution failure must never leave an orphaned
// HPCAnalysisRequest row or XML file behind. The chosen real cluster rides in
// $_SESSION['cluster']['metaschedulerClusterExecuting'] so every later
// _2.php/_3.php page can read it without re-resolving, exactly mirroring how
// submitone.php (autoflow) already calls pick_cluster().
if ( ! $submit_stopped
     && isset( $global_cluster_details[ $_SESSION['cluster']['shortname'] ] )
     && pick_cluster_is_metascheduler_entry(
          $global_cluster_details[ $_SESSION['cluster']['shortname'] ] ) )
{
  $pseudo_cluster = $_SESSION['cluster']['shortname'];
  $candidates     = $global_cluster_details[ $pseudo_cluster ][ 'clusters' ];

  // Kill switch: flipping the pool's own 'active' to false must stop this
  // resolution outright, not just hide it from the previous page's cluster
  // list (collect_config_info() filtering) -- a user who already selected
  // this pool before it was disabled would otherwise still resolve to a
  // real cluster here. Mirrors submitone.php's own check for the autoflow
  // path.
  if ( array_key_exists( 'active', $global_cluster_details[ $pseudo_cluster ] )
       && ! $global_cluster_details[ $pseudo_cluster ][ 'active' ] )
  {
    $pick = array( 'chosen' => null,
        'reason' => "metascheduler '$pseudo_cluster' is disabled (active=false)",
        'tier' => null, 'scores' => array() );
  }
  else
  {
    ## try/catch, not just the @-suppressed falsy check: this is reached from
    ## a web request, which never loads gridctl_bootstrap.php's
    ## mysqli_report( MYSQLI_REPORT_OFF ) -- so under PHP 8.1+'s real default
    ## (MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT) a connect failure throws
    ## mysqli_sql_exception instead of returning false, which @ does not
    ## suppress. Without this, a real gfac outage during an interactive
    ## submission produced an uncaught fatal error instead of the graceful
    ## "could not connect" page below -- worse exactly when the system is
    ## already in trouble, and invisible in ordinary testing because a
    ## connect attempt almost always succeeds.
    try
    {
      $gfac_link = @mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname );
    }
    catch ( \Throwable $e )
    {
      $gfac_link = false;
    }

    if ( ! $gfac_link )
    {
      ## No status data is learnable without this connection -- never treat a
      ## failed connection as permission to pick blindly (same rule
      ## submitone.php applies).
      $pick = array( 'chosen' => null, 'reason' => 'could not connect to gfac for cluster status',
                      'tier' => null, 'scores' => array() );
    }
    else
    {
      // Membership in the pool is never itself authorization for a specific
      // real cluster inside it -- the same per-person allowlist a direct
      // submission to a real cluster already goes through
      // (require_authorized_cluster(), called on the previous page with the
      // pseudo name), checked here again against every real candidate.
      $authorized = isset( $_SESSION['clusterAuth'] ) && is_array( $_SESSION['clusterAuth'] )
                  ? $_SESSION['clusterAuth'] : array();

      // Set only by submitone.php (autoflow), which has the analysis
      // profile's own XML available before this page runs and uses it to
      // approximate the job's own process-count demand (see
      // pick_cluster_estimate_job_requirements()). A genuine interactive
      // submission never sets this -- there is no equivalent data available
      // this early in that path -- so $job_requirements stays array() there,
      // exactly as it always has: pick_cluster_meets_requirements() already
      // treats an unresolved requirement as "never fails a candidate",
      // never as "requires nothing".
      $job_requirements = isset( $_SESSION[ 'metascheduler_job_requirements_estimate' ] )
                         ? $_SESSION[ 'metascheduler_job_requirements_estimate' ] : array();

      // pick_cluster_reserve(), not pick_cluster() directly: an interactive
      // submission here and an autoflow one in submitone.php can race the same
      // pool concurrently, with no coordination between the two processes
      // otherwise.
      $pick = pick_cluster_reserve( $gfac_link, $pseudo_cluster, $candidates, $global_cluster_details,
                                     $job_requirements, array(), null, $authorized );
      mysqli_close( $gfac_link );
    }
  }

  error_log( pick_cluster_log_line( $pseudo_cluster, $pick ) );

  if ( $pick[ 'chosen' ] === null )
  {
    $submit_stop_msg = "<b>No cluster in the '$pseudo_cluster' pool is currently available"
                     . ( $pick[ 'reason' ] ? " ({$pick['reason']})" : '' ) . ".</b><br/>"
                     . "Nothing was submitted. Try again shortly, or ask your administrator "
                     . "to check that pool's clusters.";
    include __DIR__ . '/submit_stop.php';
    $submit_stopped = true;
  }
  else
  {
    $_SESSION['cluster'][ 'metaschedulerClusterExecuting' ] = $pick[ 'chosen' ];
  }
}
