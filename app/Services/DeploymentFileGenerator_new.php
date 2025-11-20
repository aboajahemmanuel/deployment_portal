<?php

namespace App\Services;

class DeploymentFileGenerator
{
    /**
     * Generate the deployment PHP script content.
     */
public function make(string $projectPath, ?string $repoUrl = null): string
{
    $escapedPath = addslashes($projectPath);
    $escapedRepo = addslashes($repoUrl ?? '');

    // Extract folder name for DB name
    $folderName = basename($projectPath);
    $timestamp = time();
    $databaseName = $folderName . "_" . $timestamp;

    $php = <<<PHP
<?php

@header('Content-Type: text/plain');

// -----------------------------
// Create log folder + log file
// -----------------------------
\$logDir = __DIR__ . '/logs';
\$logFile = \$logDir . '/deploy.log';

if (!is_dir(\$logDir)) {
    mkdir(\$logDir, 0777, true);
}

function logMsg(\$text) {
    global \$logFile;
    \$timestamp = date('Y-m-d H:i:s');
    \$line = "[\$timestamp] \$text\\n";
    file_put_contents(\$logFile, \$line, FILE_APPEND);
    echo "[LOG] \$text\\n";
}

// Basic required variables
\$projectPath = '{$escapedPath}';
\$repoUrl = '{$escapedRepo}';
\$databaseName = '{$databaseName}';

logMsg("Deployment started.");
logMsg("Project path: \$projectPath");
logMsg("Repository: \$repoUrl");
logMsg("Database name: \$databaseName");

// Ensure folder exists
if (!is_dir(\$projectPath)) {
    logMsg("Project folder does not exist. Creating...");
    mkdir(\$projectPath, 0777, true);
} else {
    logMsg("Project folder exists.");
}

// Ensure Laravel required directories exist
logMsg("Ensuring Laravel directories exist...");
\$bootstrapCacheDir = \$projectPath . '/bootstrap/cache';
if (!is_dir(\$bootstrapCacheDir)) {
    mkdir(\$bootstrapCacheDir, 0777, true);
}
\$storageDir = \$projectPath . '/storage';
if (!is_dir(\$storageDir)) {
    mkdir(\$storageDir, 0777, true);
}
\$storageFrameworkDir = \$storageDir . '/framework';
if (!is_dir(\$storageFrameworkDir)) {
    mkdir(\$storageFrameworkDir, 0777, true);
}
\$storageFrameworkCacheDir = \$storageFrameworkDir . '/cache';
if (!is_dir(\$storageFrameworkCacheDir)) {
    mkdir(\$storageFrameworkCacheDir, 0777, true);
}
\$storageLogsDir = \$storageDir . '/logs';
if (!is_dir(\$storageLogsDir)) {
    mkdir(\$storageLogsDir, 0777, true);
}

// Add Git & PHP to PATH
putenv("PATH=" . getenv("PATH") . ";C:\\\\Program Files\\\\Git\\\\cmd;C:\\\\xampp\\\\php");
logMsg("Environment PATH updated.");

// Helper function to run commands
function runCmd(\$cmd) {
    logMsg("Running command: \$cmd");
    exec(\$cmd . " 2>&1", \$out, \$code);
    echo implode("\\n", \$out) . "\\n";
    if (\$code === 0) {
        logMsg("Command succeeded.");
    } else {
        logMsg("Command FAILED with code \$code.");
    }
    return \$code === 0;
}

// Set permissions for Laravel directories
logMsg("Setting permissions for Laravel directories...");
runCmd("icacls \"\$projectPath\\bootstrap\\cache\" /grant Everyone:F /T");
runCmd("icacls \"\$projectPath\\storage\" /grant Everyone:F /T");

// Clone repo if empty
if (empty(glob("\$projectPath/*"))) {
    logMsg("Project directory empty. Cloning repository...");
    if (!empty(\$repoUrl)) {
        runCmd("cd /d \$projectPath && git clone \$repoUrl .");
    } else {
        logMsg("❌ Repo URL missing.");
        exit;
    }
} else {
    logMsg("Project not empty. Pulling latest updates...");
    runCmd("cd /d \$projectPath && git pull origin main");
}

// Composer install
logMsg("Running composer install...");
runCmd("cd /d \$projectPath && composer install");

// Ensure permissions for Laravel
logMsg("Setting permissions...");
runCmd("icacls \"\$projectPath\\storage\" /grant Everyone:F /T");
runCmd("icacls \"\$projectPath\\bootstrap\\cache\" /grant Everyone:F /T");

// Generate .env file
logMsg("Generating .env file...");
\$envContent = <<<ENV
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

SESSION_DRIVER=file

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=\$databaseName
DB_USERNAME=root
DB_PASSWORD=

ENV;

file_put_contents("\$projectPath/.env", \$envContent);
logMsg(".env file created.");

// Generate app key
logMsg("Generating app key...");
runCmd("cd /d \$projectPath && php artisan key:generate");

// Create database
logMsg("Creating database...");
// Try local MySQL first
\$createDbCmd = "mysql -u root -e \"CREATE DATABASE IF NOT EXISTS \`{\$databaseName}\`;\"";
if (!runCmd(\$createDbCmd)) {
    // If local fails, try with password (you might want to customize this)
    logMsg("Retrying database creation with password...");
    \$createDbCmd = "mysql -u root -proot -e \"CREATE DATABASE IF NOT EXISTS \`{\$databaseName}\`;\"";
    runCmd(\$createDbCmd);
}

// Update .env with database credentials if needed
logMsg("Updating .env with database credentials...");
\$envPath = "\$projectPath/.env";
\$envContent = file_get_contents(\$envPath);
// Add or update database credentials
\$envContent = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME=root', \$envContent);
\$envContent = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD=', \$envContent);
file_put_contents(\$envPath, \$envContent);

// Run migrations
logMsg("Running database migrations...");
// Ensure permissions before running migrations
runCmd("icacls \"\$projectPath\\bootstrap\\cache\" /grant Everyone:F /T");
runCmd("icacls \"\$projectPath\\storage\" /grant Everyone:F /T");
runCmd("cd /d \$projectPath && php artisan migrate --force");

// Ensure permissions for Laravel
logMsg("Setting permissions...");
runCmd("icacls \"\$projectPath\\storage\" /grant Everyone:F /T");
runCmd("icacls \"\$projectPath\\bootstrap\\cache\" /grant Everyone:F /T");


// Fix permissions
logMsg("Setting permissions for storage and cache...");
runCmd("cd /d \$projectPath && icacls \"storage\" /grant Everyone:F /T");
runCmd("cd /d \$projectPath && icacls \"bootstrap\\cache\" /grant Everyone:F /T");

// Clear and cache Laravel
logMsg("Optimizing Laravel...");
runCmd("cd /d \$projectPath && php artisan optimize:clear");
runCmd("cd /d \$projectPath && php artisan config:cache");
runCmd("cd /d \$projectPath && php artisan route:cache");

logMsg("Deployment completed successfully.");
echo "\\n✅ Deployment completed.\\n";

PHP;

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
@ini_set('default_socket_timeout', '300');
putenv('COMPOSER_PROCESS_TIMEOUT=2000');

$projectPath = '__PROJECT_PATH__';
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }
$logFile = $logDir . '/rollback_' . date('Ymd_His') . '.log';
$aggregateLog = __DIR__ . '/deploy-log.txt';
$safeDir = str_replace('\\', '/', $projectPath);

// Auto-detect server type and paths based on project location
$serverType = 'unknown';
$htdocsPath = '';
$phpPath = '';

// Determine server type based on project path
if (stripos($projectPath, 'xampp') !== false) {
    $serverType = 'xampp';
    $htdocsPath = 'C:\\xampp\\htdocs';
    $phpPath = 'C:\\xampp\\php';
} elseif (stripos($projectPath, 'wamp64') !== false) {
    $serverType = 'wamp64';
    $htdocsPath = 'C:\\wamp64\\www';
    $phpPath = 'C:\\wamp64\\bin\\php\\php' . phpversion();
} elseif (stripos($projectPath, 'wamp') !== false) {
    $serverType = 'wamp';
    $htdocsPath = 'C:\\wamp\\www';
    $phpPath = 'C:\\wamp\\bin\\php\\php' . phpversion();
} else {
    // Fallback to checking common server setups
    if (is_dir('C:\\xampp\\htdocs')) {
        $serverType = 'xampp';
        $htdocsPath = 'C:\\xampp\\htdocs';
        $phpPath = 'C:\\xampp\\php';
    }
    elseif (is_dir('C:\\wamp64\\www')) {
        $serverType = 'wamp64';
        $htdocsPath = 'C:\\wamp64\\www';
        $phpPath = 'C:\\wamp64\\bin\\php\\php' . phpversion();
    }
    elseif (is_dir('C:\\wamp\\www')) {
        $serverType = 'wamp';
        $htdocsPath = 'C:\\wamp\\www';
        $phpPath = 'C:\\wamp\\bin\\php\\php' . phpversion();
    }
    else {
        $htdocsPath = $_SERVER['DOCUMENT_ROOT'] ?? 'C:\\inetpub\\wwwroot';
        $phpPath = dirname(PHP_BINARY);
    }
}

// Add Git, PHP, and Node to PATH for this script
$gitPath = 'C:\\Program Files\\Git\\cmd';
$nodePath = 'C:\\Program Files\\nodejs';
putenv("PATH=" . getenv("PATH") . ";{$gitPath};{$phpPath};{$nodePath}");

function logLine($msg, $logFile, $aggregateLog = null) {
    $line = '[' . date('Y-m-d H:i:s') . "] $msg\n";
    @file_put_contents($logFile, $line, FILE_APPEND);
    if ($aggregateLog) {
        @file_put_contents($aggregateLog, $line, FILE_APPEND);
    }
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
$projectType = $input['project_type'] ?? 'laravel';

if (!$isRollback) {
    @http_response_code(400);
    $resp = ['status' => 'error', 'message' => 'This endpoint is for rollback operations only'];
    logLine('❌ Not a rollback request', $logFile, $aggregateLog);
    echo json_encode($resp);
    exit;
}

if (!$targetCommit) {
    @http_response_code(400);
    $resp = ['status' => 'error', 'message' => 'Missing rollback_target_commit'];
    logLine('❌ Missing rollback_target_commit', $logFile, $aggregateLog);
    echo json_encode($resp);
    exit;
}

logLine("🔍 Server type: {$serverType}", $logFile, $aggregateLog);
logLine("📁 Server base path: {$htdocsPath}", $logFile, $aggregateLog);
logLine("🐘 PHP path: {$phpPath}", $logFile, $aggregateLog);
logLine('🔄 Rollback started in ' . $projectPath, $logFile, $aggregateLog);
logLine('Target commit: ' . $targetCommit, $logFile, $aggregateLog);
logLine('Project type: ' . $projectType, $logFile, $aggregateLog);
logLine('Reason: ' . $reason, $logFile, $aggregateLog);

// Execute helper
function run($cmd, $logFile, $aggregateLog = null) {
    logLine('> ' . $cmd, $logFile, $aggregateLog);
    exec($cmd . ' 2>&1', $out, $code);
    foreach ($out as $line) { logLine($line, $logFile, $aggregateLog); }
    if ($code !== 0) { logLine('❌ Command failed: ' . $code, $logFile, $aggregateLog); }
    return $code === 0;
}

$ok = true;

// Make sure directory exists
if (!is_dir($projectPath)) {
    @http_response_code(500);
    $resp = ['status' => 'error', 'message' => 'Project path not found: ' . $projectPath];
    logLine('❌ Project path not found', $logFile, $aggregateLog);
    echo json_encode($resp);
    exit;
}

$ok = $ok && run("git config --global --add safe.directory {$safeDir}", $logFile, $aggregateLog);
$ok = $ok && run("cd /d {$projectPath} && git fetch --all --tags", $logFile, $aggregateLog);
$ok = $ok && run("cd /d {$projectPath} && git checkout {$targetCommit}", $logFile, $aggregateLog);

// Install dependencies based on project type and existence of dependency files
if (($projectType === 'laravel' || $projectType === 'php') && file_exists($projectPath . '/composer.json')) {
    $ok = $ok && run("cd /d {$projectPath} && composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress", $logFile, $aggregateLog);
    // Add seeder command after composer install for Laravel projects
    if ($projectType === 'laravel' && file_exists($projectPath . '/artisan')) {
        $ok = $ok && run("cd /d {$projectPath} && php artisan db:seed --class=DatabaseSeeder --force", $logFile, $aggregateLog);
    }
} elseif ($projectType === 'nodejs' && file_exists($projectPath . '/package.json')) {
    if (file_exists($projectPath . '/package-lock.json')) {
        $ok = $ok && run("cd /d {$projectPath} && npm ci --no-audit --no-fund --silent", $logFile, $aggregateLog);
    } else {
        $ok = $ok && run("cd /d {$projectPath} && npm install --no-audit --no-fund --silent", $logFile, $aggregateLog);
    }
}

// Run project-type specific commands
if ($projectType === 'laravel' && file_exists($projectPath . '/artisan')) {
    $ok = $ok && run("cd /d {$projectPath} && php artisan optimize:clear", $logFile, $aggregateLog);
    $ok = $ok && run("cd /d {$projectPath} && php artisan config:cache", $logFile, $aggregateLog);
    $ok = $ok && run("cd /d {$projectPath} && php artisan route:cache", $logFile, $aggregateLog);
    $ok = $ok && run("cd /d {$projectPath} && php artisan config:clear", $logFile, $aggregateLog);
    $ok = $ok && run("cd /d {$projectPath} && php artisan db:seed --class=DatabaseSeeder --force", $logFile, $aggregateLog);
    
    // Optional: database rollback step can be risky in prod; keep but log
    $dbRolled = run("cd /d {$projectPath} && php artisan migrate:rollback --force", $logFile, $aggregateLog);
    if (!$dbRolled) { logLine('⚠️ Database rollback may have failed or no migrations to rollback', $logFile, $aggregateLog); }
} elseif ($projectType === 'nodejs') {
    // For Node.js projects, rebuild if there's a build script
    if (file_exists($projectPath . '/package.json')) {
        $packageJson = json_decode(file_get_contents($projectPath . '/package.json'), true);
        if (isset($packageJson['scripts']['build'])) {
            $ok = $ok && run("cd /d {$projectPath} && npm run build --if-present", $logFile, $aggregateLog);
        }
    }
}

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
        'project_type' => $projectType,
    ];
    logLine('✅ Rollback finished successfully', $logFile, $aggregateLog);
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
        'project_type' => $projectType,
    ];
    logLine('❌ Rollback finished with errors', $logFile, $aggregateLog);
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

    /**
     * Process environment variables to properly escape quotes for .env file format
     */
    public static function processEnvVariables(string $envVariables): string
    {
        $lines = explode("\n", $envVariables);
        $processedLines = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || strpos($line, '#') === 0) {
                $processedLines[] = $line;
                continue;
            }
            
            // Check if this is a key=value pair
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // If value is quoted, ensure proper escaping
                if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match('/^\'(.*)\'$/', $value, $matches)) {
                    $innerValue = $matches[1];
                    // Escape quotes within the value
                    $escapedValue = str_replace('"', '\"', $innerValue);
                    $processedLines[] = $key . '="' . $escapedValue . '"';
                } else {
                    // For unquoted values, just add as is
                    $processedLines[] = $key . '=' . $value;
                }
            } else {
                // Not a key=value pair, add as is
                $processedLines[] = $line;
            }
        }
        
        return implode("\n", $processedLines);
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Copy directory contents recursively
     */
    private function copyDirectory(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }
        
        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }
        
        $files = array_diff(scandir($source), ['.', '..']);
        foreach ($files as $file) {
            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $destinationPath = $destination . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destinationPath);
            } else {
                copy($sourcePath, $destinationPath);
            }
        }
        
        return true;
    }
}