<?php
/*
 * Deployment tool for per-instance plugin records. Only deployment registers
 * plugin code: this records which staged package version an instance uses.
 * Enabling a plugin and the license key are instance administrator settings
 * (plugin_settings.php).
 */
if ( php_sapi_name() !== 'cli' )
{
  http_response_code( 404 );
  exit( "lims_plugins.php is a command line tool\n" );
}

require_once __DIR__ . '/../lib/plugins/bootstrap.php';

use USLIMS\Plugin\Host\Manifest;
use USLIMS\Plugin\Host\ManifestException;
use USLIMS\Plugin\Host\Registry;

function lims_plugins_usage(): string
{
  return "Usage: php lims_plugins.php --instance=uslims3_NAME [--plugin-root=DIR] [--state-root=DIR] COMMAND\n"
       . "  status                 instance ID, license state and recorded plugins\n"
       . "  instance-id            print the instance ID, creating it on first use\n"
       . "  install ID VERSION     record a staged package after validating its manifest\n"
       . "  remove ID              remove the record; the package files are left in place\n";
}

$options = array();
$args = array();
foreach ( array_slice( $argv, 1 ) as $arg )
{
  if ( preg_match( '/^--(instance|plugin-root|state-root)=(.+)$/D', $arg, $m ) )
    $options[ $m[1] ] = $m[2];
  else
    $args[] = $arg;
}
if ( !isset( $options['instance'] ) || count( $args ) === 0 )
{
  fwrite( STDERR, lims_plugins_usage() );
  exit( 2 );
}

$config = array();
if ( isset( $options['plugin-root'] ) )
  $config['lims_plugin_root'] = $options['plugin-root'];
if ( isset( $options['state-root'] ) )
  $config['lims_plugin_state_root'] = $options['state-root'];

try
{
  $registry = Registry::forInstance( $options['instance'], $config );
  $state = $registry->state();
  $command = $args[0];

  if ( $command === 'instance-id' && count( $args ) === 1 )
  {
    echo $state->ensureInstanceId(), "\n";
  }
  else if ( $command === 'install' && count( $args ) === 3 )
  {
    list( , $id, $version ) = $args;
    if ( !Manifest::isPluginId( $id ) )
      throw new ManifestException( "invalid plugin ID $id" );
    $manifest = Manifest::load( $registry->packageDir( $id, $version ), $id, $version );
    $state->recordDeployment( $id, $version );
    echo "Recorded $id $version from {$manifest->dir} for {$options['instance']}.\n";
  }
  else if ( $command === 'remove' && count( $args ) === 2 )
  {
    $state->recordDeployment( $args[1], null );
    echo "Removed the record of {$args[1]} for {$options['instance']}.\n";
  }
  else if ( $command === 'status' && count( $args ) === 1 )
  {
    $ls = $registry->licenseState();
    echo 'Instance:     ', $options['instance'], "\n";
    echo 'Instance ID:  ', $state->instanceId() ?? '(not provisioned)', "\n";
    echo 'Plugin root:  ', $registry->config()->pluginRoot, "\n";
    echo 'State dir:    ', $state->dir(), "\n";
    echo 'License:      ', $ls['status'], $ls['license'] ? ' (' . $ls['license']->scopeText() . ', expires ' . $ls['license']->expiresText() . ')' : '', "\n";
    foreach ( $registry->plugins() as $p )
      echo "  {$p->id} {$p->version}: ", $p->available() ? 'available' : 'unavailable: ' . $p->reason, "\n";
  }
  else
  {
    fwrite( STDERR, lims_plugins_usage() );
    exit( 2 );
  }
}
catch ( Throwable $e )
{
  fwrite( STDERR, 'Error: ' . $e->getMessage() . "\n" );
  exit( 1 );
}
