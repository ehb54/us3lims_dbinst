<?php
/*
 * view_people_audit.php
 *
 * Read-only audit trail viewer for the people_audit table.
 *
 * Access: userlevels 0, 4, and 5 only.
 *
 * Modes:
 *   default            paginated list with optional filters
 *   ?auditID=<n>       detail view for a single event
 *
 * Filters (GET):
 *   person_id  exact personID match, driven by user dropdown
 *   actor      partial match against changed_by_email or changed_by_name
 *   action     exact match against action enum
 *   date_from  YYYY-MM-DD, inclusive lower bound on created_at
 *   date_to    YYYY-MM-DD, inclusive upper bound on created_at
 *   page       current page number (1-based)
 */

include_once 'checkinstance.php';

if ( !isset( $_SESSION['userlevel'] ) ||
     ( $_SESSION['userlevel'] != 0 &&
       $_SESSION['userlevel'] != 4 &&
       $_SESSION['userlevel'] != 5 ) )
{
  header( 'Location: index.php' );
  exit();
}

include 'config.php';
include 'db.php';

global $link;

// Mirror the same defensive default as edit_users.php.
// $enable_PAM is set in config.php; default to false if absent.
if ( !isset( $enable_PAM ) ) $enable_PAM = false;

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

define( 'AUDIT_PAGE_SIZE', 25 );

// AUDIT_ACTIONS is kept as a flat ordered list for the filter dropdown.
// Render order: lifecycle, access changes, authorization edge cases.
define( 'AUDIT_ACTIONS', [
  'CREATE',
  'UPDATE',
  'DELETE',
  'ACCOUNT_ENABLE',
  'ACCOUNT_DISABLE',
  'USERLEVEL_CHANGE',
  'ESCALATION_REJECTED',
] );

// AUDIT_ACTIONS_GROUPED drives the filter dropdown with <optgroup> sections.
// Keys are display group names; values are the action enum values.
// Display labels are generated at render time via audit_action_label() so they
// cannot drift from the list/detail view labels.
define( 'AUDIT_ACTIONS_GROUPED', [
  'Account lifecycle' => [ 'CREATE', 'UPDATE', 'DELETE' ],
  'Account access'    => [ 'ACCOUNT_ENABLE', 'ACCOUNT_DISABLE' ],
  'Authorization'     => [ 'USERLEVEL_CHANGE', 'ESCALATION_REJECTED' ],
] );

// AUDIT_FIELD_WHITELIST_GROUPED drives the Changed field dropdown with
// <optgroup> sections that mirror the section order in edit_users.php.
// Keys are JSON field keys; values are canonical UI labels.
define( 'AUDIT_FIELD_WHITELIST_GROUPED', [
  'Account access' => [
    'activated'       => 'Registration',
    'account_enabled' => 'Account status',
  ],
  'Authorization' => [
    'userlevel'       => 'User level',
    'advancelevel'    => 'Advance level',
    'gmpReviewerRole' => 'GMP reviewer role',
  ],
  'Authentication' => [
    'authenticatePAM' => 'PAM authentication',
    'userNamePAM'     => 'PAM username',
  ],
  'System access' => [
    'clusterAuthorizations' => 'Cluster authorizations',
    'instrumentPermissions' => 'Instrument permissions',
  ],
  'Profile' => [
    'fname'        => 'First name',
    'lname'        => 'Last name',
    'email'        => 'Email',
    'phone'        => 'Phone',
    'organization' => 'Organization / institution',
    'address'      => 'Address',
    'city'         => 'City',
    'state'        => 'State',
    'zip'          => 'ZIP/postal code',
    'country'      => 'Country',
  ],
] );

// ---------------------------------------------------------------------------
// Input helpers
// ---------------------------------------------------------------------------

function audit_get_string( $key )
{
  $val = isset( $_GET[ $key ] ) ? trim( $_GET[ $key ] ) : '';
  return $val === '' ? null : $val;
}

function audit_get_int( $key )
{
  if ( !isset( $_GET[ $key ] ) || $_GET[ $key ] === '' ) return null;
  $v = filter_var( $_GET[ $key ], FILTER_VALIDATE_INT );
  return $v === false ? null : (int) $v;
}

function audit_get_date( $key )
{
  $val = audit_get_string( $key );
  if ( $val === null ) return null;
  // Validate as YYYY-MM-DD
  $d = \DateTime::createFromFormat( 'Y-m-d', $val );
  return ( $d && $d->format( 'Y-m-d' ) === $val ) ? $val : null;
}

function h( $str )
{
  return htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' );
}

