<?php
/*
 * Compare a trusted legacy dbinst config.php with the schema-v1 base/overlay
 * form. The default is dry-run. --write-candidate creates an overlay and a
 * config.php.base-overlay-candidate but never replaces config.php.
 */

require_once __DIR__ . '/../lib/dbinst_config_loader.php';

function migration_usage()
{
  global $argv;
  $self = isset( $argv[0] ) ? $argv[0] : 'migrate_instance_config.php';
  return "Usage: php $self --instance=uslims3_NAME --legacy=/path/config.php "
       . "[--config-root=/home/us3/lims/etc/config] "
       . "[--credentials-file=/home/us3/lims/.us3lims.ini] "
       . "[--write-candidate]\n";
}

function migration_fail( $message )
{
  fwrite( STDERR, "ERROR: $message\n" );
  exit( 1 );
}

function migration_capture_legacy( $path )
{
  $old_cwd = getcwd();
  chdir( dirname( $path ) );
  ob_start();
  include $path;
  ob_end_clean();
  if ( $old_cwd !== false )
    chdir( $old_cwd );

  $captured = get_defined_vars();
  unset( $captured[ 'path' ], $captured[ 'old_cwd' ], $captured[ 'captured' ] );
  return $captured;
}

function migration_write_temp( $path, $source, $mode )
{
  $directory = dirname( $path );
  if ( !is_dir( $directory ) || !is_writable( $directory ) )
    migration_fail( "directory is missing or unwritable: $directory" );

  $temporary = tempnam( $directory, '.us3-migration-' );
  if ( $temporary === false || file_put_contents( $temporary, $source ) === false )
    migration_fail( "could not write temporary file in $directory" );

  if ( !chmod( $temporary, $mode ) )
  {
    @unlink( $temporary );
    migration_fail( "could not set candidate permissions for: $path" );
  }
  return $temporary;
}

$options = getopt( '', array(
  'instance:', 'legacy:', 'config-root::', 'credentials-file::',
  'write-candidate'
) );

if ( !isset( $options[ 'instance' ] ) || !isset( $options[ 'legacy' ] ) )
  migration_fail( trim( migration_usage() ) );

$instance = $options[ 'instance' ];
$legacy_path = realpath( $options[ 'legacy' ] );
$config_root = isset( $options[ 'config-root' ] )
             ? rtrim( $options[ 'config-root' ], '/' )
             : us3_dbinst_config_root();
$write_candidate = isset( $options[ 'write-candidate' ] );

if ( isset( $options[ 'credentials-file' ] ) &&
     !defined( 'US3_DBINST_CREDENTIALS_FILE' ) )
  define( 'US3_DBINST_CREDENTIALS_FILE', $options[ 'credentials-file' ] );

us3_dbinst_config_assert_instance( $instance );
if ( $legacy_path === false || !is_file( $legacy_path ) )
  migration_fail( 'legacy config.php was not found' );

$base = us3_dbinst_config_load_base( $config_root );
$legacy = migration_capture_legacy( $legacy_path );
$required = us3_dbinst_config_overlay_required_types();
$optional = us3_dbinst_config_overlay_optional_types();
$overlay_values = array();

foreach ( $required as $key => $type )
{
  if ( !array_key_exists( $key, $legacy ) )
    migration_fail( "legacy config is missing '$key'" );
  $overlay_values[ $key ] = $legacy[ $key ];
}

foreach ( $optional as $key => $type )
{
  if ( array_key_exists( $key, $legacy ) && $legacy[ $key ] !== $base[ $key ] )
    $overlay_values[ $key ] = $legacy[ $key ];
}

$contract = us3_dbinst_config_overlay_contract( $instance, $overlay_values );
us3_dbinst_config_validate_overlay( $contract, $instance );

$expected = array_merge( $base, $overlay_values );
$dbinst_root = us3_dbinst_config_path_with_slash( $expected[ 'dbinst_root' ] );
unset( $expected[ 'dbinst_root' ] );
$expected[ 'full_path' ] = $dbinst_root . $instance . '/';
$expected[ 'data_dir' ] = $expected[ 'full_path' ] . 'data/';
$expected[ 'submit_dir' ] = us3_dbinst_config_path_with_slash(
  $expected[ 'submit_dir' ] );
