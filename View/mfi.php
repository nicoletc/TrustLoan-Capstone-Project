<?php
$siteName = 'TrustLoan';
$pageTitle = 'Choose your MFI';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
require_once __DIR__ . '/../Classes/Application.php';
$userId = $_SESSION['user_id'] ?? null;
$app = $userId ? Application::getLatestByUser($userId) : null;
$mfiSubmitted = $app && Application::hasMfi($app['id']);
$allMfis = Application::getDefaultMfiForBorrower();
$areas = array_unique(array_column($allMfis, 'area_slug'));
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/mfi.css">
</head>
<body>
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="mfi-page">
        <div class="mfi-card">
            <?php if ($mfiSubmitted): ?>
            <h1 class="signin-title">MFI choice submitted</h1>
            <p class="signin-desc">Your MFI choice has been submitted, and we will contact you if needed.</p>
            <p><a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=home" class="btn btn-primary">Back to dashboard</a></p>
            <?php else: ?>
            <h1 class="signin-title">Choose your MFI</h1>
            <p class="signin-desc">Select the microfinance institution that serves your area.</p>

            <form class="mfi-form" id="mfiForm" action="<?php echo htmlspecialchars($baseUrl); ?>index.php" method="post">
                <input type="hidden" name="action" value="submit_mfi">
                <div class="form-group">
                    <label for="mfi_id">MFI</label>
                    <select id="mfi_id" name="mfi_id" required>
                        <option value="">Select MFI…</option>
                        <?php foreach ($allMfis as $mfi): ?>
                        <option value="<?php echo (int) $mfi['id']; ?>"><?php echo htmlspecialchars($mfi['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($allMfis)): ?>
                <input type="hidden" name="area" value="<?php echo htmlspecialchars($allMfis[0]['area_slug'] ?? 'adenta'); ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-block">Submit & wait for confirmation</button>
            </form>
            <?php endif; ?>
        </div>
    </main>

    <script src="<?php echo htmlspecialchars($baseUrl); ?>js/main.js"></script>
</body>
</html>
