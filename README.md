# Laravel Deployment Manager

A lightweight internal web application that allows developers to manage and trigger deployments of Laravel projects from their local environment to Windows servers connected via VPN.

![Dashboard](https://via.placeholder.com/800x400.png?text=Deployment+Manager+Dashboard)

## Overview

Instead of setting up CI/CD or manually SSHing into the server, developers can use a secure dashboard to push changes and trigger the deployment script. This application simplifies deployment for projects hosted on internal (VPN-only) Windows servers.

## Features

- **Web Interface**: Clean dashboard to manage deployment projects
- **Role-Based Access**: Admin and Developer roles with appropriate permissions
- **Project Management**: Register and configure projects with repository URLs and deployment endpoints
- **Deployment Triggering**: One-click deployment to remote servers
- **Deployment History**: Track all deployment attempts with status and logs
- **Scheduled Deployments**: Schedule deployments to run at specific times
- **Queue-based Processing**: Deployments are processed through Laravel queues for better performance
- **Secure Communication**: Token-based authentication for API communication

## Requirements

- PHP 8.2+
- MySQL or SQLite database
- Composer
- Node.js and NPM
- Laravel 10+

## Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   cd deployment-management
   ```

2. Install PHP dependencies:
   ```
   composer install
   ```

3. Install JavaScript dependencies:
   ```
   npm install
   ```

4. Copy and configure the environment file:
   ```
   cp .env.example .env
   php artisan key:generate
   ```

5. Configure your database in the `.env` file

6. Run database migrations:
   ```
   php artisan migrate
   ```

7. Seed the initial roles:
   ```
   php artisan db:seed --class=RolePermissionSeeder
   ```

8. Seed sample users:
   ```
   php artisan db:seed --class=AdminUserSeeder
   php artisan db:seed --class=DeveloperUserSeeder
   ```

9. Seed sample project:
   ```
   php artisan db:seed --class=TestProjectSeeder
   ```

10. Compile assets:
    ```
    npm run dev
    ```

11. Start the development server:
    ```
    php artisan serve
    ```

## Queue Worker Setup

Scheduled deployments are processed through Laravel queues. To process scheduled deployments:

1. Start the queue worker:
   ```
   php artisan queue:work
   ```

2. For production, set up a process supervisor to keep the queue worker running.

## Scheduled Deployments

The application supports scheduling deployments to run at specific times.

### Setting up Scheduled Deployments

1. **Via Web Interface**: Navigate to the Scheduled Deployments section and create a new scheduled deployment.

2. **Via Command Line**: Use the test command to create a scheduled deployment:
   ```
   php artisan test:scheduled-deployment --due
   ```

### Processing Scheduled Deployments

The system uses Laravel's task scheduler to check for due scheduled deployments every minute. Set up a cron job (or Windows Task Scheduler) to run:

```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

For Windows, create a scheduled task that runs every minute:
1. Open Task Scheduler
2. Create a basic task
3. Set trigger to daily, repeating every 1 minute
4. Set action to run:
   ```
   C:\xampp\php\php.exe C:\xampp\htdocs\deployment-management\artisan schedule:run
   ```

## Usage

1. Access the application at `http://localhost:8000`
2. Login with the admin account:
   - Email: `admin@deployment-manager.com`
   - Password: `password`
3. Or login with the developer account:
   - Email: `developer@deployment-manager.com`
   - Password: `password`
4. Navigate to the Deployment Manager dashboard
5. Add new projects or deploy existing ones
6. For scheduled deployments, go to the Scheduled Deployments section

## Roles and Permissions

- **Admin**: Can create, edit, and delete projects; can deploy projects; can manage users
- **Developer**: Can deploy projects and view deployment history

## Security

- All communication with deployment endpoints is token-based
- HTTPS is recommended for production use
- Access tokens are stored securely in the database

## API Endpoints

The application communicates with remote deployment endpoints via HTTP POST requests:

```
POST /deploy.php
Headers:
  Authorization: Bearer {access_token}
Body:
  {
    "project_id": {project_id},
    "branch": "{branch}",
    "user_id": {user_id},
    "deployment_id": {deployment_id}
  }
```

## Deployment Flow

1. Developer pushes code to GitHub
2. Logs into Deployment Manager
3. Clicks "Deploy" for project or schedules a deployment
4. App dispatches job to queue for processing
5. Queue worker sends POST to deploy.php with token
6. deploy.php runs git pull, composer install, migrate, and optimize
7. Returns success JSON
8. Dashboard updates with status

## Future Enhancements

- Slack/Email notifications
- Rollback feature
- Support for multiple servers
- Git commit diff preview
- Jenkins/GitHub Actions integration

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).