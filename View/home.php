<?php
$siteName = 'TrustLoan';
$pageTitle = 'Home';
$baseUrl = isset($baseUrl) ? $baseUrl : '';
$fullName = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Borrower';
$accessDeniedNotice = isset($_GET['denied']) && $_GET['denied'] === '1';

$groupName = '—';
$groupHeadName = '—';
$groupHeadPhone = '—';
$repaymentSummary = '—';
$meetingSummary = '—';
$showApprovalAlert = false;
$applicationRejected = false;
$showDocumentsSavedSuccess = !empty($_SESSION['documents_saved_success']);
if ($showDocumentsSavedSuccess) {
    unset($_SESSION['documents_saved_success']);
}

if (!empty($_SESSION['user_id'])) {
    require_once __DIR__ . '/../Classes/Application.php';
    require_once __DIR__ . '/../Classes/Loan.php';
    require_once __DIR__ . '/../Classes/Group.php';
    $userId = (int) $_SESSION['user_id'];
    $app = Application::getLatestByUser($userId);
    $congratulationsAlreadyShown = $app && (int)(isset($app['approval_congratulations_shown']) ? $app['approval_congratulations_shown'] : 0) === 1;

    if ($app) {
        $status = isset($app['status']) ? strtolower($app['status']) : '';
        if ($status === 'rejected') {
            $applicationRejected = true;
        } elseif ($status === 'approved' && !$congratulationsAlreadyShown) {
            $showApprovalAlert = true;
            Application::markApprovalCongratulationsShown($app['id']);
            $_SESSION['approval_congrats_shown_app_' . (int)$app['id']] = true;
        }
    }

    if (!$applicationRejected) {
    $loan = Loan::getLatestByUser($userId);
    if ($loan && !empty($loan['group_id'])) {
        $group = Group::getByIdWithMembers((int) $loan['group_id']);
        if ($group) {
            $groupName = isset($group['name']) ? htmlspecialchars($group['name']) : '—';
            foreach (isset($group['members']) ? $group['members'] : [] as $m) {
                if (isset($m['role']) && $m['role'] === 'head') {
                    $groupHeadName = isset($m['full_name']) ? htmlspecialchars($m['full_name']) : '—';
                    $groupHeadPhone = isset($m['phone']) ? htmlspecialchars($m['phone']) : '—';
                    break;
                }
            }
            $loc = isset($group['meeting_location']) ? $group['meeting_location'] : null;
            if ($loc && !empty($loc['name'])) {
                $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $day = isset($loc['meeting_day']) ? (int) $loc['meeting_day'] : 0;
                $dayStr = isset($dayNames[$day]) ? $dayNames[$day] : '';
                $time = isset($loc['meeting_time']) ? $loc['meeting_time'] : '';
                if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
                    $h = (int) $m[1];
                    $min = (int) $m[2];
                    $time = sprintf('%d:%02d %s', $h > 12 ? $h - 12 : ($h ?: 12), $min, $h >= 12 ? 'PM' : 'AM');
                }
                $meetingSummary = htmlspecialchars($loc['name']) . ($dayStr ? ' · ' . $dayStr . ($time ? ' ' . $time : '') : '');
            }
        }
        $nextDue = isset($loan['next_due_date']) ? $loan['next_due_date'] : null;
        $balance = isset($loan['balance_remaining']) ? (float) $loan['balance_remaining'] : 0;
        if ($nextDue) {
            $reps = Loan::getRepayments($loan['id'], 'pending');
            $nextAmount = !empty($reps[0]['amount_due']) ? (float) $reps[0]['amount_due'] : null;
            $dueStr = date('j M Y', strtotime($nextDue));
            if ($nextAmount !== null) {
                $repaymentSummary = 'GH¢ ' . number_format($nextAmount, 2) . ' due ' . $dueStr . ' · Balance: GH¢ ' . number_format($balance, 2);
            } else {
                $repaymentSummary = 'Balance: GH¢ ' . number_format($balance, 2);
            }
        }
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>Css/home.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
</head>
<body>
    <?php
    $rejectionPage = $applicationRejected;
    require __DIR__ . '/partials/header.php';
    if ($applicationRejected): ?>
    <main class="home-page rejection-page" aria-label="Application result">
        <div class="rejection-message-card">
            <h1 class="rejection-title">Sorry, the loan has been rejected.</h1>
            <p class="rejection-desc">We will contact you if we have any updates. You may sign out below.</p>
            <?php if (!empty($app['notes'])): ?>
            <div class="rejection-notes">
                <p class="rejection-notes-label">Message from us:</p>
                <p class="rejection-notes-text"><?php echo nl2br(htmlspecialchars(trim($app['notes']))); ?></p>
            </div>
            <?php endif; ?>
            <form method="post" action="<?php echo htmlspecialchars($baseUrl); ?>index.php" class="rejection-logout-form">
                <input type="hidden" name="action" value="logout_action">
                <button type="submit" class="btn btn-primary">Log out</button>
            </form>
        </div>
    </main>
    <?php else: ?>
    <main class="home-page">
        <?php if ($accessDeniedNotice): ?>
        <p class="form-error" role="alert" style="margin-bottom:1rem;">Access denied. The admin area is for staff only. You are logged in as a borrower.</p>
        <?php endif; ?>
        <header class="home-header">
            <h1 class="home-title">Welcome, <?php echo $fullName; ?></h1>
            <p class="home-subtitle">Your dashboard. Group, repayment, and meeting details appear here once your application is approved.</p>
        </header>

        <section class="dashboard-overview" aria-label="Your information">
            <h2 class="dashboard-section-title">Overview</h2>
            <div class="dashboard-grid">
                <article class="dashboard-card">
                    <h3 class="dashboard-card-title">Your group</h3>
                    <p class="dashboard-card-value" aria-hidden="true"><?php echo $groupName; ?></p>
                    <p class="dashboard-card-desc">Group name and members after approval.</p>
                </article>
                <article class="dashboard-card">
                    <h3 class="dashboard-card-title">Group head</h3>
                    <p class="dashboard-card-value" aria-hidden="true"><?php echo $groupHeadName; ?><?php if ($groupHeadPhone !== '—'): ?> · <?php echo $groupHeadPhone; endif; ?></p>
                    <p class="dashboard-card-desc">Name and contact of your group head.</p>
                </article>
                <article class="dashboard-card">
                    <h3 class="dashboard-card-title">Repayment details</h3>
                    <p class="dashboard-card-value" aria-hidden="true"><?php echo $repaymentSummary; ?></p>
                    <p class="dashboard-card-desc">Amount due, next due date, and instructions.</p>
                </article>
                <article class="dashboard-card">
                    <h3 class="dashboard-card-title">Meeting locations</h3>
                    <p class="dashboard-card-value" aria-hidden="true"><?php echo $meetingSummary; ?></p>
                    <p class="dashboard-card-desc">Where and when your group meets.</p>
                </article>
            </div>
        </section>

        <section class="home-actions" aria-label="Continue application">
            <h2 class="dashboard-section-title">Continue your application</h2>
            <div class="home-actions-links">
                <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=documents" class="home-action-link">Documents</a>
                <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=loan-amount" class="home-action-link">Loan amount</a>
                <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=guarantor" class="home-action-link">Guarantor</a>
                <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=mfi" class="home-action-link">Choose MFI</a>
                <a href="<?php echo htmlspecialchars($baseUrl); ?>index.php?page=settings" class="home-action-link">Settings</a>
            </div>
        </section>
    </main>

    <script>
    (function() {
        <?php if ($showApprovalAlert): ?>
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Congratulations!',
                text: 'Your loan has been approved. Check your dashboard for group and repayment details.',
                confirmButtonText: 'OK',
                didOpen: function() {
                    if (typeof confetti !== 'undefined') {
                        confetti({ particleCount: 150, spread: 80, origin: { y: 0.6 } });
                        setTimeout(function() { confetti({ particleCount: 80, angle: 60, spread: 55, origin: { x: 0.2 } }); }, 150);
                        setTimeout(function() { confetti({ particleCount: 80, angle: 120, spread: 55, origin: { x: 0.8 } }); }, 250);
                    }
                }
            });
        }
        <?php endif; ?>
        <?php if (!empty($showDocumentsSavedSuccess)): ?>
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'success', title: 'Saved', text: 'Your document images have been saved.' });
        }
        <?php endif; ?>
    })();
    </script>
    <?php endif; ?>
</body>
</html>
