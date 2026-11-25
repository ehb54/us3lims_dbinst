<?php
/*
 * submit_result.php
 *
 * What a submit page reports about the jobs it sent to a cluster. The page shows
 * the outcome; logins, paths, commands and cluster output stay in the server log.
 */

// Submits each request file to its cluster with one submitter and returns the outcomes. The log
// text it collects in $log is for the command line only.
function submit_requests( array $filenames, $metascheduler_cluster, $is_cli, &$log )
{
  $job      = new submit_slurm();
  $results  = array();
  $log      = '';
  $save_cwd = getcwd();
  try
  {
    foreach ( $filenames as $filename )
    {
      submit_progress( 'submitting ' . basename( $filename ) );
      chdir( dirname( $filename ) );
      $job->clear();
      $job->parse_input( basename( $filename ) );
      if ( $metascheduler_cluster !== null )
        $job->set_metascheduler_cluster_executing( $metascheduler_cluster );
      if ( ! DEBUG ) $job->submit();
      $outcome = method_exists( $job, 'submission_result' ) ? $job->submission_result() : null;
      $log    .= submit_result_record( $results, $outcome, $job->get_messages(), $filename, $is_cli );
    }
  }
  finally
  {
    $job->close_transport();
    chdir( $save_cwd );
  }
  return $results;
}

// What the page says about one request. $outcome is submit_slurm's submission_result(), or null from
// a common without it. state: submitted, untracked (accepted, but recording it had a problem), failed
// (nothing was submitted) or unknown (it may have been submitted).
function submit_result( $outcome, $filename )
{
  $request   = preg_match( '/-(\d+)\.xml\z/', (string) $filename, $m ) ? (int) $m[1] : 0;
  $result    = array( 'state' => 'unknown', 'job' => '', 'request' => $request,
                      'text' => 'It is not known whether the cluster received this request. Do not submit it again '
                              . 'until your site administrator has checked; give them the request number.' );
  $certainty = $outcome['certainty'] ?? '';

  if ( $certainty === 'ACCEPTED' )
  {
    $result['state'] = $outcome['tracked'] ? 'submitted' : 'untracked';
    $result['job']   = (string) $outcome['job_id'];
    $result['text']  = $outcome['tracked'] ? 'Submitted'
                     : 'The cluster accepted this job, but the LIMS could not finish recording it. '
                     . 'Do not submit it again; contact your site administrator.';
  }
  elseif ( $certainty === 'NOT_ACCEPTED' )
  {
    $unreachable = 'The cluster could not be reached.';
    $texts = array( 'breaker_open' => $unreachable, 'never_started' => $unreachable,
                    'connect_failure' => $unreachable, 'unreachable' => $unreachable,
                    'definite_rejection' => 'The cluster rejected this job.',
                    'busy'               => 'The cluster is busy or its queue is full.',
                    'staging_rejected'   => 'The cluster did not accept the input files.',
                    'plan_rejected'      => "This job does not fit the cluster's limits or settings." );
    $result['state'] = 'failed';
    $result['text']  = $texts[ $outcome['reason'] ] ?? 'The job could not be prepared for this cluster.';
  }
  return $result;
}

// Takes one request's outcome: keeps the full messages of a problem in the server log, and gives
// autoflow its failure in $cli_errors. Returns the log for the command line, '' on the web.
function submit_result_record( array &$results, $outcome, array $messages, $filename, $is_cli )
{
  $result    = submit_result( $outcome, $filename );
  $results[] = $result;
  $lines     = array();
  foreach ( $messages as $msg )
    $lines[] = trim( preg_replace( '/\s+/', ' ', is_string( $msg ) ? $msg : json_encode( $msg ) ) );

  if ( ! $is_cli )
  {
    if ( $result['state'] !== 'submitted' )
      foreach ( $lines as $line )
        error_log( "submission of request {$result['request']} ({$result['state']}): $line" );
    return '';
  }

  $log = implode( "\n", $lines );
  if ( $outcome !== null )
  {
    // gridctl fails a stage left unrecorded, and names this as why.
    if ( $result['state'] === 'untracked' )
      $GLOBALS['gridctl_submission_failure_reason'] = "job {$result['job']} was accepted but not fully recorded; do not resubmit: "
                                                    . trim( preg_replace( '/\s+/', ' ', $outcome['detail'] ) );
    if ( $result['state'] === 'failed' || $result['state'] === 'unknown' )
      $GLOBALS['cli_errors'][] = 'ERROR: ' . ( trim( preg_replace( '/\s+/', ' ', $outcome['detail'] ) ) ?: $result['text'] );
    // An older gridctl fails a stage on any printed ERROR: line, and cluster output can hold one for an accepted job.
    $log = str_replace( 'ERROR:', 'ERROR -', $log );
  }
  return "<pre>\n" . htmlspecialchars( $log, ENT_QUOTES | ENT_SUBSTITUTE ) . "\n</pre>\n";
}

