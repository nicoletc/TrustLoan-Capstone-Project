<?php
/**
 * TrustLoan – Settings tables (loan_products, repayment, penalties, risk, notifications).
 */
if (!defined('TRUSTLOAN_SETTINGS_LOADED')) {
    define('TRUSTLOAN_SETTINGS_LOADED', true);
}
require_once __DIR__ . '/../settings/db_class.php';

class Settings {

    private static $tables = [
        'loan_products' => 'settings_loan_products',
        'repayment' => 'settings_repayment',
        'penalties' => 'settings_penalties',
        'risk' => 'settings_risk',
        'notifications' => 'settings_notifications',
    ];

    public static function getByType($type) {
        $table = isset(self::$tables[$type]) ? self::$tables[$type] : null;
        if (!$table) return [];
        $pdo = DB::getConnection();
        $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY id");
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function updateByType($type, $key, $value) {
        $table = isset(self::$tables[$type]) ? self::$tables[$type] : null;
        if (!$table) return false;
        $pdo = DB::getConnection();
        $keyCol = $type === 'repayment' ? 'collection_day' : 'setting_key';
        $valCol = $type === 'repayment' ? 'collection_day' : 'setting_value';
        if ($type === 'repayment') {
            $stmt = $pdo->prepare("UPDATE {$table} SET collection_day = ? WHERE id = ?");
            $stmt->execute([(int) $value, (int) $key]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO {$table} ({$keyCol}, {$valCol}) VALUES (?, ?) ON DUPLICATE KEY UPDATE {$valCol} = VALUES({$valCol})");
            $stmt->execute([$key, $value]);
        }
        return true;
    }
}
