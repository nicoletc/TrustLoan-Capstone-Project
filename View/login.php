<?php
$siteName = 'TrustLoan';
$pageTitle = 'Login';
if (function_exists('is_logged_in') && is_logged_in()) {
    header('Location: ' . (isset($baseUrl) ? $baseUrl : '') . 'index.php?page=home');
    exit;
}
$isExisting = !empty($_SESSION['pending_login_user_id']);
// Only show "Create your account" when user just verified code as new user; otherwise show name+password login
$showRegisterForm = !empty($_SESSION['pending_phone']) && !empty($_SESSION['login_show_register']);
$isDirectLogin = !$isExisting && !$showRegisterForm;
$baseUrl = isset($baseUrl) ? $baseUrl : '';
$loginError = isset($_GET['error']) ? $_GET['error'] : '';
$existingMemberNotice = isset($_GET['existing_member']) && $_GET['existing_member'] === '1';
$sessionTimeoutNotice = isset($_GET['timeout']) && $_GET['timeout'] === '1' || !empty($_COOKIE['session_timeout']);
if (!empty($_COOKIE['session_timeout'])) {
    setcookie('session_timeout', '', time() - 3600, '/');
}
$errorMessages = [
    'validation'        => 'Please enter your full name and a matching password (at least 6 characters).',
    'create'            => 'Something went wrong creating your account. Please try again.',
    'invalid'           => 'Incorrect name or password. Please try again.',
    'direct_validation' => 'Please enter your name and password.'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> – <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/base.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/signin.css">
</head>
<body>
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="signin-page">
        <div class="signin-card">
            <h1 class="signin-title"><?php echo $isDirectLogin ? 'Log in' : ($isExisting ? 'Welcome back' : 'Create your account'); ?></h1>
            <p class="signin-desc"><?php echo $isDirectLogin ? 'Enter your name and password to get back into your dashboard.' : ($isExisting ? 'Enter your password to sign in.' : 'Enter your full name and create a password to sign in next time and check your credit score.'); ?></p>

            <?php if ($existingMemberNotice && $isDirectLogin): ?>
            <p class="form-notice" role="status">You're already a member. Log in with your name and password below.</p>
            <?php endif; ?>
            <?php if ($sessionTimeoutNotice): ?>
            <p class="form-notice" role="status">You were logged out due to inactivity. Please log in again.</p>
            <?php endif; ?>
            <?php if ($loginError && isset($errorMessages[$loginError])): ?>
            <p class="form-error" role="alert"><?php echo htmlspecialchars($errorMessages[$loginError]); ?></p>
            <?php endif; ?>

            <?php if ($isDirectLogin): ?>
            <form class="signin-form" id="directLoginForm" action="<?php echo htmlspecialchars($baseUrl); ?>index.php" method="post">
                <input type="hidden" name="action" value="login_with_name">
                <div class="form-group">
                    <label for="full_name">Your name</label>
                    <input type="text" id="full_name" name="full_name" required placeholder="e.g. Ama Mensah" autocomplete="name">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Your password" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Log in</button>
            </form>
            <?php elseif ($isExisting): ?>
            <form class="signin-form" id="loginForm" action="<?php echo htmlspecialchars($baseUrl); ?>index.php" method="post">
                <input type="hidden" name="action" value="login_customer_action">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Your password" autocomplete="off">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Sign in</button>
            </form>
            <?php else: ?>
            <form class="signin-form" id="registerForm" action="<?php echo htmlspecialchars($baseUrl); ?>index.php" method="post">
                <input type="hidden" name="action" value="register_customer_action">
                <div class="form-group">
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" required placeholder="e.g. Ama Mensah" autocomplete="name">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="At least 6 characters" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="6" placeholder="Same as above" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Save & continue</button>
            </form>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo htmlspecialchars($baseUrl); ?>js/password.js"></script>
    <script src="<?php echo htmlspecialchars($baseUrl); ?>js/main.js"></script>
    <script>
(function() {
    var registerForm = document.getElementById('registerForm');
    var loginForm = document.getElementById('loginForm');
    var directLoginForm = document.getElementById('directLoginForm');

    function submitForm(form) {
        form.submit();
    }

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var name = (document.getElementById('full_name').value || '').trim();
            var p = (document.getElementById('password').value || '');
            var c = (document.getElementById('password_confirm').value || '');
            if (!name) {
                Swal.fire({ icon: 'error', title: 'Please fix', text: 'Please enter your full name.' });
                return;
            }
            var v = TrustLoanPassword && TrustLoanPassword.validatePassword ? TrustLoanPassword.validatePassword(p) : { valid: p.length >= 6, message: '' };
            if (!v.valid) {
                Swal.fire({ icon: 'error', title: 'Please fix', text: v.message || 'Password must be at least 6 characters.' });
                return;
            }
            if (!(TrustLoanPassword && TrustLoanPassword.passwordsMatch ? TrustLoanPassword.passwordsMatch(p, c) : p === c)) {
                Swal.fire({ icon: 'error', title: 'Please fix', text: 'Passwords do not match.' });
                return;
            }
            submitForm(registerForm);
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            var p = (document.getElementById('password').value || '');
            if (!p) {
                e.preventDefault();
                Swal.fire({ icon: 'error', title: 'Please fix', text: 'Please enter your password.' });
                return;
            }
            return true;
        });
    }

    if (directLoginForm) {
        directLoginForm.addEventListener('submit', function(e) {
            var name = (directLoginForm.querySelector('input[name="full_name"]') || {}).value;
            var p = (directLoginForm.querySelector('input[name="password"]') || {}).value || '';
            name = name ? name.trim() : '';
            if (!name || !p) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Please fix', text: !name ? 'Please enter your name.' : 'Please enter your password.' });
                return;
            }
            return true;
        });
    }
})();
    </script>
</body>
</html>
