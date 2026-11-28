<?php
/*
 * Loads the plugin framework: plugin API v1 (USLIMS\Plugin\V1) and the host
 * side that reads manifests, licenses and per-instance state.
 */
require_once __DIR__ . '/V1/Context.php';
require_once __DIR__ . '/V1/Action.php';
require_once __DIR__ . '/Host/Base64Url.php';
require_once __DIR__ . '/Host/HostConfig.php';
require_once __DIR__ . '/Host/License.php';
require_once __DIR__ . '/Host/LicenseVerifier.php';
require_once __DIR__ . '/Host/InstanceState.php';
require_once __DIR__ . '/Host/Manifest.php';
require_once __DIR__ . '/Host/Registry.php';
require_once __DIR__ . '/Host/Dispatcher.php';
require_once __DIR__ . '/Host/RequestContext.php';

/**
 * The request's registry for the instance in config.php. Plugin locations
 * come from config values only.
 */
function lims_plugin_registry( string $dbname ): \USLIMS\Plugin\Host\Registry
{
  static $registry = array();
  if ( !isset( $registry[ $dbname ] ) )
  {
    $config = array();
    foreach ( array( 'lims_plugin_root', 'lims_plugin_state_root' ) as $key )
    {
      if ( isset( $GLOBALS[ $key ] ) )
        $config[ $key ] = $GLOBALS[ $key ];
    }
    $registry[ $dbname ] = \USLIMS\Plugin\Host\Registry::forInstance( $dbname, $config );
  }
  return $registry[ $dbname ];
}

/** Return links for built-in sections and complete heading/link groups for custom sections. */
function lims_plugin_sidebar( string $org_site, string $dbname ): array
{
  $links = array();
  $custom = '';
  try
  {
    $registry = lims_plugin_registry( $dbname );
    if ( !\USLIMS\Plugin\Host\Dispatcher::sessionAllows( $_SESSION ?? array(), $registry->config()->instance ) )
      return array( array(), '' );
    foreach ( $registry->menuSections() as $id => $section )
    {
      $html = '';
      foreach ( $section['items'] as $item )
      {
        $url = 'https://' . $org_site . '/plugin.php?'
             . http_build_query( array( 'plugin' => $item['id'], 'action' => $item['action'] ), '', '&' );
        $html .= "  <a href='" . htmlspecialchars( $url, ENT_QUOTES ) . "'>"
               . htmlspecialchars( $item['label'], ENT_QUOTES ) . "</a>\n";
      }
      $links[$id] = $html;
      if ( $section['heading'] !== null )
        $custom .= '<h4>' . htmlspecialchars( $section['heading'], ENT_QUOTES ) . "</h4>\n" . $html;
    }
  }
  catch ( \Throwable $e )
  {
    error_log( 'lims plugins: menu unavailable: ' . $e->getMessage() );
    return array( array(), '' );
  }
  return array( $links, $custom );
}

function lims_plugin_menu_html( string $section, string $org_site, string $dbname ): string
{
  list( $links ) = lims_plugin_sidebar( $org_site, $dbname );
  return $links[$section] ?? '';
}
