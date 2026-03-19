<?php
/**
 * TrustLoan – Core bootstrap: session and auth checks.
 */
if (!defined('TRUSTLOAN_CORE_LOADED')) {
    define('TRUSTLOAN_CORE_LOADED', true);
}

/** Inactivity timeout in seconds; after this, user is logged out. 30 minutes default. */
if (!defined('TRUSTLOAN_SESSION_INACTIVITY_TIMEOUT')) {
    define('TRUSTLOAN_SESSION_INACTIVITY_TIMEOUT', 1800);
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$session_expired_due_to_inactivity = false;
if (isset($_SESSION['user_id']) || (isset($_SESSION['admin_user_id']) && (int) $_SESSION['admin_user_id'] > 0)) {
    $now = time();
    $last = isset($_SESSION['last_activity']) ? (int) $_SESSION['last_activity'] : $now;
    $timeout = defined('TRUSTLOAN_SESSION_INACTIVITY_TIMEOUT') ? (int) TRUSTLOAN_SESSION_INACTIVITY_TIMEOUT : 1800;
    if (($now - $last) > $timeout) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        session_start();
        setcookie('session_timeout', '1', time() + 120, '/');
        $session_expired_due_to_inactivity = true;
    } else {
        $_SESSION['last_activity'] = $now;
    }
}

/** Set to true to show verification code on sign-in page (no SMS). */
if (!defined('TRUSTLOAN_DEV_MODE')) {
    define('TRUSTLOAN_DEV_MODE', true);
}

/** Single MFI for now; future work will add more. Used on borrower MFI choice and admin header. */
if (!defined('TRUSTLOAN_MFI_NAME')) {
    define('TRUSTLOAN_MFI_NAME', 'Adenta Municipal');
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] !== '';
}

/** Borrower/customer session (users table). Same as is_logged_in for clarity in role checks. */
function is_borrower_logged_in() {
    return is_logged_in();
}

/** Role: 1 = admin (access to Admin pages), 2 = customer (everyone who signs up). */
function has_admin_privileges() {
    if (!is_logged_in()) return false;
    $role = isset($_SESSION['role']) ? (int) $_SESSION['role'] : 0;
    return $role === 1;
}

/** Admin area: logged in via admin_users table (admin, officer, verifier). */
function is_admin_logged_in() {
    return isset($_SESSION['admin_user_id']) && (int) $_SESSION['admin_user_id'] > 0;
}

function get_admin_user_id() {
    return is_admin_logged_in() ? (int) $_SESSION['admin_user_id'] : 0;
}

/** Role-based access: clear borrower session so only admin session remains (e.g. after admin login). */
function clear_borrower_session() {
    $keys = ['user_id', 'role', 'full_name', 'phone', 'last_activity', 'pending_phone', 'pending_login_user_id', 'login_show_register'];
    foreach ($keys as $k) {
        if (isset($_SESSION[$k])) unset($_SESSION[$k]);
    }
}

/** Role-based access: clear admin session so only borrower session remains (e.g. after borrower login). */
function clear_admin_session() {
    $keys = ['admin_user_id', 'admin_role', 'admin_name', 'last_activity'];
    foreach ($keys as $k) {
        if (isset($_SESSION[$k])) unset($_SESSION[$k]);
    }
}

/**
 * Authorization: block borrowers from admin area. Call from Admin entry points.
 * If current user has borrower session but no admin session, redirect to main app.
 * @param string $baseUrl Base URL for redirect (e.g. /capstone/)
 * @return bool true if access allowed (admin or not logged in as borrower), false if redirect was sent
 */
function block_borrower_from_admin($baseUrl) {
    if (is_borrower_logged_in() && !is_admin_logged_in()) {
        header('Location: ' . $baseUrl . 'index.php?page=home&denied=1');
        exit;
    }
    return true;
}
