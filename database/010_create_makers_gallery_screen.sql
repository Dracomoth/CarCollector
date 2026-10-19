-- =============================================================================
-- Migration: 010_create_makers_gallery_screen.sql
-- Description: Register Makers Gallery screen under 'Gallery' category with sort_order 25,
--              name 'Makers', grant SuperAdmin permissions, and bump system version to 0.15.0.
-- =============================================================================

INSERT INTO `screens` (`screen_key`, `name`, `category`, `sort_order`, `grouped`) VALUES
    ('makers_gallery', 'Makers', 'Gallery', 25, 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `category` = VALUES(`category`),
    `sort_order` = VALUES(`sort_order`),
    `grouped` = VALUES(`grouped`),
    `modified` = CURRENT_TIMESTAMP;

-- Grant full write and read permissions to SuperAdmin
INSERT INTO `role_permissions` (`role_id`, `screen_id`, `access_pc`, `access_other`)
SELECT r.id, s.id, 'write', 'write'
FROM `roles` r
JOIN `screens` s ON s.screen_key = 'makers_gallery'
WHERE r.is_super = 1 OR r.name = 'SuperAdmin'
ON DUPLICATE KEY UPDATE
    `access_pc` = VALUES(`access_pc`),
    `access_other` = VALUES(`access_other`),
    `updated` = CURRENT_TIMESTAMP;

-- Update database version in system_info
UPDATE `system_info` SET `version` = '0.15.0', `updated` = CURRENT_TIMESTAMP WHERE `item` = 'database';
