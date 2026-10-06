# cPanel layout and manual deployment

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
  repositories/backlog-exam-scheduler/    Optional Git clone; not the running application
  backups/backlog-reorganization-2026-10-06/
    legacy-root/                         Original app and removed development files
    web/                                 Original public_html entry point and .htaccess
    home.htaccess                        Original account-level .htaccess
  maintenance/                           Private migration script, state and logs
  mail/, etc/, ssl/, tmp/, logs/          Hosting-managed directories
```

The `public_html` directory remains the main domain's document root. Only the
front controller's three Laravel paths change. Laravel's own `public` directory
stays inside the private application directory because the current notice
controller writes and reads `public_path('uploads/notices/...')`. Those notices
continue to be delivered by `/notice-file/{noticeid}`. Do not copy this uploads
directory into `public_html` or introduce a symlink that makes it directly public.

## Updating the application manually

1. Back up the home directory and `servicescserueta_backlog` MySQL database using
   cPanel Backup. Keep `.env`, uploads and private attachments out of Git.
2. Clone or pull the selected commit in `repositories/backlog-exam-scheduler`.
   This is optional staging space; the site does not load code from it.
3. During a short maintenance interval, copy the app's code into
   `apps/backlog-scheduler`. Preserve the production `.env`, `storage`,
   `public/uploads`, and runtime bootstrap caches until explicitly cleared.
   Keep `vendor` unless dependencies are being deliberately updated using
   `composer.lock` and the production PHP version. Do not copy `.git`,
   `node_modules`, `.DS_Store`, tests, sample files or old archives into production.
4. Publish static assets from the clone's `public/images`, `public/js`,
   `public/css`, and `public/build` into the matching directories in `public_html`.
   Preserve `public_html/index.php` and the cPanel PHP handler in `.htaccess`.
   The repository's normal `public/index.php` assumes a different relative path:
   copying it over the deployed entry point will break the website.
   Never replace all of `public_html`; it also contains the alumni website and
   remuneration application.
5. After the release's code and migration files are in `apps/backlog-scheduler`,
   confirm the MySQL backup, then use **Deploy HEAD Commit** in cPanel's Git
   Version Control to run the committed `.cpanel.yml` tasks. These clear the
   configuration cache, run `migrate --force --no-interaction`, then clear route
   and view caches. Terminal access is not required. Laravel applies only pending
   migrations; it does not re-run already recorded migrations. The unused legacy
   SQLite file is not a backup of the production MySQL database.
6. If maintenance mode was enabled, leave it through the hosting provider's
   supported execution facility, then check the homepage, login, an exam page
   and a notice download. The current deployment tasks do not enter or leave
   maintenance mode.

The production CLI commands use PHP 8.3, matching the domain's cPanel handler:

```sh
/usr/local/bin/ea-php83 /home/servicescserueta/apps/backlog-scheduler/artisan down --retry=60
/usr/local/bin/ea-php83 /home/servicescserueta/apps/backlog-scheduler/artisan config:clear
/usr/local/bin/ea-php83 /home/servicescserueta/apps/backlog-scheduler/artisan migrate --force --no-interaction
/usr/local/bin/ea-php83 /home/servicescserueta/apps/backlog-scheduler/artisan route:clear
/usr/local/bin/ea-php83 /home/servicescserueta/apps/backlog-scheduler/artisan view:clear
/usr/local/bin/ea-php83 /home/servicescserueta/apps/backlog-scheduler/artisan up
```

The repository's `.cpanel.yml` runs migrations and clears caches in the correct
application directory. It does not clone or publish files, install dependencies,
or manage maintenance mode. **Update from Remote** updates only the staging
clone; copy the release into the running app before clicking **Deploy HEAD
Commit**. Do not run migrations from the staging clone, which does not contain
the production `.env`. If a migration fails, inspect the cPanel deployment output
and resolve the failure before treating the release as complete.

The deployment configuration is committed and active in the cPanel-managed
clone. Its earlier local configuration edit was reconciled before pulling;
keep this checkout clean so the Deploy button remains available.

## Deployment verification (2026-10-07)

Commit `4241ed2` was pushed to GitHub, pulled through **Update from Remote**, and
run through **Deploy HEAD Commit**. A fresh MySQL backup was downloaded and
verified before deployment. The pending
`2026_10_06_100000_create_mail_logs_table.php` migration was copied from the
checkout into the running application's `database/migrations` directory.

cPanel recorded the deployed commit. A second MySQL backup confirmed that
`mail_logs` was created and the migration was recorded; existing application
data was unchanged. The homepage and login form continued to load. This test
published the migration only; the UI and mail-controller changes in the Git
checkout still require copying into the running app for a full application
release. Database schema and deployed application code should be checked
separately when assessing release status.

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
