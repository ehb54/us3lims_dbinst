# LIMS plugin framework (plugin API v1)

Commercial and third-party features install as plugin packages outside the web
root. Base LIMS runs unchanged with no packages installed.

## Request path

`plugin.php?plugin=<id>&action=<name>` is the only entry point. For every
request core checks, in order:

1. GET only (405 otherwise).
2. A signed-in session for this instance with user level 1 or more (401). The
   sidebar applies the same rule before it lists any plugin.
3. The plugin is recorded for this instance, its manifest is valid, the
   administrator enabled it, and the instance license covers it (404 otherwise,
   with the reason in the server log).
4. The action is declared in the installed manifest (404).

The menu uses the same decision (`Registry::plugins()`), so a plugin that is
not available has no menu entry and no working links, including saved links.
The browser supplies only the plugin ID and the action name; the handler class
always comes from the manifest.

## Manifest

`composer.json` is the manifest. Example (see also `plugins/example-hello/`):

```json
{
  "name": "uslims/gmp-reports",
  "version": "1.0.0",
  "type": "lims-plugin",
  "autoload": { "psr-4": { "USLIMS\\GmpReports\\": "src/" } },
  "extra": {
    "lims": {
      "api": 1,
      "id": "gmp-reports",
      "menu": { "section": "gmp", "sectionLabel": "GMP", "label": "GMP Reports", "action": "list" },
      "stylesheets": ["assets/gmp-reports.css"],
      "actions": { "list": "USLIMS\\GmpReports\\ListReports" }
    }
  }
}
```

Core rejects a package whose ID or version differs from the deployment record,
whose `api` is not `1`, whose menu names an invalid section (a built-in section
or a custom one with a `sectionLabel`, see Sidebar sections) or an undeclared action, a stylesheet path that is not a plain relative
`.css` path or is missing, or a package with no `vendor/autoload.php`.

## Styling

Plugin pages render inside the LIMS layout, so the shared `common.css` classes
apply: use `table.style1` for tables and `.message` for notices. A plugin's own
CSS goes in files listed under `stylesheets`. Core serves only those files, at
`plugin.php?plugin=<id>&asset=<path>`, under the same session and availability
checks as the plugin's actions, and links them on every page the plugin renders.
Browsers revalidate them on every use (`no-cache` with an ETag), so logout,
disablement and expiry apply to stylesheets at once.
Packages stay outside the web root, and the site's `style-src 'self'` policy
allows these same-origin stylesheets; inline styles are blocked.

### Sidebar sections

Declare one landing link per plugin. For an existing heading, set `menu.section` to
`navigation`, `project`, `analysis`, `status-monitor` or `general`. LIMS appends the
link wherever that heading is shown for the signed-in user. Core owns its label.

For a shared custom heading, declare a lowercase section ID using the plugin ID
syntax and include a nonempty `sectionLabel` in each contributing manifest.
LIMS inserts populated custom sections after Project, ordered by section ID,
with one link per available plugin ordered by plugin ID. Contributors should agree
on the heading text. If labels differ, the first available plugin in ID order
supplies the heading. Each instance uses its own enablement and license state.

## Handler contract

```php
interface USLIMS\Plugin\V1\Action {
    public function handle(Context $context, array $query): void;
}
```

Handlers are constructed without arguments. `$query` is the GET parameters
without `plugin` and `action`. `Context` provides:

| Method | Value |
|---|---|
| `instanceId()` | The instance's stable plugin ID |
| `loginId()` | The signed-in person's actual `loginID`, never a delegated investigator |
| `db()` | The instance's existing `mysqli` connection |
| `actionUrl($action, $params)` | Relative URL of another action of the same plugin |
| `renderPage($title, $html)` | Show HTML inside the LIMS layout after the handler returns |

