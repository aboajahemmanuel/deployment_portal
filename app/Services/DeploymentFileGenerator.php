<?php

namespace App\Services;

class DeploymentFileGenerator
{
    /**
     * Generate the deployment PHP script content.
     */
    public function make(string $projectPath, ?string $repoUrl = null, ?string $projectType = 'laravel', ?string $envVariables = null): string
    {
        $escapedPath = addslashes($projectPath);
        $escapedRepo = addslashes($repoUrl ?? '');
        $escapedEnv = addslashes($envVariables ?? '');

        $php = <<<'PHP'
<?php

$projectPath = '__PROJECT_PATH__';
$repoUrl = '__REPO_URL__';
$envVariables = '__ENV_VARS__';
$runId = date('Ymd_His');
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }
$logFile = $logDir . "/deploy_$runId.log";
$aggregateLog = __DIR__ . '/deploy-log.txt';
$safeDir = str_replace('\\', '/', $projectPath);

// Always send a 200 unless we explicitly detect a failure
@header('Content-Type: text/html; charset=utf-8');
@http_response_code(200);
@header('X-Deployment-Status: started');

// Increase execution limits for long installs
@set_time_limit(0);
@ini_set('max_execution_time', '0');
@ini_set('default_socket_timeout', '300');
putenv('COMPOSER_PROCESS_TIMEOUT=2000');

// Add Git and Node to PATH for this script
$gitPath = 'C:\\Program Files\\Git\\cmd';
$composerPath = 'C:\\xampp\\php'; // Adjust if needed
$nodePath = 'C:\\Program Files\\nodejs';
putenv("PATH=" . getenv("PATH") . ";{$gitPath};{$composerPath};{$nodePath}");

$output = "";
$hadError = false;

function logLine($message, &$output, $logFile, $aggregateLog) {
    $line = '[' . date('Y-m-d H:i:s') . "] " . $message . "\n";
    $output .= $line;
    @file_put_contents($logFile, $line, FILE_APPEND);
    @file_put_contents($aggregateLog, $line, FILE_APPEND);
}

logLine("🚀 Deployment started in {$projectPath}", $output, $logFile, $aggregateLog);
logLine("Run ID: {$runId}", $output, $logFile, $aggregateLog);

// Helper function to execute commands
function executeCommand($cmd, &$output, $logFile, $aggregateLog) {
    $start = microtime(true);
    logLine("> Running: {$cmd}", $output, $logFile, $aggregateLog);
    exec($cmd . ' 2>&1', $result, $returnCode);
    $resultString = implode("\n", $result);
    foreach (explode("\n", $resultString) as $line) {
        if ($line !== '') { logLine($line, $output, $logFile, $aggregateLog); }
    }
    $duration = number_format(microtime(true) - $start, 2);
    if ($returnCode !== 0) {
        logLine("❌ Command failed with code {$returnCode} ({$duration}s)", $output, $logFile, $aggregateLog);
        return false;
    }
    logLine("✅ Completed ({$duration}s)", $output, $logFile, $aggregateLog);
    return true;
}

if (!is_dir($projectPath)) {
    // Folder doesn't exist — create it and clone project
    if (empty($repoUrl)) {
        logLine("❌ Repo URL missing. Cannot clone repository. Provide 'repo_url' when generating this file.", $output, $logFile, $aggregateLog);
        mkdir($projectPath, 0777, true);
        $commands = [
            "git config --global --add safe.directory {$safeDir}",
        ];
    } else {
        logLine("📁 Project folder not found. Creating folder and cloning repository...", $output, $logFile, $aggregateLog);
        mkdir($projectPath, 0777, true);
        $commands = [
            "git config --global --add safe.directory {$safeDir}",
            "cd /d {$projectPath} && git clone {$repoUrl} .",
        ];
        
        // Execute git clone first
        foreach ($commands as $cmd) {
            if (!executeCommand($cmd, $output, $logFile, $aggregateLog)) {
                $hadError = true;
                @header('X-Deployment-Status: failed');
                @http_response_code(500);
                logLine('DEPLOYMENT_STATUS=failed', $output, $logFile, $aggregateLog);
                echo '<pre>' . htmlspecialchars($output) . '</pre>';
                exit(0);
            }
        }
        
        // Create .env file if provided and project is Laravel
        if (!empty($envVariables) && '__PROJECT_TYPE__' === 'laravel') {
            logLine("📝 Creating .env file...", $output, $logFile, $aggregateLog);
            $envPath = $projectPath . '\\.env';
            if (file_put_contents($envPath, $envVariables)) {
                logLine("✅ .env file created successfully", $output, $logFile, $aggregateLog);
            } else {
                logLine("❌ Failed to create .env file", $output, $logFile, $aggregateLog);
                $hadError = true;
            }
        }
        
        // Setup commands based on project type
        if ('__PROJECT_TYPE__' === 'laravel') {
            // Laravel-specific setup commands
            $commands = [
                "cd /d {$projectPath} && composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress",
                "cd /d {$projectPath} && php artisan key:generate",
                "cd /d {$projectPath} && php artisan migrate --force",
                "cd /d {$projectPath} && php artisan optimize:clear",
                "cd /d {$projectPath} && php artisan cache:clear",
                "cd /d {$projectPath} && php artisan config:cache",
                "cd /d {$projectPath} && php artisan route:cache",
            ];
        } elseif ('__PROJECT_TYPE__' === 'nodejs') {
            // Node.js project setup
            $commands = [
                "cd /d {$projectPath} && npm install --no-audit --no-fund",
                "cd /d {$projectPath} && npm run build --if-present",
            ];
        } elseif ('__PROJECT_TYPE__' === 'php') {
            // Generic PHP project setup
            $commands = [
                "cd /d {$projectPath} && composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress",
            ];
        } else {
            // Static or other project types - no setup commands needed
            $commands = [];
        }
    }
} else {
    // Folder exists — just pull latest updates
    logLine("📦 Folder exists. Pulling latest changes...", $output, $logFile, $aggregateLog);
    
    // Base commands for all project types
    $commands = [
        "cd /d {$projectPath} && git config --local --add safe.directory {$safeDir}",
        "cd /d {$projectPath} && git fetch origin main",
        "cd /d {$projectPath} && git pull origin main",
    ];
    
    // Add project-type specific commands
    if ('__PROJECT_TYPE__' === 'laravel') {
        $commands = array_merge($commands, [
            "cd /d {$projectPath} && composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress",
            "cd /d {$projectPath} && php artisan migrate --force",
            "cd /d {$projectPath} && php artisan optimize:clear",
            "cd /d {$projectPath} && php artisan cache:clear",
            "cd /d {$projectPath} && php artisan config:cache",
            "cd /d {$projectPath} && php artisan route:cache",
        ]);
    } elseif ('__PROJECT_TYPE__' === 'nodejs') {
        $commands = array_merge($commands, [
            "cd /d {$projectPath} && npm install --no-audit --no-fund",
            "cd /d {$projectPath} && npm run build --if-present",
        ]);
    } elseif ('__PROJECT_TYPE__' === 'php') {
        $commands = array_merge($commands, [
            "cd /d {$projectPath} && composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress",
        ]);
    }
    // For static or other project types, only git commands are needed
}

// Execute remaining commands and capture output
foreach ($commands as $cmd) {
    // Skip artisan commands if vendor is not installed yet
    if (strpos($cmd, 'php artisan') !== false && !file_exists($projectPath . '/vendor/autoload.php')) {
        logLine("⏭️ Skipping: {$cmd} (vendor/autoload.php not found)", $output, $logFile, $aggregateLog);
        continue;
    }

    $ok = executeCommand($cmd, $output, $logFile, $aggregateLog);

    // Fallback: if composer install via dist fails, try prefer-source
    if (!$ok && strpos($cmd, 'composer install') !== false) {
        $fallback = "cd /d {$projectPath} && composer install --no-interaction --prefer-source --no-progress";
        $ok2 = executeCommand($fallback, $output, $logFile, $aggregateLog);
        if (!$ok2) { $hadError = true; }
    }

    if (!$ok) {
        $hadError = true;
    }

    // After composer install, install Node modules if package.json exists
    if (strpos($cmd, 'composer install') !== false && file_exists($projectPath . '/package.json')) {
        logLine("📦 Node project detected. Installing npm dependencies...", $output, $logFile, $aggregateLog);
        if (file_exists($projectPath . '/package-lock.json')) {
            $npmCmd = "cd /d {$projectPath} && npm ci --no-audit --no-fund --silent";
            $okNpm = executeCommand($npmCmd, $output, $logFile, $aggregateLog);
            if (!$okNpm) {
                $fallbackNpm = "cd /d {$projectPath} && npm install --no-audit --no-fund --silent";
                $okNpm2 = executeCommand($fallbackNpm, $output, $logFile, $aggregateLog);
                if (!$okNpm2) { $hadError = true; }
            }
        } else {
            $npmCmd = "cd /d {$projectPath} && npm install --no-audit --no-fund --silent";
            $okNpm = executeCommand($npmCmd, $output, $logFile, $aggregateLog);
            if (!$okNpm) { $hadError = true; }
        }
        // Build assets if script exists
        $buildCmd = "cd /d {$projectPath} && npm run build --if-present";
        $okBuild = executeCommand($buildCmd, $output, $logFile, $aggregateLog);
        if (!$okBuild) { $hadError = true; }
    }
}

// Handle web accessibility based on project type
$appSlug = null;
if ('__PROJECT_TYPE__' === 'laravel') {
    // Laravel projects have _deploy suffix
    if (preg_match('/([^\\\\\/]+)_deploy$/', $projectPath, $m)) {
        $appSlug = $m[1];
    }
} else {
    // Non-Laravel projects are deployed directly to web directory
    if (preg_match('/([^\\\\\/]+)$/', $projectPath, $m)) {
        $appSlug = $m[1];
    }
}

if ($appSlug) {
    $htdocs = 'C:\\xampp\\htdocs';
    
    if ('__PROJECT_TYPE__' === 'laravel') {
        // Laravel projects: Create separate web directory with index.php stub
        $webDir = $htdocs . '\\' . $appSlug;
        if (!is_dir($webDir)) { @mkdir($webDir, 0777, true); }

        // index.php stub to forward into the deployed app's public/index.php
        $publicIndex = str_replace('\\', '/', $projectPath) . '/public/index.php';
        $indexStub = "<?php\nrequire '" . $publicIndex . "';\n";
        @file_put_contents($webDir . '\\index.php', $indexStub);

        // Minimal .htaccess to route pretty URLs to index.php under /{slug}
        $htaccess = "<IfModule mod_rewrite.c>\n" .
                    "RewriteEngine On\n" .
                    "RewriteBase /{$appSlug}/\n" .
                    "RewriteCond %{REQUEST_FILENAME} !-f\n" .
                    "RewriteCond %{REQUEST_FILENAME} !-d\n" .
                    "RewriteRule ^ index.php [L]\n" .
                    "</IfModule>\n";
        @file_put_contents($webDir . '\\.htaccess', $htaccess);

        logLine("🌐 Laravel app URL prepared at http://101-php-01.fmdqgroup.com/{$appSlug}", $output, $logFile, $aggregateLog);
        
    } elseif ('__PROJECT_TYPE__' === 'static') {
        // Static sites: Deploy directly to web directory (no _deploy suffix)
        logLine("ℹ️ Static site deployed directly to web directory", $output, $logFile, $aggregateLog);
        logLine("🌐 Static site URL: http://101-php-01.fmdqgroup.com/{$appSlug}", $output, $logFile, $aggregateLog);
        
    } elseif ('__PROJECT_TYPE__' === 'php') {
        // Generic PHP projects: Deploy directly to web directory (no _deploy suffix)
        logLine("ℹ️ PHP project deployed directly to web directory", $output, $logFile, $aggregateLog);
        logLine("🌐 PHP app URL: http://101-php-01.fmdqgroup.com/{$appSlug}", $output, $logFile, $aggregateLog);
        
    } elseif ('__PROJECT_TYPE__' === 'nodejs') {
        // Node.js projects: Note about manual server setup
        logLine("ℹ️ Node.js project deployed. Manual server setup required on custom port.", $output, $logFile, $aggregateLog);
        logLine("🌐 Project files available at: {$projectPath}", $output, $logFile, $aggregateLog);
        
    } else {
        // Other project types
        logLine("ℹ️ Project deployed to: {$projectPath}", $output, $logFile, $aggregateLog);
    }
} else {
    logLine("ℹ️ Could not derive app slug from project path '{$projectPath}'. Skipping web alias creation.", $output, $logFile, $aggregateLog);
}

// Mark completion in logs for reliable detection and set headers appropriately
if ($hadError) {
    @header('X-Deployment-Status: failed');
    @http_response_code(500);
    logLine('DEPLOYMENT_STATUS=failed', $output, $logFile, $aggregateLog);
    logLine('❗ Deployment finished with errors', $output, $logFile, $aggregateLog);
} else {
    @header('X-Deployment-Status: success');
    @http_response_code(200);
    logLine('✅ Deployment finished successfully', $output, $logFile, $aggregateLog);
    logLine('DEPLOYMENT_STATUS=success', $output, $logFile, $aggregateLog);
}

// Save to log and display nicely
echo '<style>body{font-family:Segoe UI,Arial,sans-serif} .ok{color:#0a0} .err{color:#a00} pre{white-space:pre-wrap;word-wrap:break-word}</style>';
echo '<pre>' . htmlspecialchars($output) . '</pre>';
PHP;
        $php = str_replace([
            '__PROJECT_PATH__',
            '__REPO_URL__',
            '__ENV_VARS__',
            '__PROJECT_TYPE__',
        ], [
            $escapedPath,
            $escapedRepo,
            $escapedEnv,
            $projectType,
        ], $php);
        return $php;
    }

    /**
     * Generate the rollback PHP script content for a specific project path.
     * This script expects a JSON POST with Authorization: Bearer test-token-123
     * and fields: rollback=true, rollback_target_commit, project_id, deployment_id, rollback_reason.
     */
    public function makeRollback(string $projectPath): string
    {
        $escapedPath = addslashes($projectPath);

        $php = <<<'PHP'
<?php

// Per-project rollback script (Windows friendly)
// Generated by DeploymentFileGenerator::makeRollback

@header('Content-Type: application/json');
@http_response_code(200);

// Increase limits for long operations
@set_time_limit(0);
@ini_set('max_execution_time', '0');

$projectPath = '__PROJECT_PATH__';
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }
$logFile = $logDir . '/rollback_' . date('Ymd_His') . '.log';

