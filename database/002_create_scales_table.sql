-- =============================================================================
-- Migration: 002_create_scales_table.sql
-- Description: Create scales table, seed standard model car scales, register Scales screen under Metadata category with sort_order 70, and grant SuperAdmin permissions.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `scales` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `scale` VARCHAR(20) NOT NULL UNIQUE,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed standard model car scales
INSERT INTO `scales` (`scale`) VALUES
    ('1:8'),
    ('1:12'),
    ('1:18'),
    ('1:24'),
    ('1:32'),
    ('1:43'),
    ('1:50'),
    ('1:64'),
    ('1:72'),
    ('1:87')
ON DUPLICATE KEY UPDATE
    `scale` = VALUES(`scale`);

-- Register Scales Screen under Metadata category with sort_order 70
INSERT INTO `screens` (`screen_key`, `name`, `category`, `sort_order`, `grouped`) VALUES
    ('scales', 'Scales', 'Metadata', 70, 1)
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
JOIN `screens` s ON s.screen_key = 'scales'
WHERE r.is_super = 1 OR r.name = 'SuperAdmin'
ON DUPLICATE KEY UPDATE
    `access_pc` = VALUES(`access_pc`),
    `access_other` = VALUES(`access_other`),
    `updated` = CURRENT_TIMESTAMP;

-- Update database version in system_info
UPDATE `system_info` SET `version` = '0.7.0', `updated` = CURRENT_TIMESTAMP WHERE `item` = 'database';
