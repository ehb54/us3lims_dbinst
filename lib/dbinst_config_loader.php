<?php
/*
 * Versioned dbinst base + instance-overlay loader.
 *
 * This file is application code and travels with the dbinst checkout. Runtime
 * configuration remains outside the document root, under
 * /home/us3/lims/etc/config by default.
 */

/*
 * The base/overlay contract this loader implements.
 *
 * Every instance has its own dbinst checkout, so a process that handles more
 * than one instance sees more than one copy of this file, and PHP will not
 * redeclare functions. newinst therefore has to decide whether an already
 * loaded copy can stand in for the one shipping with the checkout it is
 * generating for, and this number is that decision: same version, same
 * contract. A breaking change bumps it and adds dbinst-base.v2.php rather than
 * altering v1 under deployed instances.
 *
 * newinst reads this out of the file textually, without executing it, so keep
 * the declaration literal: a bare integer return, no constants or expressions.
 */
function us3_dbinst_config_contract_version()
{
  return 1;
}

/*
 * The base file this loader reads. Versioned in the name so a v2 loader reads
 * dbinst-base.v2.php and leaves an already-installed v1 base untouched for the
 * instances still running v1 code.
 */
function us3_dbinst_config_base_filename()
{
  return 'dbinst-base.v' . us3_dbinst_config_contract_version() . '.php';
}

function us3_dbinst_config_base_types()
{
  return array(
    'org_name'        => 'string',
    'site_author'     => 'string',
    'site_keywords'   => 'string',
    'site_desc'       => 'string',
    'globaldbuser'    => 'string',
    'globaldbname'    => 'string',
    'globaldbhost'    => 'string',
    'ipaddr'          => 'string',
    'ipa_ext'         => 'string',
    'udpport'         => 'integer',
    'top_image'       => 'string',
    'top_banner'      => 'string',
    'submit_dir'      => 'string',
    'class_dir'       => 'string',
    'disclaimer_file' => 'string',
    'timezone'        => 'string',
    'dbinst_root'     => 'string',
    'enable_GMP'      => 'boolean',
    'enable_PAM'      => 'boolean'
  );
}

function us3_dbinst_config_overlay_required_types()
{
  return array(
    'org_site'       => 'string',
    'admin'          => 'string',
    'admin_phone'    => 'string',
    'admin_email'    => 'string',
    'dbusername'     => 'string',
    'dbpasswd'       => 'string',
    'dbname'         => 'string',
    'dbhost'         => 'string',
    'secure_user'    => 'string',
    'secure_pw'      => 'string',
    'last_update'    => 'string',
    'copyright_date' => 'string'
  );
}

function us3_dbinst_config_overlay_optional_types()
{
  return array(
    // These are the only v1 base values an instance may replace.
    'enable_GMP' => 'boolean',
    'enable_PAM' => 'boolean'
  );
}

function us3_dbinst_config_overlay_contract( $instance, $values )
{
  return array(
    'schema_version' => us3_dbinst_config_contract_version(),
    'instance'       => $instance,
    'values'         => $values
  );
}

function us3_dbinst_config_overlay_source( $contract )
{
  return "<?php\n"
       . "/* Generated configuration; do not edit by hand. */\n"
       . 'return ' . var_export( $contract, true ) . ";\n";
}

/*
 * The foreach publishes into whatever scope included config.php, which is
 * exactly what the legacy file's plain assignments did. That parity matters:
 * a legacy config.php included from inside a function set function locals and
 * touched no globals, and web/us3/lib/controls.php's check_filesize() relies
 * on it, including config.php mid-function purely so the top.php/links.php/
 * bottom.php it then includes can see those values.
 *
 * Do not add a $GLOBALS export here. It would look redundant at global scope,
 * where the foreach already writes globals, and would only take effect in the
 * function-scope case, turning an ordinary helper call into a rewrite of every
 * config global (including $configs, which holds the parsed .us3lims.ini).
 */
function us3_dbinst_config_shim_source( $instance )
{
  us3_dbinst_config_assert_instance( $instance );
  $literal = var_export( $instance, true );
  return <<<PHP
<?php
/* Generated configuration entry point; do not edit by hand. */
\$is_cli = php_sapi_name() == 'cli';
\$lims_instance = $literal;

require_once __DIR__ . '/lib/dbinst_config_loader.php';
\$dbinst_config_values = us3_dbinst_config_bootstrap( \$lims_instance );
foreach ( \$dbinst_config_values as \$dbinst_config_key => \$dbinst_config_value )
  \${\$dbinst_config_key} = \$dbinst_config_value;
unset( \$dbinst_config_key, \$dbinst_config_value, \$dbinst_config_values );

include_once __DIR__ . '/elog.php';

PHP;
}

