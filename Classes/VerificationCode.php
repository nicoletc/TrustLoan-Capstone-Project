<?php
/**
 * TrustLoan – Verification code (USSD/OTP) logic. Dev mode: store code in session for testing.
 */
if (!defined('TRUSTLOAN_VERIFICATION_CODE_LOADED')) {
    define('TRUSTLOAN_VERIFICATION_CODE_LOADED', true);
}
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/User.php';

class VerificationCode {

    const EXPIRY_SECONDS = 600;

    /** Set to true in settings or core to show code on sign-in page (no SMS). */
    public static function isDevMode() {
        return defined('TRUSTLOAN_DEV_MODE') && TRUSTLOAN_DEV_MODE === true;
    }

    public static function sendCode($phone) {
        $phone = User::normalisePhone($phone);
        if (strlen($phone) < 9) return false;
        $code = (string) random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', time() + self::EXPIRY_SECONDS);
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('INSERT INTO verification_codes (phone, code, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$phone, $code, $expiresAt]);
        if (self::isDevMode()) {
            $_SESSION['dev_verification_code'] = $code;
            $_SESSION['dev_verification_phone'] = $phone;
        }
        return $code;
    }

    public static function validate($phone, $code) {
        $phone = User::normalisePhone($phone);
        $code = trim((string) $code);
        if ($phone === '' || $code === '') return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare(
            'SELECT id FROM verification_codes WHERE phone = ? AND code = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$phone, $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        $stmt = $pdo->prepare('UPDATE verification_codes SET used_at = NOW() WHERE id = ?');
        $stmt->execute([$row['id']]);
        if (self::isDevMode()) {
            unset($_SESSION['dev_verification_code'], $_SESSION['dev_verification_phone']);
        }
        return true;
    }
}
