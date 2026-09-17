<?php
/*
 * Post-upgrade installation check.
 *
 * Read-only. Answers "we changed things and believe they are right; prove it"
 * for the configuration surface: the versioned base, per-instance overlays,
 * the compatibility shims, credentials, and the deployment globals whose keys
 * moved. It writes nothing, connects to no cluster, and prints no secret.
 *
 * Exit status: 0 when nothing failed (warnings allowed), 1 when any check
 * failed, 2 when the check tool could not run at all.
 *
 * Usage:
 *   php tools/check_installation.php [options]
 *
 *   --config-root=PATH        default: the loader's compiled-in root
 *   --credentials-file=PATH   default: ~us3/lims/.us3lims.ini
 *   --global-config=PATH      deployment global_config.php to inspect
 *   --instance=NAME           check only this instance; repeatable
 *   --dbinst-root=PATH        where instance checkouts live, for the
 *                             unmigrated-instance scan
 *   --deep                    also open each database with its real credentials
 *   --quiet                   print only WARN and FAIL lines
 */

if ( php_sapi_name() !== 'cli' )
{
  header( 'HTTP/1.1 403 Forbidden' );
  exit( "check_installation.php is a command line tool\n" );
}

require_once __DIR__ . '/../lib/dbinst_config_loader.php';

/* ------------------------------------------------------------------ report */

$GLOBALS[ 'check_counts' ]  = array( 'PASS' => 0, 'WARN' => 0, 'FAIL' => 0 );
$GLOBALS[ 'check_quiet' ]   = false;
$GLOBALS[ 'check_section' ] = '';

function check_section( $title )
{
  $GLOBALS[ 'check_section' ] = $title;
  if ( !$GLOBALS[ 'check_quiet' ] )
    echo "\n== $title\n";
}

function check_report( $status, $name, $detail = '' )
{
  $GLOBALS[ 'check_counts' ][ $status ]++;

  if ( $GLOBALS[ 'check_quiet' ] && $status === 'PASS' )
    return $status === 'PASS';

  if ( $GLOBALS[ 'check_quiet' ] && $GLOBALS[ 'check_section' ] !== '' )
  {
    echo "\n== " . $GLOBALS[ 'check_section' ] . "\n";
    $GLOBALS[ 'check_section' ] = '';
  }

  printf( "  [%-4s] %-46s %s\n", $status, $name, $detail );
  return $status === 'PASS';
}

function check_pass( $name, $detail = '' ) { return check_report( 'PASS', $name, $detail ); }
function check_warn( $name, $detail = '' ) { return check_report( 'WARN', $name, $detail ); }
function check_fail( $name, $detail = '' ) { return check_report( 'FAIL', $name, $detail ); }

function check_abort( $message )
{
  fwrite( STDERR, "ERROR: $message\n" );
  exit( 2 );
}

/* ------------------------------------------------------------- small tools */

/*
 * A subprocess that dies reports a fatal with a stack trace. The useful part
 * is the exception message; the frames are noise in a one-line report and
 * would push the rest of the check off screen.
 */
function check_condense_error( $text )
{
  if ( preg_match( '/Uncaught \w+(?:\\\\\w+)*: (.+?) in \//s', $text, $m ) )
    return trim( $m[ 1 ] );

  $first = strtok( trim( $text ), "\n" );
  return $first === false ? 'failed with no output' : $first;
}

/*
 * Run PHP in a clean process and decode its JSON.
 *
 * Needed for two independent reasons: the loader refuses to load a second
 * instance in one process, and a deployment global_config.php is executable
 * code we do not want in the checker's own scope.
 */
