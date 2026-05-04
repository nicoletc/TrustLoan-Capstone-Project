<?php
/** ML Option B: PD → 0–100 stored in credit_scores; labels/tips from score bands; optional $forAdmin blurbs. */
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

    /** PD → clamped 0–100 score and band label. */
    public static function pdToScoreAndLabel($pd) {
        $pd = (float) $pd;
        $pd = max(0.0, min(1.0, $pd));
        $score = (int) round((1.0 - $pd) * 100);
        $score = max(0, min(100, $score));
        return [$score, self::labelForScore($score)];
    }

    /** Bands: 85+ Excellent, 70+ Good, 55+ Fair, else Poor. */
    public static function labelForScore($score) {
        $score = max(0, min(100, (int) $score));
        if ($score >= 85) {
            return 'Excellent';
        }
        if ($score >= 70) {
            return 'Good';
        }
        if ($score >= 55) {
            return 'Fair';
        }
        return 'Poor';
    }

    /** Same bands as labelForScore; returns tier slug, risk phrase, word label. */
    private static function scoreBandMeta($score) {
        $score = max(0, min(100, (int) $score));
        if ($score >= 85) {
            return ['risk_tier' => 'low', 'risk_tier_label' => 'Low risk', 'score_label' => 'Excellent'];
        }
        if ($score >= 70) {
            return ['risk_tier' => 'moderate', 'risk_tier_label' => 'Moderate risk', 'score_label' => 'Good'];
        }
        if ($score >= 55) {
            return ['risk_tier' => 'high', 'risk_tier_label' => 'High risk', 'score_label' => 'Fair'];
        }
        return ['risk_tier' => 'very_high', 'risk_tier_label' => 'Very high risk', 'score_label' => 'Poor'];
    }

    /** Borrower context from score only; $forAdmin switches blurb style. */
    public static function borrowerContextFromScore($score, $isApproximate = false, $forAdmin = false) {
        $score = max(0, min(100, (int) $score));
        $meta = self::scoreBandMeta($score);
        $tier = $meta['risk_tier'];
        $tierLabel = $meta['risk_tier_label'];
        $word = $meta['score_label'];

        if ($forAdmin) {
            $base = 'TrustLoan score ' . $score . '/100 (' . $word . '). Estimated risk: ' . $tierLabel . '. MFIs still make the final decision.';
            $riskBlurb = $isApproximate ? ('From last saved score. ' . $base) : $base;
        } else {
            $core = 'Your score of ' . $score . '/100 (' . $word . ') and estimated risk level (' . $tierLabel . ') both come from TrustLoan\'s automated assessment of your application. MFIs still make the final decision.';
            if ($isApproximate) {
                $riskBlurb = 'We\'re showing your last saved result from our automated system. ' . $core;
            } else {
                $riskBlurb = $core;
            }
        }

        return [
            'risk_tier' => $tier,
            'risk_tier_label' => $tierLabel,
            'risk_blurb' => $riskBlurb,
            'tips' => self::borrowerTipsForTier($tier),
            'is_approximate' => (bool) $isApproximate,
            'score_label' => $word,
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
            case 'high':
                return [
                        'Double-check loan amount and repayment period — a smaller, shorter loan can be easier to approve.',
                        'Add any documents MFIs ask for promptly; missing items often delay decisions.',
                        $common,
                    ];
            case 'very_high':
                return [
                        'Consider adjusting the requested amount or term if an MFI suggests it.',
                        'Speak with your group or loan officer if something on your profile is outdated.',
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

    /** Sets calculated_at and borrower[]; empty if no score yet. */
    private static function applyAudiencePolicy(array &$out, $userId, $forAdmin, $insightsApproximate) {
        $uid = (int) $userId;
        $row = $uid > 0 ? self::getByUserId($uid) : null;
        $out['calculated_at'] = ($row && !empty($row['calculated_at'])) ? (string) $row['calculated_at'] : null;

        if (($out['score_label'] ?? '') === 'No score' && (int) ($out['score'] ?? 0) === 0) {
            $out['borrower'] = null;
            return;
        }

        $out['borrower'] = self::borrowerContextFromScore((int) ($out['score'] ?? 0), $insightsApproximate, $forAdmin);
    }

    /** For UI: score, label, summary, borrower insights; $includeMlPayload adds raw ml array for admin. */
    public static function getForDisplay($userId, $includeMlPayload = false, $forAdmin = false) {
        $id = (int) $userId;
        if ($id <= 0) {
            $out = self::defaultDisplay($includeMlPayload);
            $out['ml_service_unreachable'] = false;
            self::applyAudiencePolicy($out, $id, $forAdmin, false);
            return $out;
        }

        if (!defined('TRUSTLOAN_ML_SCORING_URL') || TRUSTLOAN_ML_SCORING_URL === '') {
            $out = self::fromStoredOnly($id, $includeMlPayload);
            $out['ml_service_unreachable'] = false;
            self::applyAudiencePolicy($out, $id, $forAdmin, true);
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
            self::applyAudiencePolicy($out, $id, $forAdmin, false);
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
            self::applyAudiencePolicy($out, $id, $forAdmin, true);
            return $out;
        }

        $out = self::defaultDisplay($includeMlPayload);
        $out['ml_service_unreachable'] = true;
        self::applyAudiencePolicy($out, $id, $forAdmin, true);
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
