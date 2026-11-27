<?php
namespace USLIMS\Plugin\Host;

final class ManifestException extends \RuntimeException
{
}

/**
 * A plugin's composer.json, read as data and validated against plugin API v1.
 */
final class Manifest
{
  const API = 1;
  const SECTIONS = array( 'navigation', 'project', 'analysis', 'status-monitor', 'general' );

  /** @var string */
  public $dir;
  /** @var string */
  public $name;
  /** @var string */
  public $id;
  /** @var string */
  public $version;
  /** @var array{section:string,sectionLabel?:string,label:string,action:string}|null */
  public $menu;
  /** @var array<string,string> action name => handler class */
  public $actions;
  /** @var string[] stylesheets (package-relative paths) core serves and links on the plugin's pages */
  public $stylesheets = array();

  public static function isPluginId( string $id ): bool
  {
    return (bool) preg_match( '/^[a-z](?:[a-z0-9-]{0,62}[a-z0-9])?$/D', $id );
  }

  /** A package-relative stylesheet path: lowercase segments, no "." or ".." segment, ending in .css. */
  public static function isStylesheetPath( string $path ): bool
  {
    return (bool) preg_match( '#^[a-z0-9_-]+(/[a-z0-9_-]+)*(\.[a-z0-9_-]+)*\.css$#D', $path );
  }

  public static function isActionName( string $name ): bool
  {
    return (bool) preg_match( '/^[a-z][a-z0-9_-]{0,63}$/D', $name );
  }

  /**
   * @throws ManifestException when the manifest is missing, malformed or does
   *         not match the deployment record
   */
  public static function load( string $dir, string $expectedId, string $expectedVersion ): self
  {
    $file = $dir . '/composer.json';
    if ( !is_file( $file ) )
      throw new ManifestException( 'package not installed' );
    $data = json_decode( (string) @file_get_contents( $file ), true );
    if ( !is_array( $data ) )
      throw new ManifestException( 'composer.json is not valid JSON' );

    if ( ( $data['type'] ?? null ) !== 'lims-plugin' )
      throw new ManifestException( 'package type is not lims-plugin' );
    $lims = $data['extra']['lims'] ?? null;
    if ( !is_array( $lims ) )
      throw new ManifestException( 'extra.lims is missing' );
    if ( ( $lims['api'] ?? null ) !== self::API )
      throw new ManifestException( 'unsupported plugin API ' . json_encode( $lims['api'] ?? null ) );

    $m = new self();
    $m->dir     = $dir;
    $m->name    = is_string( $data['name'] ?? null ) ? $data['name'] : '';
    $m->id      = is_string( $lims['id'] ?? null ) ? $lims['id'] : '';
    $m->version = is_string( $data['version'] ?? null ) ? $data['version'] : '';

    if ( !self::isPluginId( $m->id ) )
      throw new ManifestException( 'invalid plugin ID' );
    if ( $m->id !== $expectedId )
      throw new ManifestException( "plugin ID {$m->id} does not match the deployment record" );
    if ( $m->version === '' || $m->version !== $expectedVersion )
      throw new ManifestException( "package version {$m->version} does not match the deployment record ($expectedVersion)" );

    $actions = $lims['actions'] ?? null;
    if ( !is_array( $actions ) || count( $actions ) === 0 )
      throw new ManifestException( 'no actions declared' );
    $m->actions = array();
    foreach ( $actions as $name => $class )
    {
      if ( !is_string( $name ) || !self::isActionName( $name ) )
        throw new ManifestException( 'invalid action name' );
      if ( !is_string( $class ) || !preg_match( '/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $class ) )
        throw new ManifestException( "invalid handler class for action $name" );
      $m->actions[ $name ] = $class;
    }

    $menu = $lims['menu'] ?? null;
    if ( $menu !== null )
    {
      if ( !is_array( $menu ) || !is_string( $menu['section'] ?? null )
           || !is_string( $menu['label'] ?? null ) || !is_string( $menu['action'] ?? null ) )
        throw new ManifestException( 'menu needs section, label and action' );
      $section = $menu['section'];
      if ( !self::isPluginId( $section ) )
        throw new ManifestException( 'invalid menu section ID' );
      $custom = !in_array( $section, self::SECTIONS, true );
      if ( $custom && ( !is_string( $menu['sectionLabel'] ?? null ) || trim( $menu['sectionLabel'] ) === '' ) )
        throw new ManifestException( "unknown menu section $section needs a sectionLabel" );
      if ( trim( $menu['label'] ) === '' )
        throw new ManifestException( 'empty menu label' );
      if ( !isset( $m->actions[ $menu['action'] ] ) )
        throw new ManifestException( "menu action {$menu['action']} is not declared" );
      $m->menu = array( 'section' => $menu['section'], 'label' => $menu['label'], 'action' => $menu['action'] );
      if ( $custom )
        $m->menu['sectionLabel'] = trim( $menu['sectionLabel'] );
    }

    $stylesheets = $lims['stylesheets'] ?? array();
    if ( !is_array( $stylesheets ) )
      throw new ManifestException( 'stylesheets must be a list' );
    foreach ( $stylesheets as $path )
    {
      if ( !is_string( $path ) || !self::isStylesheetPath( $path ) )
        throw new ManifestException( 'invalid stylesheet path' );
      if ( !is_file( $dir . '/' . $path ) )
        throw new ManifestException( "stylesheet $path is missing" );
      $m->stylesheets[] = $path;
    }

    if ( !is_file( $dir . '/vendor/autoload.php' ) )
      throw new ManifestException( 'vendor/autoload.php is missing; build the package with Composer' );

    return $m;
  }
}
