<?php
namespace USLIMS\Plugin\Host;

/**
 * Plugin locations and trusted license keys, resolved from deployment
 * configuration only. Nothing here comes from the request.
 */
final class HostConfig
{
  /** @var string */
  public $instance;
  /** @var string */
  public $pluginRoot;
  /** @var string */
  public $stateRoot;
  /** @var string[] raw 32-byte Ed25519 public keys */
  public $publicKeys;

  public function __construct( string $instance, string $pluginRoot, string $stateRoot, array $publicKeys )
  {
    if ( !preg_match( '/^[A-Za-z0-9_]{1,64}$/D', $instance ) )
      throw new \InvalidArgumentException( 'invalid instance name' );
    $this->instance   = $instance;
    $this->pluginRoot = rtrim( $pluginRoot, '/' );
    $this->stateRoot  = rtrim( $stateRoot, '/' );
    $this->publicKeys = array_values( $publicKeys );
  }

  /**
   * Resolve from config.php values. $lims_plugin_root and
   * $lims_plugin_state_root override the defaults under the us3 account's
   * home, the same home config.php reads .us3lims.ini from.
   */
  public static function fromConfig( string $dbname, array $config = array() ): self
  {
    $pw   = function_exists( 'posix_getpwnam' ) ? posix_getpwnam( 'us3' ) : false;
    $home = ( $pw && !empty( $pw['dir'] ) ? $pw['dir'] : '/home/us3' ) . '/lims';

    $root  = isset( $config['lims_plugin_root'] ) && is_string( $config['lims_plugin_root'] )
           ? $config['lims_plugin_root'] : $home . '/plugins';
    $state = isset( $config['lims_plugin_state_root'] ) && is_string( $config['lims_plugin_state_root'] )
           ? $config['lims_plugin_state_root'] : $home . '/etc/plugins';

    return new self( $dbname, $root, $state, self::trustedKeys() );
  }

  /**
   * Built-in vendor keys, plus a development key from the server
   * environment (LIMS_PLUGIN_TEST_PUBLIC_KEY). Production hosts do not set it.
   *
   * @return string[]
   */
  public static function trustedKeys(): array
  {
    $keys = array();
    foreach ( require __DIR__ . '/../license_keys.php' as $encoded )
    {
      $raw = self::decodeKey( $encoded );
      if ( $raw !== null )
        $keys[] = $raw;
    }
    $test = getenv( 'LIMS_PLUGIN_TEST_PUBLIC_KEY' );
    if ( is_string( $test ) && $test !== '' )
    {
      $raw = self::decodeKey( $test );
      if ( $raw !== null )
        $keys[] = $raw;
    }
    return $keys;
  }

  public static function decodeKey( string $encoded ): ?string
  {
    $raw = Base64Url::decode( trim( $encoded ) );
    return ( $raw !== null && strlen( $raw ) === 32 ) ? $raw : null;
  }
}