function check_subprocess( $source )
{
  $file = tempnam( sys_get_temp_dir(), 'us3-check-' );
  if ( $file === false )
    check_abort( 'could not create a temporary file' );

  file_put_contents( $file, $source );
  $output = array();
  $code   = 0;
  exec( 'php ' . escapeshellarg( $file ) . ' 2>&1', $output, $code );
  unlink( $file );

  $text = implode( "\n", $output );
  if ( $code !== 0 )
    return array( 'ok' => false, 'error' => check_condense_error( $text ) );

  $decoded = json_decode( $text, true );
  if ( !is_array( $decoded ) )
    return array( 'ok' => false, 'error' => $text );

  return array( 'ok' => true, 'data' => $decoded );
}

function check_mode( $path )
{
  $perms = @fileperms( $path );
  return $perms === false ? null : ( $perms & 0777 );
}

function check_owner( $path )
{
  $uid = @fileowner( $path );
  if ( $uid === false )
    return '?';

  $entry = function_exists( 'posix_getpwuid' ) ? posix_getpwuid( $uid ) : false;
  return $entry ? $entry[ 'name' ] : (string) $uid;
}

/* Reject anything readable beyond its owner and group. */
function check_secret_mode( $path, $label )
{
  $mode = check_mode( $path );
  if ( $mode === null )
    return check_fail( "$label permissions", "cannot stat $path" );

  $printable = sprintf( '%04o owner=%s', $mode, check_owner( $path ) );

  if ( $mode & 0004 )
    return check_fail( "$label permissions", "world readable ($printable): $path" );
  if ( $mode & 0002 )
    return check_fail( "$label permissions", "world writable ($printable): $path" );
  if ( $mode & 0020 )
    return check_warn( "$label permissions", "group writable ($printable): $path" );

  return check_pass( "$label permissions", $printable );
}

/* ---------------------------------------------------------------- options */

$options = getopt( '', array(
  'config-root::', 'credentials-file::', 'global-config::',
  'instance::', 'dbinst-root::', 'deep', 'quiet', 'help'
) );

if ( isset( $options[ 'help' ] ) )
{
  readfile( __FILE__ );
  exit( 0 );
}

$GLOBALS[ 'check_quiet' ] = isset( $options[ 'quiet' ] );
$deep = isset( $options[ 'deep' ] );

$config_root_given = isset( $options[ 'config-root' ] );
$config_root = $config_root_given
             ? rtrim( $options[ 'config-root' ], '/' )
             : us3_dbinst_config_root();

$credentials = isset( $options[ 'credentials-file' ] )
             ? $options[ 'credentials-file' ]
             : us3_dbinst_config_credentials_file();

$only = array();
if ( isset( $options[ 'instance' ] ) )
  $only = is_array( $options[ 'instance' ] )
        ? $options[ 'instance' ] : array( $options[ 'instance' ] );

echo "UltraScan LIMS installation check\n";
echo "Config root:  $config_root\n";
echo "Credentials:  " . ( $credentials === '' ? '(unresolved)' : $credentials ) . "\n";
echo "Checker from: " . realpath( __DIR__ . '/..' ) . "\n";
echo "Contract:     v" . us3_dbinst_config_contract_version()
   . ' (' . us3_dbinst_config_base_filename() . ")\n";

/* ------------------------------------------------------------- host base */

check_section( 'Host base' );

$base_values = null;

