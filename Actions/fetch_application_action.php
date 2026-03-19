<?php
/**
 * TrustLoan – Admin: fetch single application with details (JSON).
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Application.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid_id']);
    exit;
}

$app = Application::getByIdWithDetails($id);
if (!$app) {
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

echo json_encode(['ok' => true, 'data' => $app]);
