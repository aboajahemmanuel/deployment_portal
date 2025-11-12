<?php

namespace App\Services\SecurityScanners;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Exception;

class SecretScanner implements SecurityScannerInterface
{
    protected string $toolName = 'Secret Pattern Scanner';
    protected string $scanType = 'secrets';

    public function scan(array $config): array
    {
        $projectPath = $config['project_path'];
        $findings = [];

        try {
            // Ensure code is available
            $this->ensureCodeAvailable($config);

            // Run secret detection patterns
            $findings = $this->scanForSecrets($projectPath);
            
        } catch (Exception $e) {
            Log::error('Secret scan failed', ['error' => $e->getMessage()]);
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
            $cloneCommand = "git clone -b {$branch} {$repositoryUrl} {$projectPath}";
            $result = Process::run($cloneCommand);
            
            if (!$result->successful()) {
                throw new Exception("Failed to clone repository: " . $result->errorOutput());
            }
        }
    }

    protected function scanForSecrets(string $projectPath): array
    {
        $findings = [];
        $secretPatterns = $this->getSecretPatterns();
        $excludePatterns = $this->getExcludePatterns();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            
            $relativePath = str_replace($projectPath, '', $file->getPathname());
            
            // Skip excluded files/directories
            if ($this->shouldExcludeFile($relativePath, $excludePatterns)) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $lines = explode("\n", $content);
            
            foreach ($secretPatterns as $pattern) {
                foreach ($lines as $lineNumber => $line) {
                    if (preg_match($pattern['regex'], $line, $matches)) {
                        // Additional validation to reduce false positives
                        if ($this->validateSecret($pattern, $line, $matches)) {
                            $findings[] = [
                                'scan_type' => $this->scanType,
                                'tool_name' => $this->toolName,
                                'severity' => $pattern['severity'],
                                'title' => $pattern['title'],
                                'description' => $pattern['description'],
                                'file_path' => $relativePath,
                                'line_number' => $lineNumber + 1,
                                'code_snippet' => $this->maskSecret($line),
                                'remediation_advice' => $pattern['remediation'],
                                'status' => 'open',
                                'metadata' => [
                                    'secret_type' => $pattern['type'],
                                    'entropy' => $this->calculateEntropy($matches[0] ?? ''),
                                ],
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

    protected function getSecretPatterns(): array
    {
        return [
            [
                'type' => 'aws_access_key',
                'regex' => '/AKIA[0-9A-Z]{16}/',
                'severity' => 'critical',
                'title' => 'AWS Access Key ID',
                'description' => 'AWS Access Key ID found in source code',
                'remediation' => 'Remove AWS credentials from code and use IAM roles or environment variables'
            ],
            [
                'type' => 'aws_secret_key',
                'regex' => '/[A-Za-z0-9\/\+=]{40}/',
                'severity' => 'critical',
                'title' => 'Potential AWS Secret Access Key',
                'description' => 'Potential AWS Secret Access Key found',
                'remediation' => 'Remove AWS credentials from code and use secure credential management'
            ],
            [
                'type' => 'github_token',
                'regex' => '/gh[pousr]_[A-Za-z0-9_]{36,255}/',
                'severity' => 'critical',
                'title' => 'GitHub Personal Access Token',
                'description' => 'GitHub Personal Access Token found in source code',
                'remediation' => 'Revoke the token and use secure credential storage'
            ],
            [
                'type' => 'slack_token',
                'regex' => '/xox[baprs]-([0-9a-zA-Z]{10,48})?/',
                'severity' => 'high',
                'title' => 'Slack Token',
                'description' => 'Slack API token found in source code',
                'remediation' => 'Remove Slack tokens from code and use environment variables'
            ],
            [
                'type' => 'generic_api_key',
                'regex' => '/(?:api[_-]?key|apikey|secret[_-]?key|secretkey)\s*[:=]\s*["\']([a-zA-Z0-9_\-]{20,})["\']/',
                'severity' => 'high',
                'title' => 'Generic API Key',
                'description' => 'Potential API key or secret found in source code',
                'remediation' => 'Move API keys to environment variables or secure configuration'
            ],
            [
                'type' => 'database_url',
                'regex' => '/(?:mysql|postgres|mongodb):\/\/[^\s]+:[^\s]+@[^\s]+/',
                'severity' => 'high',
                'title' => 'Database Connection String',
                'description' => 'Database connection string with credentials found',
                'remediation' => 'Use environment variables for database credentials'
            ],
            [
                'type' => 'private_key',
                'regex' => '/-----BEGIN (?:RSA |EC |DSA |OPENSSH )?PRIVATE KEY-----/',
                'severity' => 'critical',
                'title' => 'Private Key',
                'description' => 'Private key found in source code',
                'remediation' => 'Remove private keys from code and store securely'
            ],
            [
                'type' => 'jwt_token',
                'regex' => '/eyJ[A-Za-z0-9_\/+-]*\.eyJ[A-Za-z0-9_\/+-]*\.[A-Za-z0-9_\/+-]*/',
                'severity' => 'medium',
                'title' => 'JWT Token',
                'description' => 'JSON Web Token found in source code',
                'remediation' => 'Avoid hardcoding JWT tokens in source code'
            ],
            [
                'type' => 'password',
                'regex' => '/(?:password|passwd|pwd)\s*[:=]\s*["\']([^"\']{8,})["\']/',
                'severity' => 'high',
                'title' => 'Hardcoded Password',
                'description' => 'Hardcoded password found in source code',
                'remediation' => 'Use secure password storage and environment variables'
            ]
        ];
    }

    protected function getExcludePatterns(): array
    {
        return [
            '/\.git/',
            '/node_modules/',
            '/vendor/',
            '/storage/logs/',
            '/bootstrap/cache/',
            '/\.env\.example',
            '/\.md$/',
            '/\.txt$/',
            '/\.log$/',
            '/\.json$/',
            '/\.lock$/',
            '/test/',
            '/tests/',
            '/spec/',
            '/docs/',
            '/documentation/',
        ];
    }

    protected function shouldExcludeFile(string $filePath, array $excludePatterns): bool
    {
        foreach ($excludePatterns as $pattern) {
            if (preg_match($pattern, $filePath)) {
                return true;
            }
        }
        return false;
    }

    protected function validateSecret(array $pattern, string $line, array $matches): bool
    {
        // Skip obvious false positives
        $falsePositives = [
            'example',
            'test',
            'demo',
            'placeholder',
            'your_key_here',
            'insert_key_here',
            'replace_with',
            'todo',
            'fixme',
            'xxx',
            'yyy',
            'zzz'
        ];

        $matchedText = strtolower($matches[0] ?? '');
        
        foreach ($falsePositives as $fp) {
            if (strpos($matchedText, $fp) !== false) {
                return false;
            }
        }

        // Check entropy for generic patterns
        if ($pattern['type'] === 'generic_api_key') {
            $entropy = $this->calculateEntropy($matchedText);
            return $entropy > 3.5; // Threshold for randomness
        }

        return true;
    }

    protected function calculateEntropy(string $string): float
    {
        if (empty($string)) return 0;
        
        $chars = array_count_values(str_split($string));
        $length = strlen($string);
        $entropy = 0;
        
        foreach ($chars as $count) {
            $probability = $count / $length;
            $entropy -= $probability * log($probability, 2);
        }
        
        return $entropy;
    }

    protected function maskSecret(string $line): string
    {
        // Mask potential secrets in the code snippet
        $patterns = [
            '/(["\'][^"\']*?)([a-zA-Z0-9_\-]{8,})([^"\']*?["\'])/' => '$1***MASKED***$3',
            '/([a-zA-Z0-9_\-]{20,})/' => '***MASKED***'
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $line = preg_replace($pattern, $replacement, $line);
        }
        
        return $line;
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
        return true;
    }
}
