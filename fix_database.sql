-- Fix for multi-environment deployment system
-- Run this SQL in phpMyAdmin or your MySQL client

-- Make legacy project fields nullable to support new multi-environment system
ALTER TABLE `projects` 
MODIFY COLUMN `deploy_endpoint` VARCHAR(255) NULL,
MODIFY COLUMN `rollback_endpoint` VARCHAR(255) NULL,
MODIFY COLUMN `application_url` VARCHAR(255) NULL;

-- Verify the changes
DESCRIBE `projects`;
