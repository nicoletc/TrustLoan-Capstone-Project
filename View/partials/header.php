<?php
/**
 * TrustLoan – Shared header (menu bar) for all front-end pages.
 * When signed in: nav = Check credit score, Dashboard, Settings; right = name + Sign out.
 * When signed out: nav = Home, How it works, Check credit score (optional Dashboard); right = Sign in.
 */
$baseUrl = isset($baseUrl) ? $baseUrl : '';
$siteName = isset($siteName) ? $siteName : 'TrustLoan';
$navActive = isset($navActive) ? $navActive : '';
$isLoggedIn = isset($isLoggedIn) ? $isLoggedIn : false;
$fullName = isset($fullName) ? $fullName : '';
$showDashboard = isset($showDashboard) ? $showDashboard : true;
$rejectionPage = isset($rejectionPage) ? $rejectionPage : false;
?>
<header class="header">
    <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php<?php echo $isLoggedIn && !$rejectionPage ? '?page=home' : ''; ?>" class="logo">
        <span class="logo-icon" aria-hidden="true"></span>
        <span class="logo-text"><?php echo htmlspecialchars($siteName); ?></span>
    </a>
    <?php if (!$rejectionPage): ?>
    <nav class="nav">
        <?php if ($isLoggedIn): ?>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=creditscore" class="<?php echo $navActive === 'creditscore' ? 'nav-active' : ''; ?>">Check credit score</a>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=home" class="<?php echo $navActive === 'dashboard' ? 'nav-active' : ''; ?>">Dashboard</a>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=settings" class="<?php echo $navActive === 'settings' ? 'nav-active' : ''; ?>">Settings</a>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php" class="<?php echo $navActive === 'home' ? 'nav-active' : ''; ?>">Home</a>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=overview" class="<?php echo $navActive === 'overview' ? 'nav-active' : ''; ?>">How it works</a>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=creditscore" class="<?php echo $navActive === 'creditscore' ? 'nav-active' : ''; ?>">Check credit score</a>
            <?php if ($showDashboard): ?><a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=home" class="<?php echo $navActive === 'dashboard' ? 'nav-active' : ''; ?>">Dashboard</a><?php endif; ?>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
    <div class="header-actions">
        <?php if ($isLoggedIn): ?>
            <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
            <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>index.php" class="header-logout-form">
                <input type="hidden" name="action" value="logout_action">
                <button type="submit" class="btn btn-sm btn-outline-header">Sign out</button>
            </form>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=signin" class="btn btn-primary<?php echo $navActive === 'signin' ? ' nav-active' : ''; ?>">Sign in</a>
        <?php endif; ?>
    </div>
</header>
