-- Manual migration SQL for multi-environment deployment system
-- Run this in phpMyAdmin if Laravel migrations are failing

-- 1. Create environments table
CREATE TABLE IF NOT EXISTS `environments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `server_base_path` varchar(255) NOT NULL,
  `server_unc_path` varchar(255) NOT NULL,
  `web_base_url` varchar(255) NOT NULL,
  `deploy_endpoint_base` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `environments_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create project_environments table
CREATE TABLE IF NOT EXISTS `project_environments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `environment_id` bigint(20) UNSIGNED NOT NULL,
  `deploy_endpoint` varchar(255) NOT NULL,
  `rollback_endpoint` varchar(255) NOT NULL,
  `application_url` varchar(255) NOT NULL,
  `project_path` varchar(255) NOT NULL,
  `env_variables` text DEFAULT NULL,
  `branch` varchar(255) NOT NULL DEFAULT 'main',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_environments_project_id_foreign` (`project_id`),
  KEY `project_environments_environment_id_foreign` (`environment_id`),
  CONSTRAINT `project_environments_environment_id_foreign` FOREIGN KEY (`environment_id`) REFERENCES `environments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_environments_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add environment_id to deployments table
ALTER TABLE `deployments` ADD COLUMN `environment_id` bigint(20) UNSIGNED NULL AFTER `id`;
ALTER TABLE `deployments` ADD KEY `deployments_environment_id_foreign` (`environment_id`);
ALTER TABLE `deployments` ADD CONSTRAINT `deployments_environment_id_foreign` FOREIGN KEY (`environment_id`) REFERENCES `environments` (`id`) ON DELETE SET NULL;

-- 4. Make legacy project fields nullable
ALTER TABLE `projects` 
MODIFY COLUMN `deploy_endpoint` VARCHAR(255) NULL,
MODIFY COLUMN `rollback_endpoint` VARCHAR(255) NULL,
MODIFY COLUMN `application_url` VARCHAR(255) NULL;

-- 5. Insert default environments
INSERT INTO `environments` (`name`, `slug`, `server_base_path`, `server_unc_path`, `web_base_url`, `deploy_endpoint_base`, `description`, `is_active`, `order`, `created_at`, `updated_at`) VALUES
('Development', 'development', 'C:\\xampp\\htdocs\\dev', '\\\\10.10.15.59\\c$\\xampp\\htdocs\\dep_env_dev', 'http://dev-101-php-01.fmdqgroup.com', 'http://101-php-01.fmdqgroup.com/dep_env_dev', 'Development environment for testing new features', 1, 1, NOW(), NOW()),
('Staging', 'staging', 'C:\\xampp\\htdocs\\staging', '\\\\10.10.15.59\\c$\\xampp\\htdocs\\dep_env_staging', 'http://staging-101-php-01.fmdqgroup.com', 'http://101-php-01.fmdqgroup.com/dep_env_staging', 'Staging environment for pre-production testing', 1, 2, NOW(), NOW()),
('Production', 'production', 'C:\\xampp\\htdocs\\prod', '\\\\10.10.15.59\\c$\\xampp\\htdocs\\dep_env', 'http://101-php-01.fmdqgroup.com', 'http://101-php-01.fmdqgroup.com/dep_env', 'Production environment for live applications', 1, 3, NOW(), NOW()),
('QA', 'qa', 'C:\\xampp\\htdocs\\qa', '\\\\10.10.15.59\\c$\\xampp\\htdocs\\dep_env_qa', 'http://qa-101-php-01.fmdqgroup.com', 'http://101-php-01.fmdqgroup.com/dep_env_qa', 'QA environment for quality assurance testing', 1, 4, NOW(), NOW())
ON DUPLICATE KEY UPDATE `updated_at` = NOW();

-- 6. Update migrations table to mark as completed
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2025_11_15_000001_create_environments_table', 1),
('2025_11_15_000002_create_project_environments_table', 1),
('2025_11_15_000003_add_environment_id_to_deployments_table', 1),
('2025_11_15_100649_make_legacy_project_fields_nullable', 1)
ON DUPLICATE KEY UPDATE `batch` = 1;
