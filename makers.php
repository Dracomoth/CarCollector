<?php
/**
 * Makers Screen Entrypoint (makers.php)
 * 
 * Directly serves or delegates the Makers catalog screen.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/source/controllers/MakersController.php';
MakersController::handle();
