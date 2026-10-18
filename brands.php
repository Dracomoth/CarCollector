<?php
/**
 * Brands Screen Entrypoint (brands.php)
 * 
 * Directly serves or delegates the Brands catalog screen.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/source/controllers/BrandsController.php';
BrandsController::handle();
