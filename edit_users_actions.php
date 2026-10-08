<?php
/*
 * edit_users_actions.php
 *
 * All action and helper functions for edit_users.php.
 *
 * Extracted into a separate file so that the dispatch layer, HTML output,
 * and session-guard exit() in edit_users.php are kept separate from the
 * business logic.  Production behavior is unchanged: edit_users.php includes
 * this file and calls the same functions as before.
 */

// ---------------------------------------------------------------------------
// Redirect-and-exit wrapper
// ---------------------------------------------------------------------------

/**
 * Send an HTTP redirect and terminate execution.
 * Replaces every inline header()/exit() pair inside action functions.
 */
function _eu_redirect_and_exit( $url )
{
  header( "Location: $url" );
  exit();
}

// ---------------------------------------------------------------------------
// Audit helpers
// ---------------------------------------------------------------------------

// Fields that must never appear in audit JSON columns.
// Covers passwords, hashes, tokens, and system-managed timestamps.
define( 'AUDIT_EXCLUDED_FIELDS', [
  'password',
  'passwd',
  'pwd',
  'hash',
  'token',
  'reset_token',
  'session',
  'lastLogin',
  'personGUID',
] );

// Returns an array with the three changed-by identity fields sourced from the
// current session.  Uses loginID (never changes in delegation) rather than id.
function audit_actor_snapshot()
{
  return [
    'personID' => isset( $_SESSION['loginID'] ) ? intval( $_SESSION['loginID'] ) : null,
    'email'    => $_SESSION['email']     ?? null,
    'name'     => isset( $_SESSION['firstname'], $_SESSION['lastname'] )
                  ? trim( $_SESSION['firstname'] . ' ' . $_SESSION['lastname'] )
                  : null,
  ];
}

// Removes excluded fields from an associative array before it is written to
// an audit column.  Returns the filtered array, or null if the input is null.
function audit_filter_values( $values )
{
  if ( $values === null ) return null;
  return array_diff_key( $values, array_flip( AUDIT_EXCLUDED_FIELDS ) );
}

// Computes a diff between two associative arrays representing the same row.
// Returns [ 'old' => [...], 'new' => [...] ] containing only keys whose
// values differ.  Excluded fields are stripped before comparison.
// Returns null for both sides if no auditable fields changed.
function audit_diff( $old_row, $new_row )
{
  $old_row = audit_filter_values( $old_row );
  $new_row = audit_filter_values( $new_row );

  $old_diff = [];
  $new_diff = [];

  $all_keys = array_unique( array_merge( array_keys( $old_row ), array_keys( $new_row ) ) );
  foreach ( $all_keys as $key )
  {
    $old_val = $old_row[ $key ] ?? null;
    $new_val = $new_row[ $key ] ?? null;
    // Cast to string for comparison so '1' and 1 are treated as equal.
    if ( (string) $old_val !== (string) $new_val )
    {
      $old_diff[ $key ] = $old_val;
      $new_diff[ $key ] = $new_val;
    }
  }

  if ( empty( $old_diff ) && empty( $new_diff ) ) return null;

  return [ 'old' => $old_diff, 'new' => $new_diff ];
}

// Inserts one row into people_audit.
//
// Parameters:
//   $link       -- the mysqli connection
//   $personID   -- the people.personID that was created/updated/deleted
//   $user_email -- email snapshot of the user at the time of the action
//   $user_name  -- name snapshot of the user at the time of the action
//   $action     -- one of the people_audit action enum values
//   $old_values -- associative array or null; will be filtered and JSON-encoded
//   $new_values -- associative array or null; will be filtered and JSON-encoded
//   $notes      -- optional VARCHAR(255) string, or null
//
// Does not throw.  Logs any error to the PHP error log and returns false on
// failure so that the caller can decide whether to propagate the error.
// When called inside a transaction the caller is responsible for rolling back
// if a false return value must abort the operation.
function write_audit_row( $link, $personID, $user_email, $user_name, $action, $old_values, $new_values, $notes = null )
{
  $actor = audit_actor_snapshot();

  $old_json = ( $old_values !== null ) ? json_encode( audit_filter_values( $old_values ) ) : null;
  $new_json = ( $new_values !== null ) ? json_encode( audit_filter_values( $new_values ) ) : null;

  $sql = "INSERT INTO people_audit
            ( personID,  user_email,  user_name,  changed_by_personID,  changed_by_email,  changed_by_name,  action,  old_values,  new_values,  notes )
          VALUES
            ( ?,         ?,           ?,          ?,                    ?,                 ?,                ?,       ?,           ?,           ? )";

  $stmt = $link->prepare( $sql );
  if ( !$stmt )
  {
    error_log( 'people_audit prepare failed: ' . $link->error );
    return false;
  }

  $stmt->bind_param(
    'ississssss',
    $personID,
    $user_email,
    $user_name,
    $actor['personID'],
    $actor['email'],
    $actor['name'],
    $action,
    $old_json,
    $new_json,
    $notes
  );

  $ok = $stmt->execute();
  if ( !$ok )
    error_log( 'people_audit insert failed: ' . $stmt->error );

  $stmt->close();
  return $ok;
}

// Loads instrument permissions for a given personID from the permits table.
// Returns a deterministic sorted colon-delimited string of instrumentIDs,
// e.g. '1:3:7'.  Returns an empty string if no permits exist.
// Must be called inside an open transaction when used for audit snapshots.
function audit_load_instrument_permissions( $link, $personID )
{
  $stmt = $link->prepare( "SELECT DISTINCT instrumentID FROM permits WHERE personID = ? ORDER BY instrumentID ASC" );
  if ( !$stmt )
  {
    error_log( 'audit_load_instrument_permissions prepare failed: ' . $link->error );
    return '';
  }
  $stmt->bind_param( 'i', $personID );
  $stmt->execute();
  $result = $stmt->get_result();
  $ids    = [];
  while ( $row = $result->fetch_row() )
    $ids[] = $row[0];
  $result->close();
  $stmt->close();
  return implode( ':', $ids );
}

