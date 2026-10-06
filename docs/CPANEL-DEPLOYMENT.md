# cPanel deployment and recovery

The backlog scheduler at `https://services.cse.ruet.ac.bd` uses this layout:

```text
/home/servicescserueta/
  apps/backlog-scheduler/                 Laravel application and production .env
    public/uploads/notices/              Notice files served by the Laravel controller
    storage/                             Sessions, attachments, logs, framework caches
    vendor/                              Composer dependencies
  public_html/                           Domain document root
    index.php                            Loads apps/backlog-scheduler
    .htaccess                            Laravel routing plus cPanel PHP handler
    images/, js/, css/, build/            Published static assets, when present
    alumni/                              Existing separate website
    remuneration.services.cse.ruet.ac.bd/ Existing separate application
  repositories/backlog-exam-scheduler/    cPanel-managed source checkout; not the running app
  backups/backlog-reorganization-2026-10-06/
    legacy-root/                         Original app and removed development files
    web/                                 Original public_html entry point and .htaccess
    home.htaccess                        Original account-level .htaccess
  backups/backlog-deployments/            Per-release app, asset and database backups
  maintenance/                           Private deployment/reorganization state and logs
  mail/, etc/, ssl/, tmp/, logs/          Hosting-managed directories
```

The `public_html` directory remains the main domain's document root. Only the
front controller's three Laravel paths change. Laravel's own `public` directory
stays inside the private application directory because the current notice
controller writes and reads `public_path('uploads/notices/...')`. Those notices
continue to be delivered by `/notice-file/{noticeid}`. Do not copy this uploads
directory into `public_html` or introduce a symlink that makes it directly public.

## Full deployment through cPanel

1. Commit the release, including migration files and public assets, and push it
   to GitHub. Keep `.env`, uploads, database dumps and runtime storage out of Git.
2. Open **Git Version Control → Manage → Pull or Deploy** for
   `repositories/backlog-exam-scheduler` and click **Update from Remote**.
3. Confirm the intended HEAD commit, then click **Deploy HEAD Commit**. Normal
   releases need no terminal, manual file copying or temporary cron jobs.
4. Wait for completion, then check the homepage, login, an exam page and notice
   attachment. Inspect the private deployment state and log if anything fails;
   cPanel's last-deployed SHA alone does not prove every task succeeded.

The committed `.cpanel.yml` runs one command with the domain's PHP 8.3 CLI:

```sh
/usr/local/bin/ea-php83 /home/servicescserueta/repositories/backlog-exam-scheduler/scripts/cpanel-deploy.php
```

### What the script does

- Locks deployment and checks the clean Git checkout, production prerequisites,
  and customized web entry point. Refuses pre-existing maintenance mode or an
  unresolved interrupted deployment.
- Prepares tracked runtime code and assets in a private staging directory. Keeps
  production `.env`, sessions, storage and notice uploads. Excludes Git metadata,
  tests, development scripts, sample files and local database files.
- Copies existing dependencies into the candidate. When Composer is available,
  runs `install --no-dev --prefer-dist --no-interaction --optimize-autoloader`
  and `check-platform-reqs --no-dev`. Uses `composer.lock`, never `composer update`.
  If Composer is unavailable, reuses dependencies only with an unchanged lockfile;
  otherwise stops before touching production. Supported locations include
  `/opt/cpanel/composer/bin/composer` and `maintenance/composer.phar`.
  This account has the official Composer PHAR installed in the latter location.
- Checks the database connection and counts pending migrations. Creates a private
  MySQL schema/data gzip backup using a consistent transaction, then reads it back
  before proceeding. Exports and restores timestamps in UTC explicitly. The
  built-in backup supports InnoDB tables without views or
  triggers; unsupported schemas stop deployment. SQLite snapshots support tests.
- Backs up managed web assets, enters maintenance mode, refreshes mutable data,
  archives the old application and activates the candidate at the existing path.
- Publishes tracked static assets into `public_html`, retaining their copies in
  the app's `public/` directory for asset versioning. Removes obsolete assets only
  when recorded in the previous deployment manifest. Preserves the customized
  `index.php`, `.htaccess`, other websites and hosting directories. Never publishes
  uploads or storage; rejects executable PHP assets and symlink destinations.
- Runs `migrate --force --no-interaction`, clears configuration/route/view caches,
  and discovers packages. Checks HTTP responses inside Laravel for the homepage,
  login and latest exam's notices using a private maintenance bypass. Records the
  release manifest, leaves maintenance mode and records completion. The browser
  check additionally verifies the actual web server and public asset URLs.

There is no frontend build step today: views load committed `public/css` and
`public/js` files. If Vite output becomes part of the UI, prepare the assets before
deploying; this script publishes tracked `public/build` files but does not run npm.

### Logs, backups and failure recovery

Inspect these private paths using cPanel File Manager:

- `maintenance/deployment.json`: latest status, commit, backup and log paths.
- `maintenance/deployment-<release-id>.log`: command output and diagnostics.
- `apps/backlog-scheduler/.deployment.json`: active release manifest.
- `backups/backlog-deployments/<release-id>/application`: previous application.
- `backups/backlog-deployments/<release-id>/database.sql.gz`: production MySQL backup.
- `backups/backlog-deployments/<release-id>/assets` and `assets.json`: asset backups.