function us3_dbinst_config_fail( $message )
{
  throw new RuntimeException( 'dbinst configuration: ' . $message );
}

function us3_dbinst_config_assert_instance( $instance )
{
  if ( !is_string( $instance ) ||
       !preg_match( '/^uslims3_[A-Za-z0-9_]+$/', $instance ) )
    us3_dbinst_config_fail( 'invalid instance name' );
}

function us3_dbinst_config_assert_type( $key, $value, $type, $source )
{
  $valid = false;

  switch ( $type )
  {
    case 'string':  $valid = is_string( $value );  break;
    case 'integer': $valid = is_int( $value );     break;
    case 'boolean': $valid = is_bool( $value );    break;
  }

  if ( !$valid )
    us3_dbinst_config_fail( "$source key '$key' must be $type" );

  if ( $type == 'string' && $value === '' &&
       !in_array( $key, array( 'admin_phone', 'disclaimer_file' ), true ) )
    us3_dbinst_config_fail( "$source key '$key' must not be empty" );
}

function us3_dbinst_config_read_file( $path, $source )
{
  if ( !is_file( $path ) || !is_readable( $path ) )
    us3_dbinst_config_fail( "$source file is missing or unreadable: $path" );

  $contract = include $path;
  if ( !is_array( $contract ) )
    us3_dbinst_config_fail( "$source file must return an array" );

  $top_keys = array_keys( $contract );
  sort( $top_keys );
  if ( $top_keys !== array( 'schema_version', 'values' ) &&
       $top_keys !== array( 'instance', 'schema_version', 'values' ) )
    us3_dbinst_config_fail( "$source file has unknown top-level keys" );

  $version = us3_dbinst_config_contract_version();
  if ( !isset( $contract[ 'schema_version' ] ) ||
       $contract[ 'schema_version' ] !== $version )
    us3_dbinst_config_fail(
      "$source schema_version must be integer $version" );

  if ( !isset( $contract[ 'values' ] ) || !is_array( $contract[ 'values' ] ) )
    us3_dbinst_config_fail( "$source values must be an array" );

  return $contract;
}

function us3_dbinst_config_validate_values( $values, $required_types,
                                             $optional_types, $source )
{
  $allowed = array_merge( $required_types, $optional_types );

  foreach ( $values as $key => $value )
  {
    if ( !isset( $allowed[ $key ] ) )
      us3_dbinst_config_fail( "$source has unknown key '$key'" );

    us3_dbinst_config_assert_type( $key, $value, $allowed[ $key ], $source );
  }

  foreach ( $required_types as $key => $type )
  {
    if ( !array_key_exists( $key, $values ) )
      us3_dbinst_config_fail( "$source is missing required key '$key'" );
  }
}

function us3_dbinst_config_root()
{
  if ( defined( 'US3_DBINST_CONFIG_ROOT' ) )
    return rtrim( US3_DBINST_CONFIG_ROOT, '/' );

  return '/home/us3/lims/etc/config';
}

function us3_dbinst_config_credentials_file()
{
  if ( defined( 'US3_DBINST_CREDENTIALS_FILE' ) )
    return US3_DBINST_CREDENTIALS_FILE;

  $entry = function_exists( 'posix_getpwnam' ) ? posix_getpwnam( 'us3' ) : false;
  return $entry ? $entry[ 'dir' ] . '/lims/.us3lims.ini' : '';
}

function us3_dbinst_config_path_with_slash( $path )
{
  return rtrim( $path, '/' ) . '/';
}

function us3_dbinst_config_load_base( $root )
{
  $root = rtrim( $root, '/' );
  $base = us3_dbinst_config_read_file(
    $root . '/' . us3_dbinst_config_base_filename(), 'base' );
  if ( isset( $base[ 'instance' ] ) )
    us3_dbinst_config_fail( 'base file must not declare an instance' );

  us3_dbinst_config_validate_values(
    $base[ 'values' ], us3_dbinst_config_base_types(), array(), 'base' );

  if ( !in_array( $base[ 'values' ][ 'timezone' ],
                  timezone_identifiers_list(), true ) )
    us3_dbinst_config_fail( "base key 'timezone' is not recognized" );

  if ( $base[ 'values' ][ 'udpport' ] < 1 ||
       $base[ 'values' ][ 'udpport' ] > 65535 )
    us3_dbinst_config_fail( "base key 'udpport' must be between 1 and 65535" );

  return $base[ 'values' ];
}

