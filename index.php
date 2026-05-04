<?php
/** Routes POST actions, then loads View/*.php by ?page=. */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/settings/core.php';
require_once __DIR__ . '/settings/db_class.php';

$baseUrl = (function () {
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $dir = dirname($script);
    return ($dir === '/' || $dir === '\\') ? '/' : rtrim(str_replace('\\', '/', $dir), '/') . '/';
})();

// post_max exceeded → empty POST/FILES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false) {
    $_SESSION['documents_error'] = 'Request too large. Please use smaller images (under 2MB each) and try again.';
    header('Location: ' . $baseUrl . 'index.php?page=documents');
    exit;
}

// POST + action → Actions/{action}.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && trim($_POST['action']) !== '') {
    $action = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['action'])));
    $actionFile = __DIR__ . '/Actions/' . $action . '.php';
    if ($action !== '' && is_file($actionFile)) {
        require $actionFile;
        return;
    }
}

$page = isset($_GET['page']) ? trim($_GET['page']) : 'landing';

$protectedPages = ['documents', 'loan-amount', 'guarantor', 'mfi', 'waiting', 'home', 'settings'];
if (in_array($page, $protectedPages, true) && !is_logged_in()) {
    header('Location: ' . $baseUrl . 'index.php?page=signin');
    exit;
}

// Nav active tab + header
$navActive = 'home';
if ($page === 'overview') {
    $navActive = 'overview';
} elseif ($page === 'creditscore') {
    $navActive = 'creditscore';
} elseif ($page === 'settings') {
    $navActive = 'settings';
} elseif (in_array($page, ['home', 'documents', 'loan-amount', 'guarantor', 'mfi', 'waiting'], true)) {
    $navActive = 'dashboard';
} elseif ($page === 'signin' || $page === 'login') {
    $navActive = 'signin';
}
$isLoggedIn = is_logged_in();
$fullName = ($isLoggedIn && isset($_SESSION['full_name'])) ? (string) $_SESSION['full_name'] : '';
// Hide Dashboard on marketing / credit-score pages
$showDashboard = !in_array($page, ['landing', 'overview', 'creditscore'], true);

switch ($page) {
    case 'overview':
        require __DIR__ . '/View/overview.php';
        break;
    case 'signin':
        require __DIR__ . '/View/signin.php';
        break;
    case 'login':
        require __DIR__ . '/View/login.php';
        break;
    case 'creditscore':
        require __DIR__ . '/View/creditscore.php';
        break;
    case 'documents':
        require __DIR__ . '/View/documents.php';
        break;
    case 'loan-amount':
        require __DIR__ . '/View/loan-amount.php';
        break;
    case 'guarantor':
        require __DIR__ . '/View/guarantor.php';
        break;
    case 'mfi':
        require __DIR__ . '/View/mfi.php';
        break;
    case 'waiting':
        require __DIR__ . '/View/waiting.php';
        break;
    case 'home':
        require __DIR__ . '/View/home.php';
        break;
    case 'settings':
        require __DIR__ . '/View/settings.php';
        break;
    case 'view_application_image':
        require __DIR__ . '/View/view_application_image.php';
        return;
    case 'landing':
    default:
        require __DIR__ . '/View/landing.php';
        break;
}
