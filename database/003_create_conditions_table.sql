-- =============================================================================
-- Migration: 003_create_conditions_table.sql
-- Description: Create conditions table, seed standard collector conditions, register Conditions screen under Metadata category with sort_order 75, and grant SuperAdmin permissions.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `conditions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed standard collector condition values
INSERT INTO `conditions` (`name`) VALUES
    ('Junkyard'),
    ('Major Scratches'),
    ('Minor Scratches'),
    ('Mint Loose'),
    ('Mint In Box (MIB)'),
    ('Mint on Mint Card (MOMC)')
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`);

-- Register Conditions Screen under Metadata category with sort_order 75
INSERT INTO `screens` (`screen_key`, `name`, `category`, `sort_order`, `grouped`) VALUES
    ('conditions', 'Conditions', 'Metadata', 75, 1)
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
JOIN `screens` s ON s.screen_key = 'conditions'
WHERE r.is_super = 1 OR r.name = 'SuperAdmin'
ON DUPLICATE KEY UPDATE
    `access_pc` = VALUES(`access_pc`),
    `access_other` = VALUES(`access_other`),
    `updated` = CURRENT_TIMESTAMP;

-- Update database version in system_info
UPDATE `system_info` SET `version` = '0.8.0', `updated` = CURRENT_TIMESTAMP WHERE `item` = 'database';