function us3_dbinst_config_validate_overlay( $overlay, $instance )
{
  us3_dbinst_config_assert_instance( $instance );

  if ( !is_array( $overlay ) )
    us3_dbinst_config_fail( 'overlay must be an array' );
  $version = us3_dbinst_config_contract_version();
  if ( !isset( $overlay[ 'schema_version' ] ) ||
       $overlay[ 'schema_version' ] !== $version )
    us3_dbinst_config_fail(
      "overlay schema_version must be integer $version" );
  if ( !isset( $overlay[ 'instance' ] ) || $overlay[ 'instance' ] !== $instance )
    us3_dbinst_config_fail( 'overlay instance does not match requested instance' );
  if ( !isset( $overlay[ 'values' ] ) || !is_array( $overlay[ 'values' ] ) )
    us3_dbinst_config_fail( 'overlay values must be an array' );

  $top_keys = array_keys( $overlay );
  sort( $top_keys );
  if ( $top_keys !== array( 'instance', 'schema_version', 'values' ) )
    us3_dbinst_config_fail( 'overlay file has unknown top-level keys' );

  us3_dbinst_config_validate_values(
    $overlay[ 'values' ], us3_dbinst_config_overlay_required_types(),
    us3_dbinst_config_overlay_optional_types(), 'overlay' );

  if ( $overlay[ 'values' ][ 'dbname' ] !== $instance )
    us3_dbinst_config_fail( 'overlay dbname does not match requested instance' );

  return $overlay[ 'values' ];
}

/*
 * Read and validate configuration without publishing legacy globals.
 * $load_credentials=false is used by trusted generation/migration tools.
 */
function us3_dbinst_config_load( $instance, $root = null,
                                 $load_credentials = true )
{
  us3_dbinst_config_assert_instance( $instance );

  if ( $root === null )
    $root = us3_dbinst_config_root();
  $root = rtrim( $root, '/' );

  $base_values = us3_dbinst_config_load_base( $root );
  $overlay_path = $root . '/instances/' . $instance . '.php';
  $instances_dir = realpath( $root . '/instances' );
  $overlay_real = realpath( $overlay_path );
  if ( $instances_dir === false || $overlay_real === false ||
       dirname( $overlay_real ) !== $instances_dir )
    us3_dbinst_config_fail( 'overlay must be a regular file in instances/' );
  $overlay_path = $overlay_real;
  $overlay = us3_dbinst_config_read_file( $overlay_path, 'overlay' );
  $overlay_values = us3_dbinst_config_validate_overlay( $overlay, $instance );

  $values = array_merge( $base_values, $overlay_values );
  $dbinst_root = us3_dbinst_config_path_with_slash( $values[ 'dbinst_root' ] );
  unset( $values[ 'dbinst_root' ] );

  $values[ 'full_path' ] = $dbinst_root . $instance . '/';
  $values[ 'data_dir' ] = $values[ 'full_path' ] . 'data/';
  $values[ 'submit_dir' ] = us3_dbinst_config_path_with_slash(
    $values[ 'submit_dir' ] );
  $values[ 'class_dir' ] = us3_dbinst_config_path_with_slash(
    $values[ 'class_dir' ] );
  $values[ 'current_year' ] = date( 'Y' );
  $values[ 'is_cli' ] = php_sapi_name() == 'cli';

  if ( $load_credentials )
  {
    $cfgfile = us3_dbinst_config_credentials_file();
    if ( $cfgfile === '' || !is_file( $cfgfile ) || !is_readable( $cfgfile ) )
      us3_dbinst_config_fail( 'credential INI file is missing or unreadable' );

    $configs = parse_ini_file( $cfgfile, true );
    if ( !is_array( $configs ) ||
         !isset( $configs[ 'gfac' ][ 'password' ] ) ||
         !is_string( $configs[ 'gfac' ][ 'password' ] ) )
      us3_dbinst_config_fail( 'credential INI has no gfac password' );

    $values[ 'cfgfile' ] = $cfgfile;
    $values[ 'configs' ] = $configs;
    $values[ 'globaldbpasswd' ] = $configs[ 'gfac' ][ 'password' ];
  }

  return $values;
}

/* Publish the validated values using the variable interface legacy code uses. */
function us3_dbinst_config_bootstrap( $instance, $root = null )
{
  if ( isset( $GLOBALS[ 'us3_dbinst_loaded_instance' ] ) &&
       $GLOBALS[ 'us3_dbinst_loaded_instance' ] !== $instance )
    us3_dbinst_config_fail( 'attempted to load two instances in one process' );

  $values = us3_dbinst_config_load( $instance, $root, true );
  date_default_timezone_set( $values[ 'timezone' ] );
  unset( $values[ 'timezone' ] );

  if ( !defined( 'HOME_DIR' ) )
    define( 'HOME_DIR', $values[ 'full_path' ] );
  if ( !defined( 'DEBUG' ) )
    define( 'DEBUG', false );

  $GLOBALS[ 'us3_dbinst_loaded_instance' ] = $instance;
  return $values;
}