if ( !is_dir( $config_root ) )
{
  /*
   * Migration is opt-in per instance, so a site with nothing migrated has no
   * config root and is not broken. Only an explicit --config-root asserts that
   * one should be there.
   */
  $config_root_given
    ? check_fail( 'config root exists', $config_root )
    : check_warn( 'base and overlay configuration installed',
                  "none at $config_root; every instance is on a legacy "
                  . 'config.php, which is a supported state' );
}
else
{
  check_pass( 'config root exists', $config_root );

  $base_path = $config_root . '/' . us3_dbinst_config_base_filename();
  if ( !is_file( $base_path ) )
  {
    check_fail( 'base file exists', $base_path );

    /* A base for another contract version is the likely upgrade mistake. */
    foreach ( glob( $config_root . '/dbinst-base.v*.php' ) ?: array() as $other )
      check_warn( 'base file version', 'found ' . basename( $other )
                  . ', this code wants ' . us3_dbinst_config_base_filename() );
  }
  else
  {
    check_pass( 'base file exists', $base_path );
    check_secret_mode( $base_path, 'base file' );

    try
    {
      $base_values = us3_dbinst_config_load_base( $config_root );
      check_pass( 'base validates against the v'
                  . us3_dbinst_config_contract_version() . ' contract',
                  count( $base_values ) . ' values' );

      foreach ( array( 'submit_dir', 'class_dir' ) as $key )
      {
        $dir = $base_values[ $key ];
        is_dir( $dir )
          ? check_pass( "base $key exists", $dir )
          : check_warn( "base $key exists", "not a directory: $dir" );
      }

      $dbinst_root = rtrim( $base_values[ 'dbinst_root' ], '/' );
      is_dir( $dbinst_root )
        ? check_pass( 'base dbinst_root exists', $dbinst_root )
        : check_fail( 'base dbinst_root exists', "not a directory: $dbinst_root" );

      /* Configuration under the dbinst root is inside the served tree. */
      if ( strpos( realpath( $config_root ) . '/', $dbinst_root . '/' ) === 0 )
        check_fail( 'base is outside the served tree',
                    "$config_root is under $dbinst_root" );
      else
        check_pass( 'base is outside the served tree' );
    }
    catch ( RuntimeException $e )
    {
      check_fail( 'base validates', $e->getMessage() );
    }
  }

  is_dir( $config_root . '/instances' )
    ? check_pass( 'instances directory exists', $config_root . '/instances' )
    : check_fail( 'instances directory exists', $config_root . '/instances' );
}

/* ----------------------------------------------------------- credentials */

check_section( 'Credentials' );

if ( $credentials === '' )
  check_fail( 'credential file resolved',
              'no us3 account found and no --credentials-file given' );
elseif ( !is_file( $credentials ) )
  check_fail( 'credential file exists', $credentials );
elseif ( !is_readable( $credentials ) )
  check_fail( 'credential file readable by this account', $credentials );
else
{
  check_pass( 'credential file exists', $credentials );
  check_secret_mode( $credentials, 'credential file' );

  $parsed = @parse_ini_file( $credentials, true );
  if ( !is_array( $parsed ) )
    check_fail( 'credential file parses', $credentials );
  elseif ( !isset( $parsed[ 'gfac' ][ 'password' ] ) ||
           !is_string( $parsed[ 'gfac' ][ 'password' ] ) ||
           $parsed[ 'gfac' ][ 'password' ] === '' )
    check_fail( 'credential file has a gfac password',
                'migrated instances fail every request without it' );
  else
    check_pass( 'credential file has a gfac password' );

  /*
   * The file is resolved from the us3 account's home while web requests
   * usually run as another account. A legacy config.php degraded quietly when
   * the web account could not read it; a migrated instance fails outright, so
   * this is worth naming rather than leaving to discovery.
   */
  $mode = check_mode( $credentials );
  if ( $mode !== null && !( $mode & 0040 ) )
    check_warn( 'credential file readable by the web account',
                sprintf( 'mode %04o is owner-only; confirm the web account is '
                         . 'the owner (%s)', $mode, check_owner( $credentials ) ) );
  else
    check_warn( 'credential file readable by the web account',
                'verify from the web account; this check runs as '
                . ( function_exists( 'posix_geteuid' )
                    ? check_owner( $credentials ) . '-readable, uid '
                      . posix_geteuid() : 'an unknown uid' ) );
}

/* -------------------------------------------------------------- instances */

check_section( 'Instances' );

$overlays = array();
foreach ( glob( $config_root . '/instances/*.php' ) ?: array() as $path )
  $overlays[] = basename( $path, '.php' );

if ( $only )
  $overlays = array_values( array_intersect( $overlays, $only ) );

if ( !$overlays )
  check_warn( 'migrated instances found',
              'none; every instance is still on a legacy config.php' );

