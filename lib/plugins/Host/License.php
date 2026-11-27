<?php
namespace USLIMS\Plugin\Host;

/**
 * A license key whose signature and payload format have been verified.
 * Whether it applies to this instance now is decided by status().
 */
final class License
{
  const VALID          = 'valid';
  const EXPIRED        = 'expired';
  const WRONG_INSTANCE = 'wrong_instance';

  /** @var string */
  public $instanceId;
  /** @var string|string[] "*" or a list of plugin IDs */
  public $plugins;
  /** @var int expiry as a UTC Unix time */
  public $expiresAt;

  public function __construct( string $instanceId, $plugins, int $expiresAt )
  {
    $this->instanceId = $instanceId;
    $this->plugins    = $plugins;
    $this->expiresAt  = $expiresAt;
  }

  /** Access requires the current time to be strictly earlier than expiry. */
  public function status( string $instanceId, int $now ): string
  {
    if ( !hash_equals( $this->instanceId, $instanceId ) )
      return self::WRONG_INSTANCE;
    if ( $now >= $this->expiresAt )
      return self::EXPIRED;
    return self::VALID;
  }

  public function covers( string $pluginId ): bool
  {
    return $this->plugins === '*' || in_array( $pluginId, $this->plugins, true );
  }

  public function scopeText(): string
  {
    return $this->plugins === '*' ? 'all plugins' : implode( ', ', $this->plugins );
  }

  /** Last covered UTC day, e.g. 2027-12-31 for an expiry of 2028-01-01T00:00:00Z. */
  public function paidThrough(): string
  {
    return gmdate( 'Y-m-d', $this->expiresAt - 1 );
  }

  public function expiresText(): string
  {
    return gmdate( 'Y-m-d\TH:i:s\Z', $this->expiresAt );
  }
}