// Build a query-string from filter params, optionally overriding some keys.
// auditID is included so View links work; pass [ 'auditID' => '' ] to clear it.
function audit_query_string( array $overrides = [] )
{
  $keys = [ 'auditID', 'person_id', 'actor', 'action', 'changed_field', 'date_from', 'date_to', 'page' ];
  $parts = [];
  foreach ( $keys as $k )
  {
    $val = array_key_exists( $k, $overrides ) ? $overrides[ $k ] : ( $_GET[ $k ] ?? '' );
    if ( $val !== null && $val !== '' )
      $parts[] = urlencode( $k ) . '=' . urlencode( (string) $val );
  }
  return $parts ? '?' . implode( '&', $parts ) : '?';
}

// ---------------------------------------------------------------------------
// Collect filter inputs
// ---------------------------------------------------------------------------

$f_actor         = audit_get_string( 'actor' );
$f_action        = audit_get_string( 'action' );
$f_person_id     = audit_get_int( 'person_id' );
$f_date_from     = audit_get_date( 'date_from' );
$f_date_to       = audit_get_date( 'date_to' );
$f_page          = max( 1, audit_get_int( 'page' ) ?? 1 );
$f_audit_id      = audit_get_int( 'auditID' );
$f_changed_field = audit_get_string( 'changed_field' );

// Validate action filter against known enum values
if ( $f_action !== null && !in_array( $f_action, AUDIT_ACTIONS, true ) )
  $f_action = null;

// Whitelist of JSON field keys that may appear in old_values / new_values.
// Maps the field key (stored in JSON) to its canonical UI label.
// Used both for the Changed field filter dropdown and for WHERE clause safety.
// Built by flattening AUDIT_FIELD_WHITELIST_GROUPED so there is one source of truth.
define( 'AUDIT_FIELD_WHITELIST', array_merge( ...array_values( AUDIT_FIELD_WHITELIST_GROUPED ) ) );

// Validate changed_field against whitelist keys only — never pass raw GET to SQL
if ( $f_changed_field !== null && !array_key_exists( $f_changed_field, AUDIT_FIELD_WHITELIST ) )
  $f_changed_field = null;

// ---------------------------------------------------------------------------
// Build WHERE clause shared by count and list queries
// ---------------------------------------------------------------------------

function audit_build_where( $f_actor, $f_action, $f_person_id, $f_date_from, $f_date_to, $f_changed_field = null )
{
  $where  = [];
  $types  = '';
  $params = [];
  if ( $f_actor !== null )
  {
    // If the input is a pure integer, also search changed_by_personID exactly.
    // This is the most stable identifier and handles rows with missing/corrupt email.
    $actor_as_int = filter_var( $f_actor, FILTER_VALIDATE_INT );
    if ( $actor_as_int !== false )
    {
      $where[]  = '( changed_by_personID = ? OR changed_by_email LIKE ? OR changed_by_name LIKE ? )';
      $like     = '%' . $f_actor . '%';
      $types   .= 'iss';
      $params[] = (int) $actor_as_int;
      $params[] = $like;
      $params[] = $like;
    }
    else
    {
      $where[]  = '( changed_by_email LIKE ? OR changed_by_name LIKE ? )';
      $like     = '%' . $f_actor . '%';
      $types   .= 'ss';
      $params[] = $like;
      $params[] = $like;
    }
  }

  if ( $f_action !== null )
  {
    $where[]  = 'action = ?';
    $types   .= 's';
    $params[] = $f_action;
  }

  if ( $f_person_id !== null )
  {
    $where[]  = 'personID = ?';
    $types   .= 'i';
    $params[] = $f_person_id;
  }

  if ( $f_date_from !== null )
  {
    $where[]  = 'created_at >= ?';
    $types   .= 's';
    $params[] = $f_date_from . ' 00:00:00';
  }

  if ( $f_date_to !== null )
  {
    $where[]  = 'created_at <= ?';
    $types   .= 's';
    $params[] = $f_date_to . ' 23:59:59';
  }

    // Changed-field filter: key is whitelisted above; safe to search as JSON text.
    // Only apply this filter to diff-style rows. CREATE and DELETE rows store full
    // snapshots, so matching those rows would mean "field exists in snapshot" rather
    // than "field changed".
    if ( $f_changed_field !== null && array_key_exists( $f_changed_field, AUDIT_FIELD_WHITELIST ) )
    {
        $needle   = '%"' . $f_changed_field . '"%';
        $where[]  = "( action NOT IN ('CREATE', 'DELETE') AND ( old_values LIKE ? OR new_values LIKE ? ) )";
        $types   .= 'ss';
        $params[] = $needle;
        $params[] = $needle;
    }

  $sql = $where ? ( 'WHERE ' . implode( ' AND ', $where ) ) : '';
  return [ $sql, $types, $params ];
}

