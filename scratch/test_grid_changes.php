<?php
require_once __DIR__ . '/../source/repositories/ModelRepository.php';
require_once __DIR__ . '/../source/repositories/BrandRepository.php';
require_once __DIR__ . '/../source/repositories/MakerRepository.php';
require_once __DIR__ . '/../source/repositories/ScaleRepository.php';
require_once __DIR__ . '/../source/repositories/ConditionRepository.php';

$cookieFile = __DIR__ . '/../test_cookies.txt';
$baseUrl = 'http://localhost:8000';

// 1. Create a model with distinct condition and series
$brands = BrandRepository::getAll();
$makers = MakerRepository::getAll();
$scales = ScaleRepository::getAll();
$conditions = ConditionRepository::getAll();

$brandId = (int)$brands[0]['id'];
$makerId = (int)$makers[0]['id'];
$scaleId = (int)$scales[0]['id'];

// Find condition 'Mint In Box (MIB)'
$mibCondId = null;
foreach ($conditions as $c) {
    if (strpos($c['name'], 'MIB') !== false) {
        $mibCondId = (int)$c['id'];
        break;
    }
}
if (!$mibCondId) $mibCondId = (int)$conditions[0]['id'];

$uniqueModelName = "TestVerifGridCar_" . rand(1000, 9999);
$testId = ModelRepository::create([
    'user_id'       => 1,
    'brand_id'      => $brandId,
    'maker_id'      => $makerId,
    'scale_id'      => $scaleId,
    'model'         => $uniqueModelName,
    'series'        => 'SuperRareSeriesSecret',
    'series_number' => '99/99',
    'condition_id'  => $mibCondId,
    'year'          => 2024,
    'image_path'    => '/images/models/fake_preview.png'
]);

echo "Created test model ID: {$testId}\n";

// 2. Fetch /models via curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/models');
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
assert($httpCode === 200);

// Check 1: Image column header removed
$hasPhotoTh = (strpos($html, '>Photo<') !== false || strpos($html, 'Photo</th>') !== false);
echo "Has Photo Column Header: " . ($hasPhotoTh ? 'YES (FAIL)' : 'NO (PASS)') . "\n";
assert(!$hasPhotoTh, "Image/Photo column should be removed");

// Check 2: Condition column header removed
$hasCondTh = (strpos($html, '<th data-column="condition_name"') !== false);
echo "Has Condition Column Header: " . ($hasCondTh ? 'YES (FAIL)' : 'NO (PASS)') . "\n";
assert(!$hasCondTh, "Separate condition column should be removed");

// Check 3: Second column has model name with condition badge
$hasBadgeWithName = (strpos($html, $uniqueModelName) !== false && strpos($html, 'Mint In Box (MIB)') !== false);
echo "Model name with Condition badge present: " . ($hasBadgeWithName ? 'YES (PASS)' : 'NO (FAIL)') . "\n";
assert($hasBadgeWithName, "Condition badge should be displayed with model name");

// Check 4: Series name and number removed from the grid cells (strictly within a single <td>)
$hasSeriesInTd = preg_match('/<td[^>]*>(?:(?!<\/td>).)*SuperRareSeriesSecret(?:(?!<\/td>).)*<\/td>/si', $html);
echo "Series displayed in grid <td>: " . ($hasSeriesInTd ? 'YES (FAIL)' : 'NO (PASS)') . "\n";
assert(!$hasSeriesInTd, "Series name and number should not appear in any grid <td> cell");

// Check 5: Maker is shown as text
$makerName = $makers[0]['name'];
$hasMakerText = (strpos($html, htmlspecialchars($makerName)) !== false);
echo "Maker shown as text ('{$makerName}'): " . ($hasMakerText ? 'YES (PASS)' : 'NO (FAIL)') . "\n";
assert($hasMakerText, "Maker should be displayed as text");

// Cleanup
ModelRepository::delete($testId, 1);
echo "\n>>> ALL GRID CUSTOMIZATION TESTS PASSED! <<<\n";
