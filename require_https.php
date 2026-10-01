<?php
// Credentials and sessions are HTTPS only: redirect plain HTTP to the configured site.
if ( PHP_SAPI !== 'cli' && ( empty( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS'] === 'off' ) )
{
  header( 'Location: https://' . strtok( $org_site, '/' ) . $_SERVER['REQUEST_URI'], true, 301 );
  exit();
}
