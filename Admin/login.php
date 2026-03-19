<?php
/**
 * TrustLoan – Admin login page. Session-based auth via admin_users.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';

$baseUrl = (function () {
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $dir = dirname(dirname($script));
    return ($dir === '/' || $dir === '\\') ? '/' : rtrim(str_replace('\\', '/', $dir), '/') . '/';
})();

if (is_admin_logged_in()) {
    header('Location: ' . $baseUrl . 'Admin/');
    exit;
}
block_borrower_from_admin($baseUrl);

$error = isset($_GET['error']) ? trim((string) $_GET['error']) : '';
$sessionTimeout = isset($_GET['timeout']) && $_GET['timeout'] === '1' || !empty($_COOKIE['session_timeout']);
if (!empty($_COOKIE['session_timeout'])) {
    setcookie('session_timeout', '', time() - 3600, '/');
}
$siteName = 'TrustLoan';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/base.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="admin-body admin-login-page">
    <div class="admin-login-card">
        <h1 class="admin-login-title"><?php echo htmlspecialchars($siteName); ?> Admin</h1>
        <p class="admin-login-desc">Welcome back. Sign in with your email and password.</p>
        <form class="admin-login-form" method="post" action="<?php echo htmlspecialchars($baseUrl); ?>index.php">
            <input type="hidden" name="action" value="admin_login_action">
            <div class="form-group">
                <label for="admin_email">Email</label>
                <input type="email" id="admin_email" name="email" required placeholder="admin@trustloan.local" autocomplete="email">
            </div>
            <div class="form-group">
                <label for="admin_password">Password</label>
                <input type="password" id="admin_password" name="password" required placeholder="Password" autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign in</button>
        </form>
    </div>
    <script>
    (function() {
        var params = new URLSearchParams(window.location.search);
        if (params.get('error') === 'invalid') {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Sign in failed', text: 'Invalid email or password.' });
            history.replaceState({}, '', window.location.pathname);
        }
        if (params.get('timeout') === '1' || document.cookie.indexOf('session_timeout') !== -1) {
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'info', title: 'Session expired', text: 'You were logged out due to inactivity. Please sign in again.' });
            history.replaceState({}, '', window.location.pathname);
        }
    })();
    </script>
</body>
</html>