// ---------------------------------------------------------------------------
// Authorization helpers
// ---------------------------------------------------------------------------

// Returns true if the actor (by userlevel) may manage the target (by userlevel).
// userlevel 4 and 5: may manage anyone.
// userlevel 0: may manage userlevel 0-3 only.
// All other actors: denied (they should not be on this page at all).
function can_manage_userlevel( $actor_level, $target_level )
{
  $actor_level  = intval( $actor_level );
  $target_level = intval( $target_level );

  if ( $actor_level == 4 || $actor_level == 5 ) {
    return true;
  }

  if ( $actor_level == 0 ) {
    return $target_level <= 3;
  }

  return false;
}

// Loads userlevel for a given personID from the database.
// Returns the userlevel as an integer, or false if the record does not exist.
// Fails closed: returns false on any error or missing record.
function get_target_userlevel( $link, $personID )
{
  $personID = intval( $personID );
  $query    = "SELECT userlevel FROM people WHERE personID = ? LIMIT 1";
  $stmt     = $link->prepare( $query );
  if ( !$stmt ) return false;
  $stmt->bind_param( 'i', $personID );
  $stmt->execute();
  $result = $stmt->get_result();
  if ( !$result || $result->num_rows < 1 ) {
    $stmt->close();
    return false;
  }
  $row = $result->fetch_assoc();
  $stmt->close();
  return intval( $row['userlevel'] );
}

// ---------------------------------------------------------------------------
// Navigation
// ---------------------------------------------------------------------------

// Function to redirect to prior record
function do_prior($link)
{
  $personID = $_POST['personID'];

  $querywhere = "";
  if ( $_SESSION['userlevel'] == 0 ) {
      $querywhere = "WHERE userlevel <= 3 ";
  }

  $query  = "SELECT personID FROM people $querywhere" .
            "ORDER BY lname, fname ";
  $result = mysqli_query($link, $query)
      or die("Query failed : $query<br />\n" . mysqli_error($link));

  // Find prior record
  list($current) = mysqli_fetch_array($result);
  $prior = null;
  while ($current != NULL && $personID != $current)
  {
    $prior = $current;
    list($current) = mysqli_fetch_array($result);
  }

  $redirect = ($prior == null) ? "" : "?personID=$prior";
  header("Location: {$_SERVER['PHP_SELF']}$redirect");
}

// Function to redirect to next record
function do_next($link)
{
  $personID = $_POST['personID'];

  $querywhere = "";
  if ( $_SESSION['userlevel'] == 0 ) {
      $querywhere = "WHERE userlevel <= 3 ";
  }

  $query  = "SELECT personID FROM people $querywhere" .
            "ORDER BY lname, fname ";
  $result = mysqli_query($link, $query)
      or die("Query failed : $query<br />\n" . mysqli_error($link));

  // Find next record
  $current = null;
  while ($personID != $current)
    list($current) = mysqli_fetch_array($result);
  list($next) = mysqli_fetch_array($result);

  $redirect = ($next == null) ? "?personID=$personID" : "?personID=$next";
  header("Location: {$_SERVER['PHP_SELF']}$redirect");
}

// ---------------------------------------------------------------------------
// Delete
// ---------------------------------------------------------------------------

// Function to delete the current record
function do_delete($link)
{
  global $admin_list;      // To protect our admin entries
  global $enable_PAM;

  $personID    = intval( $_POST['personID'] );
  $actor_level = intval( $_SESSION['userlevel'] );

  // Server-side authorization: verify actor may manage this target
  $target_level = get_target_userlevel( $link, $personID );
  if ( $target_level === false || !can_manage_userlevel( $actor_level, $target_level ) ) {
    $_SESSION['message'] = "Not authorized to delete this account.";
    _eu_redirect_and_exit( $_SERVER['PHP_SELF'] );
    return;
  }

  $admins = implode( "','", $admin_list );

  $link->begin_transaction();
  try
  {
    // Fetch full row snapshot for audit inside the transaction so the
    // read and the delete are atomic — no window for another process to
    // change the row between the snapshot and the delete.
    $snap_query = "SELECT lname, fname, organization, address, city, state, zip, country,
                          phone, email, activated, account_enabled, userlevel, advancelevel,
                          clusterAuthorizations, gmpReviewerRole, authenticatePAM, userNamePAM
                   FROM people
                   WHERE personID = ?
                     AND email NOT IN ( '$admins' )";
    $snap_stmt  = $link->prepare( $snap_query );
    $snap_stmt->bind_param( 'i', $personID );
    if ( !$snap_stmt->execute() )
      throw new RuntimeException( 'Pre-delete snapshot query failed: ' . $snap_stmt->error );
    $snap_result     = $snap_stmt->get_result();
    $delete_snapshot = $snap_result->fetch_assoc();   // null if admin-protected
    $snap_result->close();
    $snap_stmt->close();

    $userNamePAM = $delete_snapshot['userNamePAM'] ?? null;

    $query = "DELETE FROM people " .
             "WHERE personID = ? " .
             "AND email NOT IN ( '$admins' ) ";
    $stmt = $link->prepare( $query );
    $stmt->bind_param( 'i', $personID );
    if ( !$stmt->execute() )
      throw new RuntimeException( 'Delete failed: ' . $stmt->error );
    $affected = $stmt->affected_rows;
    $stmt->close();

    // Only write audit row if the DELETE actually removed a row.
    // affected_rows == 0 means the admin-guard IN clause blocked it.
    if ( $affected > 0 && $delete_snapshot !== null )
    {
      $ok = write_audit_row( $link, $personID,
                              $delete_snapshot['email'] ?? null,
                              trim( ( $delete_snapshot['fname'] ?? '' ) . ' ' . ( $delete_snapshot['lname'] ?? '' ) ) ?: null,
                              'DELETE',
                              $delete_snapshot, null );
      if ( !$ok )
        throw new RuntimeException( 'Audit insert failed for DELETE of personID ' . $personID );
    }

    $link->commit();
  }
  catch ( RuntimeException $e )
  {
    $link->rollback();
    error_log( $e->getMessage() );
    $_SESSION['message'] = 'An error occurred and the account was not deleted. Please try again.';
    _eu_redirect_and_exit( $_SERVER['PHP_SELF'] );
    return;
  }

  $pam_message = '';
  if ( $enable_PAM && !empty( $userNamePAM ) ) {
    $pam_message = grant_integrity( $userNamePAM, false, 0 );
  }

  $_SESSION['message'] = implode( '<br>', array_filter( [ 'User account was deleted.', $pam_message ] ) );

  _eu_redirect_and_exit( $_SERVER['PHP_SELF'] );
}

