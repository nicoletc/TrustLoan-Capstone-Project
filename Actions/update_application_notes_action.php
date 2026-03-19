<?php
/**
 * TrustLoan – Admin: update application notes. JSON response.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Application.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$id = isset($_POST['application_id']) ? (int) $_POST['application_id'] : 0;
$notes = isset($_POST['notes']) ? (string) $_POST['notes'] : '';
if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid_id']);
    exit;
}

$ok = Application::updateNotes($id, $notes);
echo json_encode(['ok' => $ok]);
