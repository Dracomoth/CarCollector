<?php
/**
 * ImageHelper Service
 * 
 * Provides utility methods for cache-busting image URLs by appending
 * the parent record's modification timestamp as ?v=(timestamp).
 */

class ImageHelper {
    /**
     * Appends the modification timestamp of the parent record as a query parameter ?v=...
     * 
     * @param string|null $path Image path (e.g., "uploads/brands/12_logo.jpg")
     * @param string|int|null $modified Last modification timestamp or datetime string
     * @return string Versioned image path
     */
    public static function versioned(?string $path, $modified = null): string {
        if (empty($path)) {
            return '';
        }

        $timestamp = self::toTimestamp($modified);
        if (!$timestamp) {
            return $path;
        }

        $separator = strpos($path, '?') !== false ? '&' : '?';
        return $path . $separator . 'v=' . $timestamp;
    }

    /**
     * Convert datetime string or numeric value to integer timestamp.
     * 
     * @param mixed $val
     * @return int|null
     */
    public static function toTimestamp($val): ?int {
        if (empty($val)) {
            return null;
        }

        if (is_numeric($val)) {
            $ts = (int)$val;
            return $ts > 0 ? $ts : null;
        }

        if (is_string($val)) {
            $ts = strtotime($val);
            return ($ts !== false && $ts > 0) ? $ts : null;
        }

        return null;
    }
}

if (!function_exists('img_v')) {
    /**
     * Helper shortcut to get versioned image URL with modification timestamp.
     * 
     * @param string|null $path
     * @param string|int|null $modified
     * @return string
     */
    function img_v(?string $path, $modified = null): string {
        return ImageHelper::versioned($path, $modified);
    }
}
