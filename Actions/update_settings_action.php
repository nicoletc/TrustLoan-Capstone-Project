<?php
/**
 * TrustLoan – Admin: update setting. JSON. Params: type, key, value (or id for repayment).
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Settings.php';
require_once __DIR__ . '/../Classes/AuditLog.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$type = isset($_POST['type']) ? trim((string) $_POST['type']) : '';
$key = isset($_POST['key']) ? trim((string) $_POST['key']) : (isset($_POST['id']) ? (int) $_POST['id'] : '');
$value = isset($_POST['value']) ? (string) $_POST['value'] : '';
if ($type === '' || !in_array($type, ['loan_products', 'repayment', 'penalties', 'risk', 'notifications'], true)) {
    echo json_encode(['ok' => false, 'error' => 'invalid_type']);
    exit;
}

$ok = Settings::updateByType($type, $key, $value);
if (!$ok) {
    echo json_encode(['ok' => false, 'error' => 'update_failed']);
    exit;
}

AuditLog::add(get_admin_user_id(), 'Updated settings', $type, null, "key:{$key}");
echo json_encode(['ok' => true]);
