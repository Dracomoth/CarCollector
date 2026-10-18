<?php
require_once __DIR__ . '/../source/repositories/MakerRepository.php';
require_once __DIR__ . '/../source/repositories/ScreenRepository.php';

echo "=== 1. TEST REPOSITORY CRUD ===\n";
// Create test maker
$testName = "TestHotWheels_" . time();
$makerId = MakerRepository::create([
    'name' => $testName,
    'description' => 'Test maker description',
    'logo_path' => null
]);
echo "Created Maker ID: {$makerId}\n";

// Find by ID
$maker = MakerRepository::findById($makerId);
echo "Find by ID Name: " . ($maker['name'] ?? 'NONE') . "\n";
assert($maker['name'] === $testName);

// Name exists
$exists = MakerRepository::nameExists($testName);
echo "Name exists check: " . ($exists ? 'YES' : 'NO') . "\n";
assert($exists === true);

// Update maker
MakerRepository::update($makerId, [
    'name' => $testName . "_Updated",
    'description' => 'Updated test description',
    'logo_path' => '/images/makers/' . $makerId . '_logo.png'
]);

$updatedMaker = MakerRepository::findById($makerId);
echo "Updated Name: " . ($updatedMaker['name'] ?? 'NONE') . "\n";
echo "Updated Logo: " . ($updatedMaker['logo_path'] ?? 'NONE') . "\n";
assert($updatedMaker['name'] === $testName . "_Updated");
assert($updatedMaker['logo_path'] === '/images/makers/' . $makerId . '_logo.png');

// Delete test maker
MakerRepository::delete($makerId);
$deletedMaker = MakerRepository::findById($makerId);
echo "After Delete: " . ($deletedMaker === null ? 'NULL (Success)' : 'STILL EXISTS') . "\n";
assert($deletedMaker === null);

echo "=== 2. TEST SCREENS ORDER ===\n";
$pdo = Database::getConnection();
$screens = $pdo->query("SELECT screen_key, name, category, sort_order FROM screens WHERE category = 'Catalog' ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
print_r($screens);

echo "ALL REPO TESTS PASSED!\n";
