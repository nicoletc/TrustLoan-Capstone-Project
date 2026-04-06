<?php
/**
 * TrustLoan – Credit score from ML service only (Option B). Persists 0–100 in credit_scores.
 */
if (!defined('TRUSTLOAN_CREDIT_SCORE_LOADED')) {
    define('TRUSTLOAN_CREDIT_SCORE_LOADED', true);
}
require_once __DIR__ . '/../settings/db_class.php';

class CreditScore {

    /** Get stored score for user, or null if none. */
    public static function getByUserId($userId) {
        $id = (int) $userId;
        if ($id <= 0) {
            return null;
        }
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
        if ($id <= 0) {
            return false;
        }
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('INSERT INTO credit_scores (user_id, score, calculated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE score = VALUES(score), calculated_at = NOW()');
        $stmt->execute([$id, $score]);
        return true;
    }

    /**
     * Map probability of default (0–1) to display score 0–100 (lower PD → higher score).
     *
     * @return array{0: int, 1: string} [score, label]
     */
    public static function pdToScoreAndLabel($pd) {
        $pd = (float) $pd;
        $pd = max(0.0, min(1.0, $pd));
        $score = (int) round((1.0 - $pd) * 100);
        $score = max(0, min(100, $score));
        return [$score, self::labelForScore($score)];
    }

    public static function labelForScore($score) {
        $score = max(0, min(100, (int) $score));
        return $score >= 85 ? 'Excellent' : ($score >= 70 ? 'Good' : ($score >= 50 ? 'Fair' : 'Poor'));
    }

    /**
     * User-safe context derived from PD (0–1). No model names or routing.
     *
     * @return array{risk_tier: string, risk_tier_label: string, risk_blurb: string, tips: list<string>, is_approximate: bool}
     */
    public static function borrowerContextFromPd($pd, $isApproximate = false) {
        $pd = max(0.0, min(1.0, (float) $pd));
        $pct = $pd * 100;
        if ($pct <= 0.05) {
            $pctPhrase = 'under 0.1%';
        } elseif ($pct < 10) {
            $pctPhrase = sprintf('about %.1f%%', $pct);
        } else {
            $pctPhrase = sprintf('about %.0f%%', $pct);
        }

        if ($pd < 0.12) {
            $tier = 'low';
            $tierLabel = 'Low';
        } elseif ($pd < 0.28) {
            $tier = 'moderate';
            $tierLabel = 'Moderate';
        } elseif ($pd < 0.45) {
            $tier = 'elevated';
            $tierLabel = 'Elevated';
        } else {
            $tier = 'high';
            $tierLabel = 'Higher';
        }

        $prefix = $isApproximate
            ? 'From your last saved score, we estimate '
            : 'Based on your latest assessment, relative default risk is roughly ';
        $suffix = ' compared with similar application profiles (lower is better). MFIs still make the final decision.';
        $riskBlurb = $prefix . $pctPhrase . $suffix;

        return [
            'risk_tier' => $tier,
            'risk_tier_label' => $tierLabel,
            'risk_blurb' => $riskBlurb,
            'tips' => self::borrowerTipsForTier($tier),
            'is_approximate' => (bool) $isApproximate,
        ];
    }

    private static function borrowerTipsForTier($tier) {
        $common = 'Keep your phone number and application details accurate so MFIs can reach you.';
        switch ($tier) {
            case 'low':
                return [
                        'Continue repaying on time once you have a loan — that strengthens your profile over time.',
                        'If your income changes, updating your application helps MFIs assess you fairly.',
                        $common,
                    ];
            case 'moderate':
                return [
                        'A complete guarantor and business description can help MFIs review your case faster.',
                        'Requesting an amount that fits your income may improve how your file is viewed.',
                        $common,
                    ];
            case 'elevated':
                return [
                        'Double-check loan amount and repayment period — a smaller, shorter loan can be easier to approve.',
                        'Add any documents MFIs ask for promptly; missing items often delay decisions.',
                        $common,
                    ];
            default:
                return [
                        'Consider adjusting the requested amount or term if an MFI suggests it.',
                        'Speak with your group or loan officer if something on your profile is outdated.',
                        $common,
                    ];
        }
    }

