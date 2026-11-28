<?php
namespace USLIMS\Plugin\Host;

/** Unpadded base64url, strict on decode. */
final class Base64Url
{
  public static function encode( string $bytes ): string
  {
    return rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' );
  }

  public static function decode( string $text ): ?string
  {
    if ( $text === '' || !preg_match( '/^[A-Za-z0-9_-]+$/D', $text ) || strlen( $text ) % 4 === 1 )
      return null;
    $bytes = base64_decode( strtr( $text, '-_', '+/' ), true );
    if ( $bytes === false || self::encode( $bytes ) !== $text )
      return null;
    return $bytes;
  }
}