foreach ( $overlays as $instance )
{
  check_section( "Instance $instance" );
  $overlay_path = $config_root . '/instances/' . $instance . '.php';
  check_secret_mode( $overlay_path, 'overlay' );

  /* The loader refuses a second instance per process, so isolate each one. */
  $probe = check_subprocess(
      "<?php\n"
    . 'define(' . var_export( 'US3_DBINST_CONFIG_ROOT', true ) . ', '
    . var_export( $config_root, true ) . ");\n"
    . 'define(' . var_export( 'US3_DBINST_CREDENTIALS_FILE', true ) . ', '
    . var_export( $credentials, true ) . ");\n"
    . 'require ' . var_export( __DIR__ . '/../lib/dbinst_config_loader.php', true ) . ";\n"
    . '$v = us3_dbinst_config_load(' . var_export( $instance, true ) . ", null, true);\n"
      /* Names and paths only. Nothing here may carry a credential. */
    . "echo json_encode(array(\n"
    . "  'dbname' => \$v['dbname'], 'dbhost' => \$v['dbhost'],\n"
    . "  'full_path' => \$v['full_path'], 'data_dir' => \$v['data_dir'],\n"
    . "  'org_site' => \$v['org_site'],\n"
    . "  'has_globaldbpasswd' => \$v['globaldbpasswd'] !== '',\n"
    . "));\n" );

  if ( !$probe[ 'ok' ] )
  {
    check_fail( 'effective configuration loads', $probe[ 'error' ] );
    continue;
  }

  $effective = $probe[ 'data' ];
  check_pass( 'effective configuration loads',
              'db=' . $effective[ 'dbname' ] . ' site=' . $effective[ 'org_site' ] );

  $effective[ 'dbname' ] === $instance
    ? check_pass( 'database name matches the instance' )
    : check_fail( 'database name matches the instance',
                  $effective[ 'dbname' ] . " != $instance" );

  $effective[ 'has_globaldbpasswd' ]
    ? check_pass( 'global database password resolved' )
    : check_fail( 'global database password resolved', 'empty' );

  foreach ( array( 'full_path' => 'FAIL', 'data_dir' => 'FAIL' ) as $key => $severity )
  {
    $dir = $effective[ $key ];
    if ( !is_dir( $dir ) )
      check_fail( "$key exists", $dir );
    elseif ( $key === 'data_dir' && !is_writable( $dir ) )
      check_warn( "$key writable", "not writable by this account: $dir" );
    else
      check_pass( "$key exists", $dir );
  }

  /* The shim, and the marker gridctl/submitctl.php greps for. */
  $config_php = rtrim( $effective[ 'full_path' ], '/' ) . '/config.php';
  if ( !is_file( $config_php ) )
  {
    check_fail( 'config.php present', $config_php );
    continue;
  }

  $source = (string) file_get_contents( $config_php );
  $is_shim = strpos( $source, 'us3_dbinst_config_bootstrap' ) !== false;

  check_pass( 'config.php present',
              $is_shim ? 'base+overlay shim' : 'legacy complete file' );

  if ( strpos( $source, '$is_cli' ) === false )
    check_fail( 'config.php declares $is_cli literally',
                'submitctl.php greps for it and skips instances without it' );
  else
    check_pass( 'config.php declares $is_cli literally' );

  if ( $is_shim )
  {
    if ( strpos( $source, var_export( $instance, true ) ) === false )
      check_fail( 'shim names this instance', $config_php );
    else
      check_pass( 'shim names this instance' );

    $loader = rtrim( $effective[ 'full_path' ], '/' ) . '/lib/dbinst_config_loader.php';
    if ( !is_file( $loader ) )
      check_fail( 'instance ships a loader', $loader );
    else
    {
      $declared = @file_get_contents( $loader );
      if ( preg_match( '/function\s+us3_dbinst_config_contract_version\s*\(\s*\)'
                       . '\s*\{\s*return\s+(\d+)\s*;/', (string) $declared, $m ) )
      {
        (int) $m[ 1 ] === us3_dbinst_config_contract_version()
          ? check_pass( 'instance loader contract version', 'v' . $m[ 1 ] )
          : check_fail( 'instance loader contract version',
                        'instance is v' . $m[ 1 ] . ', base is v'
                        . us3_dbinst_config_contract_version() );
      }
      else
        check_fail( 'instance loader declares a contract version', $loader );
    }
  }
  else
    check_warn( 'instance is migrated',
                'an overlay exists but config.php is still the legacy file' );

  if ( $deep )
  {
    $connect = check_subprocess(
        "<?php\n"
      . 'define(' . var_export( 'US3_DBINST_CONFIG_ROOT', true ) . ', '
      . var_export( $config_root, true ) . ");\n"
      . 'define(' . var_export( 'US3_DBINST_CREDENTIALS_FILE', true ) . ', '
      . var_export( $credentials, true ) . ");\n"
      . 'require ' . var_export( __DIR__ . '/../lib/dbinst_config_loader.php', true ) . ";\n"
      . '$v = us3_dbinst_config_load(' . var_export( $instance, true ) . ", null, true);\n"
      . "\$r = array('instance' => false, 'global' => false);\n"
      . "foreach (array('instance' => array('dbhost','dbusername','dbpasswd','dbname'),\n"
      . "               'global' => array('globaldbhost','globaldbuser','globaldbpasswd','globaldbname'))\n"
      . "         as \$which => \$k) {\n"
      . "  try { \$c = @mysqli_connect(\$v[\$k[0]], \$v[\$k[1]], \$v[\$k[2]], \$v[\$k[3]]);\n"
      . "        \$r[\$which] = (bool) \$c; if (\$c) mysqli_close(\$c); }\n"
      . "  catch (Throwable \$e) { \$r[\$which] = false; }\n"
      . "}\n"
      . "echo json_encode(\$r);\n" );

    if ( !$connect[ 'ok' ] )
      check_fail( 'database connections', $connect[ 'error' ] );
    else
    {
      $connect[ 'data' ][ 'instance' ]
        ? check_pass( 'instance database connects', $effective[ 'dbname' ] )
        : check_fail( 'instance database connects', $effective[ 'dbname' ]
                      . ' on ' . $effective[ 'dbhost' ] );
      $connect[ 'data' ][ 'global' ]
        ? check_pass( 'global database connects' )
        : check_fail( 'global database connects', 'gfac' );
    }
  }
}

