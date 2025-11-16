<?php

namespace Tests\Unit;

use App\Services\DeploymentFileGenerator;
use Tests\TestCase;

class DeploymentFileGeneratorTest extends TestCase
{
    /** @test */
    public function it_generates_rollback_script_with_server_detection()
    {
        $generator = new DeploymentFileGenerator();
        
        $projectPath = 'C:\\xampp\\htdocs\\test_project_deploy';
        $rollbackScript = $generator->makeRollback($projectPath, 'laravel');
        
        // Check that the rollback script contains server detection logic
        $this->assertStringContainsString('Auto-detect server type and paths', $rollbackScript);
        $this->assertStringContainsString('$serverType = \'unknown\';', $rollbackScript);
        $this->assertStringContainsString('C:\\xampp\\htdocs', $rollbackScript);
        
        // Check that project type is handled
        $this->assertStringContainsString('$projectType = $input[\'project_type\'] ?? \'laravel\';', $rollbackScript);
        $this->assertStringContainsString('Project type: \' . $projectType', $rollbackScript);
    }
    
    /** @test */
    public function it_generates_rollback_script_with_project_type_specific_commands()
    {
        $generator = new DeploymentFileGenerator();
        
        // Test Laravel project type
        $laravelRollback = $generator->makeRollback('C:\\xampp\\htdocs\\test_project_deploy', 'laravel');
        $this->assertStringContainsString('php artisan', $laravelRollback);
        $this->assertStringContainsString('migrate:rollback', $laravelRollback);
        
        // Test Node.js project type
        $nodeRollback = $generator->makeRollback('C:\\xampp\\htdocs\\test_project_deploy', 'nodejs');
        $this->assertStringContainsString('npm ci', $nodeRollback);
        $this->assertStringContainsString('npm run build', $nodeRollback);
    }
}