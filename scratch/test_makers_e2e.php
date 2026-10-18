<?php
/**
 * End-to-End Test for Makers Controller & Uploads
 */

$cookieFile = __DIR__ . '/../test_cookies.txt';
$baseUrl = 'http://localhost:8000';

function httpPost($url, $fields, $cookieFile) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $response];
}

function httpGet($url, $cookieFile) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $response];
}

echo "=== 1. TEST GET /makers ===\n";
$res = httpGet($baseUrl . '/makers', $cookieFile);
echo "GET /makers HTTP Code: {$res['code']}\n";
assert($res['code'] === 200);
assert(strpos($res['body'], 'Makers Catalog') !== false);
assert(strpos($res['body'], 'Makers Directory') !== false);

echo "=== 2. TEST GET /makers.php ===\n";
$res2 = httpGet($baseUrl . '/makers.php', $cookieFile);
echo "GET /makers.php HTTP Code: {$res2['code']}\n";
assert($res2['code'] === 200);

echo "=== 3. CREATE DUMMY IMAGE FOR LOGO UPLOAD ===\n";
$sampleImgPath = __DIR__ . '/test_maker_logo.png';
file_put_contents($sampleImgPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));
echo "Sample image generated at: {$sampleImgPath}\n";

echo "=== 4. TEST CREATE MAKER WITH LOGO UPLOAD ===\n";
$cfile = new CURLFile($sampleImgPath, 'image/png', 'hw_logo.png');
$makerName = "Hot Wheels Test " . rand(1000, 9999);
$fields = [
    'action' => 'save',
    'id' => '',
    'name' => $makerName,
    'description' => 'Iconic 1:64 scale diecast car maker by Mattel.',
    'logo' => $cfile
];

$res = httpPost($baseUrl . '/makers', $fields, $cookieFile);
echo "Create Maker HTTP Code: {$res['code']}\n";
assert($res['code'] === 200);
assert(strpos($res['body'], 'created successfully') !== false);
assert(strpos($res['body'], htmlspecialchars($makerName)) !== false);

// Extract maker ID from page
require_once __DIR__ . '/../source/repositories/MakerRepository.php';
$createdMaker = MakerRepository::findByName($makerName);
echo "Created Maker in DB: ID {$createdMaker['id']}, Logo: {$createdMaker['logo_path']}\n";
assert($createdMaker !== null);
assert(!empty($createdMaker['logo_path']));

$diskLogo = __DIR__ . '/../' . ltrim($createdMaker['logo_path'], '/');
echo "Disk Logo Exists: " . (file_exists($diskLogo) ? 'YES' : 'NO') . " ({$diskLogo})\n";
assert(file_exists($diskLogo));

echo "=== 5. TEST UPDATE MAKER & REMOVE LOGO ===\n";
$updateFields = [
    'action' => 'save',
    'id' => $createdMaker['id'],
    'name' => $makerName . " Renamed",
    'description' => 'Updated description without logo.',
    'remove_logo' => '1'
];

$res = httpPost($baseUrl . '/makers', $updateFields, $cookieFile);
echo "Update Maker HTTP Code: {$res['code']}\n";
assert($res['code'] === 200);
assert(strpos($res['body'], 'updated successfully') !== false);

$updatedMaker = MakerRepository::findById($createdMaker['id']);
echo "Updated Maker in DB: Name '{$updatedMaker['name']}', Logo: " . var_export($updatedMaker['logo_path'], true) . "\n";
assert($updatedMaker['name'] === $makerName . " Renamed");
assert(empty($updatedMaker['logo_path']));
assert(!file_exists($diskLogo)); // Should have been removed from disk!
echo "Old disk logo removed successfully!\n";

echo "=== 6. TEST DELETE MAKER ===\n";
$deleteFields = [
    'action' => 'delete',
    'id' => $createdMaker['id']
];

$res = httpPost($baseUrl . '/makers', $deleteFields, $cookieFile);
echo "Delete Maker HTTP Code: {$res['code']}\n";
assert($res['code'] === 200);
assert(strpos($res['body'], 'deleted successfully') !== false);

$checkDeleted = MakerRepository::findById($createdMaker['id']);
assert($checkDeleted === null);
echo "Maker successfully deleted from database!\n";

// Cleanup temp image
@unlink($sampleImgPath);

echo "\n>>> ALL E2E INTEGRATION TESTS PASSED SUCCESSFULLY! <<<\n";
