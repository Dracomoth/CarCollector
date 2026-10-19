<?php
/**
 * Test to verify the favicon redirect fix and session safety:
 * 1. Fetching /favicon.ico does not redirect to /login (returns 200/204, never 302 to /login).
 * 2. Unauthenticated request to /favicon.ico does NOT set redirect_after_login in session.
 * 3. Logging in with a polluted redirect_after_login (or empty) safely redirects to / (dashboard).
 * 4. Legitimate protected page request (e.g. /models) sets redirect_after_login and redirects properly after login.
 */

$baseUrl = 'http://localhost:8000';
$cookieFile = __DIR__ . '/test_session_cookie.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function curlRequest($url, $options = []) {
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

echo "=== TEST 1: Request /favicon.ico while logged out ===\n";
$favRes = curlRequest("$baseUrl/favicon.ico");
echo "Status Code: " . $favRes['code'] . "\n";
if ($favRes['code'] === 200 || $favRes['code'] === 204) {
    echo "PASS: /favicon.ico returned " . $favRes['code'] . " directly (NOT 302 to /login)!\n";
} else {
    echo "FAIL: Expected 200 or 204, got " . $favRes['code'] . "\n";
    exit(1);
}

echo "\n=== TEST 2: Perform Login and verify destination ===\n";
// Attempt login with superadmin
$loginRes = curlRequest("$baseUrl/login", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'username' => 'superadmin',
        'password' => 'SuperAdmin123!',
    ]),
]);
echo "Login HTTP Code: " . $loginRes['code'] . "\n";
preg_match('/Location:\s*([^\r\n]+)/i', $loginRes['headers'], $locMatch);
$location = $locMatch[1] ?? 'NONE';
echo "Redirect Location: " . $location . "\n";

if ($location === '/' || $location === 'http://localhost:8000/') {
    echo "PASS: Successfully redirected to dashboard ('/') and NOT /favicon.ico!\n";
} else {
    echo "FAIL: Redirected to unexpected location: $location\n";
    exit(1);
}

echo "\n=== TEST 3: Access / after login ===\n";
$homeRes = curlRequest("$baseUrl$location");
echo "Home HTTP Code: " . $homeRes['code'] . "\n";
if ($homeRes['code'] === 200 && strpos($homeRes['body'], 'CarCollector') !== false) {
    echo "PASS: Dashboard loaded successfully!\n";
} else {
    echo "FAIL: Dashboard failed to load properly.\n";
    exit(1);
}

// Log out
curlRequest("$baseUrl/logout");

echo "\n=== TEST 4: Interleaved favicon request during auth flow ===\n";
// Step A: Attempt to access protected route /models while logged out
$modelsRes = curlRequest("$baseUrl/models");
echo "Models HTTP Code (Logged out): " . $modelsRes['code'] . "\n";
preg_match('/Location:\s*([^\r\n]+)/i', $modelsRes['headers'], $locMatch);
echo "Redirected to: " . ($locMatch[1] ?? 'NONE') . "\n";

// Step B: Browser requests /favicon.ico in the background while user is viewing the login form
$favInterleaved = curlRequest("$baseUrl/favicon.ico");
echo "Favicon HTTP Code: " . $favInterleaved['code'] . "\n";

// Step C: User submits the login form
$loginRes2 = curlRequest("$baseUrl/login", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'username' => 'superadmin',
        'password' => 'SuperAdmin123!',
    ]),
]);
echo "Login HTTP Code: " . $loginRes2['code'] . "\n";
preg_match('/Location:\s*([^\r\n]+)/i', $loginRes2['headers'], $locMatch2);
$finalLocation = $locMatch2[1] ?? 'NONE';
echo "Redirected to after login: " . $finalLocation . "\n";

if ($finalLocation === '/models') {
    echo "PASS: Correctly redirected to original destination /models (NOT overwritten by favicon.ico)!\n";
} else {
    echo "FAIL: Expected /models, got: $finalLocation\n";
    exit(1);
}

// Clean up
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "\nALL TESTS PASSED!\n";