// ---------------------------------------------------------------------------
// Update
// ---------------------------------------------------------------------------

// Function to update the current record
function do_update($link)
{
  global $enable_PAM;
  global $enable_GMP;
  include __DIR__ . '/get_user_info.php';

  $personID    = intval( $_POST['personID'] );
  $actor_level = intval( $_SESSION['userlevel'] );

  // Server-side authorization: verify actor may manage this target
  $target_level = get_target_userlevel( $link, $personID );
  if ( $target_level === false || !can_manage_userlevel( $actor_level, $target_level ) ) {
    $_SESSION['message'] = "Not authorized to update this account.";
    _eu_redirect_and_exit( $_SERVER['PHP_SELF'] );
    return;
  }

  $activated       = ( $_POST['activated'] == 'on' ) ? 1 : 0;
  $account_enabled = ( isset( $_POST['account_enabled'] ) && $_POST['account_enabled'] == 'on' ) ? 1 : 0;
  $userlevel       = intval( $_POST['userlevel'] );
  $advancelevel    = $_POST['advancelevel'];

  // Prevent userlevel escalation beyond what the actor is authorized to assign.
  // userlevel 0 may only assign userlevel 0-3; userlevel 4/5 may assign 0-4.
  $escalation_notes = null;
  if ( $actor_level == 0 && $userlevel > 3 ) {
    $escalation_notes = 'Submitted userlevel ' . intval( $_POST['userlevel'] ) . ' rejected; actor (level 0) may assign 0-3 only.';
    $userlevel = $target_level;   // silently preserve existing value
  }
  if ( ( $actor_level == 4 || $actor_level == 5 ) && $userlevel > 4 ) {
    $escalation_notes = 'Submitted userlevel ' . intval( $_POST['userlevel'] ) . ' rejected; userlevel 5 cannot be assigned via UI.';
    $userlevel = $target_level;   // userlevel 5 cannot be assigned via UI
  }

  if ( isset( $enable_GMP ) && $enable_GMP ) {
    $gmpReviewerRole = $_POST['gmpReviewerRole'];
  }
  else {
    $gmpReviewerRole = 'NONE';
  }
  if ( $gmpReviewerRole == '' ) {
    $gmpReviewerRole = 'NONE';
  }
  if ( $enable_PAM ) {
      $authenticatePAM = ( isset( $_POST['authenticatePAM'] ) && $_POST['authenticatePAM'] == 'on' ) ? 1 : 0;
      $userNamePAM     = $_POST['userNamePAM'] ?? $_POST['email'];
  }

  // Get cluster information
  global $clusters;
  $userClusterAuth = array();
  foreach ( $clusters as $cluster )
  {
    if ( isset($_POST[$cluster->short_name]) == 'on' )
      $userClusterAuth[] = $cluster->short_name;
  }

  $clusterAuth = implode( ":", $userClusterAuth );

  // Get operator permissions
  $instrumentIDs = array();
  foreach( $_POST as $ndx => $value )
  {
    $exploded = explode( "_", $ndx );
    if ( count( $exploded ) > 1 ) {
      $prefix       = $exploded[ 0 ];
      $instrumentID = $exploded[ 1 ];
      if ( $prefix == 'inst' && $value == 'on' ) {
        $instrumentIDs[] = $instrumentID;
      }
    }
  }

  if ( empty($message) )
  {
    $link->begin_transaction();
    try
    {
      // Fetch pre-update snapshot for audit diff inside the transaction so the
      // read and the update are atomic — no window for another process to
      // change the row between the snapshot and the update.
      $pre_stmt = $link->prepare(
        "SELECT lname, fname, organization, address, city, state, zip, country,
                phone, email, activated, account_enabled, userlevel, advancelevel,
                clusterAuthorizations, gmpReviewerRole, authenticatePAM, userNamePAM
         FROM people WHERE personID = ?"
      );
      $pre_stmt->bind_param( 'i', $personID );
      if ( !$pre_stmt->execute() )
        throw new RuntimeException( 'Pre-update snapshot query failed: ' . $pre_stmt->error );
      $pre_result   = $pre_stmt->get_result();
      $pre_snapshot = $pre_result->fetch_assoc();
      $pre_result->close();
      $pre_stmt->close();

      // Augment people snapshot with instrument permissions so permit changes
      // are included in the audit diff alongside people field changes.
      $pre_snapshot['instrumentPermissions'] = audit_load_instrument_permissions( $link, $personID );

      // Also capture last_userNamePAM from the snapshot for the PAM grant_integrity call.
      $last_userNamePAM = $pre_snapshot['userNamePAM'] ?? null;
      // language=MariaDB
      $query = "UPDATE people " .
               "SET lname             = ?,   " .
               "fname                 = ?,   " .
               "organization          = ?,   " .
               "address               = ?,   " .
               "city                  = ?,   " .
               "state                 = ?,   " .
               "zip                   = ?,   " .
               "country               = ?,   " .
               "phone                 = ?,   " .
               "email                 = ?,   " .
               "activated             = ?,   " .
               "account_enabled       = ?,   " .
               "userlevel             = ?,   " .
               "advancelevel          = ?,   " .
               "clusterAuthorizations = ?    ";
      $args = [ $lname, $fname, $organization, $address, $city, $state, $zip, $country, $phone, $email,
                $activated, $account_enabled, $userlevel, $advancelevel, $clusterAuth ];
      $arg_types = 'ssssssssssiiiis';
      if ( isset( $enable_GMP ) && $enable_GMP ) {
        $query .= ", gmpReviewerRole = ? ";
        $args[] = $gmpReviewerRole;
        $arg_types .= 's';
      }
      if ( isset( $enable_PAM ) && $enable_PAM ) {
          $query .= ", authenticatePAM = ?, userNamePAM = ? ";
          $args[] = $authenticatePAM;
          $args[] = $userNamePAM;
          $arg_types .= 'is';
      }
      $query .= "WHERE personID = ? ";
      $arg_types .= 'i';
      $args[] = $personID;
      $stmt = $link->prepare( $query );
      $stmt->bind_param( $arg_types, ...$args );
      if ( !$stmt->execute() )
        throw new RuntimeException( 'Update failed: ' . $stmt->error );
      $stmt->close();

      // Now delete operator permissions, because we're going to redo it
      $query  = "DELETE FROM permits " .
                "WHERE personID = ? ";
      $stmt = $link->prepare( $query );
      $stmt->bind_param( 'i', $personID );
      if ( !$stmt->execute() )
        throw new RuntimeException( 'Permits delete failed: ' . $stmt->error );
      $stmt->close();

      $query  = "INSERT INTO permits " .
          "SET instrumentID = ?, " .
          "personID         = ? ";
      $stmt = $link->prepare( $query );

      // Now add the new ones
      foreach ( $instrumentIDs as $instrumentID )
      {
        $stmt->bind_param( 'ii', $instrumentID, $personID );
        if ( !$stmt->execute() )
          throw new RuntimeException( 'Permits insert failed: ' . $stmt->error );
      }
      $stmt->close();

      // Fetch post-update snapshot and compute diff for audit.
      $post_stmt = $link->prepare(
        "SELECT lname, fname, organization, address, city, state, zip, country,
                phone, email, activated, account_enabled, userlevel, advancelevel,
                clusterAuthorizations, gmpReviewerRole, authenticatePAM, userNamePAM
         FROM people WHERE personID = ?"
      );
      $post_stmt->bind_param( 'i', $personID );
      if ( !$post_stmt->execute() )
        throw new RuntimeException( 'Post-update snapshot query failed: ' . $post_stmt->error );
      $post_result   = $post_stmt->get_result();
      $post_snapshot = $post_result->fetch_assoc();
      $post_result->close();
      $post_stmt->close();

      // Augment post-update snapshot with the now-committed permit state.
      $post_snapshot['instrumentPermissions'] = audit_load_instrument_permissions( $link, $personID );

      // User identity sourced from pre-update snapshot for both audit rows.
      $user_email = $pre_snapshot['email'] ?? null;
      $user_name  = trim( ( $pre_snapshot['fname'] ?? '' ) . ' ' . ( $pre_snapshot['lname'] ?? '' ) ) ?: null;

      // Write escalation audit row first if a clamp occurred.
      if ( $escalation_notes !== null )
      {
        $ok = write_audit_row( $link, $personID, $user_email, $user_name, 'ESCALATION_REJECTED', null, null, $escalation_notes );
        if ( !$ok )
          throw new RuntimeException( 'Audit insert failed for ESCALATION_REJECTED, personID ' . $personID );
      }

      // Write the field-change audit row if anything auditable changed.
      $diff = audit_diff( $pre_snapshot, $post_snapshot );
      if ( $diff !== null )
      {
        // Select action label by priority: account gate > userlevel > generic update.
        if ( array_key_exists( 'account_enabled', $diff['new'] ) )
          $audit_action = $diff['new']['account_enabled'] ? 'ACCOUNT_ENABLE' : 'ACCOUNT_DISABLE';
        elseif ( array_key_exists( 'userlevel', $diff['new'] ) )
          $audit_action = 'USERLEVEL_CHANGE';
        else
          $audit_action = 'UPDATE';

        $ok = write_audit_row( $link, $personID, $user_email, $user_name, $audit_action, $diff['old'], $diff['new'] );
        if ( !$ok )
          throw new RuntimeException( 'Audit insert failed for ' . $audit_action . ', personID ' . $personID );
      }

      $link->commit();
    }
    catch ( RuntimeException $e )
    {
      $link->rollback();
      error_log( $e->getMessage() );
      $_SESSION['message'] = 'An error occurred and the account was not updated. Please try again.';
      _eu_redirect_and_exit( $_SERVER['PHP_SELF'] . "?personID=$personID" );
      return;
    }

    if ( $enable_PAM ) {
      if ( $last_userNamePAM != $userNamePAM ) {
        $msg_old = !empty( $last_userNamePAM ) ? grant_integrity( $last_userNamePAM, false, 0 ) : '';
        $msg_new = grant_integrity( $userNamePAM, $authenticatePAM, $userlevel );
        $_SESSION['message'] = implode( '<br>', array_filter( [ $msg_old, $msg_new ] ) );
      } else {
        $_SESSION['message'] =
            grant_integrity( $userNamePAM, $authenticatePAM, $userlevel )
            ;
      }
    }

    if ( empty( $_SESSION['message'] ) ) {
      $_SESSION['message'] = 'User account settings were updated successfully.';
    }
  }

  else
    $_SESSION['message'] = "The following errors were noted:<br />" .
                           $message .
                           "Changes were not recorded.";

  _eu_redirect_and_exit( $_SERVER['PHP_SELF'] . "?personID=$personID" );
}

