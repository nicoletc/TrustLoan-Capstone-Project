<?php
/**
 * TrustLoan – Admin: set group head. JSON response.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Group.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$groupId = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
$userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
if ($groupId <= 0 || $userId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid_params']);
    exit;
}

$ok = Group::setHead($groupId, $userId);
echo json_encode(['ok' => $ok]);
