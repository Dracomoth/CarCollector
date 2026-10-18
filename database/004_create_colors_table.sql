-- =============================================================================
-- Migration: 004_create_colors_table.sql
-- Description: Create colors table, seed standard collector colors and hex codes, register Colors screen under Metadata category with sort_order 80, and grant SuperAdmin permissions.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `colors` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `hex_code` VARCHAR(7) NOT NULL,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed standard car collector colors with accurate hex values
INSERT INTO `colors` (`name`, `hex_code`) VALUES
    ('White', '#FFFFFF'),
    ('Silver', '#C0C0C0'),
    ('Chrome', '#E5E4E2'),
    ('Grey', '#808080'),
    ('Charcoal', '#36454F'),
    ('Black', '#000000'),
    ('Brown', '#8B4513'),
    ('Tan', '#D2B48C'),
    ('Gold', '#FFD700'),
    ('Yellow', '#FFFF00'),
    ('Orange', '#FFA500'),
    ('Red', '#FF0000'),
    ('Burgundy', '#800020'),
    ('Purple', '#800080'),
    ('Pink', '#FFC0CB'),
    ('Navy', '#000080'),
    ('Blue', '#0000FF'),
    ('Teal', '#008080'),
    ('Lime', '#00FF00'),
    ('Green', '#008000'),
    ('Olive', '#808000')
ON DUPLICATE KEY UPDATE
    `hex_code` = VALUES(`hex_code`);

-- Register Colors Screen under Metadata category with sort_order 80
INSERT INTO `screens` (`screen_key`, `name`, `category`, `sort_order`, `grouped`) VALUES
    ('colors', 'Colors', 'Metadata', 80, 1)
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
JOIN `screens` s ON s.screen_key = 'colors'
WHERE r.is_super = 1 OR r.name = 'SuperAdmin'
ON DUPLICATE KEY UPDATE
    `access_pc` = VALUES(`access_pc`),
    `access_other` = VALUES(`access_other`),
    `updated` = CURRENT_TIMESTAMP;

-- Update database version in system_info
UPDATE `system_info` SET `version` = '0.9.0', `updated` = CURRENT_TIMESTAMP WHERE `item` = 'database';
