<?php
/*
 * plugin_settings.php
 *
 * Instance administrators: the instance ID, the plugin license key and
 * enablement of installed plugins. Package installation is a deployment step
 * (tools/lims_plugins.php), not a web action.
 */
include_once 'checkinstance.php';

if ( ( $_SESSION['userlevel'] ?? 0 ) < 4 || ( $_SESSION['instance'] ?? '' ) !== $dbname )
{
  header( 'Location: index.php' );
  exit();
}

include 'config.php';
include 'lib/utility.php';
require_once __DIR__ . '/lib/plugins/bootstrap.php';

use USLIMS\Plugin\Host\License;
use USLIMS\Plugin\Host\LicenseException;

$h = function ( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); };
$registry = lims_plugin_registry( (string) $dbname );
$state = $registry->state();

if ( $_SERVER['REQUEST_METHOD'] === 'POST' )
{
  $notice = '';
  $ok = false;
  if ( !hash_equals( csrf_token(), (string) ( $_POST['csrf_token'] ?? '' ) ) )
  {
    $notice = 'The form expired. Please try again.';
  }
  else
  {
    try
    {
      if ( isset( $_POST['save_license'] ) )
      {
        $instanceId = $state->ensureInstanceId();
        $license = $registry->verifier()->decode( (string) ( $_POST['license_key'] ?? '' ) );
        $status = $license->status( $instanceId, time() );
        if ( $status === License::WRONG_INSTANCE )
          throw new LicenseException( 'the key was issued for a different instance ID' );
        if ( $status === License::EXPIRED )
          throw new LicenseException( 'the key expired ' . $license->expiresText() );
        $state->replaceLicense( (string) $_POST['license_key'] );
        $ok = true;
        $notice = 'License key saved: ' . $license->scopeText() . ', paid through ' . $license->paidThrough() . ' UTC.';
      }
      else if ( isset( $_POST['remove_license'] ) )
      {
        if ( ( $_POST['confirm_remove'] ?? '' ) !== '1' )
          throw new RuntimeException( 'confirm the removal first' );
        $state->removeLicense();
        $ok = true;
        $notice = 'License key removed.';
      }
      else if ( isset( $_POST['plugin_id'], $_POST['enable'] ) )
      {
        $id = (string) $_POST['plugin_id'];
        if ( !array_key_exists( $id, $state->deployment() ) )
          throw new RuntimeException( 'that plugin is not installed' );
        $state->setEnabled( $id, $_POST['enable'] === '1' );
        $ok = true;
        $notice = ( $_POST['enable'] === '1' ? 'Enabled ' : 'Disabled ' ) . $id . '.';
      }
    }
    catch ( LicenseException $e )
    {
      $notice = 'The license key was not saved; the current key is unchanged. Reason: ' . $e->getMessage() . '.';
    }
    catch ( Throwable $e )
    {
      error_log( 'plugin_settings.php: ' . $e->getMessage() );
      $notice = 'The change was not saved: ' . $e->getMessage() . '.';
    }
  }
  $_SESSION['plugin_settings_notice'] = array( $ok, $notice );
  header( "Location: {$_SERVER['PHP_SELF']}" );
  exit();
}

$notice = $_SESSION['plugin_settings_notice'] ?? null;
unset( $_SESSION['plugin_settings_notice'] );

try
{
  $instanceId = $state->ensureInstanceId();
}
catch ( Throwable $e )
{
  error_log( 'plugin_settings.php: ' . $e->getMessage() );
  $instanceId = null;
}

$page_title = 'Plugins and License';
$css = 'css/plugin_settings.css';
include 'header.php';
$csrf = "<input type='hidden' name='csrf_token' value='" . $h( csrf_token() ) . "' />";
$badge = function ( $kind, $text ) use ( $h ) { return "<span class='plugin-state plugin-state-$kind'>" . $h( $text ) . "</span>"; };

