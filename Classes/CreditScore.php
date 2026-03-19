<?php
/**
 * TrustLoan – Credit score per user. Read/write credit_scores; calculate from loans and repayments.
 */
if (!defined('TRUSTLOAN_CREDIT_SCORE_LOADED')) {
    define('TRUSTLOAN_CREDIT_SCORE_LOADED', true);
}
require_once __DIR__ . '/../settings/db_class.php';

class CreditScore {

    /** Get stored score for user, or null if none. */
    public static function getByUserId($userId) {
        $id = (int) $userId;
        if ($id <= 0) return null;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('SELECT user_id, score, calculated_at FROM credit_scores WHERE user_id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Save or update score for user. */
    public static function save($userId, $score) {
        $id = (int) $userId;
        $score = max(0, min(100, (int) $score));
        if ($id <= 0) return false;
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('INSERT INTO credit_scores (user_id, score, calculated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE score = VALUES(score), calculated_at = NOW()');
        $stmt->execute([$id, $score]);
        return true;
    }

    /**
     * Calculate score and breakdown from loans and repayments. Returns array with score, score_label, breakdown, summary_why.
     */
    public static function calculateForUser($userId) {
        $id = (int) $userId;
        if ($id <= 0) return self::defaultDisplay();

        $pdo = DB::getConnection();

        $stmt = $pdo->prepare('SELECT created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $accountMonths = 0;
        if ($user && !empty($user['created_at'])) {
            $created = new DateTime($user['created_at']);
            $accountMonths = (int) $created->diff(new DateTime())->format('%m') + (int) $created->diff(new DateTime())->format('%y') * 12;
        }

        $stmt = $pdo->prepare('SELECT id, status FROM loans WHERE user_id = ?');
        $stmt->execute([$id]);
        $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $loanIds = array_column($loans, 'id');
        $loansCompleted = count(array_filter($loans, function ($l) { return ($l['status'] ?? '') === 'closed'; }));
        $totalLoans = count($loans);

        $totalDue = 0;
        $paidOnTime = 0;
        $paidLate = 0;
        if (!empty($loanIds)) {
            $placeholders = implode(',', array_fill(0, count($loanIds), '?'));
            $stmt = $pdo->prepare("SELECT due_date, status, paid_at FROM loan_repayments WHERE loan_id IN ($placeholders)");
            $stmt->execute(array_values($loanIds));
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $totalDue++;
                if (($r['status'] ?? '') === 'paid') {
                    if (empty($r['paid_at']) || strtotime($r['paid_at']) <= strtotime($r['due_date'] . ' 23:59:59')) {
                        $paidOnTime++;
                    } else {
                        $paidLate++;
                    }
                }
            }
        }

        $repaymentPct = $totalDue > 0 ? (int) round(($paidOnTime / $totalDue) * 100) : 100;
        $onTimeTotal = $paidOnTime + $paidLate;
        $onTimeStr = $totalDue > 0 ? ($paidOnTime + $paidLate) . '/' . $totalDue : '0/0';
        if ($totalDue === 0) $onTimeStr = '0/0';

        $score = 50;
        $score += min(35, $repaymentPct * 0.35);
        $score += min(15, $loansCompleted * 8);
        $score += $totalDue > 0 ? min(25, (($paidOnTime + $paidLate) / $totalDue) * 25) : 0;
        $score += min(12, $accountMonths);
        $score = (int) max(0, min(100, round($score)));

        $label = $score >= 85 ? 'Excellent' : ($score >= 70 ? 'Good' : ($score >= 50 ? 'Fair' : 'Poor'));
        $breakdown = [
            ['label' => 'Repayment history', 'value' => $repaymentPct . '%', 'reason' => $totalDue > 0 ? 'You have paid ' . $paidOnTime . ' of ' . $totalDue . ' instalments on time.' : 'No repayments due yet.'],
            ['label' => 'Loans completed', 'value' => (string) $loansCompleted, 'reason' => 'You have successfully completed ' . $loansCompleted . ' loan(s) with TrustLoan.'],
            ['label' => 'On-time payments', 'value' => $onTimeStr, 'reason' => $totalDue > 0 ? 'No missed or late payments in the last 12 months.' : 'No payments due yet.'],
            ['label' => 'Account age', 'value' => $accountMonths . ' month' . ($accountMonths !== 1 ? 's' : ''), 'reason' => 'Longer history with consistent behaviour helps your score.'],
        ];
        $summaryWhy = 'Your score is in the ' . $label . ' range. ' . ($repaymentPct >= 80 ? 'You have a strong repayment history.' : 'Improve by paying instalments on time.') . ' Keeping up repayments will help maintain or improve your score.';

        return [
            'score' => $score,
            'score_label' => $label,
            'score_max' => 100,
            'breakdown' => $breakdown,
            'summary_why' => $summaryWhy,
        ];
    }

    /** Default display when user missing or no data. */
    private static function defaultDisplay() {
        return [
            'score' => 0,
            'score_label' => 'No score',
            'score_max' => 100,
            'breakdown' => [
                ['label' => 'Repayment history', 'value' => '—', 'reason' => 'No repayment history yet.'],
                ['label' => 'Loans completed', 'value' => '0', 'reason' => 'Complete a loan to build your score.'],
                ['label' => 'On-time payments', 'value' => '0/0', 'reason' => 'No payments due yet.'],
                ['label' => 'Account age', 'value' => '0 months', 'reason' => 'Longer history helps your score.'],
            ],
            'summary_why' => 'Apply for a loan and repay on time to build your credit score.',
        ];
    }

    /**
     * Get score and breakdown for display. Calculates from loans/repayments, saves to credit_scores, returns data for the view.
     */
    public static function getForDisplay($userId) {
        $id = (int) $userId;
        if ($id <= 0) return self::defaultDisplay();

        $calculated = self::calculateForUser($id);
        self::save($id, $calculated['score']);
        return $calculated;
    }
}
