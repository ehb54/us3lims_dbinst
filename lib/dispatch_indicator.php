<?php
// The open dispatch alert count shown to administrators in the menu: this
// instance's items, controller-level items listing its requests, and a lost
// worker heartbeat. Cached in the session for a minute; 0 when gfac cannot
// be read, so a database problem never breaks page rendering.
function dispatch_open_alert_count()
{
  global $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname, $dbname;

  $cached = $_SESSION['dispatch_open_alerts'] ?? null;
  if ( is_array( $cached ) && $cached['at'] > time() - 60 )
    return $cached['n'];
  $n = 0;
  try
  {
    $g = @mysqli_connect( $globaldbhost, $globaldbuser, $globaldbpasswd, $globaldbname );
    if ( $g )
    {
      $stmt = mysqli_prepare( $g, "SELECT COUNT(*) FROM dispatch_alert a WHERE a.openFlag = 1 AND ( a.instanceDb = ?
          OR ( a.instanceDb IS NULL AND ( a.kind = 'worker_heartbeat' OR EXISTS ( SELECT 1 FROM dispatch_alert_request q
               WHERE q.alertID = a.alertID AND q.instanceDb = ? ) ) ) )" );
      if ( $stmt )
      {
        mysqli_stmt_bind_param( $stmt, 'ss', $dbname, $dbname );
        mysqli_stmt_execute( $stmt );
        mysqli_stmt_bind_result( $stmt, $count );
        if ( mysqli_stmt_fetch( $stmt ) )
          $n = (int) $count;
        mysqli_stmt_close( $stmt );
      }
      mysqli_close( $g );
    }
  }
  catch ( Throwable $e )
  {
    $n = 0;
  }
  $_SESSION['dispatch_open_alerts'] = array( 'at' => time(), 'n' => $n );
  return $n;
}
