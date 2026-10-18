<?php
/**
 * Colors Screen Entrypoint (colors.php)
 * 
 * Directly serves or delegates the Colors metadata screen.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/source/controllers/ColorsController.php';
ColorsController::handle();
