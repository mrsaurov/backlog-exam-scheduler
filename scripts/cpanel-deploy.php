<?php
/** Full cPanel release deployment. Run with the domain's PHP CLI, never over HTTP. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
umask(0077);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

function deployDir(string $path, int $mode = 0700): void
{
    if (!is_dir($path) && !mkdir($path, $mode, true)) { throw new RuntimeException('Cannot create directory: '.$path); }
}
function deployWrite(string $path, string $bytes, int $mode = 0600): void
{
    deployDir(dirname($path));
    $temporary = $path.'.tmp-'.bin2hex(random_bytes(4));
    if (file_put_contents($temporary, $bytes) === false || !chmod($temporary, $mode) || !rename($temporary, $path)) {
        throw new RuntimeException('Cannot write: '.$path);
    }
}
function deployCopy(string $source, string $destination): void
{
    if (is_link($source)) {
        $link = readlink($source);
        if ($link === false || str_starts_with($link, '/') || !file_exists($source)) {
            throw new RuntimeException('Unsupported runtime symlink: '.$source);
        }
        deployDir(dirname($destination));
        if (!symlink($link, $destination)) { throw new RuntimeException('Cannot copy runtime symlink.'); }
    } elseif (is_dir($source)) {
        deployDir($destination);
        foreach (new DirectoryIterator($source) as $entry) {
            if (!$entry->isDot()) { deployCopy($entry->getPathname(), $destination.'/'.$entry->getFilename()); }
        }
    } elseif (is_file($source)) {
        deployDir(dirname($destination));
        if (!copy($source, $destination) || !chmod($destination, fileperms($source) & 0777)) {
            throw new RuntimeException('Cannot copy: '.$source);
        }
    } else { throw new RuntimeException('Missing file: '.$source); }
}
/** Only used for this invocation's private staging files, never live data or backups. */
function deployRemoveStage(string $path): void
{
    if (is_link($path) || is_file($path)) {
        if (!unlink($path)) { throw new RuntimeException('Cannot remove staging file.'); }
    } elseif (is_dir($path)) {
        foreach (new DirectoryIterator($path) as $entry) {
            if (!$entry->isDot()) { deployRemoveStage($entry->getPathname()); }
        }
        if (!rmdir($path)) { throw new RuntimeException('Cannot remove staging directory.'); }
    }
}
function deployCommand(array $command, string $directory, string $log): string
{
    $output = tempnam(dirname($log), 'command-');
    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', $output, 'w'], 2 => ['file', $log, 'a']], $pipes, $directory);
    if (!is_resource($process)) { throw new RuntimeException('Cannot start deployment command.'); }
    $code = proc_close($process);
    $bytes = file_get_contents($output);
    if (!str_contains($bytes, "\0")) { file_put_contents($log, $bytes, FILE_APPEND); }
    unlink($output);
    if ($code !== 0) { throw new RuntimeException('Deployment command failed (exit '.$code.'); inspect the private log.'); }
    return $bytes;
}
function deployAsset(string $path): bool
{
    if (!str_starts_with($path, 'public/') || preg_match('#(^|/)(\.|\.\.|\.[^/]+)(/|$)#', $path)) { return false; }
    $relative = substr($path, 7);
    if (in_array($relative, ['index.php', 'hot'], true) || preg_match('#^(uploads|storage)(/|$)#', $relative)) { return false; }
    if (preg_match('/\.(php\d*|phtml|phar)$/i', $relative)) { throw new RuntimeException('Executable public assets are not supported.'); }
    return true;
}
function deployManaged(string $path): bool
{
    if (preg_match('#(^|/)(\.|\.\.|\.[^/]+)(/|$)#', $path)) { return false; }
    return preg_match('#^(app|config|lang|resources|routes)/#', $path)
        || str_starts_with($path, 'database/migrations/')
        || in_array($path, ['artisan', 'bootstrap/app.php', 'composer.json', 'composer.lock', 'public/index.php'], true)
        || deployAsset($path);
}
function deployPublicPath(string $web, string $relative): string
{
    if (!deployAsset('public/'.$relative)) { throw new RuntimeException('Invalid managed asset path.'); }
    $path = $web;
    foreach (explode('/', $relative) as $part) {
        $path .= '/'.$part;
        if (is_link($path)) { throw new RuntimeException('Public destination contains a symlink: '.$relative); }
    }
    return $path;
}
function deployPublish(string $source, string $web, string $relative): void
{
    $destination = deployPublicPath($web, $relative);
    $parent = dirname($relative);
    $path = $web;
    if ($parent !== '.') {
        foreach (explode('/', $parent) as $part) {
            $path .= '/'.$part;
            deployDir($path, 0755);
            chmod($path, 0755);
        }
    }
    deployWrite($destination, file_get_contents($source), 0644);
}

