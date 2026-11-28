<?php
namespace USLIMS\Plugin\Host;

final class LicenseException extends \RuntimeException
{
}

/**
 * Offline verification of license key format version 1: two unpadded
 * base64url fields joined by one dot, the exact UTF-8 JSON payload bytes and
 * their detached Ed25519 signature.
 */
final class LicenseVerifier
{
  const MAX_TOKEN_LENGTH = 8192;

  /** @var string[] */
  private $publicKeys;

  /** @param string[] $publicKeys raw 32-byte Ed25519 public keys */
  public function __construct( array $publicKeys )
  {
    $this->publicKeys = $publicKeys;
  }

  /**
   * Check signature and payload format. Instance and expiry are left to
   * License::status() so settings can still describe a saved key.
   *
   * @throws LicenseException with a short reason
   */
  public function decode( string $token ): License
  {
    if ( !function_exists( 'sodium_crypto_sign_verify_detached' ) )
      throw new LicenseException( 'license verification is unavailable: the PHP sodium extension is not loaded' );
    if ( count( $this->publicKeys ) === 0 )
      throw new LicenseException( 'no license verification key is configured' );

    $token = trim( $token );
    if ( strlen( $token ) > self::MAX_TOKEN_LENGTH || substr_count( $token, '.' ) !== 1 )
      throw new LicenseException( 'the key is not in license key format' );

    list( $payloadText, $signatureText ) = explode( '.', $token );
    $payload   = Base64Url::decode( $payloadText );
    $signature = Base64Url::decode( $signatureText );
    if ( $payload === null || $signature === null || strlen( $signature ) !== SODIUM_CRYPTO_SIGN_BYTES )
      throw new LicenseException( 'the key is not in license key format' );

    $signed = false;
    foreach ( $this->publicKeys as $key )
    {
      if ( strlen( $key ) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
           && sodium_crypto_sign_verify_detached( $signature, $payload, $key ) )
      {
        $signed = true;
        break;
      }
    }
    if ( !$signed )
      throw new LicenseException( 'the key signature is not valid' );

    return self::parsePayload( $payload );
  }

  private static function parsePayload( string $payload ): License
  {
    $data = json_decode( $payload, false, 4 );
    if ( !( $data instanceof \stdClass ) )
      throw new LicenseException( 'the key payload is not a JSON object' );

    $fields = get_object_vars( $data );
    $expected = array( 'version', 'instance_id', 'plugins', 'expires_at' );
    $keys = array_keys( $fields );
    sort( $keys );
    $sorted = $expected;
    sort( $sorted );
    if ( $keys !== $sorted )
      throw new LicenseException( 'the key payload fields are not version 1 fields' );

    if ( !is_int( $fields['version'] ) || $fields['version'] !== 1 )
      throw new LicenseException( 'unsupported license format version' );

    $instance = $fields['instance_id'];
    if ( !is_string( $instance ) || !InstanceState::isInstanceId( $instance ) )
      throw new LicenseException( 'the key has an invalid instance ID' );

    $plugins = $fields['plugins'];
    if ( $plugins !== '*' )
    {
      if ( !is_array( $plugins ) || count( $plugins ) === 0 )
        throw new LicenseException( 'the key has an invalid plugin scope' );
      foreach ( $plugins as $id )
      {
        if ( !is_string( $id ) || !Manifest::isPluginId( $id ) )
          throw new LicenseException( 'the key has an invalid plugin scope' );
      }
      if ( count( array_unique( $plugins ) ) !== count( $plugins ) )
        throw new LicenseException( 'the key has an invalid plugin scope' );
    }

    $expires = $fields['expires_at'];
    $time = is_string( $expires ) ? self::parseUtc( $expires ) : null;
    if ( $time === null )
      throw new LicenseException( 'the key has an invalid expiry' );

    return new License( $instance, $plugins, $time );
  }

  /** Strict YYYY-MM-DDTHH:MM:SSZ. */
  public static function parseUtc( string $text ): ?int
  {
    if ( !preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $text ) )
      return null;
    $dt = \DateTime::createFromFormat( '!Y-m-d\TH:i:s\Z', $text, new \DateTimeZone( 'UTC' ) );
    if ( $dt === false || $dt->format( 'Y-m-d\TH:i:s\Z' ) !== $text )
      return null;
    return $dt->getTimestamp();
  }
}