// ---------------------------------------------------------------------------
// Action label helpers
// ---------------------------------------------------------------------------

// Compliance-friendly display label for a stored action enum value.
function audit_action_label( $action )
{
  $map = [
    'CREATE'              => 'User created',
    'UPDATE'              => 'User updated',
    'DELETE'              => 'User deleted',
    'ACCOUNT_ENABLE'      => 'Account enabled',
    'ACCOUNT_DISABLE'     => 'Account disabled',
    'USERLEVEL_CHANGE'    => 'User level changed',
    'ESCALATION_REJECTED' => 'Escalation rejected',
  ];
  return $map[ $action ] ?? $action;
}

// Returns true for actions whose JSON is a full snapshot (CREATE/DELETE)
// rather than a before/after diff.
function audit_action_is_snapshot( $action )
{
  return in_array( $action, [ 'CREATE', 'DELETE' ], true );
}

// ---------------------------------------------------------------------------
// Detail mode — field formatting helpers
// ---------------------------------------------------------------------------

// Canonical human-readable label for every auditable field key.
// Must agree with AUDIT_FIELD_WHITELIST values and with the labels used on
// edit_users.php / view_users.php (Registration, Account, etc.).
function audit_detail_label( $key )
{
  // AUDIT_FIELD_WHITELIST is the single source of truth for all auditable field
  // labels, including gmpReviewerRole (Authorization group). Fall back to a
  // humanised key name for any unexpected field not in the whitelist.
  return AUDIT_FIELD_WHITELIST[ $key ] ?? ucfirst( str_replace( '_', ' ', $key ) );
}

// Format a single field value for human-readable display in the detail view.
// Returns an HTML string (not yet wrapped in a <td>).
function audit_format_value( $key, $val, $key_present, $instrument_map )
{
  if ( !$key_present )
    return '<span class="audit-absent">Not set</span>';
  if ( $val === null )
    return '<span class="audit-null">Not set</span>';
  if ( $val === '' )
    return '<span class="audit-empty">(empty)</span>';

  // Field-specific formatting.
  switch ( $key )
  {
    case 'account_enabled':
      return $val == '1'
        ? '<span class="audit-val-on">Enabled</span>'
        : '<span class="audit-val-off">Disabled</span>';

    case 'activated':
      return $val == '1'
        ? '<span class="audit-val-on">Activated</span>'
        : '<span class="audit-val-off">Not Activated</span>';

    case 'advancelevel':
      return h( (string) $val );

    case 'authenticatePAM':
      return $val == '1' ? 'Yes' : 'No';

    case 'userlevel':
      $labels = [
        '0' => 'UL0 — User Management Admin',
        '1' => 'UL1 — Unprivileged User',
        '2' => 'UL2 — Analyst',
        '3' => 'UL3 — Data Manager',
        '4' => 'UL4 — Superuser',
        '5' => 'UL5',
      ];
      return h( $labels[ (string) $val ] ?? 'UL' . h( (string) $val ) );

    case 'instrumentPermissions':
      // Stored as colon-delimited IDs, e.g. "1:3:7" or "" for none.
      if ( $val === '' || $val === null )
        return '<em>None</em>';
      $ids  = array_filter( explode( ':', (string) $val ) );
      if ( empty( $ids ) )
        return '<em>None</em>';
      $names = [];
      foreach ( $ids as $id )
      {
        $id = trim( $id );
        $names[] = isset( $instrument_map[ $id ] )
          ? h( $instrument_map[ $id ] ) . ' <span class="audit-id">(#' . h( $id ) . ')</span>'
          : 'ID ' . h( $id );
      }
      return implode( ', ', $names );

    case 'clusterAuthorizations':
      // Colon-delimited cluster short-names; empty means none.
      if ( $val === '' )
        return '<em>None</em>';
      return h( str_replace( ':', ', ', (string) $val ) );

    case 'gmpReviewerRole':
      $labels = [
        'NONE'     => 'None',
        'REVIEWER' => 'Reviewer',
        'APPROVER' => 'Approver',
      ];
      return h( $labels[ (string) $val ] ?? $val );

    default:
      return h( (string) $val );
  }
}

