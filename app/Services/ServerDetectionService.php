<?php

namespace App\Services;

class ServerDetectionService
{
    /**
     * Detect the server type and return appropriate paths
     */
    public static function detectServer(): array
    {
        $serverType = 'unknown';
        $webRoot = '';
        $phpPath = '';

        // Check for XAMPP
        if (is_dir('C:\\xampp\\htdocs')) {
            $serverType = 'xampp';
            $webRoot = 'C:\\xampp\\htdocs';
            $phpPath = 'C:\\xampp\\php';
        }
        // Check for WAMP64
        elseif (is_dir('C:\\wamp64\\www')) {
            $serverType = 'wamp64';
            $webRoot = 'C:\\wamp64\\www';
            $phpPath = self::detectWampPhpPath('C:\\wamp64\\bin\\php');
        }
        // Check for WAMP
        elseif (is_dir('C:\\wamp\\www')) {
            $serverType = 'wamp';
            $webRoot = 'C:\\wamp\\www';
            $phpPath = self::detectWampPhpPath('C:\\wamp\\bin\\php');
        }
        // Check for LARAGON
        elseif (is_dir('C:\\laragon\\www')) {
            $serverType = 'laragon';
            $webRoot = 'C:\\laragon\\www';
            $phpPath = 'C:\\laragon\\bin\\php';
        }
        // Check for MAMP (Windows)
        elseif (is_dir('C:\\MAMP\\htdocs')) {
            $serverType = 'mamp';
            $webRoot = 'C:\\MAMP\\htdocs';
            $phpPath = 'C:\\MAMP\\bin\\php';
        }
        // Fallback to common paths
        else {
            $webRoot = $_SERVER['DOCUMENT_ROOT'] ?? 'C:\\inetpub\\wwwroot';
            $phpPath = dirname(PHP_BINARY);
            
            // Try to detect from current script path
            if (strpos(__DIR__, 'xampp') !== false) {
                $serverType = 'xampp';
                $webRoot = 'C:\\xampp\\htdocs';
                $phpPath = 'C:\\xampp\\php';
            } elseif (strpos(__DIR__, 'wamp64') !== false) {
                $serverType = 'wamp64';
                $webRoot = 'C:\\wamp64\\www';
                $phpPath = self::detectWampPhpPath('C:\\wamp64\\bin\\php');
            } elseif (strpos(__DIR__, 'wamp') !== false) {
                $serverType = 'wamp';
                $webRoot = 'C:\\wamp\\www';
                $phpPath = self::detectWampPhpPath('C:\\wamp\\bin\\php');
            }
        }

        return [
            'type' => $serverType,
            'web_root' => $webRoot,
            'php_path' => $phpPath,
            'git_path' => 'C:\\Program Files\\Git\\cmd',
            'node_path' => 'C:\\Program Files\\nodejs'
        ];
    }

    /**
     * Detect the correct PHP version path for WAMP
     */
    private static function detectWampPhpPath(string $basePath): string
    {
        if (!is_dir($basePath)) {
            return dirname(PHP_BINARY);
        }

        // Get current PHP version
        $currentVersion = PHP_VERSION;
        $majorMinor = implode('.', array_slice(explode('.', $currentVersion), 0, 2));

        // Try to find matching PHP version directory
        $phpDirs = glob($basePath . '\\php*');
        
        foreach ($phpDirs as $phpDir) {
            if (strpos($phpDir, $majorMinor) !== false) {
                return $phpDir;
            }
        }

        // Fallback to the latest PHP version available
        if (!empty($phpDirs)) {
            return end($phpDirs);
        }

        return dirname(PHP_BINARY);
    }

    /**
     * Get web root path for the current server
     */
    public static function getWebRoot(): string
    {
        return self::detectServer()['web_root'];
    }

    /**
     * Get PHP path for the current server
     */
    public static function getPhpPath(): string
    {
        return self::detectServer()['php_path'];
    }

    /**
     * Get server type
     */
    public static function getServerType(): string
    {
        return self::detectServer()['type'];
    }
}
