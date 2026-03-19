<?php
/**
 * TrustLoan – Admin: fetch applications list (JSON). Filters: status, date_from, date_to, search.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Application.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$filters = [
    'status'   => isset($_POST['status']) ? trim((string) $_POST['status']) : null,
    'date_from'=> isset($_POST['date_from']) ? trim((string) $_POST['date_from']) : null,
    'date_to'  => isset($_POST['date_to']) ? trim((string) $_POST['date_to']) : null,
    'search'   => isset($_POST['search']) ? trim((string) $_POST['search']) : null,
];

$list = Application::getAllForAdmin($filters);
echo json_encode(['ok' => true, 'data' => $list]);