// Load instrument ID -> "name (serialNumber)" map for this DB connection.
// Returns an associative array keyed by string instrumentID.
function audit_load_instrument_map( $link )
{
  $map    = [];
  $result = $link->query( "SELECT instrumentID, name, serialNumber FROM instrument ORDER BY instrumentID" );
  if ( !$result ) return $map;
  while ( $row = $result->fetch_assoc() )
  {
    $label = trim( $row['name'] ?? '' );
    $sn    = trim( $row['serialNumber'] ?? '' );
    if ( $sn !== '' ) $label .= ' [' . $sn . ']';
    $map[ (string) $row['instrumentID'] ] = $label !== '' ? $label : 'Instrument #' . $row['instrumentID'];
  }
  $result->free();
  return $map;
}

function render_json_diff( $old_json, $new_json, $instrument_map, $action = 'UPDATE' )
{
  global $enable_PAM;
  $pam_keys = [ 'authenticatePAM', 'userNamePAM' ];

  // Helper: sort a key array by whitelist order.
  $sort_keys = function( array &$keys ) use ( $pam_keys ) {
    global $enable_PAM;
    if ( !$enable_PAM )
      $keys = array_values( array_diff( $keys, $pam_keys ) );
    $key_order = array_keys( AUDIT_FIELD_WHITELIST );
    usort( $keys, function( $a, $b ) use ( $key_order ) {
      $pa = array_search( $a, $key_order );
      $pb = array_search( $b, $key_order );
      $pa = ( $pa === false ) ? PHP_INT_MAX : $pa;
      $pb = ( $pb === false ) ? PHP_INT_MAX : $pb;
      return $pa !== $pb ? $pa - $pb : strcmp( $a, $b );
    } );
  };

  // CREATE / DELETE: render a single-column snapshot table.
  if ( audit_action_is_snapshot( $action ) )
  {
    $snap_json  = ( $action === 'DELETE' ) ? $old_json : $new_json;
    $snap_title = ( $action === 'DELETE' ) ? 'Deleted user snapshot' : 'Created user snapshot';
    $snap_data  = ( $snap_json !== null ) ? @json_decode( $snap_json, true ) : null;

    if ( !is_array( $snap_data ) || empty( $snap_data ) )
    {
      echo "<p class='audit-none'>No snapshot recorded.</p>\n";
      return;
    }

    $keys = array_keys( $snap_data );
    $sort_keys( $keys );

    echo "<table class='audit-diff'>\n";
    echo "<thead><tr><th colspan='2'>" . h( $snap_title ) . "</th></tr>\n";
    echo "<tr><th>Field</th><th>Value</th></tr></thead>\n";
    echo "<tbody>\n";
    foreach ( $keys as $key )
    {
      $val  = $snap_data[ $key ] ?? null;
      $disp = audit_format_value( $key, $val, array_key_exists( $key, $snap_data ), $instrument_map );
      echo "<tr><td>" . h( audit_detail_label( $key ) ) . "</td><td>$disp</td></tr>\n";
    }
    echo "</tbody></table>\n";
    return;
  }

  // UPDATE-class: before/after diff table.
  $old_raw = ( $old_json !== null ) ? @json_decode( $old_json, true ) : null;
  $new_raw = ( $new_json !== null ) ? @json_decode( $new_json, true ) : null;

  $old_valid = is_array( $old_raw );
  $new_valid = is_array( $new_raw );

  if ( $old_json === null && $new_json === null )
  {
    echo "<p class='audit-none'>No field-level changes recorded for this event.</p>\n";
    return;
  }

  if ( $old_json !== null && !$old_valid && $new_json !== null && !$new_valid )
  {
    echo "<pre class='audit-raw'>" . h( $old_json ) . "\n---\n" . h( $new_json ) . "</pre>\n";
    return;
  }

  $old_data = $old_valid ? $old_raw : [];
  $new_data = $new_valid ? $new_raw : [];

  $all_keys = array_unique( array_merge( array_keys( $old_data ), array_keys( $new_data ) ) );
  $sort_keys( $all_keys );

  if ( empty( $all_keys ) )
  {
    echo "<p class='audit-none'>No fields recorded.</p>\n";
    return;
  }

  echo "<table class='audit-diff'>\n";
  echo "<thead><tr><th colspan='3'>Changed fields</th></tr>\n";
  echo "<tr><th>Field</th><th>Before</th><th>After</th></tr></thead>\n";
  echo "<tbody>\n";

  foreach ( $all_keys as $key )
  {
    $old_val     = array_key_exists( $key, $old_data ) ? $old_data[ $key ] : null;
    $new_val     = array_key_exists( $key, $new_data ) ? $new_data[ $key ] : null;
    $old_present = array_key_exists( $key, $old_data );
    $new_present = array_key_exists( $key, $new_data );

    $old_disp  = audit_format_value( $key, $old_val, $old_present, $instrument_map );
    $new_disp  = audit_format_value( $key, $new_val, $new_present, $instrument_map );
    $changed   = ( (string) $old_val !== (string) $new_val );
    $row_class = $changed ? ' class="audit-changed"' : '';

    echo "<tr$row_class><td>" . h( audit_detail_label( $key ) ) . "</td><td>$old_disp</td><td>$new_disp</td></tr>\n";
  }

  echo "</tbody></table>\n";
}

