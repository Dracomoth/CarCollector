<?php
require_once __DIR__ . '/../source/services/Database.php';

$pdo = Database::getConnection();
echo "=== SYSTEM INFO ===\n";
print_r($pdo->query("SELECT * FROM system_info")->fetchAll(PDO::FETCH_ASSOC));

echo "=== SCREENS ===\n";
print_r($pdo->query("SELECT id, screen_key, name, category, sort_order, grouped FROM screens ORDER BY category, sort_order")->fetchAll(PDO::FETCH_ASSOC));
