<?php
/**
 * TrustLoan – Admin login. Authenticate against admin_users, set session, redirect to Admin/.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/AdminUser.php';

$baseUrl = (function () {
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $dir = dirname($script);
    return ($dir === '/' || $dir === '\\') ? '/' : rtrim(str_replace('\\', '/', $dir), '/') . '/';
})();

$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';

if ($email === '' || $password === '') {
    header('Location: ' . $baseUrl . 'Admin/login.php?error=invalid');
    exit;
}

$admin = AdminUser::getByEmail($email);
if (!$admin || !AdminUser::verifyPassword((int) $admin['id'], $password)) {
    header('Location: ' . $baseUrl . 'Admin/login.php?error=invalid');
    exit;
}

session_regenerate_id(true);
if (function_exists('clear_borrower_session')) {
    clear_borrower_session();
}
$_SESSION['admin_user_id'] = (int) $admin['id'];
$_SESSION['admin_role'] = isset($admin['role']) ? (string) $admin['role'] : 'officer';
$_SESSION['admin_name'] = isset($admin['name']) ? (string) $admin['name'] : '';
$_SESSION['last_activity'] = time();

header('Location: ' . $baseUrl . 'Admin/');
exit;
