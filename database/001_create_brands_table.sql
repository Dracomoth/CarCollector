-- =============================================================================
-- Migration: 001_create_brands_table.sql
-- Description: Create brands table, register Brands screen under Catalog group, and grant SuperAdmin permissions.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `brands` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `logo_path` VARCHAR(255) NULL,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Register Brands Screen under Catalog group
INSERT INTO `screens` (`screen_key`, `name`, `category`, `sort_order`, `grouped`) VALUES
    ('brands', 'Brands', 'Catalog', 10, 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `category` = VALUES(`category`),
    `sort_order` = VALUES(`sort_order`),
    `grouped` = VALUES(`grouped`),
    `modified` = CURRENT_TIMESTAMP;

-- Grant full write permissions to SuperAdmin
INSERT INTO `role_permissions` (`role_id`, `screen_id`, `access_pc`, `access_other`)
SELECT r.id, s.id, 'write', 'write'
FROM `roles` r
JOIN `screens` s ON s.screen_key = 'brands'
WHERE r.is_super = 1 OR r.name = 'SuperAdmin'
ON DUPLICATE KEY UPDATE
    `access_pc` = VALUES(`access_pc`),
    `access_other` = VALUES(`access_other`),
    `updated` = CURRENT_TIMESTAMP;

-- Update database version in system_info
UPDATE `system_info` SET `version` = '0.6.0', `updated` = CURRENT_TIMESTAMP WHERE `item` = 'database';
