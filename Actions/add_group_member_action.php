<?php
/**
 * TrustLoan – Admin: add borrower to group (only if application approved). JSON response.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Group.php';
require_once __DIR__ . '/../Classes/Application.php';
require_once __DIR__ . '/../Classes/AuditLog.php';

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

$pdo = DB::getConnection();
$stmt = $pdo->prepare('SELECT id FROM applications WHERE user_id = ? AND status = ? LIMIT 1');
$stmt->execute([$userId, 'approved']);
if (!$stmt->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'user_not_approved']);
    exit;
}

$ok = Group::addMember($groupId, $userId);
if (!$ok) {
    echo json_encode(['ok' => false, 'error' => 'already_member_or_failed']);
    exit;
}

AuditLog::add(get_admin_user_id(), 'Added member to group', 'group', $groupId, "user_id:{$userId}");
echo json_encode(['ok' => true]);