// ---------------------------------------------------------------------------
// Create
// ---------------------------------------------------------------------------

// Function to create a new record
function do_create($link)
{
  global $enable_PAM;
  global $enable_GMP;

  include __DIR__ . '/get_user_info.php';

  $guid = uuid();

  if ( empty($message) )
  {
    $userlevel = 1; // default for new users

    $query = "INSERT INTO people " .
             "SET lname        = ?, " .
             "fname            = ?, " .
             "personGUID       = ?, " .
             "organization     = ?, " .
             "address          = ?, " .
             "city             = ?, " .
             "state            = ?, " .
             "zip              = ?, " .
             "country          = ?, " .
             "phone            = ?, " .
             "email            = ?, " .
             "userlevel        = ?, " .
             "advancelevel     = 0, " .
             "activated        = 1, " .
             "userNamePAM      = ?, " .
             "password         = '__invalid__', " .
             "signup           = NOW()  ";    // account_enabled defaults to 1
    $args = [ $lname, $fname, $guid, $organization, $address, $city, $state, $zip, $country, $phone, $email,
        $userlevel, $userNamePAM ];
    $args_type = 'sssssssssssis';
    if ( isset( $enable_GMP ) && $enable_GMP ) {
      $query .= ", gmpReviewerRole = ? ";
      $args[] = $gmpReviewerRole;
      $args_type .= 's';
    }
    if ( isset( $enable_PAM ) && $enable_PAM ) {
        $query .= ", authenticatePAM = ? ";
        $args[] = $authenticatePAM;
        $args_type .= 'i';
    }
    $stmt = $link->prepare( $query );
    $stmt->bind_param( $args_type, ...$args );

    $link->begin_transaction();
    try
    {
      if ( !$stmt->execute() )
        throw new RuntimeException( 'Create failed: ' . $stmt->error );
      $new = $stmt->insert_id;
      $stmt->close();

      // Fetch the newly created row to capture DB-applied defaults in the audit record.
      $snap_stmt = $link->prepare(
        "SELECT lname, fname, organization, address, city, state, zip, country,
                phone, email, activated, account_enabled, userlevel, advancelevel,
                clusterAuthorizations, gmpReviewerRole, authenticatePAM, userNamePAM
         FROM people WHERE personID = ?"
      );
      $snap_stmt->bind_param( 'i', $new );
      if ( !$snap_stmt->execute() )
        throw new RuntimeException( 'Post-create snapshot query failed: ' . $snap_stmt->error );
      $snap_result     = $snap_stmt->get_result();
      $create_snapshot = $snap_result->fetch_assoc();
      $snap_result->close();
      $snap_stmt->close();

      $ok = write_audit_row( $link, $new,
                              $create_snapshot['email'] ?? null,
                              trim( ( $create_snapshot['fname'] ?? '' ) . ' ' . ( $create_snapshot['lname'] ?? '' ) ) ?: null,
                              'CREATE', null, $create_snapshot );
      if ( !$ok )
        throw new RuntimeException( 'Audit insert failed for CREATE, personID ' . $new );

      $link->commit();
    }
    catch ( RuntimeException $e )
    {
      $link->rollback();
      error_log( $e->getMessage() );
      $_SESSION['message'] = 'An error occurred and the account was not created. Please try again.';
      _eu_redirect_and_exit( $_SERVER['PHP_SELF'] );
      return;
    }

    if ( $enable_PAM ) {
      $_SESSION['message'] = grant_integrity( $userNamePAM, $authenticatePAM, $userlevel );
    }

    if ( empty( $_SESSION['message'] ) ) {
      $_SESSION['message'] = 'User account was created successfully.';
    }

    _eu_redirect_and_exit( $_SERVER['PHP_SELF'] . "?personID=$new" );
    return;
  }

  else
    $_SESSION['message'] = "The following errors were noted:<br />" .
                           $message .
                           "New user was not created!";

  _eu_redirect_and_exit( $_SERVER['PHP_SELF'] );
}

