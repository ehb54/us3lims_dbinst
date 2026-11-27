<?php
namespace USLIMS\Plugin\Host;

/**
 * Per-instance plugin state, kept outside the shared code tree so it survives
 * upgrades and stays separate for instances that mount the same PHP tree:
 *
 *   <state root>/<instance>/instance-id      stable random instance ID
 *   <state root>/<instance>/deployment.json  installed plugin versions (deployment only)
 *   <state root>/<instance>/enabled.json     plugins the administrator enabled
 *   <state root>/<instance>/license.key      the current license key
 *
 * Every write goes to a temporary file in the same directory and is renamed
 * into place, so a reader sees either the old or the new content.
 */
final class InstanceState
{
  /** @var string */
  private $dir;

  public function __construct( HostConfig $config )
  {
    $this->dir = $config->stateRoot . '/' . $config->instance;
  }

  public function dir(): string
  {
    return $this->dir;
  }

  public static function isInstanceId( string $id ): bool
  {
    return (bool) preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $id );
  }

  /** The saved instance ID, or null before provisioning. */
  public function instanceId(): ?string
  {
    $id = $this->read( 'instance-id' );
    if ( !is_string( $id ) )
      return null;
    $id = trim( $id );
    return self::isInstanceId( $id ) ? $id : null;
  }

  /**
   * Provision the instance ID once. A concurrent first call cannot produce two
   * IDs: the file is created with link(), which fails if it already exists.
   */
  public function ensureInstanceId(): string
  {
    $id = $this->instanceId();
    if ( $id !== null )
      return $id;
    if ( $this->read( 'instance-id' ) !== null )
      throw new \RuntimeException( "the instance ID file in {$this->dir} is unreadable or not a valid ID" );

    $this->ensureDir();
    $bytes = random_bytes( 16 );
    $bytes[6] = chr( ( ord( $bytes[6] ) & 0x0f ) | 0x40 );
    $bytes[8] = chr( ( ord( $bytes[8] ) & 0x3f ) | 0x80 );
    $hex = bin2hex( $bytes );
    $new = substr( $hex, 0, 8 ) . '-' . substr( $hex, 8, 4 ) . '-' . substr( $hex, 12, 4 ) . '-'
         . substr( $hex, 16, 4 ) . '-' . substr( $hex, 20, 12 );

    $tmp = $this->tempFile( $new . "\n" );
    $linked = @link( $tmp, $this->path( 'instance-id' ) );
    @unlink( $tmp );
    $id = $this->instanceId();
    if ( $id === null )
      throw new \RuntimeException( "cannot create the instance ID in {$this->dir}" . ( $linked ? '' : ': link failed' ) );
    return $id;
  }

  /** @return array<string,string> plugin ID => installed version */
  public function deployment(): array
  {
    $out = $this->readJson( 'deployment.json', 'plugins', false );
    ksort( $out );
    return $out;
  }

  public function recordDeployment( string $id, ?string $version ): void
  {
    $this->locked( function () use ( $id, $version ) {
      // The validated content read under the lock is what gets updated.
      $plugins = $this->readJson( 'deployment.json', 'plugins', true );
      if ( $version === null )
        unset( $plugins[ $id ] );
      else
        $plugins[ $id ] = $version;
      ksort( $plugins );
      $this->writeJson( 'deployment.json', array( 'plugins' => (object) $plugins ) );
    } );
  }

  /** @return string[] */
  public function enabledIds(): array
  {
    return $this->readJson( 'enabled.json', 'enabled', false );
  }

  public function setEnabled( string $id, bool $enabled ): void
  {
    $this->locked( function () use ( $id, $enabled ) {
      $ids = array_diff( $this->readJson( 'enabled.json', 'enabled', true ), array( $id ) );
      if ( $enabled )
        $ids[] = $id;
      sort( $ids );
      $this->writeJson( 'enabled.json', array( 'enabled' => array_values( $ids ) ) );
    } );
  }

  public function licenseToken(): ?string
  {
    $token = $this->read( 'license.key' );
    if ( $token === false )
      error_log( "lims plugins: cannot read {$this->dir}/license.key" );
    return is_string( $token ) ? trim( $token ) : null;
  }

  /** Callers verify the token first; the previous key stays until the rename. */
  public function replaceLicense( string $token ): void
  {
    $this->ensureDir();
    $tmp = $this->tempFile( trim( $token ) . "\n" );
    if ( !@rename( $tmp, $this->path( 'license.key' ) ) )
    {
      @unlink( $tmp );
      throw new \RuntimeException( 'cannot save the license key' );
    }
  }

  public function removeLicense(): void
  {
    $file = $this->path( 'license.key' );
    if ( is_file( $file ) && !@unlink( $file ) )
      throw new \RuntimeException( 'cannot remove the license key' );
  }

  private function path( string $name ): string
  {
    return $this->dir . '/' . $name;
  }

  /** Null when the file is absent; false when it exists but cannot be read. */
  private function read( string $name )
  {
    $file = $this->path( $name );
    if ( !file_exists( $file ) )
      return null;
    $text = is_file( $file ) ? @file_get_contents( $file ) : false;
    return $text === false ? false : $text;
  }

  /**
   * The $key member of a state file: a JSON list of strings for "enabled", a JSON object of
   * string versions for "plugins". A missing file is empty. An unreadable or wrong-shaped file
   * reads as empty, except before a rewrite ($strict), which must not drop its records.
   */
  private function readJson( string $name, string $key, bool $strict ): array
  {
    $text = $this->read( $name );
    if ( $text === null )
      return array();
    // Decoded as objects, so a JSON object can never pass for a list.
    $data = $text === false ? null : json_decode( $text );
    $value = $data instanceof \stdClass && property_exists( $data, $key ) ? $data->$key : null;
    $out = null;
    if ( $key === 'enabled' && is_array( $value ) && count( array_filter( $value, 'is_string' ) ) === count( $value ) )
      $out = $value;
    else if ( $key === 'plugins' && $value instanceof \stdClass )
    {
      $out = get_object_vars( $value );
      foreach ( $out as $id => $version )
      {
        if ( !is_string( $id ) || !Manifest::isPluginId( $id ) || !is_string( $version ) )
          $out = null;
      }
    }
    if ( $out === null )
    {
      $why = $text === false ? 'cannot be read' : 'is not a valid state file';
      if ( $strict )
        throw new \RuntimeException( "{$this->dir}/$name $why; fix or remove it first" );
      error_log( "lims plugins: ignoring {$this->dir}/$name, which $why" );
      return array();
    }
    return $out;
  }

  /** Serialize read-modify-write updates of the state files. */
  private function locked( callable $update ): void
  {
    $this->ensureDir();
    $lock = @fopen( $this->path( '.lock' ), 'c' );
    if ( $lock === false || !flock( $lock, LOCK_EX ) )
      throw new \RuntimeException( "cannot lock {$this->dir}" );
    try
    {
      $update();
    }
    finally
    {
      flock( $lock, LOCK_UN );
      fclose( $lock );
    }
  }

  private function writeJson( string $name, array $data ): void
  {
    $this->ensureDir();
    $tmp = $this->tempFile( json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
    if ( !@rename( $tmp, $this->path( $name ) ) )
    {
      @unlink( $tmp );
      throw new \RuntimeException( "cannot write {$this->dir}/$name" );
    }
  }

  private function tempFile( string $content ): string
  {
    $tmp = $this->dir . '/.tmp-' . bin2hex( random_bytes( 8 ) );
    if ( @file_put_contents( $tmp, $content, LOCK_EX ) !== strlen( $content ) )
    {
      @unlink( $tmp );
      throw new \RuntimeException( "cannot write to {$this->dir}" );
    }
    @chmod( $tmp, 0640 );
    return $tmp;
  }

  /**
   * The state root and instance directory are shared by the deployment
   * account and the web server, so both are group-writable (setgid where the
   * platform allows) whatever the process umask.
   */
  private function ensureDir(): void
  {
    foreach ( array( dirname( $this->dir ), $this->dir ) as $dir )
    {
      if ( is_dir( $dir ) )
        continue;
      if ( !@mkdir( $dir, 0770, true ) && !is_dir( $dir ) )
        throw new \RuntimeException( "cannot create $dir" );
      @chmod( $dir, 02770 );
    }
  }
}
