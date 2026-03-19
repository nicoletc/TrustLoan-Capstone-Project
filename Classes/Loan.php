<?php
/**
 * TrustLoan – Loan and repayments. Create loan, schedule, record payment.
 */
if (!defined('TRUSTLOAN_LOAN_LOADED')) {
    define('TRUSTLOAN_LOAN_LOADED', true);
}
require_once __DIR__ . '/../settings/db_class.php';

class Loan {

    /** Return existing loan for this application if any (avoids duplicate loans/repayments). */
    public static function getByApplicationId($applicationId) {
        $id = (int) $applicationId;
        if ($id <= 0) return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id FROM loans WHERE application_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['id'] : null;
    }

    /**
     * Create loan and repayment schedule (weekly installments).
     * Does nothing if a loan already exists for this application (no duplicate repayments).
     */
    public static function create($applicationId, $userId, $groupId, $amountBorrowed, $repaymentWeeks = 12) {
        $appId = (int) $applicationId;
        $userId = (int) $userId;
        $groupId = (int) $groupId;
        $amount = (float) $amountBorrowed;
        $weeks = (int) $repaymentWeeks;
        if ($appId <= 0 || $userId <= 0 || $groupId <= 0 || $amount <= 0 || $weeks <= 0) return null;
        $existing = self::getByApplicationId($appId);
        if ($existing) return $existing;
        $pdo = DB::getConnection();
        $installment = round($amount / $weeks, 2);
        $firstDue = date('Y-m-d', strtotime('+1 week'));
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO loans (user_id, application_id, group_id, amount_borrowed, balance_remaining, next_due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $appId, $groupId, $amount, $amount, $firstDue, 'active']);
            $loanId = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO loan_repayments (loan_id, due_date, amount_due, status) VALUES (?, ?, ?, ?)');
            $due = $firstDue;
            for ($i = 0; $i < $weeks; $i++) {
                $stmt->execute([$loanId, $due, $installment, 'pending']);
                $due = date('Y-m-d', strtotime($due . ' +1 week'));
            }
            $pdo->commit();
            return $loanId;
        } catch (Exception $e) {
            $pdo->rollBack();
            return null;
        }
    }

    public static function getById($loanId) {
        $id = (int) $loanId;
        if ($id <= 0) return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM loans WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Borrower: get latest loan for user (active, overdue, or closed). */
    public static function getLatestByUser($userId) {
        $id = (int) $userId;
        if ($id <= 0) return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM loans WHERE user_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function getRepayments($loanId, $status = null) {
        $id = (int) $loanId;
        if ($id <= 0) return [];
        $pdo = DB::getConnection();
        if ($status && in_array($status, ['pending', 'paid', 'overdue'], true)) {
            $stmt = $pdo->prepare('SELECT * FROM loan_repayments WHERE loan_id = ? AND status = ? ORDER BY due_date');
            $stmt->execute([$id, $status]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM loan_repayments WHERE loan_id = ? ORDER BY due_date');
            $stmt->execute([$id]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Record payment for a repayment row; update loan balance and status.
     */
    public static function recordPayment($repaymentId, $amountPaid, $paidAt = null) {
        $repId = (int) $repaymentId;
        $amount = (float) $amountPaid;
        if ($repId <= 0 || $amount <= 0) return false;
        $paidAt = $paidAt ?: date('Y-m-d H:i:s');
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT id, loan_id, amount_due, status FROM loan_repayments WHERE id = ? LIMIT 1');
        $stmt->execute([$repId]);
        $rep = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rep || $rep['status'] === 'paid') return false;
        $loanId = (int) $rep['loan_id'];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE loan_repayments SET amount_paid = ?, paid_at = ?, status = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$amount, $paidAt, 'paid', $repId]);
            $stmt = $pdo->prepare('SELECT balance_remaining FROM loans WHERE id = ? LIMIT 1');
            $stmt->execute([$loanId]);
            $loan = $stmt->fetch(PDO::FETCH_ASSOC);
            $newBalance = max(0, (float) $loan['balance_remaining'] - $amount);
            $stmt = $pdo->prepare('SELECT due_date FROM loan_repayments WHERE loan_id = ? AND status = ? ORDER BY due_date ASC LIMIT 1');
            $stmt->execute([$loanId, 'pending']);
            $next = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextDue = $next ? $next['due_date'] : null;
            $status = 'active';
            if ($newBalance <= 0) $status = 'closed';
            elseif ($nextDue && $nextDue < date('Y-m-d')) $status = 'overdue';
            $stmt = $pdo->prepare('UPDATE loans SET balance_remaining = ?, next_due_date = COALESCE(?, next_due_date), status = ? WHERE id = ?');
            $stmt->execute([$newBalance, $nextDue, $status, $loanId]);
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /**
     * Admin: list loans by status (active, overdue, closed).
     */
    public static function getAllForAdmin($status = null) {
        $pdo = DB::getConnection();
        $sql = 'SELECT l.*, u.full_name AS borrower_name FROM loans l JOIN users u ON u.id = l.user_id WHERE 1=1';
        $params = [];
        if ($status && in_array($status, ['active', 'overdue', 'closed'], true)) {
            $sql .= ' AND l.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY l.next_due_date ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Admin: upcoming/overdue repayments (by date range).
     */
    public static function getRepaymentsForAdmin($dateFrom = null, $dateTo = null, $status = null) {
        $pdo = DB::getConnection();
        $sql = 'SELECT r.*, l.user_id, l.amount_borrowed, l.balance_remaining, u.full_name AS borrower_name
                FROM loan_repayments r
                JOIN loans l ON l.id = r.loan_id
                JOIN users u ON u.id = l.user_id
                WHERE 1=1';
        $params = [];
        if ($dateFrom) {
            $sql .= ' AND r.due_date >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= ' AND r.due_date <= ?';
            $params[] = $dateTo;
        }
        if ($status && in_array($status, ['pending', 'paid', 'overdue'], true)) {
            $sql .= ' AND r.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY r.due_date ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
