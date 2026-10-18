<?php
/**
 * Conditions Screen Entrypoint (conditions.php)
 * 
 * Directly serves or delegates the Conditions metadata screen.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/source/controllers/ConditionsController.php';
ConditionsController::handle();
