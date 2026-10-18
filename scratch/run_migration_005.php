<?php
require_once __DIR__ . '/../source/services/Database.php';

$pdo = Database::getConnection();
if (!$pdo) {
    die("Database connection failed.\n");
}

$sql = file_get_contents(__DIR__ . '/../database/005_create_makers_table.sql');

try {
    $pdo->exec($sql);
    echo "Migration 005 executed successfully.\n";
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