// ---------------------------------------------------------------------------
// Display / navigation
// ---------------------------------------------------------------------------

// Function to display and navigate records
function display_record($link)
{
  global $enable_PAM;
  global $enable_GMP;

  // Find a record to display
  $personID = get_id($link);
  if ($personID === false)
    return;

  $query  = "SELECT lname, fname, organization, " .
            "address, city, state, zip, country, phone, email, " .
            "activated, account_enabled, userlevel, advancelevel, clusterAuthorizations ";
  if ( $enable_GMP ) {
      $query .= ", gmpReviewerRole ";
  }
  if ( $enable_PAM ) {
      $query .= ", authenticatePAM, userNamePAM ";
  }
  $query .= "FROM people WHERE personID = ? ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $personID );
  $stmt->execute()
  or die("Query failed : $query<br />\n" . $stmt->error);
  $result = $stmt->get_result()
  or die("Query failed : $query<br />\n" . $stmt->error);

  $row    = mysqli_fetch_array($result, MYSQLI_ASSOC);

  foreach ($row as $key => $value)
  {
    $$key = (empty($value)) ? "" : html_entity_decode(stripslashes( $value ));
  }
  $result->close();
  $stmt->close();

  $userlevel             = $row['userlevel'];    // 0 translates to null
  $advancelevel          = $row['advancelevel']; // 0 translates to null
  $gmpReviewerRole       = $row['gmpReviewerRole'] ?? 'NONE';
  $authenticatePAM       = $row['authenticatePAM'] ?? 0;
  $userNamePAM           = $row['userNamePAM'] ?? $row['email'];
  $activated             = ( $row['activated'] == 1 ) ? "Activated" : "Not Activated";
  $account_enabled_disp  = ( $row['account_enabled'] == 1 ) ? "Enabled" : "Disabled";
  $clusterAuth           = explode( ":", $row['clusterAuthorizations'] );
  $clusterAuthorizations = implode( ", ", $clusterAuth );

  // Operator permissions
  $query  = "SELECT name " .
            "FROM permits, instrument " .
            "WHERE permits.personID = ? " .
            "AND permits.instrumentID = instrument.instrumentID ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $personID );
  $stmt->execute()
        or die("Query failed : $query<br />\n" . $stmt->error);
  $result = $stmt->get_result()
          or die("Query failed : $query<br />\n" . $stmt->error);

  $instruments = array();
  while ( list( $instName ) = mysqli_fetch_array( $result ) )
    $instruments[] = $instName;
  $instruments_text = implode( ", ", $instruments );
  $result->close();
  $stmt->close();

  // Populate a list box to allow user to jump to another record
  $nav_listbox =  "<select name='nav_box' id='nav_box' " .
                  "        onchange='get_person(this);' >" .
                  "  <option value='null'>None selected...</option>\n";
  $querywhere = "";
  if ( $_SESSION['userlevel'] == 0 ) {
      $querywhere = "WHERE userlevel <= 3 ";
  }
  $query  = "SELECT personID, lname, fname FROM people $querywhere" .
            "ORDER BY lname, fname ";
  $result = mysqli_query($link, $query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));
  while (list($t_id, $t_last, $t_first) = mysqli_fetch_array($result))
  {
    $t_last   = html_entity_decode( stripslashes($t_last)  );
    $t_first  = html_entity_decode( stripslashes($t_first) );
    $selected = ($personID == $t_id) ? " selected='selected'" : "";
    $nav_listbox .= "  <option$selected value='$t_id'>$t_last, $t_first</option>\n";
  }
  $nav_listbox .= "</select>\n";

  $extrasGMP =
    $enable_GMP
    ? "<tr><th>GMP Reviewer Role:</th>"
      . "<td>$gmpReviewerRole</td></tr>"
    : ""
    ;

  $extrasPAM =
    $enable_PAM
    ? "<tr><th colspan='2' style='background:#3a3a3a;color:#fff;font-weight:bold;font-size:0.82em;letter-spacing:0.06em;text-transform:uppercase;padding:6px 10px;'>Authentication</th></tr>"
      . "<tr><th>Authenticate via PAM:</th>"
      . "<td>" . ( $authenticatePAM ? "yes" : "no" ) . "</td></tr>"
      . "<tr><th>User name (PAM):</th>"
      . " <td>$userNamePAM</td></tr>"
    : ""
    ;

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='2'>Edit Profile</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'>Jump to: $nav_listbox
                          <input type='submit' name='prior' value='&lt;' />
                          <input type='submit' name='next' value='&gt;' />
                          <input type='submit' name='new' value='New' />
                          <input type='submit' name='edit' value='Edit' />
                          <input type='submit' name='delete' value='Delete' />
                          <input type='hidden' name='personID' value='$personID' />
          </td></tr>
    </tfoot>
    <tbody>
      <tr><th colspan='2' style='background:#3a3a3a;color:#fff;font-weight:bold;font-size:0.82em;letter-spacing:0.06em;text-transform:uppercase;padding:6px 10px;'>Profile Information</th></tr>
      <tr><th>First Name:</th>
          <td>$fname</td></tr>
      <tr><th>Last Name:</th>
          <td>$lname</td></tr>
      <tr><th>Organization:</th>
          <td>$organization</td></tr>
      <tr><th>Address:</th>
          <td>$address</td></tr>
      <tr><th>City:</th>
          <td>$city</td></tr>
      <tr><th>State (Province):</th>
          <td>$state</td></tr>
      <tr><th>Postal Code:</th>
          <td>$zip</td></tr>
      <tr><th>Country:</th>
          <td>$country</td></tr>
      <tr><th>Phone:</th>
          <td>$phone</td></tr>
      <tr><th>Email:</th>
          <td>$email</td></tr>
      <tr><th colspan='2' style='background:#3a3a3a;color:#fff;font-weight:bold;font-size:0.82em;letter-spacing:0.06em;text-transform:uppercase;padding:6px 10px;'>Account Access</th></tr>
      <tr><th>Registration:</th>
          <td>$activated</td></tr>
      <tr><th>Account status:</th>
          <td>$account_enabled_disp</td></tr>
      <tr><th colspan='2' style='background:#3a3a3a;color:#fff;font-weight:bold;font-size:0.82em;letter-spacing:0.06em;text-transform:uppercase;padding:6px 10px;'>Authorization</th></tr>
      <tr><th>User Level:</th>
          <td>$userlevel</td></tr>
      <tr><th>Advance Level:</th>
          <td>$advancelevel</td></tr>
      $extrasGMP
      $extrasPAM
      <tr><th colspan='2' style='background:#3a3a3a;color:#fff;font-weight:bold;font-size:0.82em;letter-spacing:0.06em;text-transform:uppercase;padding:6px 10px;'>System Access</th></tr>
      <tr><th>Cluster Authorizations:</th>
          <td>$clusterAuthorizations</td></tr>
      <tr><th>Instrument Permissions:</th>
          <td>$instruments_text</td></tr>
    </tbody>
  </table>
  </form>

