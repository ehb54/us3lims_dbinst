<?php
namespace USLIMS\Plugin\Host;

use USLIMS\Plugin\V1\Action;

/** The dispatcher's decision for one request. */
final class Route
{
  /** @var int HTTP status; 200 when the handler should run */
  public $status;
  /** @var string reason for the server log, never shown to the browser */
  public $reason;
  /** @var PluginStatus|null */
  public $plugin;
  /** @var string */
  public $handlerClass = '';
  /** @var array */
  public $query = array();
  /** @var string absolute path of a declared stylesheet to serve instead of running a handler */
  public $stylesheet = '';

  public function __construct( int $status, string $reason = '' )
  {
    $this->status = $status;
    $this->reason = $reason;
  }
}

/**
 * Request checks shared by every plugin action: GET only, a signed-in
 * session for this instance, then the plugin and action through the
 * installed manifest. The browser names a plugin ID and an action; the
 * handler class always comes from the manifest.
 */
final class Dispatcher
{
  /** @var Registry */
  private $registry;

  public function __construct( Registry $registry )
  {
    $this->registry = $registry;
  }

  /** The session rule the menu and direct requests share: signed in to this instance, user level 1 or more. */
  public static function sessionAllows( array $session, string $instance ): bool
  {
    $positive = function ( $v ) {
      return ( is_int( $v ) || ( is_string( $v ) && ctype_digit( $v ) ) ) && (int) $v > 0;
    };
    return $positive( $session['loginID'] ?? null ) && $positive( $session['userlevel'] ?? null )
        && ( $session['instance'] ?? null ) === $instance;
  }

  public function route( string $method, array $session, array $get ): Route
  {
    if ( $method !== 'GET' )
      return new Route( 405, 'method not allowed' );

    $instance = $this->registry->config()->instance;
    if ( !self::sessionAllows( $session, $instance ) )
      return new Route( 401, 'no signed-in session with plugin access for this instance' );

    $id = $get['plugin'] ?? null;
    $action = $get['action'] ?? null;
    $asset = $get['asset'] ?? null;
    if ( !is_string( $id ) || !Manifest::isPluginId( $id ) )
      return new Route( 404, 'malformed plugin name' );
    if ( $asset === null && !( is_string( $action ) && Manifest::isActionName( $action ) ) )
      return new Route( 404, 'malformed action name' );
    if ( $asset !== null && !( is_string( $asset ) && Manifest::isStylesheetPath( $asset ) ) )
      return new Route( 404, 'malformed asset path' );

    $plugins = $this->registry->plugins();
    if ( !isset( $plugins[ $id ] ) )
      return new Route( 404, "plugin $id is not installed for $instance" );
    $plugin = $plugins[ $id ];
    if ( !$plugin->available() )
      return new Route( 404, "plugin $id unavailable: {$plugin->reason}" );
    if ( $asset !== null )
    {
      // Only stylesheets the installed manifest declares; the path is never built from other input.
      if ( !in_array( $asset, $plugin->manifest->stylesheets, true ) )
        return new Route( 404, "plugin $id declares no stylesheet $asset" );
      $route = new Route( 200 );
      $route->plugin = $plugin;
      $route->stylesheet = $plugin->manifest->dir . '/' . $asset;
      return $route;
    }
    if ( !isset( $plugin->manifest->actions[ $action ] ) )
      return new Route( 404, "plugin $id has no action $action" );

    $route = new Route( 200 );
    $route->plugin = $plugin;
    $route->handlerClass = $plugin->manifest->actions[ $action ];
    $query = $get;
    unset( $query['plugin'], $query['action'] );
    $route->query = $query;
    return $route;
  }

  /**
   * Same-origin URLs of the plugin's stylesheets, HTML-escaped, with the
   * package version so a new release is fetched again.
   *
   * @return string[]
   */
  public static function stylesheetUrls( PluginStatus $plugin ): array
  {
    $urls = array();
    foreach ( $plugin->manifest->stylesheets as $path )
    {
      $urls[] = htmlspecialchars( 'plugin.php?' . http_build_query(
        array( 'plugin' => $plugin->id, 'asset' => $path, 'v' => $plugin->version ), '', '&' ), ENT_QUOTES );
    }
    return $urls;
  }

  /**
   * Load the package's Composer autoloader and construct the declared handler.
   *
   * @throws \RuntimeException when the declared handler cannot be used
   */
  public static function handler( Route $route ): Action
  {
    require_once $route->plugin->manifest->dir . '/vendor/autoload.php';
    $class = $route->handlerClass;
    if ( !class_exists( $class ) )
      throw new \RuntimeException( "handler $class not found" );
    $reflection = new \ReflectionClass( $class );
    if ( !$reflection->implementsInterface( Action::class ) || !$reflection->isInstantiable() )
      throw new \RuntimeException( "handler $class does not implement " . Action::class );
    $constructor = $reflection->getConstructor();
    if ( $constructor !== null && $constructor->getNumberOfRequiredParameters() > 0 )
      throw new \RuntimeException( "handler $class needs constructor arguments" );
    return $reflection->newInstance();
  }
}
