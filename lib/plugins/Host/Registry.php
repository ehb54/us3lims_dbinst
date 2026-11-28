<?php
namespace USLIMS\Plugin\Host;

/** One deployment-recorded plugin and whether it may run now. */
final class PluginStatus
{
  /** @var string */
  public $id;
  /** @var string */
  public $version;
  /** @var Manifest|null */
  public $manifest;
  /** @var bool */
  public $installed = false;
  /** @var bool */
  public $enabled = false;
  /** @var bool */
  public $entitled = false;
  /** @var string why the plugin is unavailable; empty when available */
  public $reason = '';

  public function available(): bool
  {
    return $this->installed && $this->enabled && $this->entitled;
  }
}

/**
 * The one availability decision used by the menu, the dispatcher and the
 * settings page: installed with a valid manifest, enabled by the instance
 * administrator, and covered by an active license for this instance.
 */
final class Registry
{
  /** @var HostConfig */
  private $config;
  /** @var InstanceState */
  private $state;
  /** @var LicenseVerifier */
  private $verifier;
  /** @var int */
  private $now;
  /** @var array<string,PluginStatus>|null */
  private $plugins;
  /** @var array|null */
  private $license;

  public function __construct( HostConfig $config, InstanceState $state, LicenseVerifier $verifier, ?int $now = null )
  {
    $this->config   = $config;
    $this->state    = $state;
    $this->verifier = $verifier;
    $this->now      = $now ?? time();
  }

  public static function forInstance( string $dbname, array $config = array() ): self
  {
    $host = HostConfig::fromConfig( $dbname, $config );
    return new self( $host, new InstanceState( $host ), new LicenseVerifier( $host->publicKeys ) );
  }

  public function config(): HostConfig
  {
    return $this->config;
  }

  public function state(): InstanceState
  {
    return $this->state;
  }

  public function verifier(): LicenseVerifier
  {
    return $this->verifier;
  }

  public function now(): int
  {
    return $this->now;
  }

  /**
   * The saved key's state: status is one of none, invalid, expired,
   * wrong_instance, no_instance_id or valid.
   *
   * @return array{status:string,reason:string,license:?License}
   */
  public function licenseState(): array
  {
    if ( $this->license !== null )
      return $this->license;

    $token = $this->state->licenseToken();
    if ( $token === null || $token === '' )
      return $this->license = array( 'status' => 'none', 'reason' => 'no license key', 'license' => null );

    try
    {
      $license = $this->verifier->decode( $token );
    }
    catch ( LicenseException $e )
    {
      return $this->license = array( 'status' => 'invalid', 'reason' => 'license key invalid: ' . $e->getMessage(), 'license' => null );
    }

    $instanceId = $this->state->instanceId();
    if ( $instanceId === null )
      return $this->license = array( 'status' => 'no_instance_id', 'reason' => 'instance ID not provisioned', 'license' => $license );

    $status = $license->status( $instanceId, $this->now );
    $reasons = array(
      License::VALID          => '',
      License::EXPIRED        => 'license expired ' . $license->expiresText(),
      License::WRONG_INSTANCE => 'license key is for a different instance',
    );
    return $this->license = array( 'status' => $status, 'reason' => $reasons[ $status ], 'license' => $license );
  }

  /** @return array<string,PluginStatus> every deployment-recorded plugin */
  public function plugins(): array
  {
    if ( $this->plugins !== null )
      return $this->plugins;

    $enabled = $this->state->enabledIds();
    $license = $this->licenseState();
    $this->plugins = array();

    foreach ( $this->state->deployment() as $id => $version )
    {
      $p = new PluginStatus();
      $p->id = $id;
      $p->version = $version;
      $p->enabled = in_array( $id, $enabled, true );
      $p->entitled = $license['status'] === License::VALID && $license['license']->covers( $id );

      try
      {
        $p->manifest = Manifest::load( $this->packageDir( $id, $version ), $id, $version );
        $p->installed = true;
      }
      catch ( ManifestException $e )
      {
        $p->reason = $e->getMessage();
      }

      if ( $p->reason === '' && !$p->enabled )
        $p->reason = 'disabled';
      if ( $p->reason === '' && !$p->entitled )
        $p->reason = $license['status'] === License::VALID ? 'not covered by the license' : $license['reason'];

      $this->plugins[ $id ] = $p;
    }
    return $this->plugins;
  }

  /** The plugin's status when it may run now, otherwise null. */
  public function available( string $id ): ?PluginStatus
  {
    $plugins = $this->plugins();
    return isset( $plugins[ $id ] ) && $plugins[ $id ]->available() ? $plugins[ $id ] : null;
  }

  /** Available landing links grouped by stable section ID. */
  public function menuSections(): array
  {
    $sections = array();
    // deployment() sorts plugin IDs. The first available contributor supplies the heading.
    foreach ( $this->plugins() as $p )
    {
      if ( !$p->available() || $p->manifest->menu === null )
        continue;
      $menu = $p->manifest->menu;
      $section = $menu['section'];
      if ( !isset( $sections[$section] ) )
        $sections[$section] = array( 'heading' => $menu['sectionLabel'] ?? null, 'items' => array() );
      $sections[$section]['items'][] = array( 'id' => $p->id, 'label' => $menu['label'], 'action' => $menu['action'] );
    }
    ksort( $sections, SORT_STRING );
    return $sections;
  }

  public function menuItems( string $section ): array
  {
    return $this->menuSections()[$section]['items'] ?? array();
  }

  /**
   * <root>/<id>/<version>/ when deployment staged versions side by side,
   * otherwise <root>/<id>/.
   */
  public function packageDir( string $id, string $version ): string
  {
    if ( !Manifest::isPluginId( $id ) )
      throw new ManifestException( 'invalid plugin ID in the deployment record' );
    $base = $this->config->pluginRoot . '/' . $id;
    if ( preg_match( '/^[0-9A-Za-z][0-9A-Za-z.+-]{0,63}$/D', $version ) && strpos( $version, '..' ) === false
         && is_file( "$base/$version/composer.json" ) )
      return "$base/$version";
    return $base;
  }
}
