-- =============================================================================
-- Migration: 006_create_models_table.sql
-- Description: Create models table for user car model collections, register Models screen under Collection category with sort_order 30, and grant SuperAdmin permissions.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `models` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `brand_id` INT UNSIGNED NOT NULL,
    `year` SMALLINT UNSIGNED NULL,
    `scale_id` INT UNSIGNED NOT NULL,
    `series` VARCHAR(100) NULL,
    `series_number` VARCHAR(50) NULL,
    `serial_number` VARCHAR(50) NULL,
    `maker_id` INT UNSIGNED NULL,
    `model` VARCHAR(150) NOT NULL,
    `model_year` SMALLINT UNSIGNED NULL,
    `color_id` INT UNSIGNED NULL,
    `condition_id` INT UNSIGNED NULL,
    `link` VARCHAR(500) NULL,
    `description` TEXT NULL,
    `image_path` VARCHAR(255) NULL,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_models_user_id` (`user_id`),
    INDEX `idx_models_brand_id` (`brand_id`),
    INDEX `idx_models_maker_id` (`maker_id`),
    INDEX `idx_models_scale_id` (`scale_id`),
    INDEX `idx_models_color_id` (`color_id`),
    INDEX `idx_models_condition_id` (`condition_id`),
    CONSTRAINT `fk_models_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_models_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_models_maker` FOREIGN KEY (`maker_id`) REFERENCES `makers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_models_scale` FOREIGN KEY (`scale_id`) REFERENCES `scales` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_models_color` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_models_condition` FOREIGN KEY (`condition_id`) REFERENCES `conditions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Register Models Screen under Collection category with sort_order 30
INSERT INTO `screens` (`screen_key`, `name`, `category`, `sort_order`, `grouped`) VALUES
    ('models', 'Models', 'Collection', 30, 1)
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
JOIN `screens` s ON s.screen_key = 'models'
WHERE r.is_super = 1 OR r.name = 'SuperAdmin'
ON DUPLICATE KEY UPDATE
    `access_pc` = VALUES(`access_pc`),
    `access_other` = VALUES(`access_other`),
    `updated` = CURRENT_TIMESTAMP;

-- Update database version in system_info
UPDATE `system_info` SET `version` = '0.11.0', `updated` = CURRENT_TIMESTAMP WHERE `item` = 'database';
