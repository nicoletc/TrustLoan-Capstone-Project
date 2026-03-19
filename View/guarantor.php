<?php
$siteName = 'TrustLoan';
$pageTitle = 'Guarantor';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
require_once __DIR__ . '/../Classes/Application.php';
$userId = $_SESSION['user_id'] ?? null;
$app = $userId ? Application::getLatestByUser($userId) : null;
$guarantorSubmitted = $app && Application::hasGuarantor($app['id']);
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
            <?php if ($guarantorSubmitted): ?>
            <h1 class="signin-title">Guarantor submitted</h1>
            <p class="signin-desc">Your guarantor details have been submitted, and we will contact you if needed.</p>
            <p><a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=home" class="btn btn-primary">Back to dashboard</a></p>
            <?php else: ?>
            <h1 class="signin-title">Guarantor</h1>
            <p class="signin-desc">Name someone who can vouch for you (name, phone, relationship, what they do).</p>

            <form class="signin-form" action="<?php echo htmlspecialchars($baseUrl); ?>index.php" method="post">
                <input type="hidden" name="action" value="submit_guarantor">
                <div class="form-group">
                    <label for="guarantor_name">Guarantor name</label>
                    <input type="text" id="guarantor_name" name="guarantor_name" required placeholder="e.g. Kofi Asante">
                </div>
                <div class="form-group">
                    <label for="guarantor_phone">Phone number</label>
                    <input type="tel" id="guarantor_phone" name="guarantor_phone" required placeholder="e.g. 024 412 3456">
                </div>
                <div class="form-group">
                    <label for="relationship">Relationship</label>
                    <input type="text" id="relationship" name="relationship" required placeholder="e.g. Brother, Friend">
                </div>
                <div class="form-group">
                    <label for="occupation">What they do</label>
                    <input type="text" id="occupation" name="occupation" placeholder="e.g. Trader, Teacher">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Continue</button>
            </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
