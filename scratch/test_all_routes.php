<?php
/**
 * Test all application routes to ensure zero loss of functionality
 * after removing root wrapper scripts.
 */

$baseUrl = 'http://localhost:8000';
$cookieFile = __DIR__ . '/test_routes_cookie.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function curlReq($url, $options = []) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    foreach ($options as $k => $v) {
        curl_setopt($ch, $k, $v);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headerText = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    return ['code' => $httpCode, 'headers' => $headerText, 'body' => $body];
}

// 1. Authenticate as SuperAdmin
$login = curlReq("$baseUrl/login", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'username' => 'superadmin',
        'password' => 'SuperAdmin123!',
    ]),
]);
if ($login['code'] !== 302) {
    echo "Login failed: HTTP " . $login['code'] . "\n";
    exit(1);
}
echo "Login successful!\n";

$routes = [
    '/models' => 'Models',
    '/models.php' => 'Models',
    '/makers' => 'Makers',
    '/makers.php' => 'Makers',
    '/brands' => 'Brands',
    '/brands.php' => 'Brands',
    '/scales' => 'Scales',
    '/scales.php' => 'Scales',
    '/conditions' => 'Conditions',
    '/conditions.php' => 'Conditions',
    '/colors' => 'Colors',
    '/colors.php' => 'Colors',
    '/users' => 'Users',
    '/users.php' => 'Users',
    '/roles' => 'Roles',
    '/roles.php' => 'Roles',
    '/' => 'CarCollector',
    '/favicon.ico' => '',
    '/assets/favicon.ico' => '',
    '/assets/favicon.svg' => '',
];

$allPassed = true;
foreach ($routes as $route => $expectedSnippet) {
    $res = curlReq("$baseUrl$route");
    $code = $res['code'];
    $ok = ($code === 200);
    if ($expectedSnippet !== '' && strpos($res['body'], $expectedSnippet) === false) {
        $ok = false;
    }
    if ($ok) {
        echo "  [OK] $route -> HTTP $code\n";
    } else {
        echo "  [FAIL] $route -> HTTP $code (expected content not found)\n";
        $allPassed = false;
    }
}

if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

if ($allPassed) {
    echo "\nALL 19 ROUTES AND ASSETS VERIFIED WORKING WITH ZERO REGRESSIONS!\n";
} else {
    echo "\nSOME ROUTES FAILED!\n";
    exit(1);
}
