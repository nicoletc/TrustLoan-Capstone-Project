<?php
/**
 * TrustLoan – Application (loan application) business logic. No HTML, no HTTP.
 */
if (!defined('TRUSTLOAN_APPLICATION_LOADED')) {
    define('TRUSTLOAN_APPLICATION_LOADED', true);
}
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/User.php';

class Application {

    /**
     * Create or get latest application for user.
     * @param int $userId
     * @return int|false Application id or false
     */
    public static function createOrGetLatest($userId) {
        $userId = (int) $userId;
        if ($userId <= 0) return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM applications WHERE user_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return (int) $row['id'];
        $stmt = $pdo->prepare('INSERT INTO applications (user_id, requested_amount) VALUES (?, 0)');
        $stmt->execute([$userId]);
        return (int) $pdo->lastInsertId();
    }

    public static function getLatestByUser($userId) {
        $userId = (int) $userId;
        if ($userId <= 0) return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM applications WHERE user_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function updateLoanAmount($applicationId, $amount, $repaymentWeeks = 12) {
        $id = (int) $applicationId;
        $amount = (float) $amount;
        $weeks = (int) $repaymentWeeks;
        if ($id <= 0 || $amount <= 0) return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('UPDATE applications SET requested_amount = ?, repayment_weeks = ? WHERE id = ?');
        $stmt->execute([$amount, $weeks, $id]);
        return true;
    }

    public static function setGuarantor($applicationId, $fullName, $phone, $relationship, $occupation = '') {
        $id = (int) $applicationId;
        if ($id <= 0 || trim($fullName) === '' || trim($phone) === '' || trim($relationship) === '') return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO guarantors (application_id, full_name, phone, relationship, occupation) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), phone = VALUES(phone), relationship = VALUES(relationship), occupation = VALUES(occupation)'
        );
        $stmt->execute([$id, trim($fullName), User::normalisePhone($phone), trim($relationship), trim($occupation)]);
        return true;
    }

    /** Set guarantor status to confirmed (and confirmed_at) for an application. Used when application is approved. */
    public static function confirmGuarantorForApplication($applicationId) {
        $id = (int) $applicationId;
        if ($id <= 0) return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('UPDATE guarantors SET status = ?, confirmed_at = NOW() WHERE application_id = ?');
        $stmt->execute(['confirmed', $id]);
        return true;
    }

    public static function setMfi($applicationId, $mfiId, $area = '') {
        $appId = (int) $applicationId;
        $mfiId = (int) $mfiId;
        if ($appId <= 0 || $mfiId <= 0) return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO application_mfi (application_id, mfi_id, area) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE mfi_id = VALUES(mfi_id), area = VALUES(area)'
        );
        $stmt->execute([$appId, $mfiId, $area]);
        return true;
    }

    /** Check if application has a guarantor record (step submitted). */
    public static function hasGuarantor($applicationId) {
        $id = (int) $applicationId;
        if ($id <= 0) return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM guarantors WHERE application_id = ? LIMIT 1');
        $stmt->execute([$id]);
        return (bool) $stmt->fetch();
    }

    /** Check if application has an MFI selected (step submitted). */
    public static function hasMfi($applicationId) {
        $id = (int) $applicationId;
        if ($id <= 0) return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM application_mfi WHERE application_id = ? LIMIT 1');
        $stmt->execute([$id]);
        return (bool) $stmt->fetch();
    }

    public static function getMfisByArea($areaSlug) {
        $area = trim((string) $areaSlug);
        if ($area === '') return [];
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, area_slug FROM mfis WHERE area_slug = ? ORDER BY name');
        $stmt->execute([$area]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllMfis() {
        $pdo = DB::getConnection();
        $stmt = $pdo->query('SELECT id, name, area_slug FROM mfis ORDER BY area_slug, name');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /** Single MFI for borrower choice (current phase: one MFI only). Returns list with one item or empty. */
    public static function getDefaultMfiForBorrower() {
        $name = defined('TRUSTLOAN_MFI_NAME') ? TRUSTLOAN_MFI_NAME : 'Adenta Municipal';
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, area_slug FROM mfis WHERE name = ? LIMIT 1');
        $stmt->execute([$name]);
        $one = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($one) return [$one];
        $stmt = $pdo->prepare('SELECT id, name, area_slug FROM mfis WHERE area_slug = ? ORDER BY name LIMIT 1');
        $stmt->execute(['adenta']);
        $one = $stmt->fetch(PDO::FETCH_ASSOC);
        return $one ? [$one] : [];
    }

    /**
     * Admin: list applications with borrower name, phone, filters (status, date, search).
     */
    public static function getAllForAdmin(array $filters = []) {
        $pdo = DB::getConnection();
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $status = trim((string) $filters['status']);
            if (in_array($status, ['new', 'in_progress', 'approved', 'rejected'], true)) {
                $where[] = 'a.status = ?';
                $params[] = $status;
            }
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'a.submitted_at >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'a.submitted_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $where[] = '(u.full_name LIKE ? OR u.phone LIKE ?)';
            $params[] = $search;
            $params[] = $search;
        }
        $sql = 'SELECT a.id, a.user_id, a.requested_amount, a.repayment_weeks, a.status, a.submitted_at, a.reviewed_at, a.officer_id, a.notes,
                       u.full_name AS borrower_name, u.phone AS borrower_phone,
                       o.name AS officer_name
                FROM applications a
                JOIN users u ON u.id = a.user_id
                LEFT JOIN admin_users o ON o.id = a.officer_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY a.submitted_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Admin: single application with user, guarantor, photos, mfi.
     */
    public static function getByIdWithDetails($applicationId) {
        $id = (int) $applicationId;
        if ($id <= 0) return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM applications WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$app) return null;
        $stmt = $pdo->prepare('SELECT id, full_name, phone, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$app['user_id']]);
        $app['borrower'] = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('SELECT * FROM guarantors WHERE application_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $app['guarantor'] = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('SELECT id, file_path, sort_order FROM application_photos WHERE application_id = ? ORDER BY sort_order');
        $stmt->execute([$id]);
        $app['photos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('SELECT am.mfi_id, am.area, m.name AS mfi_name, m.area_slug FROM application_mfi am JOIN mfis m ON m.id = am.mfi_id WHERE am.application_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $app['mfi'] = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('SELECT score, calculated_at FROM credit_scores WHERE user_id = ? LIMIT 1');
        $stmt->execute([$app['user_id']]);
        $cs = $stmt->fetch(PDO::FETCH_ASSOC);
        $app['credit_score'] = $cs ? (int) $cs['score'] : null;
        $app['credit_score_calculated_at'] = $cs ? ($cs['calculated_at'] ?? null) : null;
        return $app;
    }

    public static function updateStatus($applicationId, $status, $officerId = null) {
        $id = (int) $applicationId;
        if ($id <= 0) return false;
        $status = trim((string) $status);
        if (!in_array($status, ['new', 'in_progress', 'approved', 'rejected'], true)) return false;
        $pdo = DB::getConnection();
        $officerId = $officerId ? (int) $officerId : null;
        $stmt = $pdo->prepare('UPDATE applications SET status = ?, reviewed_at = NOW(), officer_id = ? WHERE id = ?');
        $stmt->execute([$status, $officerId, $id]);
        return true;
    }

    public static function updateNotes($applicationId, $notes) {
        $id = (int) $applicationId;
        if ($id <= 0) return false;
        $notes = $notes !== null ? (string) $notes : '';
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('UPDATE applications SET notes = ? WHERE id = ?');
        $stmt->execute([$notes, $id]);
        return true;
    }

    /** Update Ghana Card image paths for an application. */
    public static function updateGhanaCardPaths($applicationId, $frontPath, $backPath) {
        $id = (int) $applicationId;
        if ($id <= 0) return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('UPDATE applications SET ghana_card_front_path = ?, ghana_card_back_path = ? WHERE id = ?');
        $stmt->execute([$frontPath !== null ? (string) $frontPath : null, $backPath !== null ? (string) $backPath : null, $id]);
        return true;
    }

    /** Replace application photos with a list of file paths (sort_order 0, 1, 2, ...). */
    public static function setApplicationPhotos($applicationId, array $filePaths) {
        $id = (int) $applicationId;
        if ($id <= 0) return false;
        $pdo = DB::getConnection();
        $pdo->prepare('DELETE FROM application_photos WHERE application_id = ?')->execute([$id]);
        $stmt = $pdo->prepare('INSERT INTO application_photos (application_id, file_path, sort_order) VALUES (?, ?, ?)');
        foreach ($filePaths as $i => $path) {
            if ($path !== null && $path !== '') {
                $stmt->execute([$id, (string) $path, (int) $i]);
            }
        }
        return true;
    }

    /** Mark that the approval congratulations have been shown (first login after approval). */
    public static function markApprovalCongratulationsShown($applicationId) {
        $id = (int) $applicationId;
        if ($id <= 0) return false;
        $pdo = DB::getConnection();
        try {
            $stmt = $pdo->prepare('UPDATE applications SET approval_congratulations_shown = 1 WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->rowCount() >= 0;
        } catch (\Throwable $e) {
            if (strpos($e->getMessage(), 'approval_congratulations_shown') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                try {
                    $pdo->exec('ALTER TABLE applications ADD COLUMN approval_congratulations_shown TINYINT(1) NOT NULL DEFAULT 0');
                    $stmt = $pdo->prepare('UPDATE applications SET approval_congratulations_shown = 1 WHERE id = ?');
                    $stmt->execute([$id]);
                    return true;
                } catch (\Throwable $e2) {
                    return false;
                }
            }
            return false;
        }
    }
}
