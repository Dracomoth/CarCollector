<?php
/**
 * HTTP Integration Test for Models Screen, Brands Badge & Camera Upload
 */

$cookieFile = __DIR__ . '/../test_cookies.txt';
$baseUrl = 'http://localhost:8000';

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

echo "=== 1. TEST GET /models ===\n";
$res = httpGet($baseUrl . '/models', $cookieFile);
echo "GET /models HTTP Code: {$res['code']}\n";
assert($res['code'] === 200);
assert(strpos($res['body'], 'Models Collection') !== false);
assert(strpos($res['body'], 'My Collection Directory') !== false);
assert(strpos($res['body'], 'model-color-picker-widget') !== false);
assert(strpos($res['body'], 'Take Photo') !== false);

echo "=== 2. TEST GET /models.php ===\n";
$res2 = httpGet($baseUrl . '/models.php', $cookieFile);
echo "GET /models.php HTTP Code: {$res2['code']}\n";
assert($res2['code'] === 200);

echo "=== 3. CREATE DUMMY IMAGE FOR UPLOAD ===\n";
$sampleImgPath = __DIR__ . '/dummy_car.png';
file_put_contents($sampleImgPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

require_once __DIR__ . '/../source/repositories/BrandRepository.php';
require_once __DIR__ . '/../source/repositories/ScaleRepository.php';
$brandId = (int)BrandRepository::getAll()[0]['id'];
$scaleId = (int)ScaleRepository::getAll()[0]['id'];

echo "=== 4. TEST CREATE MODEL VIA POST WITH IMAGE ===\n";
$cfile = new CURLFile($sampleImgPath, 'image/png', 'car_snap.png');
$modelName = "Dodge Charger Daytona " . rand(100, 999);
$fields = [
    'action'        => 'save',
    'id'            => '',
    'brand_id'      => $brandId,
    'model'         => $modelName,
    'scale_id'      => $scaleId,
    'year'          => 2024,
    'model_year'    => 1969,
    'series'        => 'Muscle Mania',
    'series_number' => '02/10',
    'serial_number' => 'MM690',
    'image'         => $cfile
];

$res = httpPost($baseUrl . '/models', $fields, $cookieFile);
echo "Create Model HTTP Code: {$res['code']}\n";
assert($res['code'] === 200);
assert(strpos($res['body'], 'created successfully') !== false);

require_once __DIR__ . '/../source/repositories/ModelRepository.php';
$allByUser = ModelRepository::getAllByUser(1);
$createdModel = null;
foreach ($allByUser as $m) {
    if ($m['model'] === $modelName) {
        $createdModel = $m;
        break;
    }
}
echo "Created Model ID: " . ($createdModel['id'] ?? 'NONE') . "\n";
echo "Image Path in DB: " . ($createdModel['image_path'] ?? 'NONE') . "\n";
assert($createdModel !== null);
assert(!empty($createdModel['image_path']));

// Verify naming convention: <id>_<user_id>_image.<ext>
$expectedFilename = "{$createdModel['id']}_{$createdModel['user_id']}_image.png";
echo "Expected filename: {$expectedFilename}\n";
assert(strpos($createdModel['image_path'], $expectedFilename) !== false);

$diskPath = __DIR__ . '/../' . ltrim($createdModel['image_path'], '/');
echo "Disk image exists: " . (file_exists($diskPath) ? 'YES' : 'NO') . "\n";
assert(file_exists($diskPath));

echo "=== 5. TEST AJAX CAMERA UPLOAD ===\n";
$camFile = new CURLFile($sampleImgPath, 'image/png', 'camera_capture.png');
$camFields = [
    'action'       => 'upload_camera_ajax',
    'id'           => $createdModel['id'],
    'camera_image' => $camFile
];

$resCam = httpPost($baseUrl . '/models', $camFields, $cookieFile);
echo "Camera Upload HTTP Code: {$resCam['code']}\n";
echo "Camera Upload Response: {$resCam['body']}\n";
assert($resCam['code'] === 200);
$camJson = json_decode($resCam['body'], true);
assert($camJson['success'] === true);

echo "=== 6. TEST BRANDS GRID HAS MODELS BADGE ===\n";
$resBrands = httpGet($baseUrl . '/brands', $cookieFile);
echo "GET /brands HTTP Code: {$resBrands['code']}\n";
assert($resBrands['code'] === 200);
assert(strpos($resBrands['body'], 'Models') !== false);
assert(strpos($resBrands['body'], 'badge') !== false);

echo "=== 7. TEST MAKERS GRID HAS MODELS BADGE ===\n";
$resMakers = httpGet($baseUrl . '/makers', $cookieFile);
echo "GET /makers HTTP Code: {$resMakers['code']}\n";
assert($resMakers['code'] === 200);
assert(strpos($resMakers['body'], 'Models') !== false);
assert(strpos($resMakers['body'], 'badge') !== false);

echo "=== 8. TEST DELETE MODEL VIA POST ===\n";
$deleteFields = [
    'action' => 'delete',
    'id'     => $createdModel['id']
];
$resDel = httpPost($baseUrl . '/models', $deleteFields, $cookieFile);
echo "Delete Model HTTP Code: {$resDel['code']}\n";
assert($resDel['code'] === 200);
assert(strpos($resDel['body'], 'deleted successfully') !== false);

// Verify disk file is cleaned up
assert(!file_exists($diskPath));
echo "Disk image removed upon deletion: YES\n";

@unlink($sampleImgPath);
echo "\n>>> ALL HTTP & WORKFLOW TESTS PASSED SUCCESSFULLY! <<<\n";
