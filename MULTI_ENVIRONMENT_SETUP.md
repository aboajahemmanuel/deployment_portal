# Multi-Environment Deployment Setup Guide

## Overview
This deployment management system now supports deploying projects to multiple environments (Development, Staging, Production). When you create a project, deployment files are automatically generated for all active environments.

## What's New

### 1. Database Structure
- **environments** table: Stores environment configurations (dev, staging, prod)
- **project_environments** table: Links projects to environments with specific configurations
- **deployments.environment_id**: Tracks which environment each deployment targets

### 2. Models Created
- `Environment`: Manages deployment environments
- `ProjectEnvironment`: Manages project-specific environment configurations

### 3. Key Features
- **Automatic File Generation**: When creating a project, deployment and rollback files are created for ALL active environments
- **Environment Selection**: Users can select which environment to deploy to
- **Environment-Specific Configurations**: Each environment can have different:
  - Server paths
  - Branch names
  - Environment variables
  - URLs

## Setup Instructions

### Step 1: Run Migrations
```bash
php artisan migrate
```

This will create:
- `environments` table
- `project_environments` table
- Add `environment_id` to `deployments` table

### Step 2: Seed Environments
```bash
php artisan db:seed --class=EnvironmentSeeder
```

This creates three default environments:
1. **Development**
   - Server: `C:\xampp\htdocs\dev`
   - UNC: `\\10.10.15.59\c$\xampp\htdocs\dep_env_dev`
   - URL: `http://dev-101-php-01.fmdqgroup.com`

2. **Staging**
   - Server: `C:\xampp\htdocs\staging`
   - UNC: `\\10.10.15.59\c$\xampp\htdocs\dep_env_staging`
   - URL: `http://staging-101-php-01.fmdqgroup.com`

3. **Production**
   - Server: `C:\xampp\htdocs\prod`
   - UNC: `\\10.10.15.59\c$\xampp\htdocs\dep_env`
   - URL: `http://101-php-01.fmdqgroup.com`

### Step 3: Verify Environment Setup
Check that environments are created:
```bash
php artisan tinker
>>> App\Models\Environment::all();
```

## How It Works

### Creating a Project
When you create a new project through the UI:

1. The system retrieves all active environments
2. For each environment, it:
   - Generates environment-specific deployment file (e.g., `myproject_development.php`)
   - Generates environment-specific rollback file (e.g., `myproject_development_rollback.php`)
   - Creates a `ProjectEnvironment` record with:
     - Deploy endpoint URL
     - Rollback endpoint URL
     - Application URL
     - Project path on server
     - Environment variables
     - Branch name

### Deploying to an Environment
When deploying a project:

1. User clicks "Deploy Now"
2. A modal appears showing all available environments
3. User selects target environment (Development, Staging, or Production)
4. System deploys to the selected environment using its specific configuration

### File Naming Convention
Files are named with environment suffix:
- Development: `projectname_development.php`, `projectname_development_rollback.php`
- Staging: `projectname_staging.php`, `projectname_staging_rollback.php`
- Production: `projectname_production.php`, `projectname_production_rollback.php`

## Environment Configuration

### Customizing Environments
You can customize environment settings in the `EnvironmentSeeder.php` file or through the database:

```php
Environment::create([
    'name' => 'QA',
    'slug' => 'qa',
    'server_base_path' => 'C:\\xampp\\htdocs\\qa',
    'server_unc_path' => '\\\\10.10.15.59\\c$\\xampp\\htdocs\\dep_env_qa',
    'web_base_url' => 'http://qa-101-php-01.fmdqgroup.com',
    'deploy_endpoint_base' => 'http://101-php-01.fmdqgroup.com/dep_env_qa',
    'description' => 'QA environment for testing',
    'is_active' => true,
    'order' => 4,
]);
```

### Environment-Specific Branches
Each project environment can use a different branch:
- Development: `develop` or `dev`
- Staging: `staging` or `release`
- Production: `main` or `master`

## API Changes

### Deploy Endpoint
```javascript
POST /deployments/{project}/deploy
{
    "environment_id": 1  // Required: ID of target environment
}
```

### Rollback Endpoint
Rollback automatically uses the same environment as the target deployment.

## Database Relationships

```
Project
  ├─ projectEnvironments (hasMany)
  │   └─ environment (belongsTo)
  └─ deployments (hasMany)
      └─ environment (belongsTo)

Environment
  ├─ projectEnvironments (hasMany)
  └─ deployments (hasMany)
```

## Troubleshooting

### No Environments Available
If you see "No environments configured", run:
```bash
php artisan db:seed --class=EnvironmentSeeder
```

### Deployment Files Not Created
Check logs for errors:
```bash
tail -f storage/logs/laravel.log
```

Common issues:
- UNC path not accessible
- Insufficient permissions
- Invalid server paths

### Environment Not Showing in Modal
Verify:
1. Environment is active: `is_active = true`
2. ProjectEnvironment exists for the project
3. ProjectEnvironment is active

## Migration from Old System

### For Existing Projects
Existing projects won't have environment configurations. You have two options:

1. **Recreate Projects**: Delete and recreate projects to auto-generate environment files
2. **Manual Migration**: Run a migration script to create ProjectEnvironment records for existing projects

### Migration Script Example
```php
use App\Models\Project;
use App\Models\Environment;
use App\Models\ProjectEnvironment;

$projects = Project::all();
$prodEnv = Environment::where('slug', 'production')->first();

foreach ($projects as $project) {
    ProjectEnvironment::create([
        'project_id' => $project->id,
        'environment_id' => $prodEnv->id,
        'deploy_endpoint' => $project->deploy_endpoint,
        'rollback_endpoint' => $project->rollback_endpoint,
        'application_url' => $project->application_url,
        'project_path' => 'C:\\xampp\\htdocs\\prod\\' . $project->name . '_deploy',
        'env_variables' => $project->env_variables,
        'branch' => $project->current_branch,
        'is_active' => true,
    ]);
}
```

## Best Practices

1. **Environment Progression**: Deploy to environments in order: Dev → Staging → Production
2. **Branch Strategy**: Use different branches per environment
3. **Environment Variables**: Keep sensitive data in environment-specific .env files
4. **Testing**: Always test in Development before promoting to Staging/Production
5. **Rollback Plan**: Test rollback in lower environments first

## Security Considerations

1. **Access Control**: Limit production deployments to admins only
2. **Approval Workflow**: Consider requiring approval for production deployments
3. **Audit Trail**: All deployments are logged with environment information
4. **Separate Credentials**: Use different access tokens per environment

## Future Enhancements

- Environment promotion workflows
- Automated testing before deployment
- Environment-specific approval requirements
- Deployment scheduling per environment
- Environment health monitoring
- Auto-rollback on failure
