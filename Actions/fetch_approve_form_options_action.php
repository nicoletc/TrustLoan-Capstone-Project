<?php
/**
 * TrustLoan – Admin: MFIs and groups for approve-application modal (dropdowns).
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Application.php';
require_once __DIR__ . '/../Classes/Group.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$mfis = Application::getAllMfis();
$groups = Group::getAllForAdmin();
echo json_encode([
    'ok' => true,
    'data' => [
        'mfis' => $mfis,
        'groups' => $groups,
    ],
]);
