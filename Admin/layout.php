<?php
$siteName = 'TrustLoan';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
$adminPage = isset($adminPage) ? $adminPage : 'dashboard';
$adminPageTitle = isset($adminPageTitle) ? $adminPageTitle : 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/base.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/admin.css">
    <script>window.adminBaseUrl = <?php echo json_encode($baseUrl); ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo htmlspecialchars($baseUrl); ?>js/admin.js"></script>
</head>
<body class="admin-body">
    <header class="admin-mfi-header">
        <?php $mfiHeaderName = defined('TRUSTLOAN_MFI_NAME') ? TRUSTLOAN_MFI_NAME : 'Adenta Municipal'; ?>
        <span class="admin-mfi-header-name"><?php echo htmlspecialchars($mfiHeaderName); ?></span>
    </header>
    <div class="admin-body-inner">
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/"><?php echo htmlspecialchars($siteName); ?> Admin</a>
        </div>
        <nav class="admin-nav">
            <a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/?page=dashboard" class="<?php echo $adminPage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/?page=verifications" class="<?php echo $adminPage === 'verifications' ? 'active' : ''; ?>">Verifications</a>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/?page=applicants" class="<?php echo $adminPage === 'applicants' ? 'active' : ''; ?>">Applicants</a>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/?page=loans" class="<?php echo $adminPage === 'loans' ? 'active' : ''; ?>">Loans</a>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/?page=groups" class="<?php echo $adminPage === 'groups' ? 'active' : ''; ?>">Groups</a>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/?page=guarantors" class="<?php echo $adminPage === 'guarantors' ? 'active' : ''; ?>">Guarantors</a>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>Admin/?page=settings" class="<?php echo $adminPage === 'settings' ? 'active' : ''; ?>">Settings</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="admin-topbar">
            <h1 class="admin-title"><?php echo htmlspecialchars($adminPageTitle); ?></h1>
            <div class="admin-topbar-actions">
                <span class="admin-user-name"><?php echo htmlspecialchars(isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin'); ?></span>
                <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>index.php" class="admin-logout-form" style="display:inline;">
                <input type="hidden" name="action" value="admin_logout_action">
                <button type="submit" class="btn btn-sm">Sign out</button>
            </form>
            </div>
        </div>
        <div class="admin-content">
            <?php echo isset($adminContent) ? $adminContent : ''; ?>
        </div>
    </main>
    </div>
</body>
</html>
