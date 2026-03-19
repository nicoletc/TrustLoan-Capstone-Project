<?php
/**
 * TrustLoan – Admin: fetch settings by type. JSON. Param: type (loan_products|repayment|penalties|risk|notifications).
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Settings.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$type = isset($_POST['type']) ? trim((string) $_POST['type']) : (isset($_GET['type']) ? trim((string) $_GET['type']) : '');
if ($type === '' || !in_array($type, ['loan_products', 'repayment', 'penalties', 'risk', 'notifications'], true)) {
    echo json_encode(['ok' => false, 'error' => 'invalid_type']);
    exit;
}

$data = Settings::getByType($type);
echo json_encode(['ok' => true, 'data' => $data]);
