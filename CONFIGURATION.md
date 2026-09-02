# Dbinst base and instance overlays

Dbinst supports two configuration forms during migration:

- an existing complete `config.php`; or
- a small `config.php` shim that loads a versioned host base and one validated
  instance overlay.

Existing complete files remain valid. There is no automatic fallback from a
broken overlay to a legacy file because that could select the wrong database.

## Installed layout

```text
/home/us3/lims/etc/config/
|-- dbinst-base.v1.php
`-- instances/
    `-- uslims3_NAME.php
```

Install and customize `config-base.v1.php.template` as the host base. The base
contains shared portal, global-database, host, path, and default feature
values. It must not contain instance database or secure-user credentials.

Each overlay is a PHP file that returns a schema-v1 array. The loader rejects
unknown keys, missing values, invalid types, path traversal, mismatched
instance/database names, and all base overrides except the explicitly allowed
`enable_GMP` and `enable_PAM` feature exceptions.

The loader is `lib/dbinst_config_loader.php` in the dbinst checkout. Keeping it
with application code allows old and new dbinst versions to coexist. A
breaking future schema must add a new versioned base instead of changing v1
under already-deployed instances, and must bump the number returned by
`us3_dbinst_config_contract_version()`.

That function is the only place the contract number is written. The
`schema_version` stamped into generated overlays, the `schema_version` that
base and overlay files are validated against, and the versioned base filename
(`us3_dbinst_config_base_filename()`) all derive from it, so a bump is one
edit. Do not reintroduce a literal in any of them: a loader that stamps v2 and
validates as v1, or that reads the v1 base under v2 rules, is the failure this
arrangement exists to prevent.

The number is deliberately not the component `VERSION` file. `VERSION` is a
release identifier that moves every release whether or not this contract
changed, so keying anything to it would reject two checkouts whose loaders are
identical.

That version is what lets newinst tell whether an already-loaded loader can
stand in for the one shipping with the checkout it is generating for. Each
instance has its own checkout, so a process generating a second instance
cannot load that instance's loader (PHP will not redeclare the functions).
Generation reuses the loaded contract when the versions match and refuses when
they do not, rather than validating an overlay against the wrong version's
rules. The remedy for a refusal is one generation process per instance, which
is what the generated setup script already does.

The shim publishes values into whatever scope included `config.php`, which is
what the legacy file's plain assignments did. Included at global scope it sets
globals; included from inside a function it sets function locals and touches no
globals. `lib/controls.php`'s `check_filesize()` depends on the second case, so
the shim must not export to `$GLOBALS`: that would be invisible at global scope
and would make a routine helper call rewrite every config global, `$configs`
(the parsed `.us3lims.ini`) included.

## Migration

Run the comparison from a trusted dbinst checkout:

```text
php tools/migrate_instance_config.php \
  --instance=uslims3_NAME \
  --legacy=/srv/www/htdocs/uslims3/uslims3_NAME/config.php
```

The default is dry-run. It reports only equal/different/missing status and
never prints credential values. Differences block output.

The comparison runs in both directions. Every contract key is checked against
the legacy file, and every legacy assignment the v1 contract does not account
for is listed as `NEEDS REVIEW` and blocks migration. Deployed `config.php`
files are hand-maintained, so a site-added variable is the expected case, not
an exotic one; without the second direction such a variable would be dropped
while the report still read `EQUIVALENT`. Only names are printed, since a
site-local value may itself be a secret. Each one must be recognized as a base
value, an overlay value, or a deliberate removal before the instance can be
migrated.

`full_path` and `data_dir` are derived from the base's `dbinst_root` and the
instance name, and are not overlay keys. An instance installed somewhere other
than `<dbinst_root>/<instance>/` therefore reports both as `DIFFERENT` and
cannot migrate under v1. That is intended: the alternative is an override key
that would wave through exactly the instances whose paths a person should look
at. Such an instance stays on its complete legacy `config.php` until it is
either relocated under the standard root or the contract gains reviewed
exception keys in a later version.

After an equivalent dry run, `--write-candidate` may create:

- `/home/us3/lims/etc/config/instances/uslims3_NAME.php`; and
- `<dbinst>/config.php.base-overlay-candidate`.

It never replaces the active `config.php`. Activation is a separate deployment
step after web and CLI checks. Preserve the complete legacy file with
restrictive permissions; restoring that file is the rollback.

Use `--config-root` and `--credentials-file` only when validating a deployment
whose installed paths differ from the defaults.

## Credential loading fails loudly

Migrating an instance changes what a missing, unreadable, or incomplete
`~us3/lims/.us3lims.ini` does. The migration report will not show this, since
it compares values on a host where the file is fine.

A legacy `config.php` warned and carried on: `$globaldbpasswd` became `NULL`
and only code that opens the global database failed, later, at
`mysqli_connect`. Login and ordinary pages kept working.

The loader validates the file before publishing any value and fails with
`credential INI file is missing or unreadable` or `credential INI has no gfac
password`. Nothing catches it, so every web request and CLI invocation for
that instance fails at once. A deployment that cannot read its credentials is
broken, and the loader says so rather than presenting a portal whose queue
pages happen to be down.

Check this before activating, because the path is resolved from the `us3`
account's home directory while web requests usually run as a different
account. An instance whose ini is readable by the CLI account but not by the
web account degraded quietly before and will not now. Unmigrated instances are
unaffected, and restoring the legacy `config.php` restores the old behavior.

## Checking an installation after an upgrade

`tools/check_installation.php` verifies the configuration surface of a
deployment. It is read-only: it writes nothing, contacts no cluster, and prints
no credential.

```text
php tools/check_installation.php \
  --dbinst-root=/srv/www/htdocs/uslims3 \
  --global-config=/srv/www/htdocs/common/global_config.php
```

Exit status is 0 when nothing failed, 1 when something did, and 2 when the tool
could not run. Warnings do not fail the run; several of them mark checks the
tool cannot make for you, such as whether the web account can read a file when
the command is running as `us3`.

It covers the host base and its permissions, each overlay and the effective
configuration it produces, the shim and its `$is_cli` marker, contract-version
agreement between the base and each instance's loader, the credential file, the
`$is_cli` marker in unmigrated instances, retired global keys, cluster entries
still carrying keys `remote_exec` now rejects, and the circuit breaker
directory.

Useful options:

- `--config-root=PATH` asserts a base and overlay installation is present. Left
  out, a site with nothing migrated reports a warning rather than a failure.
- `--instance=NAME` limits the run to one instance; repeatable.
- `--deep` additionally opens the instance and global databases with their real
  credentials.
- `--quiet` prints only warnings and failures.

Run it as the `us3` account for the CLI view, and once more as the web account
where possible: the two see different file permissions, which is the difference
that matters most for the credential file.

## Required verification

Before activating an instance:

1. require an equivalent migration report;
2. run `tools/check_installation.php` with no failures, as both the `us3`
   account and the web account;
3. confirm `~us3/lims/.us3lims.ini` is present, readable by both the web
   account and the `us3` CLI account, and has a `gfac` password;
4. verify web login, session database identity, and instance isolation;
5. verify gridctl `submitctl.php` recognizes the literal `$is_cli` marker;
6. exercise submission, status, cancellation, and result handling; and
7. demonstrate restoration of the preserved legacy file.