HTML;
}

// Function to figure out which record to display
function get_id($link)
{
  // See if we are being directed to a particular record
  if (isset($_GET['personID']))
  {
    $personID = $_GET['personID'];
    settype( $personID, 'int' );       // Removes any remaining characters in URL
    return( $personID );
  }

  // We don't know which record, so just find the first one
  $query  = "SELECT personID FROM people " .
            "ORDER BY lname, fname " .
            "LIMIT 1 ";
  $result = mysqli_query($link, $query)
      or die("Query failed : $query<br />\n" . mysqli_error($link));

  if (mysqli_num_rows($result) == 1)
  {
    list($personID) = mysqli_fetch_array($result);
    return( $personID );
  }

  // If we're here, there aren't any records
echo<<<HTML
  <form action='{$_SERVER['PHP_SELF']}' method='post'>
  <table cellspacing='0' cellpadding='0' class='style1'>
    <thead>
      <tr><th colspan='2'>Edit Profile</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='new' value='New' />
          </td></tr>
    </tfoot>
    <tbody>
      <tr><th>Status:</th>
          <td>There are no records to display</td></tr>
    </tbody>
  </table>
  </form>

HTML;

  return( false );
}

// ---------------------------------------------------------------------------
// Edit form
// ---------------------------------------------------------------------------