function logLine($msg, $logFile) {
    $line = '[' . date('Y-m-d H:i:s') . "] $msg\n";
    @file_put_contents($logFile, $line, FILE_APPEND);
}

// Read input (JSON preferred)
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];
} else {
    $input = $_POST + $_GET;
}


// Basic validation
$isRollback = isset($input['rollback']) && ($input['rollback'] === true || $input['rollback'] === 'true' || $input['rollback'] === 1 || $input['rollback'] === '1');
$targetCommit = $input['rollback_target_commit'] ?? null;
$reason = $input['rollback_reason'] ?? 'Rollback initiated';

if (!$isRollback) {
    @http_response_code(400);
    $resp = ['status' => 'error', 'message' => 'This endpoint is for rollback operations only'];
    logLine('❌ Not a rollback request', $logFile);
    echo json_encode($resp);
    exit;
}

if (!$targetCommit) {
    @http_response_code(400);
    $resp = ['status' => 'error', 'message' => 'Missing rollback_target_commit'];
    logLine('❌ Missing rollback_target_commit', $logFile);
    echo json_encode($resp);
    exit;
}

// Ensure tools are in PATH (Git/Composer)
putenv('PATH=' . getenv('PATH') . ';C:\\Program Files\\Git\\cmd;C:\\xampp\\php');

