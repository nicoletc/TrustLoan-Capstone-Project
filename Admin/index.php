<?php
/**
 * TrustLoan – Admin entry. Only admin_users (admin/officer/verifier) can access; others to Admin login.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';

$baseUrl = (function () {
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $dir = dirname(dirname($script));
    return ($dir === '/' || $dir === '\\') ? '/' : rtrim(str_replace('\\', '/', $dir), '/') . '/';
})();

block_borrower_from_admin($baseUrl);

if (!is_admin_logged_in()) {
    header('Location: ' . $baseUrl . 'Admin/login.php');
    exit;
}

$adminPage = isset($_GET['page']) ? trim($_GET['page']) : 'dashboard';
$allowed = ['dashboard', 'verifications', 'applicants', 'loans', 'groups', 'guarantors', 'settings'];
if (!in_array($adminPage, $allowed, true)) {
    $adminPage = 'dashboard';
}

$adminPageTitle = ['dashboard' => 'Dashboard', 'verifications' => 'Verifications', 'applicants' => 'Applicants', 'loans' => 'Loans', 'groups' => 'Groups', 'guarantors' => 'Guarantors', 'settings' => 'Settings'][$adminPage];

ob_start();
require __DIR__ . '/pages/' . $adminPage . '.php';
$adminContent = ob_get_clean();

require __DIR__ . '/layout.php';
