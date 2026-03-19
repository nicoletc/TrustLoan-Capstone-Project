<?php
/**
 * TrustLoan – Admin user (admin_users table). Auth for Admin area only.
 */
if (!defined('TRUSTLOAN_ADMIN_USER_LOADED')) {
    define('TRUSTLOAN_ADMIN_USER_LOADED', true);
}
require_once __DIR__ . '/../settings/db_class.php';

class AdminUser {

    /**
     * Create a new admin user (first-time registration). Returns id or false.
     */
    public static function create($name, $email, $password, $role = 'officer') {
        $name = trim((string) $name);
        $email = trim((string) $email);
        if ($name === '' || $email === '' || strlen($password) < 6) return false;
        if (!in_array($role, ['admin', 'officer', 'verifier'], true)) $role = 'officer';
        if (self::getByEmail($email)) return false;
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('INSERT INTO admin_users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, $role]);
        return (int) $pdo->lastInsertId();
    }

    public static function getByEmail($email) {
        $email = trim((string) $email);
        if ($email === '') return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM admin_users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getById($id) {
        $id = (int) $id;
        if ($id <= 0) return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, email, role FROM admin_users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function verifyPassword($adminUserId, $password) {
        $adminUserId = (int) $adminUserId;
        if ($adminUserId <= 0 || $password === '') return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT password_hash FROM admin_users WHERE id = ? LIMIT 1');
        $stmt->execute([$adminUserId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !$row['password_hash']) return false;
        return password_verify($password, $row['password_hash']);
    }
}