function render_detail( $link, $audit_id )
{
  $stmt = $link->prepare(
    "SELECT auditID, personID, user_email, user_name,
            changed_by_personID, changed_by_email, changed_by_name,
            action, old_values, new_values, notes, created_at
     FROM people_audit
     WHERE auditID = ?"
  );
  if ( !$stmt )
  {
    echo "<p class='message'>Query failed.</p>\n";
    return;
  }
  $stmt->bind_param( 'i', $audit_id );
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $result->close();
  $stmt->close();

  if ( !$row )
  {
    echo "<p class='message'>Audit record #" . h( $audit_id ) . " not found.</p>\n";
    $back = audit_query_string( [ 'auditID' => '', 'page' => '' ] );
    echo "<p><a href='" . h( $_SERVER['PHP_SELF'] . $back ) . "'>&larr; Back to list</a></p>\n";
    return;
  }

  $instrument_map = audit_load_instrument_map( $link );

  // Back link preserves list filters
  $back = audit_query_string( [ 'auditID' => '' ] );
  echo "<p class='audit-back'><a href='" . h( $_SERVER['PHP_SELF'] . $back ) . "'>&larr; Back to list</a></p>\n";

  echo "<h2>Audit Event #" . h( $row['auditID'] ) . "</h2>\n";

  echo "<table class='audit-detail'>\n";
  echo "<tbody>\n";
  echo "<tr><th colspan='2' class='audit-section'>Event information</th></tr>\n";
  echo "<tr><th>Audit ID</th><td>"    . h( $row['auditID']    ) . "</td></tr>\n";
  echo "<tr><th>Timestamp</th><td>"   . h( $row['created_at'] ) . "</td></tr>\n";
  echo "<tr><th>Action</th><td><strong>" . h( audit_action_label( $row['action'] ) ) . "</strong></td></tr>\n";
  echo "<tr><th>Summary</th><td>"     . audit_summary( $row['action'], $row['old_values'], $row['new_values'], $row['notes'] ) . "</td></tr>\n";

  if ( $row['notes'] !== null && $row['notes'] !== '' )
    echo "<tr><th>Notes</th><td>" . h( $row['notes'] ) . "</td></tr>\n";

  echo "<tr><th colspan='2' class='audit-section'>Affected user</th></tr>\n";
  echo "<tr><th>Person ID</th><td>" . h( $row['personID']   ?? '—' ) . "</td></tr>\n";
  echo "<tr><th>Email</th><td>"     . h( $row['user_email'] ?? '—' ) . "</td></tr>\n";
  echo "<tr><th>Name</th><td>"      . h( $row['user_name']  ?? '—' ) . "</td></tr>\n";

  echo "<tr><th colspan='2' class='audit-section'>Changed by</th></tr>\n";
  echo "<tr><th>Person ID</th><td>" . h( $row['changed_by_personID'] ?? '—' ) . "</td></tr>\n";
  echo "<tr><th>Email</th><td>"     . h( $row['changed_by_email']    ?? '—' ) . "</td></tr>\n";
  echo "<tr><th>Name</th><td>"      . h( $row['changed_by_name']     ?? '—' ) . "</td></tr>\n";

  echo "</tbody></table>\n";

  render_json_diff( $row['old_values'], $row['new_values'], $instrument_map, $row['action'] );
}

// ---------------------------------------------------------------------------
// Summary line helpers (list view)
// ---------------------------------------------------------------------------

