<?php
require_once __DIR__ . '/../source/repositories/ModelRepository.php';
require_once __DIR__ . '/../source/repositories/BrandRepository.php';
require_once __DIR__ . '/../source/repositories/MakerRepository.php';
require_once __DIR__ . '/../source/repositories/ScaleRepository.php';
require_once __DIR__ . '/../source/repositories/ColorRepository.php';
require_once __DIR__ . '/../source/repositories/ConditionRepository.php';
require_once __DIR__ . '/../source/services/CollectionService.php';

echo "=== 1. VERIFY DEPENDENCIES AVAILABLE ===\n";
$brands = BrandRepository::getAll();
$makers = MakerRepository::getAll();
$scales = ScaleRepository::getAll();
$colors = ColorRepository::getAll();
$conditions = ConditionRepository::getAll();

echo "Brands count: " . count($brands) . "\n";
echo "Makers count: " . count($makers) . "\n";
echo "Scales count: " . count($scales) . "\n";
echo "Colors count: " . count($colors) . "\n";
echo "Conditions count: " . count($conditions) . "\n";

assert(count($brands) > 0, "Need at least one brand");
assert(count($scales) > 0, "Need at least one scale");

$brandId = (int)$brands[0]['id'];
$makerId = !empty($makers) ? (int)$makers[0]['id'] : null;
$scaleId = (int)$scales[0]['id'];
$colorId = !empty($colors) ? (int)$colors[0]['id'] : null;
$conditionId = !empty($conditions) ? (int)$conditions[0]['id'] : null;

// Get test user id
$pdo = Database::getConnection();
$userId = (int)$pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
echo "Test User ID: {$userId}\n";
assert($userId > 0, "Need a user in users table");

echo "=== 2. TEST REPOSITORY CRUD ===\n";
$testModelName = "Test GT3 RS " . time();
$newModelId = ModelRepository::create([
    'user_id'       => $userId,
    'brand_id'      => $brandId,
    'year'          => 2024,
    'scale_id'      => $scaleId,
    'series'        => 'Mainline',
    'series_number' => '04/250',
    'serial_number' => 'HF667',
    'maker_id'      => $makerId,
    'model'         => $testModelName,
    'model_year'    => 2022,
    'color_id'      => $colorId,
    'condition_id'  => $conditionId,
    'link'          => 'https://example.com/gt3rs',
    'description'   => 'Test model description notes.',
    'image_path'    => null
]);

echo "Created Model ID: {$newModelId}\n";
assert($newModelId > 0);

// Find by ID
$found = ModelRepository::findById($newModelId, $userId);
echo "Found Model: " . ($found['model'] ?? 'NONE') . "\n";
echo "Joined Brand: " . ($found['brand_name'] ?? 'NONE') . "\n";
echo "Joined Maker: " . ($found['maker_name'] ?? 'NONE') . "\n";
echo "Joined Scale: " . ($found['scale_name'] ?? 'NONE') . "\n";
echo "Joined Color: " . ($found['color_name'] ?? 'NONE') . " (" . ($found['color_hex'] ?? '') . ")\n";
echo "Joined Condition: " . ($found['condition_name'] ?? 'NONE') . "\n";
assert($found['model'] === $testModelName);
assert(!empty($found['brand_name']));

// Check Brand model count badge query
$brandsWithCounts = BrandRepository::getAll($userId);
$brandRow = null;
foreach ($brandsWithCounts as $b) {
    if ((int)$b['id'] === $brandId) {
        $brandRow = $b;
        break;
    }
}
echo "Brand '{$brandRow['name']}' Models Count: " . ($brandRow['models_count'] ?? 0) . "\n";
assert((int)$brandRow['models_count'] >= 1);

// Check Maker model count badge query if maker exists
if ($makerId) {
    $makersWithCounts = MakerRepository::getAll($userId);
    $makerRow = null;
    foreach ($makersWithCounts as $mk) {
        if ((int)$mk['id'] === $makerId) {
            $makerRow = $mk;
            break;
        }
    }
    echo "Maker '{$makerRow['name']}' Models Count: " . ($makerRow['models_count'] ?? 0) . "\n";
    assert((int)$makerRow['models_count'] >= 1);
}

// Check CollectionService count
$userCarCount = CollectionService::getTotalCars($userId);
echo "User Collection Total Cars: {$userCarCount}\n";
assert($userCarCount >= 1);

// Update model
ModelRepository::update($newModelId, [
    'brand_id'      => $brandId,
    'year'          => 2025,
    'scale_id'      => $scaleId,
    'series'        => 'Super Treasure Hunt',
    'series_number' => 'STH-01',
    'serial_number' => 'STH999',
    'maker_id'      => $makerId,
    'model'         => $testModelName . " [STH]",
    'model_year'    => 2023,
    'color_id'      => $colorId,
    'condition_id'  => $conditionId,
    'link'          => 'https://example.com/sth',
    'description'   => 'Spectraflame paint with Real Riders.',
    'image_path'    => "/images/models/{$newModelId}_{$userId}_image.jpg"
], $userId);

$updated = ModelRepository::findById($newModelId, $userId);
echo "Updated Model Name: " . ($updated['model'] ?? 'NONE') . "\n";
echo "Updated Series: " . ($updated['series'] ?? 'NONE') . "\n";
echo "Updated Image: " . ($updated['image_path'] ?? 'NONE') . "\n";
assert($updated['model'] === $testModelName . " [STH]");
assert($updated['series'] === 'Super Treasure Hunt');
assert($updated['image_path'] === "/images/models/{$newModelId}_{$userId}_image.jpg");

// Delete model
ModelRepository::delete($newModelId, $userId);
$afterDelete = ModelRepository::findById($newModelId, $userId);
echo "After Delete: " . ($afterDelete === null ? 'NULL (Success)' : 'STILL EXISTS') . "\n";
assert($afterDelete === null);

echo "ALL REPOSITORY TESTS PASSED!\n";
