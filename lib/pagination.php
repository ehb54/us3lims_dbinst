<?php
/*
 * lib/pagination.php
 *
 * Shared server-side pagination: page/offset math and the Previous/Next
 * link strip, extracted from view_people_audit.php so other pages (e.g.
 * edit_projects.php's Associated Runs section) don't reimplement it.
 *
 * Callers remain responsible for their own authorization and WHERE clause;
 * this file only turns a requested page + a row count into an offset, and
 * renders links that preserve whatever filter keys the caller passes in.
 */

define( 'PAGINATION_DEFAULT_PAGE_SIZE', 25 );

// Defensive ceiling on page size, independent of any one caller's default.
// Nothing today lets a user supply their own page size, but a LIMIT built
// from an unchecked value would let one ask the database for an unbounded
// result set, so every page size is clamped here before it reaches SQL.
define( 'PAGINATION_MAX_PAGE_SIZE', 200 );

// Validate a 1-based page number from an arbitrary (usually $_GET) value.
// Anything not a positive integer becomes 1; pagination_compute() below
// clamps further once the real last page is known.
function pagination_get_page( $raw )
{
  $page = filter_var( $raw, FILTER_VALIDATE_INT );
  return ( $page === false || $page < 1 ) ? 1 : $page;
}

// Clamp a requested page size into [1, PAGINATION_MAX_PAGE_SIZE].
function pagination_page_size( $requested = PAGINATION_DEFAULT_PAGE_SIZE )
{
  $size = filter_var( $requested, FILTER_VALIDATE_INT );
  if ( $size === false || $size < 1 ) return PAGINATION_DEFAULT_PAGE_SIZE;
  return min( $size, PAGINATION_MAX_PAGE_SIZE );
}

// Compute page/page_size/total_pages/offset from a requested page and a
// known total row count. $total must come from a COUNT(*) that applies the
// exact same WHERE clause (including authorization) as the results query
// that will use the returned offset -- otherwise the page count and the
// Previous/Next range will not match what the results query actually
// returns, and may include or imply rows the caller is not authorized to see.
function pagination_compute( $requested_page, $total, $page_size = PAGINATION_DEFAULT_PAGE_SIZE )
{
  $page_size   = pagination_page_size( $page_size );
  $total_pages = max( 1, (int) ceil( $total / $page_size ) );
  $page        = max( 1, min( pagination_get_page( $requested_page ), $total_pages ) );
  $offset      = ( $page - 1 ) * $page_size;
  return [
    'page'        => $page,
    'page_size'   => $page_size,
    'total_pages' => $total_pages,
    'offset'      => $offset,
  ];
}

// Build a query string from $_GET, restricted to $keys, with $overrides
// applied (value '' or null omits that key -- pass e.g. [ 'page' => '' ]
// to drop it). Mirrors view_people_audit.php's original audit_query_string()
// shape so existing links/behavior don't change when it's rewired to call this.
function pagination_query_string( array $keys, array $overrides = [] )
{
  $parts = [];
  foreach ( $keys as $k )
  {
    $val = array_key_exists( $k, $overrides ) ? $overrides[ $k ] : ( $_GET[ $k ] ?? '' );
    if ( $val !== null && $val !== '' )
      $parts[] = urlencode( $k ) . '=' . urlencode( (string) $val );
  }
  return $parts ? '?' . implode( '&', $parts ) : '?';
}

// Render the standard "Page X of Y" strip with Previous/Next links that
// preserve $filter_keys (the full list of GET keys the caller wants kept,
// including whichever one of them is the page number).
function pagination_render( $base_url, array $filter_keys, $page, $total_pages, $page_key = 'page', $css_class = 'pagination-controls' )
{
  if ( $total_pages <= 1 ) return;

  echo "<div class='" . htmlspecialchars( $css_class, ENT_QUOTES, 'UTF-8' ) . "'>\n";

  if ( $page > 1 )
    echo "<a href='" . htmlspecialchars( $base_url . pagination_query_string( $filter_keys, [ $page_key => $page - 1 ] ), ENT_QUOTES, 'UTF-8' ) . "'>&laquo; Prev</a> ";

  echo 'Page ' . (int) $page . ' of ' . (int) $total_pages;

  if ( $page < $total_pages )
    echo " <a href='" . htmlspecialchars( $base_url . pagination_query_string( $filter_keys, [ $page_key => $page + 1 ] ), ENT_QUOTES, 'UTF-8' ) . "'>Next &raquo;</a>";

  echo "\n</div>\n";
}