// Build a compact one-line summary of what changed for the list view.
// Uses the canonical AUDIT_FIELD_WHITELIST labels (title-case), no trailing "changed".
// PAM fields are suppressed when $enable_PAM is false.
// Returns an HTML string (already escaped).
function audit_summary( $action, $old_json, $new_json, $notes )
{
  global $enable_PAM;

  if ( $action === 'ESCALATION_REJECTED' )
    return h( $notes ?: 'Escalation rejected' );

  if ( $action === 'CREATE' )
    return 'Account created';

  if ( $action === 'DELETE' )
    return 'Account deleted';

  $new_data = ( $new_json !== null ) ? @json_decode( $new_json, true ) : null;
  $old_data = ( $old_json !== null ) ? @json_decode( $old_json, true ) : null;

  if ( !is_array( $new_data ) && !is_array( $old_data ) )
    return h( $action );

  $changed_keys = array_unique( array_merge(
    array_keys( is_array( $old_data ) ? $old_data : [] ),
    array_keys( is_array( $new_data ) ? $new_data : [] )
  ) );

  if ( empty( $changed_keys ) )
    return h( $action );

  $pam_keys = [ 'authenticatePAM', 'userNamePAM' ];

  $labels = [];
  foreach ( $changed_keys as $k )
  {
    $labels[] = AUDIT_FIELD_WHITELIST[ $k ] ?? ucfirst( str_replace( '_', ' ', $k ) );
  }
  sort( $labels );
  return $labels ? h( implode( '; ', $labels ) ) : h( $action );
}

// ---------------------------------------------------------------------------
// List mode
// ---------------------------------------------------------------------------

function render_list( $link, $f_actor, $f_action, $f_person_id, $f_date_from, $f_date_to, $f_page, $f_changed_field = null )
{
  [ $where_sql, $where_types, $where_params ] =
    audit_build_where( $f_actor, $f_action, $f_person_id, $f_date_from, $f_date_to, $f_changed_field );

  // Total count for pagination
  $count_sql  = "SELECT COUNT(*) FROM people_audit $where_sql";
  $count_stmt = $link->prepare( $count_sql );
  if ( $where_types )
    $count_stmt->bind_param( $where_types, ...$where_params );
  $count_stmt->execute();
  $count_stmt->bind_result( $total );
  $count_stmt->fetch();
  $count_stmt->close();

  $total_pages = max( 1, (int) ceil( $total / AUDIT_PAGE_SIZE ) );
  $page        = min( $f_page, $total_pages );
  $offset      = ( $page - 1 ) * AUDIT_PAGE_SIZE;

  render_filter_form( $link, $f_person_id, $f_actor, $f_action, $f_changed_field, $f_date_from, $f_date_to );

  if ( $total === 0 )
  {
    echo "<p class='message'>No audit records match the current filters.</p>\n";
    return;
  }

  echo "<p class='audit-count'>Showing " . h( $total ) . " record" . ( $total != 1 ? 's' : '' ) . ".</p>\n";

  // Fetch page — include old_values/new_values for summary line.
  $list_sql = "SELECT auditID, created_at, action,
                      user_email, user_name,
                      changed_by_email, changed_by_name,
                      old_values, new_values, notes
               FROM people_audit
               $where_sql
               ORDER BY auditID DESC
               LIMIT " . AUDIT_PAGE_SIZE . " OFFSET " . (int) $offset;

  $list_stmt = $link->prepare( $list_sql );
  if ( $where_types )
    $list_stmt->bind_param( $where_types, ...$where_params );
  $list_stmt->execute();
  $result = $list_stmt->get_result();

  echo "<div class='audit-scroll'>\n";
  echo "<table class='audit-list'>\n";
  echo "<thead><tr>\n";
  echo "  <th>Timestamp</th>\n";
  echo "  <th>Action</th>\n";
  echo "  <th>User</th>\n";
  echo "  <th>Changed by</th>\n";
  echo "  <th>Summary</th>\n";
  echo "  <th></th>\n";
  echo "</tr></thead>\n";
  echo "<tbody>\n";

  while ( $row = $result->fetch_assoc() )
  {
    $detail_url = h( $_SERVER['PHP_SELF'] . audit_query_string( [ 'auditID' => $row['auditID'], 'page' => '' ] ) );
    $summary    = audit_summary( $row['action'], $row['old_values'], $row['new_values'], $row['notes'] );
    $user_cell  = h( $row['user_email'] ?? '' );
    if ( ( $row['user_name'] ?? '' ) !== '' )
      $user_cell .= '<br><span class="audit-subtext">' . h( $row['user_name'] ) . '</span>';
    // Show actor name first (most readable), email as secondary.
    // changed_by_personID is intentionally omitted from the list; it is
    // visible in the detail view only.
    $actor_name  = trim( $row['changed_by_name'] ?? '' );
    $actor_email = trim( $row['changed_by_email'] ?? '' );
    if ( $actor_name !== '' ) {
      $actor_cell = h( $actor_name );
      if ( $actor_email !== '' )
        $actor_cell .= '<br><span class="audit-subtext">' . h( $actor_email ) . '</span>';
    } else {
      $actor_cell = h( $actor_email );
    }
    echo "<tr>\n";
    echo "  <td class='audit-ts'>"  . h( $row['created_at'] ) . "</td>\n";
    echo "  <td><strong>" . h( audit_action_label( $row['action'] ) ) . "</strong></td>\n";
    echo "  <td>$user_cell</td>\n";
    echo "  <td>$actor_cell</td>\n";
    echo "  <td class='audit-summary'>$summary</td>\n";
    echo "  <td class='audit-view'><a href='$detail_url'>View</a></td>\n";
    echo "</tr>\n";
  }

  $result->close();
  $list_stmt->close();

  echo "</tbody></table>\n";
  echo "</div>\n";

  render_pagination( $page, $total_pages );
}

