<?php
/**
 * TrustLoan – Admin: create group under MFI. JSON response.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Group.php';
require_once __DIR__ . '/../Classes/AuditLog.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$mfiId = isset($_POST['mfi_id']) ? (int) $_POST['mfi_id'] : 0;
$name = isset($_POST['name']) ? trim((string) $_POST['name']) : '';
if ($mfiId <= 0 || $name === '') {
    echo json_encode(['ok' => false, 'error' => 'invalid_params']);
    exit;
}

$groupId = Group::create($mfiId, $name);
if (!$groupId) {
    echo json_encode(['ok' => false, 'error' => 'create_failed']);
    exit;
}

AuditLog::add(get_admin_user_id(), 'Created group', 'group', $groupId, $name);
echo json_encode(['ok' => true, 'group_id' => $groupId]);
