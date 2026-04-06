<?php
/**
 * POST JSON → ML /score proxy (Option B). Session: borrower or admin.
 * Request: { "user_id"?: int, "n_labeled"?: int, "preferred_supervised"?: "xgboost"|"lr", "features"?: object }
 */
require_once __DIR__ . '/../settings/core.php';
require_once __DIR__ . '/../Classes/Application.php';
require_once __DIR__ . '/../Classes/MlScoringClient.php';

header('Content-Type: application/json; charset=utf-8');

$uid = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$adminOk = isset($_SESSION['admin_user_id']) && (int) $_SESSION['admin_user_id'] > 0;

if ($uid <= 0 && !$adminOk) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$input = file_get_contents('php://input');
$json = $input !== '' ? json_decode($input, true) : [];
if (!is_array($json)) {
    $json = [];
}

$targetUserId = $uid;
if ($adminOk && !empty($json['user_id'])) {
    $targetUserId = (int) $json['user_id'];
}

if ($targetUserId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_user']);
    exit;
}

$nLabeled = isset($json['n_labeled']) ? (int) $json['n_labeled'] : Application::countLabeledApplicationsApprox();
$preferred = isset($json['preferred_supervised']) && $json['preferred_supervised'] === 'lr' ? 'lr' : 'xgboost';
$features = isset($json['features']) && is_array($json['features'])
    ? $json['features']
    : Application::buildMlFeaturesForBorrower($targetUserId);

$result = MlScoringClient::score($features, $nLabeled, $preferred);
if ($result === null) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'ml_service_unavailable', 'hint' => 'Start ml_service and set TRUSTLOAN_ML_SCORING_URL']);
    exit;
}

echo json_encode(['ok' => true, 'data' => $result, 'n_labeled_used' => $nLabeled]);
