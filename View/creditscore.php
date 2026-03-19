<?php
$siteName = 'TrustLoan';
$pageTitle = 'Check credit score';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
$isLoggedIn = isset($isLoggedIn) ? $isLoggedIn : false;

$creditScore = 0;
$scoreLabel = 'No score';
$scoreMax = 100;
$breakdown = [];
$summaryWhy = '';

if ($isLoggedIn && !empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/../Classes/CreditScore.php';
    $data = CreditScore::getForDisplay((int) $_SESSION['user_id']);
    $creditScore = (int) ($data['score'] ?? 0);
    $scoreLabel = $data['score_label'] ?? 'No score';
    $scoreMax = (int) ($data['score_max'] ?? 100);
    $breakdown = $data['breakdown'] ?? [];
    $summaryWhy = $data['summary_why'] ?? '';
}
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/creditscore.css">
</head>
<body>
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="creditscore-page<?php echo !$isLoggedIn ? ' signin-page' : ''; ?>">
        <?php if ($isLoggedIn): ?>
            <div class="creditscore-container">
                <h1 class="creditscore-title">Your credit score</h1>
                <p class="creditscore-intro">MFIs on TrustLoan use this to see how well you repay. Below is your score, a breakdown, and why it is this way.</p>

                <section class="creditscore-card" aria-label="Score summary">
                    <div class="creditscore-number-wrap">
                        <span class="creditscore-number" aria-label="Score <?php echo (int) $creditScore; ?> out of <?php echo (int) $scoreMax; ?>"><?php echo (int) $creditScore; ?></span>
                        <span class="creditscore-max">/ <?php echo (int) $scoreMax; ?></span>
                    </div>
                    <p class="creditscore-label"><?php echo htmlspecialchars($scoreLabel); ?></p>
                </section>

                <section class="creditscore-breakdown" aria-label="Score breakdown">
                    <h2 class="creditscore-section-title">Breakdown</h2>
                    <ul class="creditscore-list">
                        <?php foreach ($breakdown as $item): ?>
                        <li class="creditscore-item">
                            <div class="creditscore-item-header">
                                <span class="creditscore-item-label"><?php echo htmlspecialchars($item['label']); ?></span>
                                <span class="creditscore-item-value"><?php echo htmlspecialchars($item['value']); ?></span>
                            </div>
                            <p class="creditscore-item-reason"><?php echo htmlspecialchars($item['reason']); ?></p>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="creditscore-why" aria-label="Why your score is this way">
                    <h2 class="creditscore-section-title">Why your score is this way</h2>
                    <p class="creditscore-why-text"><?php echo htmlspecialchars($summaryWhy); ?></p>
                </section>
            </div>
        <?php else: ?>
            <div class="signin-card creditscore-form-card">
                <h1 class="signin-title">Check your credit score</h1>
                <p class="signin-desc">Sign in or log in to view your score. MFIs on TrustLoan use this to see how well you repay.</p>
                <div class="creditscore-actions">
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=signin" class="btn btn-primary">Sign in</a>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=login" class="btn btn-outline-overview">Log in</a>
                </div>
                <p class="form-hint">New user? <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=signin">Sign in</a> with your phone first. Already have an account? <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=login">Log in</a> with phone and password.</p>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
