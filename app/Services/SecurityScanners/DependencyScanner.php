<?php

namespace App\Services\SecurityScanners;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class DependencyScanner implements SecurityScannerInterface
{
    protected string $toolName = 'Composer Security Checker';
    protected string $scanType = 'dependency';

    public function scan(array $config): array
    {
        $projectPath = $config['project_path'];
        $findings = [];

        try {
            // Ensure code is available
            $this->ensureCodeAvailable($config);

            // Check PHP dependencies (Composer)
            $findings = array_merge($findings, $this->scanComposerDependencies($projectPath));
            
            // Check Node.js dependencies if package.json exists
            if (file_exists($projectPath . '/package.json')) {
                $findings = array_merge($findings, $this->scanNpmDependencies($projectPath));
            }
            
        } catch (Exception $e) {
            Log::error('Dependency scan failed', ['error' => $e->getMessage()]);
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

    protected function scanComposerDependencies(string $projectPath): array
    {
        $findings = [];
        $composerLockPath = $projectPath . '/composer.lock';
        
        if (!file_exists($composerLockPath)) {
            return $this->createMockDependencyFindings($projectPath);
        }

        try {
            $lockData = json_decode(file_get_contents($composerLockPath), true);
            
            if (!$lockData || !isset($lockData['packages'])) {
                return [];
            }

            foreach ($lockData['packages'] as $package) {
                $vulnerabilities = $this->checkPackageVulnerabilities($package);
                
                foreach ($vulnerabilities as $vulnerability) {
                    $findings[] = [
                        'scan_type' => $this->scanType,
                        'tool_name' => $this->toolName,
                        'severity' => $vulnerability['severity'],
                        'vulnerability_id' => $vulnerability['id'] ?? null,
                        'cve_id' => $vulnerability['cve'] ?? null,
                        'title' => "Vulnerable dependency: {$package['name']}",
                        'description' => $vulnerability['description'],
                        'file_path' => '/composer.lock',
                        'remediation_advice' => "Update {$package['name']} to version {$vulnerability['fixed_version']} or higher",
                        'reference_url' => $vulnerability['reference'] ?? null,
                        'status' => 'open',
                        'metadata' => [
                            'package_name' => $package['name'],
                            'current_version' => $package['version'],
                            'fixed_version' => $vulnerability['fixed_version'] ?? 'unknown'
                        ],
                        'first_detected_at' => now(),
                        'last_seen_at' => now(),
                    ];
                }
            }
            
        } catch (Exception $e) {
            Log::error('Failed to parse composer.lock', ['error' => $e->getMessage()]);
        }

        return $findings;
    }

    protected function scanNpmDependencies(string $projectPath): array
    {
        $findings = [];
        $packageLockPath = $projectPath . '/package-lock.json';
        
        if (!file_exists($packageLockPath)) {
            return [];
        }

        // Run npm audit if available
        $command = "cd {$projectPath} && npm audit --json";
        $result = Process::run($command);

        if ($result->successful()) {
            $auditData = json_decode($result->output(), true);
            
            if (isset($auditData['vulnerabilities'])) {
                foreach ($auditData['vulnerabilities'] as $packageName => $vulnerability) {
                    $findings[] = [
                        'scan_type' => $this->scanType,
                        'tool_name' => 'npm audit',
                        'severity' => $this->mapNpmSeverity($vulnerability['severity']),
                        'vulnerability_id' => $vulnerability['id'] ?? null,
                        'cve_id' => $vulnerability['cve'][0] ?? null,
                        'title' => "Vulnerable npm dependency: {$packageName}",
                        'description' => $vulnerability['title'] ?? 'Vulnerability in npm package',
                        'file_path' => '/package-lock.json',
                        'remediation_advice' => "Update {$packageName} to a secure version",
                        'reference_url' => $vulnerability['url'] ?? null,
                        'status' => 'open',
                        'metadata' => [
                            'package_name' => $packageName,
                            'vulnerability_range' => $vulnerability['range'] ?? null
                        ],
                        'first_detected_at' => now(),
                        'last_seen_at' => now(),
                    ];
                }
            }
        }

        return $findings;
    }

    protected function checkPackageVulnerabilities(array $package): array
    {
        // This would typically query a vulnerability database
        // For now, we'll simulate some common vulnerable packages
        $knownVulnerabilities = $this->getKnownVulnerabilities();
        
        $packageName = $package['name'];
        $packageVersion = $package['version'];
        
        if (isset($knownVulnerabilities[$packageName])) {
            $vulns = $knownVulnerabilities[$packageName];
            $applicableVulns = [];
            
            foreach ($vulns as $vuln) {
                if ($this->isVersionVulnerable($packageVersion, $vuln['affected_versions'])) {
                    $applicableVulns[] = $vuln;
                }
            }
            
            return $applicableVulns;
        }
        
        return [];
    }

    protected function getKnownVulnerabilities(): array
    {
        return [
            'monolog/monolog' => [
                [
                    'id' => 'GHSA-w4qr-54hc-v893',
                    'cve' => 'CVE-2021-23437',
                    'severity' => 'high',
                    'description' => 'Remote code execution vulnerability in Monolog',
                    'affected_versions' => '<2.2.0',
                    'fixed_version' => '2.2.0',
                    'reference' => 'https://github.com/advisories/GHSA-w4qr-54hc-v893'
                ]
            ],
            'symfony/http-kernel' => [
                [
                    'id' => 'GHSA-56rf-j9q4-5fj5',
                    'cve' => 'CVE-2022-24894',
                    'severity' => 'medium',
                    'description' => 'Information disclosure in Symfony HttpKernel',
                    'affected_versions' => '<5.4.20',
                    'fixed_version' => '5.4.20',
                    'reference' => 'https://github.com/advisories/GHSA-56rf-j9q4-5fj5'
                ]
            ],
            'laravel/framework' => [
                [
                    'id' => 'GHSA-3p32-j457-pg5x',
                    'cve' => 'CVE-2021-21263',
                    'severity' => 'high',
                    'description' => 'SQL injection vulnerability in Laravel query builder',
                    'affected_versions' => '<8.22.1',
                    'fixed_version' => '8.22.1',
                    'reference' => 'https://github.com/advisories/GHSA-3p32-j457-pg5x'
                ]
            ]
        ];
    }

    protected function isVersionVulnerable(string $currentVersion, string $affectedVersions): bool
    {
        // Simple version comparison - in production, use a proper version comparison library
        if (strpos($affectedVersions, '<') === 0) {
            $maxVersion = trim(substr($affectedVersions, 1));
            return version_compare($currentVersion, $maxVersion, '<');
        }
        
        return false;
    }

    protected function mapNpmSeverity(string $npmSeverity): string
    {
        return match(strtolower($npmSeverity)) {
            'critical' => 'critical',
            'high' => 'high',
            'moderate' => 'medium',
            'low' => 'low',
            default => 'medium'
        };
    }

    protected function createMockDependencyFindings(string $projectPath): array
    {
        return [
            [
                'scan_type' => $this->scanType,
                'tool_name' => $this->toolName . ' (Demo)',
                'severity' => 'high',
                'title' => 'Demo Dependency Vulnerability',
                'description' => 'This is a demonstration finding. Run composer install to generate composer.lock for real analysis.',
                'file_path' => '/composer.json',
                'remediation_advice' => 'Run composer install and ensure composer.lock is present for dependency scanning',
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
        return true;
    }
}