`actionUrl()` and `renderPage()` extend the three methods in the planning
specification so a plugin can link its own actions and use the LIMS layout
without knowing core file paths. A handler that sends a file sets its own
headers and echoes the bytes instead of calling `renderPage()`. A thrown
exception becomes a generic 500 page.

## Locations

| Item | Default | Override in config.php |
|---|---|---|
| Plugin packages | `~us3/lims/plugins/<id>/` or `~us3/lims/plugins/<id>/<version>/` | `$lims_plugin_root` |
| Per-instance state | `~us3/lims/etc/plugins/<instance>/` | `$lims_plugin_state_root` |

The state directory holds `instance-id`, `deployment.json` (written by
deployment), `enabled.json` and `license.key` (written by
`plugin_settings.php`). It is keyed by database name, so instances that share
one code tree keep separate state, and it lives outside the code tree, so
upgrades do not touch it. All writes are rename-into-place. The web server
account needs write access to its instance directory.

## Deployment

Plugins are supported on OL10 with PHP 8.3 and the `sodium` extension. OL10
AppStream has no sodium package; install `php-sodium` from Oracle's EPEL
repository (`oracle-epel-release-el10`, the "Developer EPEL" channel, which is
outside Oracle support). The framework code stays PHP 7.4 compatible because
`links.php` loads it on every page of every host. On an older host base LIMS
runs unchanged and plugins stay unavailable.

The deployment creates the plugin root and the state root, owned by the
deployment account with the web server's group, and labels both for httpd on
SELinux hosts as for `~us3/lims/etc/config` (see `CONFIGURATION.md`). Instance
state directories are created group-writable and setgid, whatever the umask.

`tools/lims_plugins.php` does not read `config.php`. When an instance sets
`$lims_plugin_root` or `$lims_plugin_state_root`, pass the same paths as
`--plugin-root` and `--state-root`; `status` prints the paths in use.

```sh
# VERSION is the manifest's version. Same source and toolchain give the same bytes.
scripts/build-plugin-archive.sh plugins/gmp-reports          # in the stack repo: plugins/gmp-reports/build/
mkdir -p ~us3/lims/plugins/gmp-reports/VERSION
tar -xzf gmp-reports-VERSION.tar.gz -C ~us3/lims/plugins/gmp-reports/VERSION
runuser -u us3 -g apache -- php tools/lims_plugins.php --instance=uslims3_NAME install gmp-reports VERSION
runuser -u us3 -g apache -- php tools/lims_plugins.php --instance=uslims3_NAME status
```

Run the tool as the PHP-FPM pool's identity (us3 with group apache), so it reads the
packages exactly as the web tier does and creates state the pool can write.
`aucsol_playbooks/lims-plugins.yml` performs these steps; it is optional and separate
from the base installation.

An administrator (user level 4 or 5) then opens **Plugins and License**
(`plugin_settings.php`), sends the instance ID to the vendor, pastes the
license key and enables the plugin.

## License keys

Format version 1 is `base64url(payload).base64url(signature)`, both unpadded,
where the signature is a detached Ed25519 signature over the exact UTF-8 JSON
payload:

```json
{"version":1,"instance_id":"0c9bb327-f91a-42ac-9885-485fdf8396ff","plugins":"*","expires_at":"2028-01-01T00:00:00Z"}
```

`plugins` is `"*"` or a list of plugin IDs. Access requires the current UTC
time to be strictly earlier than `expires_at`. A key is saved only after its
signature, fields, instance ID and expiry check out, so a failed renewal keeps
the current key. Verification needs the PHP `sodium` extension (see
Deployment); without it no key verifies and every plugin stays unavailable.

Trusted public keys are in `license_keys.php` (vendor keys only). For
development, the server environment variable `LIMS_PLUGIN_TEST_PUBLIC_KEY`
adds one more key; production hosts must not set it. The issuer and the test
key pair live in the stack repository (`scripts/plugin-license/`,
`tests/fixtures/plugin-license/`), never under a web root.
