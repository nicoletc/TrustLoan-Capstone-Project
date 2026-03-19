<?php
/**
 * TrustLoan – Serve application image (Ghana Card or photos). Admin only.
 * GET: page=view_application_image&id=APPLICATION_ID&type=ghana_front|ghana_back|photo_0|photo_1|photo_2
 */
if (!is_admin_logged_in()) {
    http_response_code(403);
    exit('Forbidden');
}
$appId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$type = isset($_GET['type']) ? trim((string) $_GET['type']) : '';
if ($appId <= 0 || $type === '') {
    http_response_code(400);
    exit('Bad request');
}

require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Application.php';

$app = Application::getByIdWithDetails($appId);
if (!$app) {
    http_response_code(404);
    exit('Not found');
}

$path = null;
if ($type === 'ghana_front' && !empty($app['ghana_card_front_path'])) {
    $path = $app['ghana_card_front_path'];
} elseif ($type === 'ghana_back' && !empty($app['ghana_card_back_path'])) {
    $path = $app['ghana_card_back_path'];
} elseif (preg_match('/^photo_(\d+)$/', $type, $m) && !empty($app['photos'][(int)$m[1]]['file_path'])) {
    $path = $app['photos'][(int)$m[1]]['file_path'];
}

if ($path === null || $path === '') {
    http_response_code(404);
    exit('Not found');
}

// Resolve to filesystem path: allow only paths under project root
$root = realpath(__DIR__ . '/..');
$pathNormalized = str_replace('\\', '/', trim($path));
if ($pathNormalized === '') {
    http_response_code(404);
    exit('File not found');
}
// Relative path (e.g. uploads/5/photo.jpg) or absolute under root
if ($pathNormalized[0] === '/' || (strlen($pathNormalized) > 1 && $pathNormalized[1] === ':')) {
    $fullPath = realpath($pathNormalized);
} else {
    $fullPath = realpath($root . '/' . $pathNormalized);
}
if ($fullPath === false || strpos($fullPath, $root) !== 0 || !is_file($fullPath)) {
    http_response_code(404);
    exit('File not found');
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
$mime = isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Cache-Control: private, max-age=300');
readfile($fullPath);
exit;
