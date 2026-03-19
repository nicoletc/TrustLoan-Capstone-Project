<?php
/**
 * TrustLoan – Admin: fetch guarantors list (from applications). JSON.
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../settings/db_class.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$pdo = DB::getConnection();
$stmt = $pdo->query('
    SELECT g.*, a.id AS application_id, u.full_name AS applicant_name, u.phone AS applicant_phone
    FROM guarantors g
    JOIN applications a ON a.id = g.application_id
    JOIN users u ON u.id = a.user_id
    ORDER BY g.created_at DESC
');
$list = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
echo json_encode(['ok' => true, 'data' => $list]);