logLine('🔄 Rollback started in ' . $projectPath, $logFile);
logLine('Target commit: ' . $targetCommit, $logFile);
logLine('Reason: ' . $reason, $logFile);

// Execute helper
function run($cmd, $logFile) {
    logLine('> ' . $cmd, $logFile);
    exec($cmd . ' 2>&1', $out, $code);
    foreach ($out as $line) { logLine($line, $logFile); }
    if ($code !== 0) { logLine('❌ Command failed: ' . $code, $logFile); }
    return $code === 0;
}

$safeDir = str_replace('\\', '/', $projectPath);
$ok = true;

// Make sure directory exists
if (!is_dir($projectPath)) {
    @http_response_code(500);
    $resp = ['status' => 'error', 'message' => 'Project path not found: ' . $projectPath];
    logLine('❌ Project path not found', $logFile);
    echo json_encode($resp);
    exit;
}

$ok = $ok && run("git config --global --add safe.directory {$safeDir}", $logFile);
$ok = $ok && run("cd /d {$projectPath} && git fetch --all --tags", $logFile);
$ok = $ok && run("cd /d {$projectPath} && git checkout {$targetCommit}", $logFile);
$ok = $ok && run("cd /d {$projectPath} && composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress", $logFile);
$ok = $ok && run("cd /d {$projectPath} && php artisan optimize:clear", $logFile);
$ok = $ok && run("cd /d {$projectPath} && php artisan config:cache", $logFile);
$ok = $ok && run("cd /d {$projectPath} && php artisan route:cache", $logFile);
$ok = $ok && run("cd /d {$projectPath} && php artisan config:clear", $logFile);


