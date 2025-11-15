<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=deployment_management', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Fixing database schema...\n";
    
    $pdo->exec('ALTER TABLE projects MODIFY COLUMN deploy_endpoint VARCHAR(255) NULL');
    echo "✅ deploy_endpoint set to nullable\n";
    
    $pdo->exec('ALTER TABLE projects MODIFY COLUMN rollback_endpoint VARCHAR(255) NULL');
    echo "✅ rollback_endpoint set to nullable\n";
    
    $pdo->exec('ALTER TABLE projects MODIFY COLUMN application_url VARCHAR(255) NULL');
    echo "✅ application_url set to nullable\n";
    
    echo "\n🎉 Database schema updated successfully!\n";
    echo "Now run: php populate_project_environments.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nPlease run this SQL manually in phpMyAdmin:\n";
    echo "ALTER TABLE projects MODIFY COLUMN deploy_endpoint VARCHAR(255) NULL;\n";
    echo "ALTER TABLE projects MODIFY COLUMN rollback_endpoint VARCHAR(255) NULL;\n";
    echo "ALTER TABLE projects MODIFY COLUMN application_url VARCHAR(255) NULL;\n";
}
