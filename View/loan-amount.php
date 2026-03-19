<?php
$siteName = 'TrustLoan';
$pageTitle = 'Loan amount';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
require_once __DIR__ . '/../Classes/Application.php';
$userId = $_SESSION['user_id'] ?? null;
$app = $userId ? Application::getLatestByUser($userId) : null;
$loanAmountSubmitted = $app && (float)($app['requested_amount'] ?? 0) > 0;
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
        <div class="signin-card" style="max-width: 420px;">
            <?php if ($loanAmountSubmitted): ?>
            <h1 class="signin-title">Loan amount submitted</h1>
            <p class="signin-desc">Your loan amount has been submitted, and we will contact you if needed.</p>
            <p><a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=home" class="btn btn-primary">Back to dashboard</a></p>
            <?php else: ?>
            <h1 class="signin-title">Loan amount</h1>
            <p class="signin-desc">How much do you want to borrow (GH¢) and over how many weeks?</p>

            <form class="signin-form" action="<?php echo htmlspecialchars($baseUrl); ?>index.php" method="post">
                <input type="hidden" name="action" value="submit_loan_amount">
                <div class="form-group">
                    <label for="requested_amount">Amount (GH¢)</label>
                    <input type="number" id="requested_amount" name="requested_amount" min="100" step="50" required placeholder="e.g. 2000">
                </div>
                <div class="form-group">
                    <label for="repayment_weeks">Repayment period (weeks)</label>
                    <input type="number" id="repayment_weeks" name="repayment_weeks" min="4" max="52" value="12" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Continue</button>
            </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
