<?php
/**
 * TrustLoan – Admin logout. Clear admin session, redirect to Admin login.
 */
require_once __DIR__ . '/../settings/core.php';

$baseUrl = (function () {
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $dir = dirname($script);
    return ($dir === '/' || $dir === '\\') ? '/' : rtrim(str_replace('\\', '/', $dir), '/') . '/';
})();

unset($_SESSION['admin_user_id'], $_SESSION['admin_role'], $_SESSION['admin_name']);

header('Location: ' . $baseUrl . 'Admin/login.php');
exit;
