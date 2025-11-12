<?php

namespace App\Services\SecurityScanners;

interface SecurityScannerInterface
{
    /**
     * Run a security scan with the given configuration.
     *
     * @param array $config Configuration array containing:
     *   - project_path: string - Path to the project code
     *   - repository_url: string - Git repository URL
     *   - branch: string - Git branch to scan
     *   - timeout: int - Timeout in seconds
     *   - max_retries: int - Maximum retry attempts
     *
     * @return array Array of vulnerability findings
     */
    public function scan(array $config): array;

    /**
     * Get the name of the scanner tool.
     */
    public function getToolName(): string;

    /**
     * Get the scan type this scanner handles.
     */
    public function getScanType(): string;

    /**
     * Check if the scanner is available/configured.
     */
    public function isAvailable(): bool;
}
