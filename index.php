<?php
/**
 * Application Front Controller & Authentication Guard
 * 
 * Central entry point for both Apache (.htaccess) on Hostinger
 * and the local development server (router.php).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = rtrim($requestUri, '/');
if ($path === '') {
    $path = '/';
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$isLoggedIn = isset($_SESSION['user']) && !empty($_SESSION['user']);
$publicRoutes = ['/login', '/logout'];

// -----------------------------------------------------------------------------
// Static Asset / Favicon Short-Circuit
// If a static file or favicon reaches index.php, return 404 or 204 immediately.
// Never redirect assets to /login or save them into session.
// -----------------------------------------------------------------------------
if ($path === '/favicon.ico') {
    $fav = file_exists(__DIR__ . '/assets/favicon.ico') ? (__DIR__ . '/assets/favicon.ico') : (__DIR__ . '/favicon.ico');
    if (file_exists($fav)) {
        header('Content-Type: image/x-icon');
        header('Content-Length: ' . filesize($fav));
        readfile($fav);
        exit;
    }
    http_response_code(204);
    exit;
}

if (preg_match('/\.(ico|png|jpe?g|gif|svg|webp|css|js|map|woff2?|ttf|eot)$/i', $path)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo "404 Not Found\n";
    exit;
}

// -----------------------------------------------------------------------------
// Authentication Guard: Redirect non-logged users to the login screen
// -----------------------------------------------------------------------------
if (!$isLoggedIn && !in_array($path, $publicRoutes, true)) {
    // Only remember safe, user-navigated GET pages for post-login redirect.
    // Exclude AJAX calls, static assets, favicon, etc.
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $isAsset = (bool) preg_match('/\.(ico|png|jpe?g|gif|svg|webp|css|js|map|woff2?|ttf|eot|json|xml|txt)$/i', $path);

    if ($requestMethod === 'GET' && !$isAjax && !$isAsset && $path !== '/favicon.ico') {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    }

    header('Location: /login', true, 302);
    exit;
}

// If already logged in and visiting /login, redirect to home
if ($isLoggedIn && $path === '/login') {
    header('Location: /', true, 302);
    exit;
}

// -----------------------------------------------------------------------------
// Application Route Dispatcher
// -----------------------------------------------------------------------------
switch ($path) {
    case '/login':
        $error = null;
        if ($requestMethod === 'POST') {
            require_once __DIR__ . '/source/services/AuthService.php';
            $loginInput = trim($_POST['username'] ?? '');
            $passwordInput = trim($_POST['password'] ?? '');

            $authResult = AuthService::attempt($loginInput, $passwordInput);

            if ($authResult['success']) {
                $_SESSION['user'] = $authResult['user'];

                $redirectTo = $_SESSION['redirect_after_login'] ?? '/';
                unset($_SESSION['redirect_after_login']);

                // Sanitize redirect target: ensure internal path, exclude assets and auth pages
                $redirectPath = parse_url($redirectTo, PHP_URL_PATH) ?? '/';
                $isInvalid = empty($redirectTo)
                    || !is_string($redirectTo)
                    || str_starts_with($redirectTo, '//')
                    || !str_starts_with($redirectTo, '/')
                    || in_array(rtrim($redirectPath, '/'), ['/login', '/logout', ''], true)
                    || (bool) preg_match('/\.(ico|png|jpe?g|gif|svg|webp|css|js|map|woff2?|ttf|eot|json|xml|txt)$/i', $redirectPath);

                if ($isInvalid) {
                    $redirectTo = '/';
                }

                header('Location: ' . $redirectTo, true, 302);
                exit;
            } else {
                $error = $authResult['error'];
            }
        }

        require_once __DIR__ . '/source/services/Database.php';
        require_once __DIR__ . '/source/repositories/SystemInfoRepository.php';
        $isDbOnline = Database::isOnline();
        $systemVersions = SystemInfoRepository::getAllVersions();
        $systemVersion = $systemVersions['system'] ?? 'N/A';
        $databaseVersion = $systemVersions['database'] ?? 'N/A';

        require __DIR__ . '/source/views/login.php';
        break;

    case '/logout':
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        header('Location: /login', true, 302);
        exit;

    case '/users':
    case '/users.php':
        require_once __DIR__ . '/source/controllers/UsersController.php';
        UsersController::handle();
        break;

    case '/roles':
    case '/roles.php':
        require_once __DIR__ . '/source/controllers/RolesController.php';
        RolesController::handle();
        break;

    case '/models':
    case '/models.php':
        require_once __DIR__ . '/source/controllers/ModelsController.php';
        ModelsController::handle();
        break;

    case '/makers':
    case '/makers.php':
        require_once __DIR__ . '/source/controllers/MakersController.php';
        MakersController::handle();
        break;

    case '/brands':
    case '/brands.php':
        require_once __DIR__ . '/source/controllers/BrandsController.php';
        BrandsController::handle();
        break;

    case '/scales':
    case '/scales.php':
        require_once __DIR__ . '/source/controllers/ScalesController.php';
        ScalesController::handle();
        break;

    case '/conditions':
    case '/conditions.php':
        require_once __DIR__ . '/source/controllers/ConditionsController.php';
        ConditionsController::handle();
        break;

    case '/colors':
    case '/colors.php':
        require_once __DIR__ . '/source/controllers/ColorsController.php';
        ColorsController::handle();
        break;

    case '/':
        require_once __DIR__ . '/source/services/PermissionService.php';
        $user = $_SESSION['user'] ?? null;
        $canAccessUsers = $user ? PermissionService::canAccess($user, 'users') : false;
        $canAccessRoles = $user ? PermissionService::canAccess($user, 'roles') : false;
        $canAccessModels = $user ? PermissionService::canAccess($user, 'models') : false;
        $canAccessMakers = $user ? PermissionService::canAccess($user, 'makers') : false;
        $canAccessBrands = $user ? PermissionService::canAccess($user, 'brands') : false;
        $canAccessScales = $user ? PermissionService::canAccess($user, 'scales') : false;
        $canAccessConditions = $user ? PermissionService::canAccess($user, 'conditions') : false;
        $canAccessColors = $user ? PermissionService::canAccess($user, 'colors') : false;
        require __DIR__ . '/source/views/home.php';
        break;

    default:
        http_response_code(404);
        echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 - Page Not Found</h1><p><a href='/'>Return to Home</a></p></body></html>";
        break;
}
