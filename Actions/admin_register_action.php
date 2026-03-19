<?php
/**
 * TrustLoan – Admin registration. Create admin_users row; redirect to login so they can sign in with email and password.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/AdminUser.php';

$baseUrl = (function () {
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $dir = dirname($script);
    return ($dir === '/' || $dir === '\\') ? '/' : rtrim(str_replace('\\', '/', $dir), '/') . '/';
})();

$name = isset($_POST['name']) ? trim((string) $_POST['name']) : '';
$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';
$passwordConfirm = isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : '';
$role = isset($_POST['role']) ? trim((string) $_POST['role']) : 'officer';

if ($name === '' || $email === '') {
    header('Location: ' . $baseUrl . 'Admin/login.php?error=validation');
    exit;
}

if (strlen($password) < 6 || $password !== $passwordConfirm) {
    header('Location: ' . $baseUrl . 'Admin/login.php?error=validation');
    exit;
}

if (AdminUser::getByEmail($email)) {
    header('Location: ' . $baseUrl . 'Admin/login.php?error=email_used');
    exit;
}

$userId = AdminUser::create($name, $email, $password, $role);
if (!$userId) {
    header('Location: ' . $baseUrl . 'Admin/login.php?error=failed');
    exit;
}

header('Location: ' . $baseUrl . 'Admin/login.php?registered=1');
exit;
