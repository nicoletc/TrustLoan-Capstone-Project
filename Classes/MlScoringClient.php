<?php
/** Minimal HTTP POST to ML /score (base URL from settings). */
if (!defined('TRUSTLOAN_ML_SCORING_CLIENT_LOADED')) {
    define('TRUSTLOAN_ML_SCORING_CLIENT_LOADED', true);
}

class MlScoringClient {

    /** Trimmed TRUSTLOAN_ML_SCORING_URL or ''. */
    public static function getBaseUrl() {
        if (!defined('TRUSTLOAN_ML_SCORING_URL') || TRUSTLOAN_ML_SCORING_URL === '') {
            return '';
        }
        return rtrim((string) TRUSTLOAN_ML_SCORING_URL, '/');
    }

    /** POST /score → decoded JSON or null. */
    public static function score(array $features, $nLabeled, $preferredSupervised = 'xgboost', $seed = 42) {
        $base = self::getBaseUrl();
        if ($base === '') {
            return null;
        }
        $url = $base . '/score';
        $body = json_encode([
            'features' => $features,
            'n_labeled' => (int) $nLabeled,
            'preferred_supervised' => $preferredSupervised === 'lr' ? 'lr' : 'xgboost',
            'seed' => (int) $seed,
        ], JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            return null;
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\nContent-Length: " . strlen($body) . "\r\n",
                'content' => $body,
                'timeout' => 15,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Score from borrower id using DB-derived features and labeled-count heuristic.
     */
    public static function scoreForBorrower($userId, $preferredSupervised = 'xgboost') {
        require_once __DIR__ . '/Application.php';
        $userId = (int) $userId;
        if ($userId <= 0) {
            return null;
        }
        $features = Application::buildMlFeaturesForBorrower($userId);
        $nLabeled = Application::countLabeledApplicationsApprox();
        return self::score($features, $nLabeled, $preferredSupervised);
    }
}
