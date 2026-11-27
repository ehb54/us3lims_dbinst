<?php
/*
 * plugin.php
 *
 * Shared entry point for installed plugin actions:
 *   plugin.php?plugin=<id>&action=<name>[&...]
 * See lib/plugins/README.md for plugin API v1.
 */
include_once 'checkinstance.php';
include 'config.php';
require_once __DIR__ . '/lib/plugins/bootstrap.php';

use USLIMS\Plugin\Host\Dispatcher;
use USLIMS\Plugin\Host\RequestContext;

/** Error page in the LIMS layout; reasons go to the log only. */
function plugin_error_page( int $status, string $reason, string $plugin )
{
  global $common_dir;
  if ( $reason !== '' )
    error_log( "plugin.php: $status $plugin: $reason" );
  http_response_code( $status );
  if ( $status === 405 )
    header( 'Allow: GET' );
  header( 'Cache-Control: private, no-store' );
  $messages = array(
    400 => 'The request is not valid.',
    401 => 'Please log in to continue.',
    404 => 'The requested page was not found.',
    405 => 'This page accepts GET requests only.',
    500 => 'The request could not be completed.',
  );
  $GLOBALS['page_title'] = $messages[ $status ] ?? 'Error';
  $message = htmlspecialchars( $GLOBALS['page_title'], ENT_QUOTES );
  $GLOBALS['plugin_page_html'] = "<div id='content'>\n<p class='message'>$message</p>\n</div>\n";
}

$plugin_page_html = null;
// For the log only: printable characters, no newlines.
$requested_plugin = is_string( $_GET['plugin'] ?? null ) ? preg_replace( '/[^a-z0-9-]/', '?', substr( $_GET['plugin'], 0, 64 ) ) : '';

try
{
  $registry = lims_plugin_registry( (string) $dbname );
  $route = ( new Dispatcher( $registry ) )->route( $_SERVER['REQUEST_METHOD'] ?? '', $_SESSION ?? array(), $_GET );
}
catch ( Throwable $e )
{
  $registry = null;
  $route = new \USLIMS\Plugin\Host\Route( 404, 'plugin framework unavailable: ' . $e->getMessage() );
}

if ( $route->status !== 200 )
{
  plugin_error_page( $route->status, $route->reason, $requested_plugin );
}
else if ( $route->stylesheet !== '' )
{
  // A stylesheet the manifest declares, served same-origin so the site's style-src 'self' allows it.
  if ( is_link( $route->stylesheet ) || !is_file( $route->stylesheet ) )
  {
    plugin_error_page( 404, 'stylesheet missing or a link: ' . $route->stylesheet, $route->plugin->id );
  }
  else
  {
    // Revalidated on every use, so logout, disablement or expiry take effect at once.
    $etag = '"' . sha1_file( $route->stylesheet ) . '"';
    header( 'Cache-Control: private, no-cache' );
    header( 'ETag: ' . $etag );
    header( 'X-Content-Type-Options: nosniff' );
    // Quoted entity tags (weak or strong) or a bare *, so a comma inside a tag is not a separator.
    $header = trim( (string) ( $_SERVER['HTTP_IF_NONE_MATCH'] ?? '' ) );
    preg_match_all( '#(?:W/)?("[^"]*")#', $header, $tags );
    if ( $header === '*' || in_array( $etag, $tags[1], true ) )
    {
      http_response_code( 304 );
      exit();
    }
    header( 'Content-Type: text/css; charset=utf-8' );
    header( 'Content-Length: ' . filesize( $route->stylesheet ) );
    readfile( $route->stylesheet );
    exit();
  }
}
else
{
  include 'db.php';
  $context = null;
  try
  {
    $handler = Dispatcher::handler( $route );
    $context = new RequestContext( $route->plugin->id, (string) $registry->state()->instanceId(),
                                   (int) $_SESSION['loginID'], $link );
    // Read-only GET actions: release the session lock before plugin work.
    session_write_close();
    ob_start();
    $handler->handle( $context, $route->query );
    ob_end_flush();
  }
  catch ( Throwable $e )
  {
    while ( ob_get_level() > 0 )
      ob_end_clean();
    $context = null;
    if ( headers_sent() )
    {
      error_log( "plugin.php: {$route->plugin->id} failed after output: " . $e->getMessage() );
      exit();
    }
    header_remove();
    plugin_error_page( 500, get_class( $e ) . ': ' . $e->getMessage(), $route->plugin->id );
  }

  if ( $context !== null && $context->page() !== null )
  {
    $page = $context->page();
    $page_title = htmlspecialchars( $page['title'], ENT_QUOTES );
    // header.php links each comma-separated entry of $css.
    $plugin_stylesheets = Dispatcher::stylesheetUrls( $route->plugin );
    if ( count( $plugin_stylesheets ) > 0 )
      $css = implode( ',', $plugin_stylesheets );
    $plugin_page_html = "<div id='content'>\n" . $page['html'] . "\n</div>\n";
  }
}

if ( $plugin_page_html !== null )
{
  header( 'Content-Type: text/html; charset=utf-8' );
  header( 'Cache-Control: private, no-store' );
  include 'header.php';
  echo $plugin_page_html;
  include $common_dir . 'footer.php';
}
exit();
