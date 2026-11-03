<?php
// Included at the top level of a submit page before anything is written: with
// no cluster selected, renders a "not submitted" page and sets $submit_stopped.
//
// lib/utility.php's cluster fieldset lists only clusters in the user's
// clusterAuthorizations, so an account authorized for no configured cluster
// posts no 'cluster' and the job would otherwise be reported as accepted.

// Whether the dispatch worker can take a pool submission: gfac's dispatch tables present and
// InnoDB. A hand upgrade can leave them out; receiving the analysis then would have it wait a day.
function require_cluster_dispatch_ready()
{
  global $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname, $class_dir;
  include_once $class_dir . 'dispatch_schema.php';
  try
  {
    $glink = mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname );
  }
  catch ( mysqli_sql_exception $e )
  {
    $glink = false;
  }
  if ( ! $glink )
  {
    error_log( 'pool submission refused: the gfac database could not be reached' );
    return false;
  }
  $problems = dispatch_schema_problems( $glink, $globaldbname, dispatch_gfac_tables() );
  mysqli_close( $glink );
  if ( $problems )
  {
    error_log( 'pool submission refused, the dispatch worker cannot run: ' . implode( '; ', $problems ) );
    return false;
  }
  return true;
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

// A posted cluster may be a metascheduler pool (its $cluster_details row
// carries a 'clusters' list rather than a real sshport/workdir/queue). A pool is
// not resolved here: the page records the analysis for the dispatch queue
// (lib/dispatch_intake.php) and the dispatch worker selects the member, using
// current authorization and the job's actual plan. A disabled pool refuses new
// submissions outright. A real cluster submits directly, as it always has.
$require_cluster_entry = $submit_stopped
  ? null
  : ( $global_cluster_details[ $_SESSION['cluster']['shortname'] ] ?? null );
$require_cluster_is_pool = is_array( $require_cluster_entry )
  && array_key_exists( 'clusters', $require_cluster_entry )
  && is_array( $require_cluster_entry[ 'clusters' ] );

if ( $require_cluster_is_pool )
{
  $pseudo_cluster = $_SESSION['cluster']['shortname'];
  if ( array_key_exists( 'active', $require_cluster_entry ) && ! $require_cluster_entry[ 'active' ] )
  {
    $submit_stop_msg = "<b>metascheduler '" . htmlspecialchars( $pseudo_cluster, ENT_QUOTES )
                     . "' is disabled (active=false).</b><br/>"
                     . "Nothing was submitted. Choose a cluster and submit again.";
    include __DIR__ . '/submit_stop.php';
    $submit_stopped = true;
  }
  elseif ( ! require_cluster_dispatch_ready() )
  {
    $submit_stop_msg = "<b>Automatic cluster selection ('" . htmlspecialchars( $pseudo_cluster, ENT_QUOTES )
                     . "') is not available on this server right now.</b><br/>"
                     . "Nothing was submitted. Choose a cluster and submit again, and let your administrator know.";
    include __DIR__ . '/submit_stop.php';
    $submit_stopped = true;
  }
  else
  {
    $_SESSION['cluster']['dispatch'] = true;
  }
}
