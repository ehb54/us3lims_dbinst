<?php
// Included at the top level of a submit page before anything is written: with
// no cluster selected, renders a "not submitted" page and sets $submit_stopped.
//
// lib/utility.php's cluster fieldset lists only clusters in the user's
// clusterAuthorizations, so an account authorized for no configured cluster
// posts no 'cluster' and the job would otherwise be reported as accepted.

// Mirror cluster_is_authorized()'s own 'localhost' => true exemption
// (lib/utility.php): a direct submission to the appliance's co-located
// node is always authorized regardless of clusterAuth, since it's inherent
// site infrastructure, not an external allocation a site admin grants per
// account. Without this, going through a pool that happens to contain that
// same node disagreed with a direct submission to it -- round-12
// should-fix, dbinst#82.
function require_cluster_expand_localhost_authorized( $candidates, $global_cluster_details, $authorized )
{
  foreach ( $candidates as $candidate_name )
  {
    if ( isset( $global_cluster_details[ $candidate_name ][ 'localhost' ] )
         && $global_cluster_details[ $candidate_name ][ 'localhost' ]
         && ! in_array( $candidate_name, $authorized, true ) )
      $authorized[] = $candidate_name;
  }
  return $authorized;
}

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
// Cheap pre-check mirroring pick_cluster_is_metascheduler_entry() exactly
// (a pool entry is an array carrying its own 'clusters' array), done
// without loading any gridctl file -- gridctl is only ever pulled in below,
// and only for a submission that actually posted a pool. A direct
// submission to a real cluster, or any host with no metascheduler pools
// configured at all, never touches gridctl from here.
$require_cluster_entry = $submit_stopped
  ? null
  : ( $global_cluster_details[ $_SESSION['cluster']['shortname'] ] ?? null );
$require_cluster_is_pool = is_array( $require_cluster_entry )
  && array_key_exists( 'clusters', $require_cluster_entry )
  && is_array( $require_cluster_entry[ 'clusters' ] );

if ( $require_cluster_is_pool )
{
  // us3lims-roles installs gridctl under whatever the us3 account's own
  // home directory is, not necessarily /home/us3 -- resolved the same way
  // gridctl_bootstrap.php resolves it for gridctl's own files, so a roles
  // host with a different home for us3 still finds it. Guarded with
  // function_exists(): if something else in this same request already
  // loaded gridctl's pick_cluster*.php under a different (but equivalent)
  // path, requiring it again here under this resolved path would otherwise
  // be a fatal "Cannot redeclare".
  if ( ! function_exists( 'pick_cluster_is_metascheduler_entry' ) )
  {
    $require_cluster_us3_entry = function_exists( 'posix_getpwnam' ) ? posix_getpwnam( 'us3' ) : false;
    $require_cluster_us3_bin = ( $require_cluster_us3_entry ? $require_cluster_us3_entry[ 'dir' ] : '/home/us3' )
                              . '/lims/bin';
    require_once "$require_cluster_us3_bin/gridctl/pick_cluster_reserve.php";
  }

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
      $authorized = require_cluster_expand_localhost_authorized(
        $candidates, $global_cluster_details, $authorized );

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
