<?php

@header('Content-Type: text/plain');

// -----------------------------
// Create log folder + log file
// -----------------------------
$logDir = __DIR__ . '/logs';
$logFile = $logDir . '/deploy.log';

if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

function logMsg($text) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $text\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    echo "[LOG] $text\n";
}

// Basic required variables
$projectPath = 'C:\\xampp\\htdocs\\test_project';
$repoUrl = 'https://github.com/laravel/laravel.git';
$databaseName = 'test_project_1763478030';

logMsg("Deployment started.");
logMsg("Project path: $projectPath");
logMsg("Repository: $repoUrl");
logMsg("Database name: $databaseName");

// Ensure folder exists
if (!is_dir($projectPath)) {
    logMsg("Project folder does not exist. Creating...");
    mkdir($projectPath, 0777, true);
} else {
    logMsg("Project folder exists.");
}

// Ensure Laravel required directories exist
logMsg("Ensuring Laravel directories exist...");
$bootstrapCacheDir = $projectPath . '/bootstrap/cache';
if (!is_dir($bootstrapCacheDir)) {
    mkdir($bootstrapCacheDir, 0777, true);
}
$storageDir = $projectPath . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0777, true);
}
$storageFrameworkDir = $storageDir . '/framework';
if (!is_dir($storageFrameworkDir)) {
    mkdir($storageFrameworkDir, 0777, true);
}
$storageFrameworkCacheDir = $storageFrameworkDir . '/cache';
if (!is_dir($storageFrameworkCacheDir)) {
    mkdir($storageFrameworkCacheDir, 0777, true);
}
$storageLogsDir = $storageDir . '/logs';
if (!is_dir($storageLogsDir)) {
    mkdir($storageLogsDir, 0777, true);
}

// Add Git & PHP to PATH
putenv("PATH=" . getenv("PATH") . ";C:\\Program Files\\Git\\cmd;C:\\xampp\\php");
logMsg("Environment PATH updated.");

// Helper function to run commands
function runCmd($cmd) {
    logMsg("Running command: $cmd");
    exec($cmd . " 2>&1", $out, $code);
    echo implode("\n", $out) . "\n";
    if ($code === 0) {
        logMsg("Command succeeded.");
    } else {
        logMsg("Command FAILED with code $code.");
    }
    return $code === 0;
}

// Set permissions for Laravel directories
logMsg("Setting permissions for Laravel directories...");
runCmd("icacls \"$projectPath\bootstrap\cache\" /grant Everyone:F /T");
runCmd("icacls \"$projectPath\storage\" /grant Everyone:F /T");

// Clone repo if empty
if (empty(glob("$projectPath/*"))) {
    logMsg("Project directory empty. Cloning repository...");
    if (!empty($repoUrl)) {
        runCmd("cd /d $projectPath && git clone $repoUrl .");
    } else {
        logMsg("❌ Repo URL missing.");
        exit;
    }
} else {
    logMsg("Project not empty. Pulling latest updates...");
    runCmd("cd /d $projectPath && git pull origin main");
}

// Composer install
logMsg("Running composer install...");
runCmd("cd /d $projectPath && composer install");

// Ensure permissions for Laravel
logMsg("Setting permissions...");
runCmd("icacls \"$projectPath\storage\" /grant Everyone:F /T");
runCmd("icacls \"$projectPath\bootstrap\cache\" /grant Everyone:F /T");

// Generate .env file
logMsg("Generating .env file...");
$envContent = <<<ENV
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

SESSION_DRIVER=file

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$databaseName
DB_USERNAME=root
DB_PASSWORD=

ENV;

file_put_contents("$projectPath/.env", $envContent);
logMsg(".env file created.");

// Generate app key
logMsg("Generating app key...");
runCmd("cd /d $projectPath && php artisan key:generate");

// Create database
logMsg("Creating database...");
// Try local MySQL first
$createDbCmd = "mysql -u root -e \"CREATE DATABASE IF NOT EXISTS \`{$databaseName}\`;\"";
if (!runCmd($createDbCmd)) {
    // If local fails, try with password (you might want to customize this)
    logMsg("Retrying database creation with password...");
    $createDbCmd = "mysql -u root -proot -e \"CREATE DATABASE IF NOT EXISTS \`{$databaseName}\`;\"";
    runCmd($createDbCmd);
}

// Update .env with database credentials if needed
logMsg("Updating .env with database credentials...");
$envPath = "$projectPath/.env";
$envContent = file_get_contents($envPath);
// Add or update database credentials
$envContent = preg_replace('/DB_USERNAME=.*/', 'DB_USERNAME=root', $envContent);
$envContent = preg_replace('/DB_PASSWORD=.*/', 'DB_PASSWORD=', $envContent);
file_put_contents($envPath, $envContent);

// Run migrations
logMsg("Running database migrations...");
// Ensure permissions before running migrations
runCmd("icacls \"$projectPath\bootstrap\cache\" /grant Everyone:F /T");
runCmd("icacls \"$projectPath\storage\" /grant Everyone:F /T");
runCmd("cd /d $projectPath && php artisan migrate --force");

// Ensure permissions for Laravel
logMsg("Setting permissions...");
runCmd("icacls \"$projectPath\storage\" /grant Everyone:F /T");
runCmd("icacls \"$projectPath\bootstrap\cache\" /grant Everyone:F /T");


// Fix permissions
logMsg("Setting permissions for storage and cache...");
runCmd("cd /d $projectPath && icacls \"storage\" /grant Everyone:F /T");
runCmd("cd /d $projectPath && icacls \"bootstrap\cache\" /grant Everyone:F /T");

// Clear and cache Laravel
logMsg("Optimizing Laravel...");
runCmd("cd /d $projectPath && php artisan optimize:clear");
runCmd("cd /d $projectPath && php artisan config:cache");
runCmd("cd /d $projectPath && php artisan route:cache");

logMsg("Deployment completed successfully.");
echo "\n✅ Deployment completed.\n";