Preparation failures leave the live app untouched. Failures after activation
restore the previous code/assets and reopen the site when no pending migration
could have changed the schema. If pending migrations have started, a failure
leaves maintenance mode active with status `needs_recovery`. MySQL DDL can partially
commit; the script does not blindly reverse database changes. Backups are retained
without automatic deletion.

For interrupted or schema-changing failures, inspect the log first. Recovery can
use File Manager to archive the failed app and restore the previous application
and assets, and cPanel Backup to restore the downloaded SQL gzip if needed.
Preserve newer mutable data before restoring files. Confirm schema compatibility
before removing `storage/framework/down` and marking deployment state `rolled_back`.
New Deploy attempts remain blocked until recovery is acknowledged. Keep failed
files and backups until verification is complete.

Keep the cPanel checkout clean. Make deployment changes in the development
repository and push through GitHub instead of editing `.cpanel.yml` on the server.

### Local verification

Run `python3 scripts/tests/test-cpanel-deploy.py` with local PHP and the existing
`vendor` directory. It creates a disposable SQLite account layout and checks a
full release, repeated deployment, code/asset rollback, migration failure,
backups, runtime-data preservation and unrelated-site preservation. The fixture
uses the script's optional `--reuse-vendor` argument because local PHP can be newer
than the lockfile supports; this still rejects any lockfile change. Normal cPanel
deployment does not pass this argument and uses available Composer on PHP 8.3.

## Deployment verification (2026-10-07)

The full release at `c43ff22` was pushed, pulled and deployed through cPanel.
The first run verified the unchanged-lockfile dependency fallback; the second
used the official Composer 2.10.3 PHAR in `maintenance/composer.phar`, installed
production dependencies, removed 39 development packages and passed PHP 8.3.35
platform checks. Both completed successfully with a 14-table MySQL backup,
cache clearing and HTTP checks. No migrations were pending, since the migration
below had already been applied. The new homepage, login and notice UI is live.

Composer's downloaded SHA-256 matched the official release checksum:
`7a2d379d5b8ffdaa028580ef26494c36d2feef4b178d3dd1473a4dbc5e17c8d6`.
Existing duplicate vendor classes and unused legacy teacher controller files
produce non-fatal autoload warnings; the deployed pages passed their checks.

The downloaded automatic backup contained all 14 schemas and 1,958 rows.
Comparison with the earlier cPanel export identified only a six-hour session
timezone difference in TIMESTAMP values. The deployment backup now explicitly
exports and restores in UTC, and defines SQL mode for quoted values and zero IDs.
The disposable integration fixture also passed full/repeated release, runtime
rollback, pending migration and failed-migration recovery checks.

### Earlier migration-only deployment

Commit `4241ed2` was pushed to GitHub, pulled through **Update from Remote**, and
run through **Deploy HEAD Commit**. A fresh MySQL backup was downloaded and
verified before deployment. The pending
`2026_10_06_100000_create_mail_logs_table.php` migration was copied from the
checkout into the running application's `database/migrations` directory.

cPanel recorded the deployed commit. A second MySQL backup confirmed that
`mail_logs` was created and the migration was recorded; existing application
data was unchanged. The homepage and login form continued to load. This earlier
test published the migration only using the previous configuration. The full
deployment script above replaces that workflow. Assess database schema and
deployed application code separately when verifying a release.

## Reorganization and recovery

`scripts/cpanel-reorganize.php` is a CLI-only, one-time migration with three phases:

- `prepare`: Copies the existing deployed application, checks Laravel and its
  database connection, and records a source fingerprint. Leaves live paths alone.
- `activate`: Checks that source code/configuration have not changed, refreshes
  sessions and uploads during a maintenance interval, validates a homepage
  request, and atomically switches the front controller. Does not migrate the
  production database. Automatically restores the old entry point if activation
  fails before completion.
- `cleanup`: After browser verification, archives the original root files. It
  removes only the old inherited Laravel rewrite block from the account-level
  `.htaccess`, keeping cPanel directives. Dangling storage links to a former Mac
  development path are archived without exposing storage contents on the web.

State lives in `maintenance/reorganization.json`. Successful phases are
idempotent, and a lock prevents overlapping runs. A partial failure should be
inspected before retrying; the script does not recursively erase destinations.

To roll back after cleanup, enter maintenance mode and preserve any new sessions,
attachments and notice uploads from `apps/backlog-scheduler`. Move the app folders
and project files in `backups/backlog-reorganization-2026-10-06/legacy-root` back
to the account home. Restore `public_html/index.php`, `public_html/.htaccess`, and
the account `.htaccess` from their corresponding backups. Merge the latest
mutable data into the restored application, clear its caches, leave maintenance
mode, and verify the site. The original dangling Mac storage symlinks do not need
to be recreated. Leave the new app and rollback archive intact until recovery is
verified. Restore the database only when needed; this reorganization does not
change its schema or application records.

The initial home-directory and MySQL downloads provide an additional offline
recovery copy. Hosting-managed directories and the other websites are outside
the backlog migration's selection.
