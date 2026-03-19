<?php
/**
 * TrustLoan – Audit log. Write and read audit_logs table.
 */
if (!defined('TRUSTLOAN_AUDIT_LOG_LOADED')) {
    define('TRUSTLOAN_AUDIT_LOG_LOADED', true);
}
require_once __DIR__ . '/../settings/db_class.php';

class AuditLog {

    /**
     * Log an action. admin_user_id can be 0 if not from admin.
     */
    public static function add($adminUserId, $action, $entityType = null, $entityId = null, $details = null) {
        $adminUserId = $adminUserId ? (int) $adminUserId : null;
        $action = trim((string) $action);
        if ($action === '') return false;
        $entityType = $entityType !== null ? trim((string) $entityType) : null;
        $entityId = $entityId !== null ? (int) $entityId : null;
        $details = $details !== null ? trim((string) $details) : null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('INSERT INTO audit_logs (admin_user_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$adminUserId, $action, $entityType, $entityId, $details]);
        return true;
    }

    /**
     * Fetch recent audit logs for admin.
     */
    public static function getRecent($limit = 100) {
        $limit = (int) $limit;
        if ($limit <= 0) $limit = 100;
        $limit = min($limit, 500);
        $pdo = DB::getConnection();
        $sql = 'SELECT a.id, a.admin_user_id, a.action, a.entity_type, a.entity_id, a.details, a.created_at,
                   u.name AS admin_name
            FROM audit_logs a
            LEFT JOIN admin_users u ON u.id = a.admin_user_id
            ORDER BY a.created_at DESC
            LIMIT ' . $limit;
        $stmt = $pdo->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}
