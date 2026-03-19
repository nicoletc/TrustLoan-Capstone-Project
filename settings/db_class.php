<?php
/**
 * TrustLoan – PDO database connection.
 */
if (!defined('TRUSTLOAN_DB_CLASS_LOADED')) {
    define('TRUSTLOAN_DB_CLASS_LOADED', true);
}
require_once __DIR__ . '/db_cred.php';

class DB {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . TRUSTLOAN_DB_HOST . ';dbname=' . TRUSTLOAN_DB_NAME . ';charset=utf8mb4';
            self::$pdo = new PDO($dsn, TRUSTLOAN_DB_USER, TRUSTLOAN_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdo;
    }
}
