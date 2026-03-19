<?php
/**
 * TrustLoan – Admin: fetch repayments (upcoming/overdue). JSON. Params: date_from, date_to, status.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Loan.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$dateFrom = isset($_POST['date_from']) ? trim((string) $_POST['date_from']) : null;
$dateTo = isset($_POST['date_to']) ? trim((string) $_POST['date_to']) : null;
$status = isset($_POST['status']) ? trim((string) $_POST['status']) : null;

$list = Loan::getRepaymentsForAdmin($dateFrom, $dateTo, $status);
echo json_encode(['ok' => true, 'data' => $list]);
