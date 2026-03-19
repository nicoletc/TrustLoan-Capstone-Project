<?php
$siteName = 'TrustLoan';
$pageTitle = 'How it works';
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/base.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/signin.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/overview.css">
</head>
<body>
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="overview-page">
        <div class="overview-card">
            <h1 class="signin-title">How it works</h1>
            <p class="signin-desc">Six simple steps to apply for a loan and get matched with an MFI.</p>

            <section class="ready-section">
                <h2 class="ready-heading">Ready to get started?</h2>
                <div class="ready-buttons">
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=signin" class="btn btn-primary">Sign in</a>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=login" class="btn btn-outline-overview">Log in</a>
                </div>
            </section>

            <div class="step-boxes">
                <div class="step-box">
                    <span class="step-num">1</span>
                    <div class="step-content">
                        <h3>Sign in with your phone</h3>
                        <p>Enter your number and we’ll send a verification code via USSD. No app download needed.</p>
                    </div>
                </div>
                <div class="step-box">
                    <span class="step-num">2</span>
                    <div class="step-content">
                        <h3>Create your account</h3>
                        <p>Enter your name and create a password. Or log in if you already have an account.</p>
                    </div>
                </div>
                <div class="step-box">
                    <span class="step-num">3</span>
                    <div class="step-content">
                        <h3>Submit your documents</h3>
                        <p>Ghana Card, business details, and a few photos. We guide you step by step.</p>
                    </div>
                </div>
                <div class="step-box">
                    <span class="step-num">4</span>
                    <div class="step-content">
                        <h3>Choose your loan</h3>
                        <p>Request the amount you need and pick a repayment period that works for you.</p>
                    </div>
                </div>
                <div class="step-box">
                    <span class="step-num">5</span>
                    <div class="step-content">
                        <h3>Name a guarantor</h3>
                        <p>Someone who can vouch for you: name, phone, relationship, what they do.</p>
                    </div>
                </div>
                <div class="step-box">
                    <span class="step-num">6</span>
                    <div class="step-content">
                        <h3>Select your MFI</h3>
                        <p>We connect you with a microfinance institution that serves your area.</p>
                    </div>
                </div>
            </div>

            <section class="ready-section">
                <h2 class="ready-heading">Ready to get started?</h2>
                <div class="ready-buttons">
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=signin" class="btn btn-primary">Sign in</a>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=login" class="btn btn-outline-overview">Log in</a>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
