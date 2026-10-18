<?php
/**
 * Models Screen Entrypoint (models.php)
 * 
 * Directly serves or delegates the Models personal collection screen.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/source/controllers/ModelsController.php';
ModelsController::handle();
