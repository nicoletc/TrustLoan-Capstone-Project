<?php
$siteName = 'TrustLoan';
$pageTitle = 'Home';
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/landing.css">
</head>
<body class="landing-dark">
    <?php require __DIR__ . '/partials/header.php'; ?>
    <div class="landing-centre">
    <main class="landing-page">
        <div class="landing-hero">
            <div class="landing-left">
                <section class="hero">
                    <h1 class="hero-title">Fast and simple microfinance for Ghana’s informal sector</h1>
                    <p class="hero-desc">Apply for loans and check your credit score in minutes. Sign in with your phone, submit your documents, and get matched with an MFI that serves your area.</p>
                    <div class="hero-buttons">
                        <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=signin" class="btn btn-primary btn-lg">Get started</a>
                        <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=overview" class="btn btn-outline btn-lg">How it works</a>
                    </div>
                </section>
            </div>
            <div class="landing-right">
                <div class="ghana-card-wrap">
                    <img src="<?php echo htmlspecialchars($baseUrl); ?>Images/ghana-card.jpg" alt="Ghana Card" class="ghana-card-img">
                </div>
            </div>
        </div>

        <section class="landing-features">
            <div class="feature-box">
                <span class="feature-num">01</span>
                <h3 class="feature-title">Simple application</h3>
                <p class="feature-desc">Manage everything from your phone. Sign in with USSD, submit documents, and track your application.</p>
            </div>
            <div class="feature-box">
                <span class="feature-num">02</span>
                <h3 class="feature-title">Get matched to an MFI</h3>
                <p class="feature-desc">We connect you with a microfinance institution that serves your area. One application, one place.</p>
            </div>
            <div class="landing-stat">
                <span class="stat-value">TrustLoan</span>
                <p class="stat-label">Your microfinance partner</p>
            </div>
        </section>
    </main>
    </div>
</body>
</html>