echo "<div id='content' class='plugin-settings'>\n<h1 class='title'>Plugins and License</h1>\n";

if ( $notice )
  echo "<p class='plugin-notice plugin-notice-" . ( $notice[0] ? 'ok' : 'error' ) . "'>" . $h( $notice[1] ) . "</p>\n";

echo "<section>\n<h3>Instance</h3>\n";
if ( $instanceId === null )
  echo "<p class='plugin-notice plugin-notice-error'>The plugin state directory " . $h( $state->dir() ) . " cannot be written, so this instance has no ID yet.</p>\n";
else
  echo "<dl><dt>Instance ID</dt><dd><code>" . $h( $instanceId ) . "</code></dd></dl>\n<p>Send this ID when requesting a license key.</p>\n";
echo "</section>\n";

$ls = $registry->licenseState();
echo "<section>\n<h3>License</h3>\n<dl>\n";
if ( $ls['license'] !== null )
{
  $l = $ls['license'];
  $status = $ls['status'] === License::VALID ? $badge( 'ok', 'Active' ) : $badge( 'bad', $ls['reason'] );
  echo "<dt>Status</dt><dd>$status</dd>\n<dt>Scope</dt><dd>" . $h( $l->scopeText() ) . "</dd>\n"
     . "<dt>Paid through</dt><dd>" . $h( $l->paidThrough() ) . " UTC (expires " . $h( $l->expiresText() ) . ")</dd>\n";
}
else
{
  echo "<dt>Status</dt><dd>" . ( $ls['status'] === 'none' ? $badge( 'off', 'No license key' ) : $badge( 'bad', $ls['reason'] ) ) . "</dd>\n";
}
echo "</dl>\n";

echo <<<HTML
<form method='post'>$csrf
  <p><label for='license_key'>Paste a new or renewed license key.</label> The current key stays in use unless the new key is valid.</p>
  <textarea id='license_key' name='license_key' rows='4' cols='80' required='required'></textarea><br />
  <input type='submit' name='save_license' value='Save license key' />
</form>
</section>

HTML;

echo "<section>\n<h3>Installed plugins</h3>\n";
$plugins = $registry->plugins();
if ( count( $plugins ) === 0 )
{
  echo "<p>No plugin packages are installed for this instance.</p>\n";
}
else
{
  echo "<table class='style1'>\n<thead><tr><th>Plugin</th><th>Version</th><th>State</th><th>Enabled</th></tr></thead>\n<tbody>\n";
  foreach ( $plugins as $p )
  {
    $label = $p->manifest !== null && $p->manifest->menu !== null ? $p->manifest->menu['label'] . " ($p->id)" : $p->id;
    $stateText = $p->available() ? $badge( 'ok', 'Available' )
               : ( $p->reason === 'disabled' ? $badge( 'off', 'Disabled' ) : $badge( 'bad', 'Unavailable: ' . $p->reason ) );
    $toggle = $p->enabled ? '0' : '1';
    $button = $p->enabled ? 'Disable' : 'Enable';
    echo "<tr><td>" . $h( $label ) . "</td><td>" . $h( $p->version ) . "</td><td>$stateText</td><td>"
       . "<form method='post'>$csrf<input type='hidden' name='plugin_id' value='" . $h( $p->id ) . "' />"
       . "<input type='hidden' name='enable' value='$toggle' /><input type='submit' value='$button' /></form></td></tr>\n";
  }
  echo "</tbody>\n</table>\n";
}
echo "</section>\n";

if ( $state->licenseToken() !== null )
{
  echo <<<HTML
<section class='plugin-danger'>
<h3>Remove the license key</h3>
<form method='post'>$csrf
  <label><input type='checkbox' name='confirm_remove' value='1' required='required' />
    I understand that every plugin stops working until a new key is saved.</label>
  <input type='submit' name='remove_license' value='Remove license key' />
</form>
</section>

HTML;
}

echo "</div>\n";
include $common_dir . 'footer.php';
exit();
