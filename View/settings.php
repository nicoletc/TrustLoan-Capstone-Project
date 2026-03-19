<?php
$siteName = 'TrustLoan';
$pageTitle = 'Settings';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
$fullName = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : '—';
$phone = isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : '—';
$ghanaCardStatus = 'Not verified'; // placeholder – replace with real status when available
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/settings.css">
</head>
<body>
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="settings-page">
        <div class="settings-centre">
            <h1 class="settings-title">Settings</h1>
            <p class="settings-desc">Manage your account. You cannot edit identity details, loan terms, or group assignments here.</p>

            <!-- Language -->
            <section class="settings-block">
                <h2 class="settings-block-title">Language</h2>
                <p class="settings-block-desc">Choose your preferred language.</p>
                <div class="form-group">
                    <label for="language">Language</label>
                    <select id="language" name="language" class="settings-select">
                        <option value="en" selected>English</option>
                        <option value="tw">Twi</option>
                    </select>
                </div>
            </section>

            <!-- Change PIN or password -->
            <section class="settings-block">
                <h2 class="settings-block-title">Change PIN or password</h2>
                <p class="settings-block-desc">Update your login password.</p>
                <a href="#" class="btn btn-outline-settings">Change password</a>
            </section>

            <!-- Notification preferences -->
            <section class="settings-block">
                <h2 class="settings-block-title">Notification preferences</h2>
                <p class="settings-block-desc">Choose which reminders you receive.</p>
                <div class="settings-checkbox-row">
                    <label class="settings-checkbox">
                        <input type="checkbox" name="reminder_repayment" checked>
                        <span>Repayment reminders</span>
                    </label>
                </div>
                <div class="settings-checkbox-row">
                    <label class="settings-checkbox">
                        <input type="checkbox" name="reminder_meeting" checked>
                        <span>Meeting reminders</span>
                    </label>
                </div>
            </section>

            <!-- Personal details (view only) -->
            <section class="settings-block settings-block-readonly">
                <h2 class="settings-block-title">Personal details</h2>
                <p class="settings-block-desc">View only. Identity details cannot be edited here.</p>
                <dl class="settings-dl">
                    <dt>Name</dt>
                    <dd><?php echo $fullName; ?></dd>
                    <dt>Phone number</dt>
                    <dd><?php echo $phone; ?></dd>
                    <dt>Ghana Card status</dt>
                    <dd><?php echo htmlspecialchars($ghanaCardStatus); ?></dd>
                </dl>
            </section>

            <!-- Help / Contact support -->
            <section class="settings-block">
                <h2 class="settings-block-title">Help &amp; contact support</h2>
                <p class="settings-block-desc">Get help or contact support.</p>
                <a href="#" class="btn btn-outline-settings">Contact support</a>
            </section>

            <!-- Privacy and terms -->
            <section class="settings-block settings-block-footer">
                <h2 class="settings-block-title">Legal</h2>
                <p class="settings-block-desc">Read our policies.</p>
                <ul class="settings-links">
                    <li><a href="#">Privacy policy</a></li>
                    <li><a href="#">Terms of use</a></li>
                </ul>
            </section>
        </div>
    </main>
</body>
</html>