function render_filter_form( $link, $f_person_id, $f_actor, $f_action, $f_changed_field, $f_date_from, $f_date_to )
{
  // Build user dropdown from people table, ordered by name.
  // Userlevel 0 sees only levels 0-3 (same restriction as edit_users).
  $userlevel  = $_SESSION['userlevel'] ?? -1;
  $where_lvl  = ( $userlevel == 0 ) ? 'WHERE userlevel <= 3' : '';
  $ppl_result = mysqli_query(
    $link,
    "SELECT personID, lname, fname FROM people $where_lvl ORDER BY lname, fname"
  );

  $subject_options = "<option value=''>All users</option>\n";
  if ( $ppl_result )
  {
    while ( $ppl = mysqli_fetch_assoc( $ppl_result ) )
    {
      $sel               = ( (int) $f_person_id === (int) $ppl['personID'] ) ? " selected='selected'" : '';
      $label             = h( $ppl['lname'] . ', ' . $ppl['fname'] );
      $subject_options  .= "<option value='" . h( $ppl['personID'] ) . "'$sel>$label</option>\n";
    }
    mysqli_free_result( $ppl_result );
  }

  $action_options = "<option value=''>All actions</option>\n";
  foreach ( AUDIT_ACTIONS_GROUPED as $group_label => $actions )
  {
    $action_options .= "<optgroup label='" . h( $group_label ) . "'>\n";
    foreach ( $actions as $a )
    {
      $sel = ( $f_action === $a ) ? " selected='selected'" : '';
      // Use audit_action_label() so the dropdown label is always identical
      // to the label shown in the list table and detail page.
      $action_options .= "  <option value='" . h( $a ) . "'$sel>" . h( audit_action_label( $a ) ) . "</option>\n";
    }
    $action_options .= "</optgroup>\n";
  }

  $field_options = "<option value=''>All changed fields</option>\n";
  foreach ( AUDIT_FIELD_WHITELIST_GROUPED as $group_label => $fields )
  {
    $field_options .= "<optgroup label='" . h( $group_label ) . "'>\n";
    foreach ( $fields as $field_key => $field_label )
    {
      $sel = ( $f_changed_field === $field_key ) ? " selected='selected'" : '';
      $field_options .= "  <option value='" . h( $field_key ) . "'$sel>" . h( $field_label ) . "</option>\n";
    }
    $field_options .= "</optgroup>\n";
  }

  $v_actor     = h( $f_actor     ?? '' );
  $v_date_from = h( $f_date_from ?? '' );
  $v_date_to   = h( $f_date_to   ?? '' );
  $self        = h( $_SERVER['PHP_SELF'] );

echo <<<HTML
<form method='get' action='$self' class='audit-filter'>
  <table class='noborder'>
    <tr>
      <th>User:</th>
      <td><select name='person_id'>$subject_options</select></td>
      <th>Changed by (ID/email/name):</th>
      <td><input type='text' name='actor' value='$v_actor' size='30' /></td>
    </tr>
    <tr>
      <th>Action:</th>
      <td><select name='action'>$action_options</select></td>
      <th>Changed field:</th>
      <td><select name='changed_field'>$field_options</select></td>
    </tr>
    <tr>
      <th>Date from:</th>
      <td><input type='text' name='date_from' value='$v_date_from' size='12' placeholder='YYYY-MM-DD' /></td>
      <th>Date to:</th>
      <td><input type='text' name='date_to' value='$v_date_to' size='12' placeholder='YYYY-MM-DD' /></td>
    </tr>
    <tr>
      <td colspan='4'>
        <input type='submit' value='Apply filters' />
        <a href='$self'>Clear filters</a>
      </td>
    </tr>
  </table>
</form>
HTML;
}

