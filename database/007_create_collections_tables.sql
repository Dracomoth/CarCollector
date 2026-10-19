-- =============================================================================
-- Migration: 007_create_collections_tables.sql
-- Description: Create collections and collection_items tables, register Collections screen under Collection category with sort_order 35, and grant SuperAdmin permissions.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `collections` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `logo_path` VARCHAR(255) NULL,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_collections_user_id` (`user_id`),
    CONSTRAINT `fk_collections_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `collection_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `collection_id` INT UNSIGNED NOT NULL,
    `model_id` INT UNSIGNED NOT NULL,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_collection_model` (`collection_id`, `model_id`),
    INDEX `idx_collection_items_collection_id` (`collection_id`),
    INDEX `idx_collection_items_model_id` (`model_id`),
    CONSTRAINT `fk_collection_items_collection` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_collection_items_model` FOREIGN KEY (`model_id`) REFERENCES `models` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compatibility view for collection_item singular table name
CREATE OR REPLACE VIEW `collection_item` AS SELECT * FROM `collection_items`;

-- Register Collections Screen under Collection category with sort_order 35
INSERT INTO `screens` (`screen_key`, `name`, `category`, `sort_order`, `grouped`) VALUES
    ('collections', 'Collections', 'Collection', 35, 1)
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
JOIN `screens` s ON s.screen_key = 'collections'
WHERE r.is_super = 1 OR r.name = 'SuperAdmin'
ON DUPLICATE KEY UPDATE
    `access_pc` = VALUES(`access_pc`),
    `access_other` = VALUES(`access_other`),
    `updated` = CURRENT_TIMESTAMP;

-- Update database version in system_info
UPDATE `system_info` SET `version` = '0.12.0', `updated` = CURRENT_TIMESTAMP WHERE `item` = 'database';