/** A fresh process prevents the old app's loaded classes leaking into the candidate. */
function deployHelper(string $action, string $root, string $argument): void
{
    chdir($root);
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $console = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $console->bootstrap();
    $connection = $app->make('db')->connection();
    $pdo = $connection->getPdo();
    if ($action === 'inspect') {
        $applied = $connection->table('migrations')->pluck('migration')->all();
        $pending = array_filter(glob($root.'/database/migrations/*.php'), fn($file) => !in_array(basename($file, '.php'), $applied, true));
        echo json_encode(['driver' => $connection->getDriverName(), 'pending' => count($pending)])."\n";
    } elseif ($action === 'backup') {
        if ($connection->getDriverName() === 'sqlite') {
            $pdo->exec('VACUUM INTO '.$pdo->quote($argument.'/database.sqlite'));
            chmod($argument.'/database.sqlite', 0600);
            echo "SQLite backup complete.\n";
            return;
        }
        if ($connection->getDriverName() !== 'mysql') { throw new RuntimeException('Unsupported backup database engine.'); }
        $database = $connection->getDatabaseName();
        $statement = $pdo->prepare('SELECT TABLE_NAME, ENGINE, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?');
        $statement->execute([$database]);
        $tables = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tables as $table) {
            if ($table['TABLE_TYPE'] !== 'BASE TABLE' || $table['ENGINE'] !== 'InnoDB') {
                throw new RuntimeException('Automatic backup requires InnoDB tables without views. Use a reviewed backup procedure for this schema.');
            }
        }
        if ($pdo->query('SHOW TRIGGERS')->fetch()) { throw new RuntimeException('Automatic backup does not support database triggers.'); }
        $file = $argument.'/database.sql.gz';
        $stream = gzopen($file, 'wb9');
        if (!$stream) { throw new RuntimeException('Cannot open database backup.'); }
        chmod($file, 0600);
        $write = function (string $sql) use ($stream): void {
            if (gzwrite($stream, $sql) !== strlen($sql)) { throw new RuntimeException('Cannot finish database backup.'); }
        };
        $identifier = fn($name) => '`'.str_replace('`', '``', $name).'`';
        try {
            // Export TIMESTAMP values in a defined timezone, independent of server defaults.
            $pdo->exec("SET time_zone = '+00:00'");
            $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
            $write("-- Backlog scheduler deployment backup\nSET NAMES utf8mb4;\nSET TIME_ZONE='+00:00';\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET FOREIGN_KEY_CHECKS=0;\n");
            foreach ($tables as $table) {
                $name = $identifier($table['TABLE_NAME']);
                $create = $pdo->query('SHOW CREATE TABLE '.$name)->fetch(PDO::FETCH_NUM)[1];
                $write('DROP TABLE IF EXISTS '.$name.";\n".$create.";\n");
                $rows = $pdo->query('SELECT * FROM '.$name);
                while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                    $columns = implode(',', array_map($identifier, array_keys($row)));
                    $values = implode(',', array_map(fn($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values($row)));
                    $write('INSERT INTO '.$name.' ('.$columns.') VALUES ('.$values.");\n");
                }
                $rows->closeCursor();
            }
            $write("SET FOREIGN_KEY_CHECKS=1;\n");
            $pdo->exec('COMMIT');
        } finally { gzclose($stream); }
        // Read the entire gzip back to detect an incomplete write before changing production.
        $verify = gzopen($file, 'rb');
        while (!gzeof($verify)) { if (gzread($verify, 1048576) === false) { throw new RuntimeException('Backup verification failed.'); } }
        gzclose($verify);
        echo 'MySQL backup complete: '.count($tables)." tables.\n";
    } elseif ($action === 'smoke') {
        $down = $root.'/storage/framework/down';
        $cookies = [];
        if (is_file($down)) {
            $secret = json_decode(file_get_contents($down), true)['secret'] ?? null;
            if (!$secret) { throw new RuntimeException('Smoke check has no maintenance bypass.'); }
            $cookies['laravel_maintenance'] = Illuminate\Foundation\Http\MaintenanceModeBypassCookie::create($secret)->getValue();
        }
        $paths = ['/', '/login'];
        $exam = $connection->table('available_exams')->orderByDesc('id')->value('id');
        if ($exam) { $paths[] = '/exam/'.$exam.'/notices'; }
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        foreach ($paths as $path) {
            $request = Illuminate\Http\Request::create('https://services.cse.ruet.ac.bd'.$path, 'GET', [], $cookies);
            $response = $kernel->handle($request);
            $status = $response->getStatusCode();
            $kernel->terminate($request, $response);
            if ($status !== 200) { throw new RuntimeException('Smoke request failed: '.$path.' (HTTP '.$status.').'); }
            echo 'HTTP 200 '.$path."\n";
        }
    } else { throw new RuntimeException('Unknown deployment helper.'); }
}

