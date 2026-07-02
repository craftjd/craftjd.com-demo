<?php

namespace Deployer;

require 'recipe/laravel.php';

// VPS layout: deploy user, www-data group (see bin/provision). No ACL package needed.
set('writable_mode', 'chgrp');
set('writable_use_sudo', true);
set('http_group', 'www-data');

/**
 * Parse a CLIENT.conf file into key => value (same rules as bash source).
 */
function parse_client_conf(string $path): array
{
    $config = [];

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (preg_match('/^([A-Z_][A-Z0-9_]*)=(.*)$/', $line, $m)) {
            $value = trim($m[2]);
            $value = trim($value, '"\'');
            $config[$m[1]] = $value;
        }
    }

    return $config;
}

/**
 * Register one Deployer host from a client config file.
 * Host name = conf basename (e.g. site.conf → dep deploy site).
 */
function register_client_host(string $confFile): bool
{
    $basename = basename($confFile);
    if (str_starts_with($basename, '_')) {
        return false;
    }

    $config = parse_client_conf($confFile);
    $clientName = pathinfo($confFile, PATHINFO_FILENAME);
    $domain = $config['DOMAIN'] ?? null;
    $ip = $config['IP'] ?? null;
    $repo = $config['REPO'] ?? null;
    $tier = $config['TIER'] ?? 'flat-file';

    if (!$domain || !$ip || !$repo) {
        return false;
    }

    // Private GitHub repos: rewrite host so ~/.ssh/config picks the per-domain key.
    if (preg_match('/^git@github\.com:(.+)$/', $repo, $m)) {
        $repo = 'git@github.com-' . $domain . ':' . $m[1];
    }

    $sharedFiles = ['.env'];
    if ($tier === 'flat-file') {
        $sharedFiles[] = 'database/database.sqlite';
    }

    host($clientName)
        ->setHostname($ip)
        ->setRemoteUser('deploy')
        ->setPort(22)
        ->setDeployPath('/var/www/' . $domain)
        ->set('branch', $config['DEPLOY_BRANCH'] ?? 'main')
        ->set('keep_releases', 2)
        ->set('repository', $repo)
        ->set('shared_files', $sharedFiles)
        ->set('dotenv_example', $tier === 'app' ? '.env.app.example' : '.env.flat-file.example');

    return true;
}

$clientsDir = __DIR__ . '/bin/provision/clients';
$confFiles = glob($clientsDir . '/*.conf') ?: [];

if ($confFiles === []) {
    throw new \RuntimeException('No client configs in ' . $clientsDir);
}

$hostsRegistered = 0;
foreach ($confFiles as $confFile) {
    if (register_client_host($confFile)) {
        $hostsRegistered++;
    }
}

if ($hostsRegistered === 0) {
    throw new \RuntimeException(
        'No deploy hosts registered — add bin/provision/clients/site.conf (see _example.conf)'
    );
}

// ─── Tasks ────────────────────────────────────────────────────────────────

task('deploy:app-key', function () {
    $envPath = '{{deploy_path}}/shared/.env';
    $exists = run("test -f {$envPath} && echo 1 || echo 0");

    if (trim($exists) !== '1') {
        return;
    }

    $noKey = run("grep -q '^APP_KEY=$' {$envPath} && echo 1 || echo 0");
    if (trim($noKey) === '1') {
        $key = trim(run('php {{release_path}}/artisan key:generate --show'));
        run("echo 'APP_KEY={$key}' >> {$envPath}");
        writeln('  <info>✓</info> Generated missing APP_KEY');
    }
});
after('deploy:env', 'deploy:app-key');

task('deploy:npm', function () {
    cd('{{release_path}}');
    run('npm ci --no-audit --no-fund');
    run('npm run build');
});
after('deploy:vendors', 'deploy:npm');

task('deploy:meta', function () {
    $meta = json_encode([
        'git_sha' => trim(runLocally('git rev-parse HEAD')),
        'deployed_at' => date('c'),
        'git_commit_message' => trim(runLocally('git log -1 --format=%B')),
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    run('mkdir -p {{release_path}}/storage/app');
    run('printf %s ' . escapeshellarg($meta) . ' > {{release_path}}/storage/app/deploy-meta.json');
});
after('deploy:success', 'deploy:meta');

task('deploy:check', function () {
    cd('{{release_path}}');
    run('./bin/check');
});
after('deploy:meta', 'deploy:check');
after('rollback', 'deploy:meta');