/* ------------------------------------------------- unmigrated instances */

/*
 * Most instances are still on a complete config.php, and they can be broken by
 * an upgrade too. The one thing worth asserting without executing them is the
 * marker gridctl/submitctl.php greps for: an instance whose config.php lacks a
 * literal $is_cli is skipped by the submission daemon, and nothing surfaces
 * that in the web UI or the job logs.
 */
check_section( 'Unmigrated instances' );

$dbinst_root = '';
if ( isset( $options[ 'dbinst-root' ] ) )
  $dbinst_root = rtrim( $options[ 'dbinst-root' ], '/' );
elseif ( is_array( $base_values ) )
  $dbinst_root = rtrim( $base_values[ 'dbinst_root' ], '/' );
else
  foreach ( array( '/srv/www/htdocs/uslims3', '/var/www/html/uslims3' ) as $candidate )
    if ( is_dir( $candidate ) ) { $dbinst_root = $candidate; break; }

if ( $dbinst_root === '' || !is_dir( $dbinst_root ) )
  check_warn( 'dbinst root located',
              'pass --dbinst-root=PATH to check instances that are not migrated' );
else
{
  check_pass( 'dbinst root located', $dbinst_root );
  $legacy = 0;

  foreach ( glob( $dbinst_root . '/uslims3_*', GLOB_ONLYDIR ) ?: array() as $dir )
  {
    $instance = basename( $dir );

    /* newlims is the metadata application, not a dbinst instance. */
    if ( $instance === 'uslims3_newlims' )
      continue;
    if ( in_array( $instance, $overlays, true ) )
      continue;
    if ( $only && !in_array( $instance, $only, true ) )
      continue;

    $config_php = $dir . '/config.php';
    if ( !is_file( $config_php ) )
    {
      check_fail( "$instance config.php present", $config_php );
      continue;
    }

    $source = (string) file_get_contents( $config_php );

    /*
     * A shim with no overlay behind it is the one state that looks unmigrated
     * from here but is not: every request to the instance fatals in the loader.
     * Classifying by the file's content rather than by the overlay's presence
     * is what tells the two apart.
     */
    if ( strpos( $source, 'us3_dbinst_config_bootstrap' ) !== false )
      check_fail( "$instance overlay present",
                  "config.php is a base+overlay shim but $config_root/instances/"
                  . "$instance.php is missing; every request will fail" );
    else
      $legacy++;

    strpos( $source, '$is_cli' ) === false
      ? check_fail( "$instance declares \$is_cli literally",
                    'submitctl.php will skip this instance' )
      : check_pass( "$instance declares \$is_cli literally" );

    $lint = array();
    $code = 0;
    exec( 'php -l ' . escapeshellarg( $config_php ) . ' 2>&1', $lint, $code );
    $code === 0
      ? check_pass( "$instance config.php parses" )
      : check_fail( "$instance config.php parses", implode( ' ', $lint ) );
  }

  check_pass( 'unmigrated instances scanned', "$legacy on legacy config.php" );
}

