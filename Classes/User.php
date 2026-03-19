<?php
/**
 * TrustLoan – User (borrower) business logic. No HTML, no HTTP.
 */
if (!defined('TRUSTLOAN_USER_LOADED')) {
    define('TRUSTLOAN_USER_LOADED', true);
}
require_once __DIR__ . '/../settings/db_class.php';

class User {

    /**
     * Create a new borrower (registration).
     * @param string $phone
     * @param string $fullName
     * @param string $password Plain password (min 6 chars); stored as bcrypt(plain).
     */
    public static function create($phone, $fullName, $password) {
        $phone = self::normalisePhone($phone);
        $password = (string) $password;
        if ($phone === '' || $fullName === '' || $password === '' || strlen($password) < 6) {
            return false;
        }
        if (self::getByPhone($phone)) {
            return false;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo = DB::getConnection();
        $role = 2; // 1=admin, 2=customer; new sign-ups always get 2
        $stmt = $pdo->prepare('INSERT INTO users (phone, full_name, password_hash, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$phone, trim($fullName), $hash, $role]);
        return (int) $pdo->lastInsertId();
    }

    public static function getByPhone($phone) {
        $phone = self::normalisePhone($phone);
        if ($phone === '') return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, phone, full_name, password_hash, role, created_at FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
        // Fallback: DB may still have international format (233...) for older accounts
        if (strlen($phone) === 10 && $phone[0] === '0') {
            $international = '233' . substr($phone, 1);
            $stmt->execute([$international]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        }
        return null;
    }

    public static function getById($id) {
        $id = (int) $id;
        if ($id <= 0) return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, phone, full_name, role, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find users by full name (trimmed, case-insensitive). Returns array of rows; use with password verify for login.
     */
    public static function getByFullName($fullName) {
        $name = trim(preg_replace('/\s+/', ' ', (string) $fullName));
        if ($name === '') return [];
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, phone, full_name, password_hash, role, created_at FROM users WHERE LOWER(TRIM(full_name)) = LOWER(?)');
        $stmt->execute([$name]);
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Verify password. $password is the plain password from the form.
     * Supports both bcrypt(plain) and legacy bcrypt(sha256(plain)) stored hashes.
     */
    public static function verifyPassword($userId, $password) {
        $userId = (int) $userId;
        if ($userId <= 0 || $password === '') return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !$row['password_hash']) return false;
        if (password_verify($password, $row['password_hash'])) {
            return true;
        }
        $legacyHash = hash('sha256', $password);
        return password_verify($legacyHash, $row['password_hash']);
    }

    /**
     * Normalise phone to local format: 0 + 9 digits (e.g. 0205614656).
     * Strips non-digits, then converts Ghana +233 to leading 0.
     */
    public static function normalisePhone($phone) {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if ($digits === '') return '';
        // Ghana: 233 + 9 digits → 0 + 9 digits (e.g. 233205614656 → 0205614656)
        if (strlen($digits) >= 12 && substr($digits, 0, 3) === '233') {
            return '0' . substr($digits, 3);
        }
        // 9 digits without leading 0 → 0 + 9 digits (e.g. 205614656 → 0205614656)
        if (strlen($digits) === 9 && $digits[0] !== '0') {
            return '0' . $digits;
        }
        return $digits;
    }
}