function render_pagination( $page, $total_pages )
{
  if ( $total_pages <= 1 ) return;

  echo "<div class='audit-pagination'>\n";

  if ( $page > 1 )
    echo "<a href='" . h( $_SERVER['PHP_SELF'] . audit_query_string( [ 'page' => $page - 1 ] ) ) . "'>&laquo; Prev</a> ";

  echo "Page " . h( $page ) . " of " . h( $total_pages );

  if ( $page < $total_pages )
    echo " <a href='" . h( $_SERVER['PHP_SELF'] . audit_query_string( [ 'page' => $page + 1 ] ) ) . "'>Next &raquo;</a>";

  echo "\n</div>\n";
}

// ---------------------------------------------------------------------------
// Page output
// ---------------------------------------------------------------------------

$page_title = 'User Audit Log';
include 'header.php';
?>

<style>
/* Audit page styles — scoped to avoid touching global layout */

/* Widen the content area for this table-heavy page and add right breathing room */
#content                { width: auto; max-width: 940px; padding-right: 24px; box-sizing: border-box; }

/* Scrollable wrapper so the list never pushes outside the viewport */
.audit-scroll           { overflow-x: auto; width: 100%; }

/* Filter form */
.audit-filter th        { text-align: right; padding-right: 6px; font-weight: normal; white-space: nowrap; }
.audit-filter td        { padding: 3px 10px 3px 0; }

/* List table */
.audit-list             { width: 100%; border-collapse: collapse; border: 1px solid #2B4E72; }
.audit-list th          { background: #2B4E72; color: #fff; padding: 6px 8px; text-align: left;
                          font-size: 12px; white-space: nowrap; }
.audit-list td          { padding: 5px 8px; border-bottom: 1px solid #ddd; vertical-align: top;
                          font-size: 12px; }
.audit-list tr:hover td { background: #f0f4fb; }
.audit-ts               { white-space: nowrap; color: #555; }
.audit-subtext          { font-size: 10px; color: #777; }
.audit-summary          { color: #333; }
.audit-view             { white-space: nowrap; text-align: center; }

/* Detail metadata table */
.audit-back             { margin-bottom: 8px; }
.audit-detail           { width: 100%; border-collapse: collapse;
                          margin-bottom: 20px; }
.audit-detail th        { text-align: right; padding: 6px 12px; white-space: nowrap; width: 180px;
                          background: #f0f2f5; border: 1px solid #ccc;
                          font-weight: normal; color: #333; }
.audit-detail td        { padding: 6px 12px; border: 1px solid #ccc; }
/* Section divider rows — dark blue, readable white text */
.audit-section          { background: #2B4E72 !important; color: #fff !important;
                          font-size: 0.78em; font-weight: bold;
                          letter-spacing: 0.08em; text-transform: uppercase;
                          padding: 5px 12px; text-align: left !important;
                          border-color: #2B4E72 !important; }

/* Before/after diff table */
.audit-diff             { border-collapse: collapse; width: 100%; margin-top: 4px; }
.audit-diff th          { background: #2B4E72; color: #fff; padding: 6px 10px; text-align: left;
                          font-size: 12px; }
.audit-diff td          { padding: 6px 10px; border-bottom: 1px solid #ddd; vertical-align: top;
                          font-size: 12px; }
.audit-diff tr.audit-changed td { background: #fffbe6; }
/* Field label column — plain text, not monospace, matches site style */
.audit-diff td:first-child { color: #333; font-weight: bold; white-space: nowrap; width: 200px; }

/* Value state indicators */
.audit-val-on           { color: #2a7a2a; font-weight: bold; }
.audit-val-off          { color: #c0392b; font-weight: bold; }
.audit-id               { color: #999; font-size: 10px; }

/* Misc */
.audit-null, .audit-absent { color: #999; font-style: italic; }
.audit-empty            { color: #bbb; font-style: italic; }
.audit-pagination       { margin: 12px 0; }
.audit-pagination a     { margin: 0 6px; }
.audit-count            { color: #666; font-size: 0.9em; margin: 6px 0; }
.audit-none             { color: #999; font-style: italic; }
.audit-raw              { background: #f8f8f8; padding: 8px; font-size: 0.85em; overflow-x: auto; }
</style>

<div id='content'>
  <h1 class="title">User Audit Log</h1>

<?php

if ( $f_audit_id !== null )
  render_detail( $link, $f_audit_id );
else
  render_list( $link, $f_actor, $f_action, $f_person_id, $f_date_from, $f_date_to, $f_page, $f_changed_field );

?>

</div>

<?php
include 'footer.php';
exit();