    /**
     * @param float|null $exactPd PD from ML when freshly scored; null → derive from stored score (approximate)
     */
    private static function attachCalculatedAtAndBorrower(array &$out, $userId, $exactPd) {
        $uid = (int) $userId;
        if ($uid <= 0) {
            $out['calculated_at'] = null;
            $out['borrower'] = null;
            return;
        }
        $row = self::getByUserId($uid);
        $out['calculated_at'] = ($row && !empty($row['calculated_at'])) ? (string) $row['calculated_at'] : null;

        if (($out['score_label'] ?? '') === 'No score' && (int) ($out['score'] ?? 0) === 0) {
            $out['borrower'] = null;
            return;
        }

        if ($exactPd !== null && is_numeric($exactPd)) {
            $out['borrower'] = self::borrowerContextFromPd((float) $exactPd, false);
            return;
        }
        $s = (int) ($out['score'] ?? 0);
        $approxPd = max(0.0, min(1.0, 1.0 - ($s / 100.0)));
        $out['borrower'] = self::borrowerContextFromPd($approxPd, true);
    }

    /**
     * Score + display fields for the borrower UI (no breakdown). Optionally include raw ML payload for admin.
     *
     * @param bool $includeMlPayload When true, adds 'ml' key with API response or null
     * @return array{score: int, score_label: string, score_max: int, breakdown: array, summary_why: string, ml?: ?array}
     */
    public static function getForDisplay($userId, $includeMlPayload = false) {
        $id = (int) $userId;
        if ($id <= 0) {
            $out = self::defaultDisplay($includeMlPayload);
            $out['ml_service_unreachable'] = false;
            $out['calculated_at'] = null;
            $out['borrower'] = null;
            return $out;
        }

        if (!defined('TRUSTLOAN_ML_SCORING_URL') || TRUSTLOAN_ML_SCORING_URL === '') {
            $out = self::fromStoredOnly($id, $includeMlPayload);
            $out['ml_service_unreachable'] = false;
            self::attachCalculatedAtAndBorrower($out, $id, null);
            return $out;
        }

        require_once __DIR__ . '/MlScoringClient.php';
        $ml = MlScoringClient::scoreForBorrower($id);
        if ($ml !== null) {
            [$score, $label] = self::pdToScoreAndLabel($ml['pd'] ?? 0.5);
            self::save($id, $score);

            $out = [
                'score' => $score,
                'score_label' => $label,
                'score_max' => 100,
                'breakdown' => [],
                'summary_why' => 'Your score reflects our automated credit assessment based on your application profile.',
                'ml_service_unreachable' => false,
            ];
            if ($includeMlPayload) {
                $out['ml'] = $ml;
            }
            $exactPd = (isset($ml['pd']) && is_numeric($ml['pd'])) ? (float) $ml['pd'] : null;
            self::attachCalculatedAtAndBorrower($out, $id, $exactPd);
            return $out;
        }

        $row = self::getByUserId($id);
        if ($row) {
            $score = (int) $row['score'];
            $out = [
                'score' => $score,
                'score_label' => self::labelForScore($score),
                'score_max' => 100,
                'breakdown' => [],
                'summary_why' => 'Your score reflects our automated credit assessment based on your application profile.',
                'ml_service_unreachable' => true,
            ];
            if ($includeMlPayload) {
                $out['ml'] = null;
            }
            self::attachCalculatedAtAndBorrower($out, $id, null);
            return $out;
        }

        $out = self::defaultDisplay($includeMlPayload);
        $out['ml_service_unreachable'] = true;
        self::attachCalculatedAtAndBorrower($out, $id, null);
        return $out;
    }

    private static function fromStoredOnly($userId, $includeMlPayload) {
        $row = self::getByUserId($userId);
        if (!$row) {
            return self::defaultDisplay($includeMlPayload);
        }
        $score = (int) $row['score'];
        $out = [
            'score' => $score,
            'score_label' => self::labelForScore($score),
            'score_max' => 100,
            'breakdown' => [],
            'summary_why' => 'Your score reflects our automated credit assessment based on your application profile.',
        ];
        if ($includeMlPayload) {
            $out['ml'] = null;
        }
        return $out;
    }

    private static function defaultDisplay($includeMlPayload = false) {
        $out = [
            'score' => 0,
            'score_label' => 'No score',
            'score_max' => 100,
            'breakdown' => [],
            'summary_why' => 'Complete your application and ensure the credit service is available to receive a score.',
        ];
        if ($includeMlPayload) {
            $out['ml'] = null;
        }
        return $out;
    }
}
