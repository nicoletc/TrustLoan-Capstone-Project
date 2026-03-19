<?php
/**
 * TrustLoan – Admin: fetch groups list (JSON).
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Group.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$list = Group::getAllForAdmin();
echo json_encode(['ok' => true, 'data' => $list]);
