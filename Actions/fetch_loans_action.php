<?php
/**
 * TrustLoan – Admin: fetch loans list (active, overdue, or all). JSON.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Loan.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$status = isset($_POST['status']) ? trim((string) $_POST['status']) : null;
$list = Loan::getAllForAdmin($status);
echo json_encode(['ok' => true, 'data' => $list]);
