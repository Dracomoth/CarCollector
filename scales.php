<?php
/**
 * Scales Screen Entrypoint (scales.php)
 * 
 * Directly serves or delegates the Scales catalog screen.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/source/controllers/ScalesController.php';
ScalesController::handle();
