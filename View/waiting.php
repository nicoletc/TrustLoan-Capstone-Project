<?php
$siteName = 'TrustLoan';
$pageTitle = 'Waiting for confirmation';
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/waiting.css">
</head>
<body>
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="waiting-page">
        <div class="waiting-card">
            <h1 class="signin-title">Waiting for confirmation</h1>
            <p class="signin-desc">Your application has been submitted. We’ll review it and get back to you. Check back later or we’ll notify you.</p>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=home" class="btn btn-primary btn-block">Back to home</a>
        </div>
    </main>
</body>
</html>
