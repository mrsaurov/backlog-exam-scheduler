<?php
/** CLI-only migration of the existing cPanel installation; never deploys local code. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!in_array($argv[1] ?? '', ['prepare', 'activate', 'cleanup'], true)) {
    fwrite(STDERR, "Usage: php cpanel-reorganize.php prepare|activate|cleanup [account-home]\n"); exit(2);
}
umask(0077);
$mode = $argv[1];
$home = realpath($argv[2] ?? '/home/servicescserueta');
if (!$home || $home === '/' || !is_dir($home.'/public_html')) { throw new RuntimeException('Invalid account home.'); }
$control = $home.'/maintenance';
$target = $home.'/apps/backlog-scheduler';
$web = $home.'/public_html';
$backup = $home.'/backups/backlog-reorganization-2026-10-06';
$statePath = $control.'/reorganization.json';
$runtime = ['app','bootstrap','config','database','lang','resources','routes','storage','vendor','public'];
$projectFiles = ['artisan','composer.json','composer.lock','package.json','package-lock.json','vite.config.js','README.md','LICENSE','.editorconfig','.gitattributes','.gitignore','phpunit.xml','.cpanel.yml'];
$archiveOnly = ['.git','node_modules','__MACOSX','Archive.zip','.DS_Store','sample_document.txt','test_attachment.txt','tests'];
function dirCreate(string $path, int $mode = 0755): void {
    if (!is_dir($path) && !mkdir($path, $mode, true)) { throw new RuntimeException('Cannot create '.$path); }
}
function writeAtomic(string $path, string $content, int $mode = 0644): void {
    dirCreate(dirname($path));
    $tmp = $path.'.reorganization-tmp';
    if (file_put_contents($tmp, $content) === false || !chmod($tmp,$mode) || !rename($tmp,$path)) {
        throw new RuntimeException('Cannot atomically write '.$path);
    }
}
function copyTree(string $source, string $destination): void {
    if (is_link($source)) {
        $link = readlink($source);
        if (is_link($destination) && readlink($destination) === $link) { return; }
        if (file_exists($destination) || is_link($destination)) { throw new RuntimeException('Symlink collision: '.$destination); }
        dirCreate(dirname($destination));
        if (!symlink($link,$destination)) { throw new RuntimeException('Cannot copy symlink.'); }
    } elseif (is_dir($source)) {
        dirCreate($destination, fileperms($source) & 0777);
        foreach (new DirectoryIterator($source) as $entry) {
            if (!$entry->isDot()) { copyTree($entry->getPathname(), $destination.'/'.$entry->getFilename()); }
        }
    } elseif (is_file($source)) {
        dirCreate(dirname($destination));
        if (!copy($source,$destination)) { throw new RuntimeException('Cannot copy '.$source); }
        chmod($destination,fileperms($source) & 0777);
    } else { throw new RuntimeException('Missing source: '.$source); }
}
function sourceFingerprint(string $home, array $directories, array $files): string {
    $hashes=[];
    foreach ($directories as $dir) {
        if (!is_dir($home.'/'.$dir)) { continue; }
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($home.'/'.$dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && !$file->isLink()) { $hashes[substr($file->getPathname(),strlen($home)+1)] = hash_file('sha256',$file->getPathname()); }
        }
    }
    foreach ($files as $file) { if(is_file($home.'/'.$file)) { $hashes[$file]=hash_file('sha256',$home.'/'.$file); } }
    ksort($hashes); return hash('sha256',json_encode($hashes));
}
function appCheck(string $target): array {
    require_once $target.'/vendor/autoload.php';
    $app = require $target.'/bootstrap/app.php';
    $console = $app->make(Illuminate\Contracts\Console\Kernel::class);
    foreach (['config:clear','route:clear','view:clear'] as $command) {
        if ($console->call($command) !== 0) { throw new RuntimeException('Failed '.$command); }
    }
    $app->make('db')->connection()->getPdo()->query('SELECT 1');
    $app->make('router')->getRoutes();
    return [$app, $console];
}
function saveState(array $state, string $path): void { writeAtomic($path,json_encode($state,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n",0600); }
function publishMissing(string $source,string $destination): void {
    if (is_link($source)) { throw new RuntimeException('Refusing public asset symlink.'); }
    if (is_dir($source)) {
        dirCreate($destination);
        // Static asset directories must remain traversable by the web server.
        chmod($destination,0755);
        foreach(new DirectoryIterator($source) as $entry) { if(!$entry->isDot()) { publishMissing($entry->getPathname(),$destination.'/'.$entry->getFilename()); } }
    } elseif(is_file($source) && !file_exists($destination)) { copyTree($source,$destination); chmod($destination,0644); }
}
dirCreate($control,0700); chmod($control,0700);
$lock=fopen($control.'/reorganization.lock','c');
if(!$lock || !flock($lock,LOCK_EX|LOCK_NB)) { exit(0); }
$state=is_file($statePath)?json_decode(file_get_contents($statePath),true):[];
$state=is_array($state)?$state:[];
try {
    if ($mode==='prepare') {
        if(in_array($state['status']??'', ['prepared','active','complete'],true)) { echo "Already prepared.\n"; exit(0); }
        foreach(['artisan','.env','vendor/autoload.php','bootstrap/app.php','public_html/index.php','public/.htaccess'] as $file) {
            if(!is_file($home.'/'.$file)) { throw new RuntimeException('Missing required file: '.$file); }
        }
        if(is_dir($target) || is_dir($backup)) { throw new RuntimeException('Destination or backup already exists; inspect it before proceeding.'); }
        if(is_file($home.'/storage/framework/down')) { throw new RuntimeException('Application was already in maintenance mode.'); }
        dirCreate($backup,0700); chmod(dirname($backup),0700); chmod($backup,0700);
        dirCreate($backup.'/web',0700); dirCreate($backup.'/legacy-root',0700);
        foreach(['index.php','.htaccess'] as $file) { if(is_file($web.'/'.$file)) { copyTree($web.'/'.$file,$backup.'/web/'.$file); } }
        copyTree($home.'/.htaccess',$backup.'/home.htaccess');
        if(is_link($web.'/storage')) { writeAtomic($backup.'/web-storage-link.txt',readlink($web.'/storage')."\n",0600); }
        dirCreate($target);
        foreach($runtime as $dir) { if(is_dir($home.'/'.$dir)) { copyTree($home.'/'.$dir,$target.'/'.$dir); } }
        foreach($projectFiles as $file) { if(is_file($home.'/'.$file)) { copyTree($home.'/'.$file,$target.'/'.$file); } }
        copyTree($home.'/.env',$target.'/.env'); chmod($target.'/.env',0600);
        // Keep notice uploads outside the domain document root, as in the old installation.
        appCheck($target);
        $state=['status'=>'prepared','target'=>$target,'backup'=>$backup,'source_fingerprint'=>sourceFingerprint($home,['app','bootstrap','config','database/migrations','lang','resources','routes'],['.env','composer.json','composer.lock']), 'prepared_at'=>gmdate('c')];
        saveState($state,$statePath); echo "PREPARED: Laravel boots and the existing database responds. No schema changes performed.\n";
    } elseif($mode==='activate') {
        if(in_array($state['status']??'', ['active','complete'],true)) { echo "Already active.\n"; exit(0); }
        if(($state['status']??'')!=='prepared') { throw new RuntimeException('Prepare must succeed first.'); }
        if(sourceFingerprint($home,['app','bootstrap','config','database/migrations','lang','resources','routes'],['.env','composer.json','composer.lock'])!==$state['source_fingerprint']) { throw new RuntimeException('Live source changed after preparation.'); }
        // A short maintenance interval prevents new uploads/session writes during the final copy.
        $down=$home.'/storage/framework/down';
        writeAtomic($down,json_encode(['except'=>[],'redirect'=>null,'retry'=>60,'refresh'=>null,'secret'=>null,'status'=>503]));
        try {
            copyTree($home.'/storage',$target.'/storage');
            if(is_dir($home.'/public/uploads')) { copyTree($home.'/public/uploads',$target.'/public/uploads'); }
            [$app,$console]=appCheck($target);
            if($console->call('up')!==0) { throw new RuntimeException('Cannot leave maintenance mode in new app.'); }
            // These are static assets only: never publish uploads or storage contents.
            foreach(['images','js','css','build','robots.txt','favicon.ico'] as $asset) {
                if(file_exists($home.'/public/'.$asset)) { publishMissing($home.'/public/'.$asset,$web.'/'.$asset); }
            }
            $webRules=file_get_contents($backup.'/web/.htaccess');
            $laravelRules=file_get_contents($home.'/public/.htaccess');
            writeAtomic($web.'/.htaccess',"# Backlog scheduler routing\n".$laravelRules."\n".$webRules);
            $index=file_get_contents($backup.'/web/index.php');
            foreach(['storage/framework/maintenance.php','vendor/autoload.php','bootstrap/app.php'] as $path) {
                $old="__DIR__.'/../".$path."'";
                if(substr_count($index,$old)!==1) { throw new RuntimeException('Unexpected public index format: '.$path); }
                $index=str_replace($old,"__DIR__.'/../apps/backlog-scheduler/".$path."'",$index);
            }
            // A smoke request must render before the live front controller switches.
            $http=$app->make(Illuminate\Contracts\Http\Kernel::class);
            $request=Illuminate\Http\Request::create('https://services.cse.ruet.ac.bd/','GET');
            $response=$http->handle($request);
            if($response->getStatusCode()>=400) { throw new RuntimeException('New app smoke request failed: '.$response->getStatusCode()); }
            $http->terminate($request,$response);
            writeAtomic($web.'/index.php',$index);
            if(is_file($down)) { unlink($down); }
            $state['status']='active'; $state['activated_at']=gmdate('c'); $state['smoke_status']=$response->getStatusCode();
            saveState($state,$statePath); echo "ACTIVE: front controller switched; database and homepage checks passed.\n";
        } catch(Throwable $error) {
            copyTree($backup.'/web/index.php',$web.'/index.php'); chmod($web.'/index.php',0644);
            copyTree($backup.'/web/.htaccess',$web.'/.htaccess');
            if(is_file($down)) { unlink($down); }
            if(is_file($target.'/storage/framework/down')) { unlink($target.'/storage/framework/down'); }
            throw $error;
        }
    } else {
        if(($state['status']??'')==='complete') { echo "Already complete.\n"; exit(0); }
        if(($state['status']??'')!=='active') { throw new RuntimeException('Activate and verify the website before cleanup.'); }
        $index=file_get_contents($web.'/index.php');
        if(!str_contains($index,'../apps/backlog-scheduler/vendor/autoload.php')) { throw new RuntimeException('Web entry point does not reference new app.'); }
        // Archive originals rather than deleting them. Hosting-managed folders are never selected.
        foreach(array_merge($runtime,$projectFiles,$archiveOnly,['.env']) as $name) {
            $source=$home.'/'.$name; $destination=$backup.'/legacy-root/'.$name;
            if(file_exists($source)||is_link($source)) {
                if(file_exists($destination)||is_link($destination)) { throw new RuntimeException('Archive collision: '.$name); }
                if(!rename($source,$destination)) { throw new RuntimeException('Cannot archive '.$name); }
            }
        }
        // Old inherited Laravel rules no longer belong in the account home directory.
        $rootRules=file_get_contents($backup.'/home.htaccess');
        $start=strpos($rootRules,'<IfModule mod_rewrite.c>');
        if($start!==false) { writeAtomic($home.'/.htaccess',rtrim(substr($rootRules,0,$start))."\n"); }
        // Preserve the obsolete macOS links in the rollback archive; don't expose stored files.
        foreach([$web.'/storage'=>$backup.'/obsolete-web-storage-link', $target.'/public/storage'=>$backup.'/obsolete-app-storage-link'] as $source=>$destination) {
            if(is_link($source) && str_starts_with(readlink($source),'/Users/')) {
                if(!rename($source,$destination)) { throw new RuntimeException('Cannot archive dangling storage link.'); }
            }
        }
        $yml="---\ndeployment:\n  tasks:\n    - /usr/local/bin/ea-php83 /home/servicescserueta/apps/backlog-scheduler/artisan config:clear\n    - /usr/local/bin/ea-php83 /home/servicescserueta/apps/backlog-scheduler/artisan route:clear\n    - /usr/local/bin/ea-php83 /home/servicescserueta/apps/backlog-scheduler/artisan view:clear\n";
        writeAtomic($target.'/.cpanel.yml',$yml);
        dirCreate($home.'/repositories/backlog-exam-scheduler',0700);
        $state['status']='complete'; $state['completed_at']=gmdate('c');
        saveState($state,$statePath); echo "COMPLETE: legacy app archived; hosting folders and other websites preserved.\n";
    }
} catch(Throwable $error) {
    // Detailed diagnostics stay in a private log; no credentials are printed.
    $state['last_error_class']=get_class($error); $state['last_error']=$error->getMessage();
    saveState($state,$statePath); fwrite(STDERR,"FAILED: ".$error->getMessage()."\n"); exit(1);
}