// Function to edit a record
function edit_record($link)
{
  global $enable_GMP;
  global $enable_PAM;

  // Get the record we need to edit
  $personID = $_POST['personID'];

  $query  = "SELECT lname, fname, organization, " .
      "address, city, state, zip, country, phone, email, " .
      "activated, account_enabled, userlevel, advancelevel, clusterAuthorizations ";
  if ( $enable_GMP ) {
      $query .= ", gmpReviewerRole ";
  }
  if ( $enable_PAM ) {
      $query .= ", authenticatePAM, userNamePAM ";
  }
  $query .= "FROM people WHERE personID = ? ";
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $personID );

  $stmt->execute()
          or die("Query failed : $query<br />\n" . $stmt->error);
  $result = $stmt->get_result()
          or die("Query failed : $query<br />\n" . $stmt->error);

  $row = mysqli_fetch_array($result);

  $lname           = html_entity_decode(stripslashes($row['lname']));
  $fname           = html_entity_decode(stripslashes($row['fname']));
  $organization    = html_entity_decode(stripslashes($row['organization']));
  $address         = html_entity_decode(stripslashes($row['address']));
  $city            = html_entity_decode(stripslashes($row['city']));
  $state           = html_entity_decode(stripslashes($row['state']));
  $zip             = html_entity_decode(stripslashes($row['zip']));
  $country         = html_entity_decode(stripslashes($row['country']));
  $phone           =                                 $row['phone'];
  $email           =                    stripslashes($row['email']);
  $userlevel       =                                 $row['userlevel'];
  $advancelevel    =                                 $row['advancelevel'];
  $clusterAuth     =                                 $row['clusterAuthorizations'];

  $gmpReviewerRole =                                 $row['gmpReviewerRole'] ?? 'NONE';
  $authenticatePAM =                                 $row['authenticatePAM'] ?? 0;
  $userNamePAM     =                                 $row['userNamePAM'] ?? $email;
  $result->close();
  $stmt->close();

  // Create dropdowns
  $userlevel_text    = userlevel_select( $userlevel );
  $advancelevel_text = advancelevel_select( $advancelevel );
  $activated_chk     = ( $row['activated'] == 1 )        ? " checked='checked'" : "";
  $acct_enabled_chk  = ( $row['account_enabled'] == 1 )  ? " checked='checked'" : "";
  $activated_text    = "<label><input type='checkbox' name='activated'$activated_chk /> Activated</label>";
  $acct_enabled_text = "<label><input type='checkbox' name='account_enabled'$acct_enabled_chk /> Enabled</label>";

  // Figure out checks for cluster authorizations
  global $clusters;
  foreach ( $clusters as $cluster )
  {
    $checked_cluster  = "checked_$cluster->short_name";
    $$checked_cluster = ( strpos($clusterAuth, $cluster->short_name) === false ) ? "" : "checked='checked'";
  }

  $cluster_table = "<table cellspacing='0' cellpadding='5' class='noborder'>\n";
  foreach ( $clusters as $cluster )
  {
    $checked_cluster  = "checked_$cluster->short_name";
    $cluster_table   .= "  <tr><td>$cluster->short_name:</td>\n" .
                        "      <td><input type='checkbox' " .
                        "name='$cluster->short_name' {$$checked_cluster} /></td>\n" .
                        "  </tr>\n";
  }
  $cluster_table .= "</table>\n";

  // A list of all the instruments
  $query  = "SELECT instrumentID, name " .
            "FROM instrument ";
  $result = mysqli_query($link, $query)
            or die("Query failed : $query<br />\n" . mysqli_error($link));
  $instruments = array();
  while ( list( $instrumentID, $instName ) = mysqli_fetch_array( $result ) )
    $instruments[ $instrumentID ] = $instName;

  // A list of current user operator permissions
  $query  = "SELECT instrumentID " .
            "FROM permits " .
            "WHERE personID = ? " ;
  $stmt = $link->prepare( $query );
  $stmt->bind_param( 'i', $personID );
  $stmt->execute()
        or die( "Query failed : $query<br />\n" . $stmt->error );
  $result = $stmt->get_result()
          or die( "Query failed : $query<br />\n" . $stmt->error );

  $instrAuth = array();
  while ( list( $instrumentID ) = mysqli_fetch_array( $result ) )
    $instrAuth[] = $instrumentID;
  $stmt->close();
  $result->close();
  $instrAuth_text = implode( ":", $instrAuth );

  foreach ( $instruments as $instrumentID => $instName )
  {
    $checked_instr  = "checked_$instrumentID";
    $instrID        = "$instrumentID";   // as a string
    $$checked_instr = ( strpos( $instrAuth_text, $instrID ) === false ) ? "" : "checked='checked'";
  }

  $instrument_table = "<table cellspacing='0' cellpadding='5' class='noborder'>\n";
  foreach ( $instruments as $instrumentID => $instName )
  {
    $checked_instrument  = "checked_$instrumentID";
    $instrument_table   .= "  <tr><td>$instName:</td>\n" .
                           "      <td><input type='checkbox' name='inst_$instrumentID' {$$checked_instrument} /></td>\n" .
                           "  </tr>\n";
  }
  $instrument_table .= "</table>\n";

  $authenticatePAM_text =
     "<input type='checkbox' name='authenticatePAM'"
     . ( $authenticatePAM ? " checked" : "" )
     . ">"
     ;

  $extrasGMP =
    $enable_GMP
    ? "<tr><th>GMP Reviewer Role:</th>"
      . "<td>"
      . "<select name='gmpReviewerRole'>"
      . "<option value='NONE'" . ( $gmpReviewerRole == "NONE" ? " selected" : "" ) . ">None</option>"
      . "<option value='REVIEWER'" . ( $gmpReviewerRole == "REVIEWER" ? " selected" : "" ) . ">Reviewer</option>"
      . "<option value='APPROVER'" . ( $gmpReviewerRole == "APPROVER" ? " selected" : "" ) . ">Approver</option>"
      . "</select>"
      . "</td>"
      . "</tr>"
    : ""
    ;

  $extrasPAM =
    $enable_PAM
    ? "<tr><th colspan='2' style='background:#3a3a3a;color:#fff;font-weight:bold;font-size:0.82em;letter-spacing:0.06em;text-transform:uppercase;padding:6px 10px;'>Authentication</th></tr>"
      . "<tr><th>Authenticate via PAM:</th>"
      .  "<td>$authenticatePAM_text</td></tr>"
      .  "<tr><th>User name (PAM):</th>"
      .  "<td><input type='text' name='userNamePAM' size='40'"
      .  "          maxlength='64' value='$userNamePAM' /></td></tr>"
    : ""
    ;

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post"
        onsubmit="return validate(this);">
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='2'>Edit Profile</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='update' value='Update' />
                          <input type='hidden' name='personID' value='$personID' />
                          <input type='reset' /></td></tr>
    </tfoot>
    <tbody>

    <tr><th colspan='2' style='background:#3a3a3a;color:#fff;font-weight:bold;font-size:0.82em;letter-spacing:0.06em;text-transform:uppercase;padding:6px 10px;'>Profile Information</th></tr>
    <tr><th>First Name:</th>
        <td><input type='text' name='fname' size='40'
                   maxlength='64' value='$fname' /></td></tr>
    <tr><th>Last Name:</th>
        <td><input type='text' name='lname' size='40'
                   maxlength='64' value='$lname' /></td></tr>
    <tr><th>Organization:</th>
        <td><input type='text' name='organization' size='40'
                   maxlength='128' value='$organization' /></td></tr>
    <tr><th>Address:</th>
        <td><input type='text' name='address' size='40'
                   maxlength='128' value='$address' /></td></tr>
    <tr><th>City:</th>
        <td><input type='text' name='city' size='40'
                   maxlength='64' value='$city' /></td></tr>
    <tr><th>State (Province):</th>
        <td><input type='text' name='state' size='40'
                   maxlength='64' value='$state' /></td></tr>
    <tr><th>Postal Code:</th>
        <td><input type='text' name='zip' size='40'
                   maxlength='16' value='$zip' /></td></tr>
    <tr><th>Country:</th>
        <td><input type='text' name='country' size='40'
                   maxlength='64' value='$country' /></td></tr>
    <tr><th>Phone:</th>
        <td><input type='text' name='phone' size='40'
                   maxlength='64' value='$phone' /></td></tr>
    <tr><th>Email:</th>
        <td><input type='text' name='email' size='40'
                   maxlength='64' value='$email' /></td></tr>

    <tr><th colspan='2' style='background:#3a3a3a;color:#fff;font-weight:bold;font-size:0.82em;letter-spacing:0.06em;text-transform:uppercase;padding:6px 10px;'>Account Access</th></tr>
    <tr><th>Registration:</th>
        <td>$activated_text</td></tr>
    <tr><th>Account status:</th>
        <td>$acct_enabled_text</td></tr>

    <tr><th colspan='2' style='background:#3a3a3a;color:#fff;font-weight:bold;font-size:0.82em;letter-spacing:0.06em;text-transform:uppercase;padding:6px 10px;'>Authorization</th></tr>
    <tr><th>User Level:</th>
        <td>$userlevel_text</td></tr>
    <tr><th>Advance Level:</th>
        <td>$advancelevel_text</td></tr>
    $extrasGMP
    $extrasPAM

    <tr><th colspan='2' style='background:#3a3a3a;color:#fff;font-weight:bold;font-size:0.82em;letter-spacing:0.06em;text-transform:uppercase;padding:6px 10px;'>System Access</th></tr>
    <tr><th>Cluster Authorizations:</th>
        <td>$cluster_table</td></tr>
    <tr><th>Instrument Permissions:</th>
        <td>$instrument_table</td></tr>

    </tbody>
  </table>
  </form>

