<?php

require_once 'app/Services/DeploymentFileGenerator.php';

use App\Services\DeploymentFileGenerator;

// Test the deployment generator with database creation and migration
$generator = new DeploymentFileGenerator();

$projectPath = 'C:\\xampp\\htdocs\\test-project';
$repoUrl = 'https://github.com/example/test-repo.git';

$deploymentScript = $generator->make($projectPath, $repoUrl);

// Save to a file to inspect
file_put_contents('generated_deployment_script.php', $deploymentScript);

echo "Generated deployment script with database creation and migration functionality.\n";
echo "Check generated_deployment_script.php to verify the changes.\n";