// "X Analysis Submitted", or what happened instead.
function submit_result_title( $title, array $results )
{
  $states = array_column( $results, 'state' );
  $sent   = count( array_intersect( $states, array( 'submitted', 'untracked' ) ) );
  if ( $states && $sent === count( $states ) ) return $title;
  $what   = in_array( 'unknown', $states, true ) ? 'Submission Uncertain' : ( $sent ? 'Partly Submitted' : 'Not Submitted' );
  return str_replace( 'Submitted', $what, $title );
}

// The outcome panel of a submit page.
function submit_result_html( array $results, $cluster, $email, array $runs )
{
  $h      = function ( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES | ENT_SUBSTITUTE ); };
  $count  = array_count_values( array_column( $results, 'state' ) ) + array( 'submitted' => 0, 'untracked' => 0, 'failed' => 0, 'unknown' => 0 );
  $sent   = $count['submitted'] + $count['untracked'];
  $total  = count( $results );
  $level  = $total && $count['submitted'] === $total ? 'ok' : ( $total && $sent === 0 && $count['unknown'] === 0 ? 'bad' : 'check' );
  if ( $total === 0 )
  {
    $level = 'bad';
    $head  = 'Nothing was submitted.';
  }
  elseif ( $sent === $total )
    $head  = $total === 1 ? 'Your analysis was submitted.' : "All $total requests were submitted.";
  elseif ( $count['failed'] === $total )
    $head  = $total === 1 ? 'Your analysis was not submitted.' : "None of the $total requests were submitted.";
  elseif ( $total === 1 )
    $head  = 'It is not known whether your analysis was submitted.';
  else
    $head  = "$sent of $total requests were submitted"
           . ( $count['unknown'] ? "; it is not known whether {$count['unknown']} more " . ( $count['unknown'] === 1 ? 'was.' : 'were.' ) : '.' );

  $facts = array();
  if ( (string) $cluster !== '' ) $facts['Cluster'] = $h( $cluster );
  $runs  = array_values( array_unique( array_filter( array_map( 'strval', $runs ), 'strlen' ) ) );
  if ( $runs )
    $facts[ count( $runs ) === 1 ? 'Run' : 'Runs' ] = implode( '<br />', array_map( $h, array_slice( $runs, 0, 5 ) ) )
                    . ( count( $runs ) > 5 ? "<br />and " . ( count( $runs ) - 5 ) . ' more' : '' );
  if ( $sent ) $facts['Results email'] = $h( $email );

  $html = "<div id=\"submit-result\" class=\"submit-result submit-result-$level\">\n  <h2>$head</h2>\n";
  if ( $facts )
  {
    $html .= "  <dl>\n";
    foreach ( $facts as $name => $value ) $html .= "    <dt>$name</dt><dd>$value</dd>\n";
    $html .= "  </dl>\n";
  }
  if ( $total )
  {
    $html .= "  <table>\n    <thead><tr><th>Request</th><th>Job</th><th>Result</th></tr></thead>\n    <tbody>\n";
    foreach ( $results as $r )
      $html .= "      <tr class=\"submit-result-{$r['state']}\"><td>" . ( $r['request'] ? $h( $r['request'] ) : '-' ) . "</td><td>"
             . ( $r['job'] !== '' ? $h( $r['job'] ) : '-' ) . "</td><td>" . $h( $r['text'] ) . "</td></tr>\n";
    $html .= "    </tbody>\n  </table>\n";
  }
  if ( $count['failed'] )
    $html .= "  <p>Requests shown in red were not submitted and can be submitted again, here or on another cluster. If it happens again, "
           . "give your site administrator the request number.</p>\n";
  return $html . "  <p class=\"submit-result-links\"><a href=\"queue_viewer.php\">View the queue</a></p>\n</div>\n";
}