HTML;
}

// Function to create a new record
function do_new($link)
{
   global $enable_GMP;
   global $enable_PAM;

   $extrasGMP =
    $enable_GMP
    ? "<tr><th>GMP Reviewer Role:</th>"
      . "<td>"
      . "<select name='gmpReviewerRole'>"
      . "<option value='NONE'>None</option>"
      . "<option value='REVIEWER'>Reviewer</option>"
      . "<option value='APPROVER'>Approver</option>"
      . "</select>"
      . "</td>"
      . "</tr>"
    : ""
    ;

   $extrasPAM =
    $enable_PAM
    ? "<tr><th>Authenticate via PAM:</th>"
      . "<td><input type='checkbox' name='authenticatePAM' checked>"
      . "</td></tr>"
      . "<tr><th>User name (PAM):</th>"
      . "<td><input type='text' name='userNamePAM' size='40'"
      . "               maxlength='64'></td></tr>"
    : ""
    ;

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post"
        onsubmit="return validate(this);">
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='2'>Create a New Profile</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='create' value='Create' />
                          <input type='reset' /></td></tr>
    </tfoot>
    <tbody>

    <tr><th colspan='2' style='background:#3a3a3a;color:#fff;font-weight:bold;font-size:0.82em;letter-spacing:0.06em;text-transform:uppercase;padding:6px 10px;'>Profile Information</th></tr>
    <tr><th>First Name:</th>
        <td><input type='text' name='fname' size='40'
                   maxlength='64' /></td></tr>
    <tr><th>Last Name:</th>
        <td><input type='text' name='lname' size='40'
                   maxlength='64' /></td></tr>
    <tr><th>Organization:</th>
        <td><input type='text' name='organization' size='40'
                   maxlength='128' /></td></tr>
    <tr><th>Address:</th>
        <td><input type='text' name='address' size='40'
                   maxlength='128' /></td></tr>
    <tr><th>City:</th>
        <td><input type='text' name='city' size='40'
                   maxlength='64' /></td></tr>
    <tr><th>State (Province):</th>
        <td><input type='text' name='state' size='40'
                   maxlength='64' /></td></tr>
    <tr><th>Postal Code:</th>
        <td><input type='text' name='zip' size='40'
                   maxlength='16' /></td></tr>
    <tr><th>Country:</th>
        <td><input type='text' name='country' size='40'
                   maxlength='64' /></td></tr>
    <tr><th>Phone:</th>
        <td><input type='text' name='phone' size='40'
                   maxlength='64' /></td></tr>
    <tr><th>Email:</th>
        <td><input type='text' name='email' size='40'
                   maxlength='64' /></td></tr>

    <tr><th colspan='2' style='background:#3a3a3a;color:#fff;font-weight:bold;font-size:0.82em;letter-spacing:0.06em;text-transform:uppercase;padding:6px 10px;'>Authentication</th></tr>
    $extrasGMP
    $extrasPAM

    </tbody>
  </table>
  </form>

HTML;
}
