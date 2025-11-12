<?php

namespace App\Services\SecurityScanners;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Exception;

class SastScanner implements SecurityScannerInterface
{
    protected string $toolName = 'PHPStan Security';
    protected string $scanType = 'sast';

    public function scan(array $config): array
    {
        $projectPath = $config['project_path'];
        $findings = [];

        try {
            // Clone repository if needed
            $this->ensureCodeAvailable($config);

            // Run PHPStan with security rules
            $findings = array_merge($findings, $this->runPhpStanSecurity($projectPath));
            
            // Run custom security pattern matching
            $findings = array_merge($findings, $this->runSecurityPatterns($projectPath));
            
        } catch (Exception $e) {
            Log::error('SAST scan failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $findings;
    }

    protected function ensureCodeAvailable(array $config): void
    {
        $projectPath = $config['project_path'];
        $repositoryUrl = $config['repository_url'];
        $branch = $config['branch'];

        if (!is_dir($projectPath . '/.git')) {
            // Clone the repository
            $cloneCommand = "git clone -b {$branch} {$repositoryUrl} {$projectPath}";
            $result = Process::run($cloneCommand);
            
            if (!$result->successful()) {
                throw new Exception("Failed to clone repository: " . $result->errorOutput());
            }
        } else {
            // Pull latest changes
            $pullCommand = "cd {$projectPath} && git pull origin {$branch}";
            Process::run($pullCommand);
        }
    }

    protected function runPhpStanSecurity(string $projectPath): array
    {
        $findings = [];
        
        // Check if PHPStan is available
        if (!$this->isPhpStanAvailable()) {
            return $this->createMockSastFindings($projectPath);
        }

        $command = "cd {$projectPath} && phpstan analyse --error-format=json --no-progress";
        $result = Process::run($command);

        if ($result->successful()) {
            $output = json_decode($result->output(), true);
            
            if (isset($output['files'])) {
                foreach ($output['files'] as $file => $fileErrors) {
                    foreach ($fileErrors['messages'] as $error) {
                        $findings[] = [
                            'scan_type' => $this->scanType,
                            'tool_name' => $this->toolName,
                            'severity' => $this->mapPhpStanSeverity($error['message']),
                            'title' => 'Static Analysis Issue',
                            'description' => $error['message'],
                            'file_path' => str_replace($projectPath, '', $file),
                            'line_number' => $error['line'] ?? null,
                            'remediation_advice' => $this->getRemediationAdvice($error['message']),
                            'status' => 'open',
                            'first_detected_at' => now(),
                            'last_seen_at' => now(),
                        ];
                    }
                }
            }
        }

        return $findings;
    }

    protected function runSecurityPatterns(string $projectPath): array
    {
        $findings = [];
        $securityPatterns = $this->getSecurityPatterns();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                $lines = explode("\n", $content);
                
                foreach ($securityPatterns as $pattern) {
                    foreach ($lines as $lineNumber => $line) {
                        if (preg_match($pattern['regex'], $line)) {
                            $findings[] = [
                                'scan_type' => $this->scanType,
                                'tool_name' => $this->toolName,
                                'severity' => $pattern['severity'],
                                'title' => $pattern['title'],
                                'description' => $pattern['description'],
                                'file_path' => str_replace($projectPath, '', $file->getPathname()),
                                'line_number' => $lineNumber + 1,
                                'code_snippet' => trim($line),
                                'remediation_advice' => $pattern['remediation'],
                                'status' => 'open',
                                'first_detected_at' => now(),
                                'last_seen_at' => now(),
                            ];
                        }
                    }
                }
            }
        }

        return $findings;
    }

    protected function getSecurityPatterns(): array
    {
        return [
            [
                'regex' => '/\$_(?:GET|POST|REQUEST|COOKIE)\[.*?\].*?(?:echo|print|file_get_contents|include|require)/i',
                'severity' => 'high',
                'title' => 'Potential XSS/LFI Vulnerability',
                'description' => 'Direct output or file inclusion of user input without sanitization',
                'remediation' => 'Sanitize and validate all user inputs before use'
            ],
            [
                'regex' => '/(?:mysql_query|mysqli_query).*?\$_(?:GET|POST|REQUEST)/i',
                'severity' => 'critical',
                'title' => 'SQL Injection Vulnerability',
                'description' => 'Direct concatenation of user input in SQL queries',
                'remediation' => 'Use prepared statements or parameterized queries'
            ],
            [
                'regex' => '/eval\s*\(/i',
                'severity' => 'critical',
                'title' => 'Code Injection Risk',
                'description' => 'Use of eval() function can lead to code injection',
                'remediation' => 'Avoid using eval() and find alternative solutions'
            ],
            [
                'regex' => '/(?:password|secret|key|token)\s*=\s*["\'][^"\']{8,}["\']/i',
                'severity' => 'high',
                'title' => 'Hardcoded Credentials',
                'description' => 'Potential hardcoded password or secret in source code',
                'remediation' => 'Move credentials to environment variables or secure configuration'
            ],
            [
                'regex' => '/file_get_contents\s*\(\s*["\']https?:\/\//i',
                'severity' => 'medium',
                'title' => 'Unvalidated Remote File Access',
                'description' => 'Remote file access without proper validation',
                'remediation' => 'Validate URLs and use proper HTTP client with security checks'
            ]
        ];
    }

    protected function mapPhpStanSeverity(string $message): string
    {
        if (stripos($message, 'security') !== false) return 'high';
        if (stripos($message, 'error') !== false) return 'medium';
        return 'low';
    }

    protected function getRemediationAdvice(string $message): string
    {
        $adviceMap = [
            'undefined' => 'Define the variable or check if it exists before use',
            'type' => 'Ensure proper type checking and validation',
            'null' => 'Add null checks before accessing properties or methods',
            'array' => 'Validate array structure and keys before access',
        ];

        foreach ($adviceMap as $keyword => $advice) {
            if (stripos($message, $keyword) !== false) {
                return $advice;
            }
        }

        return 'Review the code and apply appropriate security measures';
    }

    protected function isPhpStanAvailable(): bool
    {
        $result = Process::run('phpstan --version');
        return $result->successful();
    }

    protected function createMockSastFindings(string $projectPath): array
    {
        // Return mock findings for demonstration when PHPStan is not available
        return [
            [
                'scan_type' => $this->scanType,
                'tool_name' => $this->toolName . ' (Demo)',
                'severity' => 'medium',
                'title' => 'Demo SAST Finding',
                'description' => 'This is a demonstration finding. Install PHPStan for real analysis.',
                'file_path' => '/demo/example.php',
                'line_number' => 1,
                'remediation_advice' => 'Install and configure PHPStan for actual security analysis',
                'status' => 'open',
                'first_detected_at' => now(),
                'last_seen_at' => now(),
            ]
        ];
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function getScanType(): string
    {
        return $this->scanType;
    }

    public function isAvailable(): bool
    {
        return true; // Always available (falls back to pattern matching)
    }
}
