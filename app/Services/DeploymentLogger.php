<?php

namespace App\Services;

use App\Models\DeploymentLog;
use App\Models\Deployment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as LaravelLog;

class DeploymentLogger
{
    protected $deployment;

    public function __construct(Deployment $deployment)
    {
        $this->deployment = $deployment;
    }

    /**
     * Log an info message
     */
    public function info(string $message, array $context = []): DeploymentLog
    {
        return $this->log('info', $message, $context);
    }

    /**
     * Log a warning message
     */
    public function warning(string $message, array $context = []): DeploymentLog
    {
        return $this->log('warning', $message, $context);
    }

    /**
     * Log an error message
     */
    public function error(string $message, array $context = []): DeploymentLog
    {
        return $this->log('error', $message, $context);
    }

    /**
     * Log a debug message
     */
    public function debug(string $message, array $context = []): DeploymentLog
    {
        return $this->log('debug', $message, $context);
    }

    /**
     * Log a message with the specified level
     */
    public function log(string $level, string $message, array $context = []): DeploymentLog
    {
        // Add common context data
        $context = array_merge([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'project_id' => $this->deployment->project_id,
            'deployment_id' => $this->deployment->id,
            'timestamp' => now()->toISOString(),
        ], $context);

        // Create the log entry in the database
        $logEntry = DeploymentLog::create([
            'deployment_id' => $this->deployment->id,
            'log_level' => $level,
            'message' => $message,
            'context' => $context,
        ]);

        // Also log to Laravel's default logging system
        LaravelLog::log($level, "[Deployment #{$this->deployment->id}] {$message}", $context);

        return $logEntry;
    }

    /**
     * Log HTTP request details
     */
    public function logHttpRequest($url, string $method, array $headers = [], array $params = []): DeploymentLog
    {
        // Handle null or empty URL gracefully
        $safeUrl = is_string($url) ? $url : '(invalid URL)';
        if ($safeUrl === '') {
            $safeUrl = '(empty URL)';
        }
        
        $message = "HTTP Request: {$method} {$safeUrl}";
        $context = [
            'http_method' => $method,
            'url' => $url,
            'url_type' => gettype($url),
            'headers' => $headers,
            'params' => $params,
        ];

        return $this->info($message, $context);
    }

    /**
     * Log HTTP response details
     */
    public function logHttpResponse(int $statusCode, string $body, array $headers = []): DeploymentLog
    {
        $message = "HTTP Response: Status {$statusCode}";
        $context = [
            'status_code' => $statusCode,
            'headers' => $headers,
            'body_preview' => substr($body, 0, 500), // Only store first 500 chars to avoid huge logs
            'body_length' => strlen($body),
        ];

        if ($statusCode >= 200 && $statusCode < 300) {
            return $this->info($message, $context);
        } elseif ($statusCode >= 400) {
            return $this->error($message, $context);
        } else {
            return $this->warning($message, $context);
        }
    }

    /**
     * Log an exception
     */
    public function logException(\Exception $exception): DeploymentLog
    {
        $message = "Exception: " . get_class($exception) . " - " . $exception->getMessage();
        $context = [
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        return $this->error($message, $context);
    }

    /**
     * Get all logs for this deployment
     */
    public function getLogs()
    {
        return $this->deployment->logs()->orderBy('created_at')->get();
    }

    /**
     * Get logs filtered by level
     */
    public function getLogsByLevel(string $level)
    {
        return $this->deployment->logs()->where('log_level', $level)->orderBy('created_at')->get();
    }
}