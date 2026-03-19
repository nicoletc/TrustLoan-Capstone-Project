<?php
/**
 * TrustLoan – Admin: update application status (in_progress or rejected). JSON response.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Application.php';
require_once __DIR__ . '/../Classes/AuditLog.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$id = isset($_POST['application_id']) ? (int) $_POST['application_id'] : 0;
$status = isset($_POST['status']) ? trim((string) $_POST['status']) : '';
$notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : '';
if ($id <= 0 || !in_array($status, ['in_progress', 'rejected'], true)) {
    echo json_encode(['ok' => false, 'error' => 'invalid_params']);
    exit;
}
if ($status === 'rejected' && $notes === '') {
    echo json_encode(['ok' => false, 'error' => 'Rejection reason is required.']);
    exit;
}

$ok = Application::updateStatus($id, $status, get_admin_user_id());
if (!$ok) {
    echo json_encode(['ok' => false, 'error' => 'update_failed']);
    exit;
}
if ($status === 'rejected' && $notes !== '') {
    Application::updateNotes($id, $notes);
}
AuditLog::add(get_admin_user_id(), $status === 'rejected' ? 'Rejected application' : 'Marked application in progress', 'application', $id);
echo json_encode(['ok' => true]);
