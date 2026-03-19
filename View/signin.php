<?php
$siteName = 'TrustLoan';
$pageTitle = 'Sign in';
$pendingPhone = isset($_SESSION['pending_phone']) ? (string) $_SESSION['pending_phone'] : '';
$showCodeStep = $pendingPhone !== '' && (empty($_GET['step']) || $_GET['step'] !== 'phone');
$invalidCode = isset($_GET['error']) && $_GET['error'] === 'invalid_code';
$sessionTimeoutNotice = !empty($_COOKIE['session_timeout']);
if (!empty($_COOKIE['session_timeout'])) {
    setcookie('session_timeout', '', time() - 3600, '/');
}
$baseUrl = isset($baseUrl) ? $baseUrl : '';
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19/build/css/intlTelInput.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/base.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/signin.css">
</head>
<body>
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="signin-page">
        <div class="signin-card">
            <h1 class="signin-title">Sign in</h1>
            <p class="signin-desc"><?php echo $showCodeStep ? 'Enter the verification code we sent to your phone.' : 'Enter your phone number. We’ll send a verification code via USSD to your phone.'; ?></p>

            <?php if ($invalidCode): ?>
            <p class="form-error" role="alert">Invalid or expired code. Please try again or request a new code.</p>
            <?php endif; ?>
            <?php if ($sessionTimeoutNotice): ?>
            <p class="form-notice" role="status">You were logged out due to inactivity. Sign in or log in again.</p>
            <?php endif; ?>

            <?php if (!$showCodeStep): ?>
            <form class="signin-form" id="signinForm" action="<?php echo htmlspecialchars($baseUrl); ?>index.php" method="post">
                <input type="hidden" name="action" value="send_verification_code">
                <input type="hidden" name="phone_full" id="phone_full" value="">
                <div class="form-group" id="phoneStep">
                    <label for="phone">Phone number</label>
                    <input type="tel" id="phone" name="phone" placeholder="e.g. 24 412 3456" required autocomplete="tel">
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="sendCodeBtn">Send verification code</button>
            </form>
            <div class="signin-already-member">
                <p class="signin-already-member-text">Already a member? <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=login" class="signin-login-link">Log in here</a></p>
            </div>
            <?php else:
                $devCode = (defined('TRUSTLOAN_DEV_MODE') && TRUSTLOAN_DEV_MODE && isset($_SESSION['dev_verification_code'])) ? $_SESSION['dev_verification_code'] : '';
            ?>
            <?php if ($devCode !== ''): ?>
            <p class="dev-code" role="status"><strong>Dev mode:</strong> Your code is <code><?php echo htmlspecialchars($devCode); ?></code></p>
            <?php endif; ?>
            <form class="signin-form" id="verifyForm" action="<?php echo htmlspecialchars($baseUrl); ?>index.php" method="post">
                <input type="hidden" name="action" value="verify_code">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($pendingPhone); ?>">
                <div class="form-group" id="codeStep">
                    <label for="code">Verification code</label>
                    <input type="text" id="code" name="code" placeholder="Enter code from USSD" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Verify & sign in</button>
                <p class="form-hint">Code sent to <span id="phoneDisplay"><?php echo htmlspecialchars($pendingPhone); ?></span>. <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=signin&amp;step=phone" class="link-btn">Change number</a></p>
            </form>
            <?php endif; ?>
        </div>
    </main>

    <script>window.baseUrl = <?php echo json_encode($baseUrl); ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@19/build/js/intlTelInput.min.js"></script>
    <script src="<?php echo htmlspecialchars($baseUrl); ?>js/signin.js"></script>
</body>
</html>