if (($argv[1] ?? '') === '--helper') {
    try { deployHelper($argv[2], $argv[3], $argv[4] ?? ''); }
    catch (Throwable $error) { fwrite(STDERR, $error->getMessage()."\n"); exit(1); }
    exit(0);
}
$home = realpath($argv[1] ?? '/home/servicescserueta');
if (!$home || $home === '/' || !is_dir($home.'/public_html')) { fwrite(STDERR, "Invalid account home.\n"); exit(2); }
$source = $home.'/repositories/backlog-exam-scheduler';
$target = $home.'/apps/backlog-scheduler';
$web = $home.'/public_html';
$control = $home.'/maintenance';
deployDir($control);
$lock = fopen($control.'/deployment.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { fwrite(STDERR, "A deployment is already running.\n"); exit(1); }
$stateFile = $control.'/deployment.json';
$previous = is_file($stateFile) ? json_decode(file_get_contents($stateFile), true) : [];
if (!in_array($previous['status'] ?? 'complete', ['complete', 'failed', 'rolled_back'], true)) {
    fwrite(STDERR, "An interrupted deployment requires recovery; inspect maintenance/deployment.json.\n"); exit(1);
}
$id = gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));
$backup = $home.'/backups/backlog-deployments/'.$id;
$stage = $home.'/apps/.backlog-stage-'.$id;
$log = $control.'/deployment-'.$id.'.log';
$state = ['status' => 'preparing', 'started_at' => gmdate('c'), 'backup' => $backup, 'stage' => $stage, 'log' => $log];
$activated = false;
$archived = false;
$maintenance = false;
$schemaMayHaveChanged = false;
$assetSnapshot = [];
$assetsTouched = false;
$save = function (string $status) use (&$state, $stateFile): void {
    $state['status'] = $status;
    deployWrite($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
};
$event = function (string $message) use ($log): void { echo $message."\n"; file_put_contents($log, $message."\n", FILE_APPEND); };
$run = fn(array $command, string $directory) => deployCommand($command, $directory, $log);
$artisan = fn(string $root, array $args) => $run(array_merge([PHP_BINARY, $root.'/artisan'], $args, ['--no-interaction']), $root);
$helper = fn(string $action, string $root, string $argument = '') => $run([PHP_BINARY, __FILE__, '--helper', $action, $root, $argument], $root);
try {
    deployDir($backup);
    deployWrite($log, 'Release preparation '.gmdate('c')."\n");
    $save('preparing');
    foreach ([$source, $target, $web] as $directory) {
        if (is_link($directory) || !is_dir($directory)) { throw new RuntimeException('Expected a real directory: '.$directory); }
    }
    foreach (['.env', 'artisan', 'vendor/autoload.php', 'composer.lock'] as $file) {
        if (!is_file($target.'/'.$file)) { throw new RuntimeException('Production prerequisite missing: '.$file); }
    }
    if (is_file($target.'/storage/framework/down') || is_file($target.'/storage/framework/maintenance.php')) {
        throw new RuntimeException('Application was already in maintenance mode.');
    }
    $index = file_get_contents($web.'/index.php');
    foreach (['vendor/autoload.php', 'bootstrap/app.php', 'storage/framework/maintenance.php'] as $path) {
        if (!str_contains($index, '../apps/backlog-scheduler/'.$path)) { throw new RuntimeException('Unexpected web entry point.'); }
    }
    $git = '/usr/bin/git';
    if (!is_executable($git)) { $git = '/usr/local/cpanel/3rdparty/bin/git'; }
    if (trim($run([$git, '-C', $source, 'status', '--porcelain'], $source)) !== '') { throw new RuntimeException('Git checkout must be clean.'); }
    $commit = trim($run([$git, '-C', $source, 'rev-parse', 'HEAD'], $source));
    $state['commit'] = $commit;
    $files = explode("\0", rtrim($run([$git, '-C', $source, 'ls-files', '-z'], $source), "\0"));
    $files = array_values(array_filter($files, 'deployManaged'));
    $assets = [];
    deployDir($stage);
    foreach ($files as $file) {
        if (is_link($source.'/'.$file)) { throw new RuntimeException('Tracked symlinks cannot be deployed.'); }
        deployCopy($source.'/'.$file, $stage.'/'.$file);
        if (deployAsset($file)) { $assets[] = substr($file, 7); }
    }
    foreach (['bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $directory) { deployDir($stage.'/'.$directory); }
    deployCopy($target.'/.env', $stage.'/.env');
    chmod($stage.'/.env', 0600);
    $environmentHash = hash_file('sha256', $target.'/.env');
    deployCopy($target.'/storage', $stage.'/storage');
    deployCopy($target.'/vendor', $stage.'/vendor');
    $event('Preparing code and dependencies for '.substr($commit, 0, 12).'.');
    $composer = null;
    if (($argv[2] ?? '') !== '--reuse-vendor') {
        foreach ([$control.'/composer.phar', '/opt/cpanel/composer/bin/composer', '/usr/local/bin/composer', '/usr/bin/composer', '/opt/homebrew/bin/composer'] as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) { $composer = $candidate; break; }
        }
    }
    if ($composer) {
        $run([PHP_BINARY, $composer, 'install', '--no-dev', '--prefer-dist', '--no-interaction', '--optimize-autoloader'], $stage);
        $run([PHP_BINARY, $composer, 'check-platform-reqs', '--no-dev'], $stage);
        $state['dependencies'] = 'composer install';
    } else {
        if (hash_file('sha256', $target.'/composer.lock') !== hash_file('sha256', $stage.'/composer.lock')) {
            throw new RuntimeException('Dependencies changed but Composer is unavailable. Install trusted Composer at maintenance/composer.phar first.');
        }
        $state['dependencies'] = 'reused vendor (unchanged lockfile)';
    }
    foreach (['config:clear', 'route:clear', 'view:clear', 'package:discover'] as $command) { $artisan($stage, [$command]); }
    $inspection = json_decode(trim($helper('inspect', $stage)), true, 512, JSON_THROW_ON_ERROR);
    $state['pending_migrations'] = $inspection['pending'];
    $helper('backup', $target, $backup);
    $event('Database backup verified. Preparing static asset recovery copies.');
    $oldManifest = is_file($target.'/.deployment.json') ? json_decode(file_get_contents($target.'/.deployment.json'), true) : [];
    foreach (array_unique(array_merge($assets, $oldManifest['assets'] ?? [])) as $relative) {
        $path = deployPublicPath($web, $relative);
        if (is_dir($path)) { throw new RuntimeException('Asset conflicts with an existing directory.'); }
        $assetSnapshot[$relative] = is_file($path);
        if (is_file($path)) { deployCopy($path, $backup.'/assets/'.$relative); }
    }
    deployWrite($backup.'/assets.json', json_encode($assetSnapshot, JSON_PRETTY_PRINT)."\n");
    if (hash_file('sha256', $target.'/.env') !== $environmentHash
        || trim($run([$git, '-C', $source, 'rev-parse', 'HEAD'], $source)) !== $commit
        || trim($run([$git, '-C', $source, 'status', '--porcelain'], $source)) !== '') {
        throw new RuntimeException('Source or production configuration changed during preparation.');
    }
    $save('activating');
    $artisan($target, ['down', '--retry=60', '--secret='.bin2hex(random_bytes(24))]);
    $maintenance = true;
    // Refresh mutable data under maintenance, including deletions made during preparation.
    deployRemoveStage($stage.'/storage');
    deployCopy($target.'/storage', $stage.'/storage');
    if (is_dir($target.'/public/uploads')) { deployCopy($target.'/public/uploads', $stage.'/public/uploads'); }
    if (!rename($target, $backup.'/application')) { throw new RuntimeException('Cannot archive the previous release.'); }
    $archived = true;
    if (!rename($stage, $target)) { throw new RuntimeException('Cannot activate the prepared release.'); }
    $activated = true;
    $assetsTouched = true;
    foreach ($assets as $relative) { deployPublish($target.'/public/'.$relative, $web, $relative); }
    foreach (array_diff($oldManifest['assets'] ?? [], $assets) as $relative) {
        $path = deployPublicPath($web, $relative);
        if (is_file($path) && !unlink($path)) { throw new RuntimeException('Cannot remove obsolete managed asset.'); }
    }
    $event('Code and assets published. Applying pending migrations.');
    $save('migrating');
    $schemaMayHaveChanged = $inspection['pending'] > 0;
    $artisan($target, ['migrate', '--force']);
    foreach (['config:clear', 'route:clear', 'view:clear', 'package:discover'] as $command) { $artisan($target, [$command]); }
    $save('checking');
    $helper('smoke', $target);
    deployWrite($target.'/.deployment.json', json_encode(['commit' => $commit, 'deployed_at' => gmdate('c'), 'assets' => $assets, 'files' => $files, 'backup' => $backup], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    $artisan($target, ['up']);
    $maintenance = false;
    $state['completed_at'] = gmdate('c');
    $save('complete');
    $event('COMPLETE: '.substr($commit, 0, 12).' is live. Previous release and database backup: '.$backup);
} catch (Throwable $error) {
    file_put_contents($log, get_class($error).': '.$error->getMessage()."\n", FILE_APPEND);
    try {
        if ($schemaMayHaveChanged) {
            if ($activated && !is_file($target.'/storage/framework/down')) {
                $artisan($target, ['down', '--retry=60', '--secret='.bin2hex(random_bytes(24))]);
            }
            $save('needs_recovery');
            $event('FAILED: schema changes may have run. Site remains in maintenance; inspect '.$log.' and '.$stateFile.'. Database is not automatically rolled back.');
        } else {
            if ($assetsTouched) {
                foreach ($assetSnapshot as $relative => $existed) {
                    $path = deployPublicPath($web, $relative);
                    if ($existed) { deployPublish($backup.'/assets/'.$relative, $web, $relative); }
                    elseif (is_file($path)) { unlink($path); }
                }
            }
            if ($activated) {
                if (!rename($target, $backup.'/failed-application')) { throw new RuntimeException('Cannot archive failed release.'); }
            }
            if ($archived && !rename($backup.'/application', $target)) { throw new RuntimeException('Cannot restore previous release.'); }
            if ($maintenance) { $artisan($target, ['up']); }
            if (is_dir($stage)) { deployRemoveStage($stage); }
            $save($maintenance || $archived ? 'rolled_back' : 'failed');
            $event('FAILED: '.$error->getMessage().' Live code preserved or restored. Log: '.$log);
        }
    } catch (Throwable $recoveryError) {
        file_put_contents($log, 'Recovery failure: '.$recoveryError->getMessage()."\n", FILE_APPEND);
        $save('needs_recovery');
        $event('FAILED: recovery requires attention. Inspect '.$log.' and '.$stateFile.'.');
    }
    exit(1);
}