// Optional: database rollback step can be risky in prod; keep but log
$dbRolled = run("cd /d {$projectPath} && php artisan migrate:rollback --force", $logFile);
if (!$dbRolled) { logLine('⚠️ Database rollback may have failed or no migrations to rollback', $logFile); }

// Get current commit
$currentCommit = '';
exec("cd /d {$projectPath} && git rev-parse HEAD 2>&1", $out, $code);
if (is_array($out) && isset($out[0])) { $currentCommit = trim($out[0]); }

if ($ok) {
    @http_response_code(200);
    @header('X-Deployment-Status: success');
    $resp = [
        'status' => 'success',
        'message' => 'Rollback completed successfully',
        'is_rollback' => true,
        'rollback_target_commit' => $targetCommit,
        'commit_hash' => $currentCommit,
    ];
    logLine('✅ Rollback finished successfully', $logFile);
    echo json_encode($resp);
} else {
    @http_response_code(500);
    @header('X-Deployment-Status: failed');
    $resp = [
        'status' => 'failed',
        'message' => 'Rollback failed. See logs for details',
        'is_rollback' => true,
        'rollback_target_commit' => $targetCommit,
        'commit_hash' => $currentCommit,
    ];
    logLine('❌ Rollback finished with errors', $logFile);
    echo json_encode($resp);
}
PHP;

        $php = str_replace([
            '__PROJECT_PATH__',
        ], [
            $escapedPath,
        ], $php);

        return $php;
    }
}