$expected[ 'class_dir' ] = us3_dbinst_config_path_with_slash(
  $expected[ 'class_dir' ] );
$expected[ 'current_year' ] = date( 'Y' );
$expected[ 'is_cli' ] = php_sapi_name() == 'cli';

$differences = 0;
echo "Instance: $instance\n";
echo "Legacy: $legacy_path\n";
echo "Base: $config_root/" . us3_dbinst_config_base_filename() . "\n";
foreach ( $expected as $key => $value )
{
  if ( $key === 'timezone' )
  {
    $equal = date_default_timezone_get() === $value;
  }
  else
  {
    $equal = array_key_exists( $key, $legacy ) && $legacy[ $key ] === $value;
  }
  echo sprintf( "%-22s %s\n", $key, $equal ? 'equal' : 'DIFFERENT' );
  if ( !$equal )
    $differences++;
}

$credential_equal = isset( $legacy[ 'cfgfile' ] ) &&
                    $legacy[ 'cfgfile' ] === us3_dbinst_config_credentials_file();
echo sprintf( "%-22s %s\n", 'credential_ini',
              $credential_equal ? 'equal' : 'DIFFERENT' );
if ( !$credential_equal )
  $differences++;

/*
 * Reverse pass. The comparison above only walks the keys the v1 contract knows
 * about, so a hand-edited legacy config could report every one of them equal
 * while an assignment the contract has no place for is silently dropped. These
 * files are hand-maintained on deployed hosts, so that is the likely case, not
 * the exotic one. Report such names and refuse to migrate until a human has
 * decided where each belongs. Names only; the values may be site secrets.
 */
$accounted = array_keys( $expected );

/* Supplied by the loader at bootstrap rather than by the base or overlay. */
$accounted[] = 'cfgfile';
$accounted[] = 'configs';
$accounted[] = 'globaldbpasswd';

/* Legacy bootstrap locals that exist only to produce the values above. */
$accounted[] = 'us3pwentry';

$unreviewed = array_diff( array_keys( $legacy ), $accounted );
sort( $unreviewed );

if ( $unreviewed )
{
  echo "\nUnreviewed legacy assignments (not in the schema-v1 contract):\n";
  foreach ( $unreviewed as $key )
    echo sprintf( "%-22s %s\n", $key, 'NEEDS REVIEW' );
  echo "Each must be recognized as a base value, an overlay value, or\n"
     . "deliberately dropped before this instance can be migrated.\n";
  $differences += count( $unreviewed );
}

if ( $differences )
{
  echo "Result: NOT EQUIVALENT ($differences differences, "
     . count( $unreviewed ) . " needing review); no files written.\n";
  exit( 2 );
}

echo "Result: EQUIVALENT (secret values were compared but not displayed).\n";
if ( !$write_candidate )
{
  echo "Dry run only; use --write-candidate to create non-activating files.\n";
  exit( 0 );
}

$overlay_path = $config_root . '/instances/' . $instance . '.php';
$candidate_path = dirname( $legacy_path ) . '/config.php.base-overlay-candidate';
foreach ( array( $overlay_path, $candidate_path ) as $path )
{
  if ( file_exists( $path ) )
    migration_fail( "refusing to replace existing file: $path" );
  if ( !is_dir( dirname( $path ) ) || !is_writable( dirname( $path ) ) )
    migration_fail( "directory is missing or unwritable: " . dirname( $path ) );
}

$overlay_temp = migration_write_temp(
  $overlay_path, us3_dbinst_config_overlay_source( $contract ), 0640 );
$candidate_temp = migration_write_temp(
  $candidate_path, us3_dbinst_config_shim_source( $instance ), 0644 );
if ( !rename( $overlay_temp, $overlay_path ) )
{
  @unlink( $overlay_temp );
  @unlink( $candidate_temp );
  migration_fail( "could not install candidate overlay" );
}
if ( !rename( $candidate_temp, $candidate_path ) )
{
  @unlink( $candidate_temp );
  @unlink( $overlay_path );
  migration_fail( "could not install candidate shim" );
}
echo "Created: $overlay_path\n";
echo "Created: $candidate_path\n";
echo "The active legacy config.php was not changed.\n";