/* ------------------------------------------------------ deployment globals */

check_section( 'Deployment globals' );

$global_config = isset( $options[ 'global-config' ] ) ? $options[ 'global-config' ] : '';
if ( $global_config === '' )
{
  foreach ( array( '/srv/www/htdocs/common/global_config.php',
                   '/var/www/html/common/global_config.php' ) as $candidate )
    if ( is_file( $candidate ) ) { $global_config = $candidate; break; }
}

if ( $global_config === '' || !is_file( $global_config ) )
  check_warn( 'global_config.php found',
              'not found; pass --global-config=PATH to check it' );
else
{
  check_pass( 'global_config.php found', $global_config );

  $probe = check_subprocess(
      "<?php\n"
    . 'include ' . var_export( $global_config, true ) . ";\n"
    . "\$globals = array();\n"
    . "foreach (get_defined_vars() as \$k => \$x)\n"
    . "  if (strpos(\$k, 'global_') === 0) \$globals[] = \$k;\n"
    . "echo json_encode(array(\n"
    . "  'globals' => \$globals,\n"
    . "  'clusters' => isset(\$cluster_details) && is_array(\$cluster_details)\n"
    . "                ? \$cluster_details : null,\n"
    . "  'breaker_dir' => isset(\$global_circuit_breaker_dir)\n"
    . "                   ? \$global_circuit_breaker_dir : null,\n"
    . "));\n" );

  if ( !$probe[ 'ok' ] )
    check_fail( 'global_config.php loads', $probe[ 'error' ] );
  else
  {
    check_pass( 'global_config.php loads',
                count( $probe[ 'data' ][ 'globals' ] ) . ' global_* settings' );

    /*
     * Keys retired when remote timeout, retry, and breaker policy moved into
     * remote_exec. A deployment that still sets one is not broken by it, but
     * the operator believes they have configured something that no longer
     * has a reader.
     */
    $retired = array(
      'global_ssh_connect_timeout_seconds', 'global_ssh_command_timeout_seconds',
      'global_ssh_copy_timeout_seconds', 'global_ssh_retries',
      'global_ssh_retry_wait_seconds', 'global_circuit_breaker_failures',
      'global_circuit_breaker_cooldown_seconds', 'global_circuit_breaker_disabled',
      'global_timeout_bin', 'global_sbatch_submit_retries',
      'global_sbatch_submit_retry_wait_seconds', 'global_cluster_down_after_failures',
    );
    $found = array_values( array_intersect( $retired, $probe[ 'data' ][ 'globals' ] ) );
    $found
      ? check_warn( 'no retired global keys are set', implode( ', ', $found ) )
      : check_pass( 'no retired global keys are set' );

    /*
     * These are different: remote_exec throws on a cluster entry that still
     * carries one, so every call to that cluster fails.
     */
    $legacy_cluster = array( 'ssh_connect_timeout', 'ssh_command_timeout',
                             'ssh_copy_timeout', 'ssh_retries', 'ssh_retry_wait' );
    $limits = array( 'connect_timeout_seconds' => array( 1, 300 ),
                     'command_timeout_seconds' => array( 1, 3600 ),
                     'copy_timeout_seconds'    => array( 1, 86400 ) );

    $clusters = $probe[ 'data' ][ 'clusters' ];
    if ( !is_array( $clusters ) )
      check_warn( 'cluster_details defined', 'not an array or not defined' );
    else
    {
      $bad = array();
      foreach ( $clusters as $name => $details )
      {
        if ( !is_array( $details ) )
          continue;

        foreach ( $legacy_cluster as $key )
          if ( array_key_exists( $key, $details ) )
            $bad[] = "$name.$key";

        if ( array_key_exists( 'remote_exec_overrides', $details ) )
        {
          $over = $details[ 'remote_exec_overrides' ];
          if ( !is_array( $over ) || $over === array() )
            $bad[] = "$name.remote_exec_overrides must be a non-empty array";
          else
            foreach ( $over as $key => $value )
            {
              if ( !isset( $limits[ $key ] ) )
                $bad[] = "$name.remote_exec_overrides.$key is not a known key";
              elseif ( !is_int( $value ) || $value < $limits[ $key ][ 0 ] ||
                       $value > $limits[ $key ][ 1 ] )
                $bad[] = "$name.remote_exec_overrides.$key out of range";
            }
        }
      }

      $bad
        ? check_fail( 'cluster entries use the current key surface',
                      implode( '; ', $bad ) )
        : check_pass( 'cluster entries use the current key surface',
                      count( $clusters ) . ' clusters' );
    }

    /*
     * The breaker is cross-process memory shared by the web tier and the us3
     * cron/daemon account. If the directory is unusable it silently disables
     * itself and every call reverts to per-process retry.
     */
    $breaker = $probe[ 'data' ][ 'breaker_dir' ];
    if ( $breaker === null )
    {
      $breaker = '/var/tmp/us3-circuit-breaker';
      check_pass( 'breaker directory owner', "code default ($breaker)" );
    }
    else
      check_warn( 'breaker directory owner',
                  "global_config.php overrides the code default: $breaker" );

    if ( is_dir( $breaker ) )
      is_writable( $breaker )
        ? check_pass( 'breaker directory writable', $breaker )
        : check_fail( 'breaker directory writable',
                      "not writable by this account: $breaker" );
    else
    {
      $parent = dirname( $breaker );
      is_dir( $parent ) && is_writable( $parent )
        ? check_pass( 'breaker directory creatable', "parent $parent is writable" )
        : check_fail( 'breaker directory creatable',
                      "$breaker is absent and $parent is not writable" );
    }
  }
}

/* ------------------------------------------------------------------ summary */

$counts = $GLOBALS[ 'check_counts' ];
echo "\n";
echo "Summary: {$counts['PASS']} passed, {$counts['WARN']} warnings, "
   . "{$counts['FAIL']} failed\n";

if ( $counts[ 'FAIL' ] )
{
  echo "Result: NOT READY. Resolve the failures above before serving traffic.\n";
  exit( 1 );
}

echo $counts[ 'WARN' ]
   ? "Result: OK with warnings. Review each warning; several are checks this\n"
     . "        tool cannot make on your behalf, such as access from the web\n"
     . "        account rather than the account running this command.\n"
   : "Result: OK.\n";
exit( 0 );
