<?php
/**
 * TrustLoan – Admin: fetch dashboard stats (verifications count, applicants count, active loans). JSON.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$pdo = DB::getConnection();
$verifications = (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE status IN ('new','in_progress')")->fetchColumn();
$applicants = (int) $pdo->query('SELECT COUNT(*) FROM applications')->fetchColumn();
$activeLoans = (int) $pdo->query("SELECT COUNT(*) FROM loans WHERE status IN ('active','overdue')")->fetchColumn();
$statusBreakdown = $pdo->query("SELECT status, COUNT(*) AS cnt FROM applications GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
$applicationsOverTime = $pdo->query("SELECT DATE(submitted_at) AS d, COUNT(*) AS cnt FROM applications WHERE submitted_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY d ORDER BY d")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'ok' => true,
    'data' => [
        'verifications' => $verifications,
        'applicants' => $applicants,
        'active_loans' => $activeLoans,
        'status_breakdown' => $statusBreakdown,
        'applications_over_time' => $applicationsOverTime
    ]
]);
