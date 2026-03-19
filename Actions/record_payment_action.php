<?php
/**
 * TrustLoan – Admin: record payment for a repayment. Updates loan balance/status. JSON response.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Loan.php';
require_once __DIR__ . '/../Classes/AuditLog.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$repaymentId = isset($_POST['repayment_id']) ? (int) $_POST['repayment_id'] : 0;
$amountPaid = isset($_POST['amount_paid']) ? (float) $_POST['amount_paid'] : 0;
if ($repaymentId <= 0 || $amountPaid <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid_params']);
    exit;
}

$ok = Loan::recordPayment($repaymentId, $amountPaid);
if (!$ok) {
    echo json_encode(['ok' => false, 'error' => 'record_failed']);
    exit;
}

AuditLog::add(get_admin_user_id(), 'Recorded payment', 'loan_repayment', $repaymentId, "amount:{$amountPaid}");
echo json_encode(['ok' => true]);
