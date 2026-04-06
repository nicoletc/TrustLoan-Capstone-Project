<?php
$siteName = 'TrustLoan';
$pageTitle = 'Check credit score';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
$isLoggedIn = isset($isLoggedIn) ? $isLoggedIn : false;

$creditScore = 0;
$scoreLabel = 'No score';
$scoreMax = 100;
$summaryWhy = '';
$mlUnavailable = false;
$borrower = null;
$calculatedAtFormatted = '';

if ($isLoggedIn && !empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/../Classes/CreditScore.php';
    $data = CreditScore::getForDisplay((int) $_SESSION['user_id'], false);
    $creditScore = (int) ($data['score'] ?? 0);
    $scoreLabel = $data['score_label'] ?? 'No score';
    $scoreMax = (int) ($data['score_max'] ?? 100);
    $summaryWhy = $data['summary_why'] ?? '';
    $mlUnavailable = !empty($data['ml_service_unreachable']);
    if (!empty($data['borrower']) && is_array($data['borrower'])) {
        $borrower = $data['borrower'];
    }
    if (!empty($data['calculated_at'])) {
        $t = strtotime($data['calculated_at']);
        if ($t) {
            $calculatedAtFormatted = date('j M Y, H:i', $t);
        }
    }
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
                <p class="creditscore-intro">MFIs on TrustLoan use this score when reviewing your application.</p>

                <section class="creditscore-card" aria-label="Score summary">
                    <div class="creditscore-number-wrap">
                        <span class="creditscore-number" aria-label="Score <?php echo (int) $creditScore; ?> out of <?php echo (int) $scoreMax; ?>"><?php echo (int) $creditScore; ?></span>
                        <span class="creditscore-max">/ <?php echo (int) $scoreMax; ?></span>
                    </div>
                    <p class="creditscore-label"><?php echo htmlspecialchars($scoreLabel); ?></p>
                </section>

                <?php if ($borrower !== null): ?>
                <section class="creditscore-insights" aria-label="Risk summary">
                    <h2 class="creditscore-section-title">What this means for you</h2>
                    <p class="creditscore-risk-tier">
                        <span class="creditscore-risk-label">Estimated risk level</span>
                        <span class="creditscore-risk-badge creditscore-risk-<?php echo htmlspecialchars($borrower['risk_tier'] ?? 'moderate'); ?>"><?php echo htmlspecialchars($borrower['risk_tier_label'] ?? ''); ?></span>
                    </p>
                    <p class="creditscore-risk-blurb"><?php echo htmlspecialchars($borrower['risk_blurb'] ?? ''); ?></p>
                    <?php if (!empty($borrower['is_approximate'])): ?>
                    <p class="creditscore-approx-note">Estimated from your saved TrustLoan score<?php echo $mlUnavailable ? ' — we could not reach the scoring service just now.' : '.'; ?> Check back later for a fresh assessment.</p>
                    <?php endif; ?>
                    <?php if (!empty($borrower['tips']) && is_array($borrower['tips'])): ?>
                    <h3 class="creditscore-tips-title">Ways to strengthen your profile</h3>
                    <ul class="creditscore-tips-list">
                        <?php foreach ($borrower['tips'] as $tip): ?>
                        <li><?php echo htmlspecialchars((string) $tip); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <?php if ($calculatedAtFormatted !== ''): ?>
                    <p class="creditscore-updated">Score last updated: <?php echo htmlspecialchars($calculatedAtFormatted); ?></p>
                    <?php endif; ?>
                </section>
                <?php endif; ?>

                <?php if ($summaryWhy !== ''): ?>
                <section class="creditscore-why" aria-label="About your score">
                    <p class="creditscore-why-text"><?php echo htmlspecialchars($summaryWhy); ?></p>
                </section>
                <?php endif; ?>

                <?php if ($mlUnavailable): ?>
                <p class="creditscore-why-text creditscore-ml-hint" role="status">We could not reach the scoring service just now. If your score shows &ldquo;No score&rdquo; or looks out of date, try again after a moment.</p>
                <?php endif; ?>
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
