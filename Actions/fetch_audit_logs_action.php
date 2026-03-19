<?php
/**
 * TrustLoan – Admin: fetch audit logs. JSON.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/AuditLog.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$limit = isset($_POST['limit']) ? (int) $_POST['limit'] : (isset($_GET['limit']) ? (int) $_GET['limit'] : 100);
try {
    $list = AuditLog::getRecent($limit);
    echo json_encode(['ok' => true, 'data' => $list]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Failed to load audit logs.', 'debug' => (defined('TRUSTLOAN_DEV_MODE') && TRUSTLOAN_DEV_MODE ? $e->getMessage() : null)]);
}
