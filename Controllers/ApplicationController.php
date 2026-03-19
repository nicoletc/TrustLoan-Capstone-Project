<?php
/**
 * TrustLoan – Application flow: documents, loan amount, guarantor, MFI. No SQL, no HTML.
 */
if (!defined('TRUSTLOAN_APPLICATION_CONTROLLER_LOADED')) {
    define('TRUSTLOAN_APPLICATION_CONTROLLER_LOADED', true);
}
require_once __DIR__ . '/../settings/db_class.php';
require_once __DIR__ . '/../Classes/Application.php';

class ApplicationController {

    private $baseUrl;

    public function __construct($baseUrl = '') {
        $this->baseUrl = rtrim((string) $baseUrl, '/');
    }

    private function redirectTo($page, array $extra = []) {
        $query = array_merge(['page' => $page], $extra);
        $path = $this->baseUrl ? $this->baseUrl . '/index.php' : 'index.php';
        header('Location: ' . $path . '?' . http_build_query($query));
        exit;
    }

    public function submitDocuments() {
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        if ($userId <= 0) {
            $this->redirectTo('signin');
            return;
        }
        $appId = Application::createOrGetLatest($userId);
        if (!$appId) {
            $this->redirectTo('documents');
            return;
        }
        $businessType = isset($_POST['business_type']) ? trim((string) $_POST['business_type']) : '';
        $businessDuration = isset($_POST['business_duration']) ? trim((string) $_POST['business_duration']) : '';
        $businessLocation = isset($_POST['business_location']) ? trim((string) $_POST['business_location']) : '';
        $pdo = DB::getConnection();
        $stmt = $pdo->prepare('UPDATE applications SET business_type = ?, business_duration = ?, business_location = ? WHERE id = ?');
        $stmt->execute([$businessType, $businessDuration, $businessLocation, $appId]);

        $isReupload = isset($_POST['reupload']) && $_POST['reupload'] === '1';
        // When post_max_size is exceeded, PHP may not populate $_FILES at all
        if (empty($_FILES)) {
            $_SESSION['documents_error'] = $isReupload
                ? 'Please select at least your Ghana Card front and back to save.'
                : 'No files were received. If you selected images, they may be too large. Try images under 2MB each.';
            $this->redirectTo('documents');
            return;
        }

        $uploadDir = $this->getApplicationUploadDir($appId);
        if ($uploadDir === null) {
            $_SESSION['documents_error'] = 'Upload folder could not be created. Please try again or contact support.';
            $this->redirectTo('documents');
            return;
        }

        $ghanaFront = $this->saveUploadedImage($uploadDir, 'ghana_card_front', 'ghana_front');
        $ghanaBack = $this->saveUploadedImage($uploadDir, 'ghana_card_back', 'ghana_back');
        if ($ghanaFront !== null || $ghanaBack !== null) {
            Application::updateGhanaCardPaths($appId, $ghanaFront, $ghanaBack);
        }
        $photoPaths = [];
        foreach (['photo_1', 'photo_2', 'photo_3'] as $idx => $name) {
            $path = $this->saveUploadedImage($uploadDir, $name, 'photo_' . $idx);
            if ($path !== null) {
                $photoPaths[] = $path;
            }
        }
        if (!empty($photoPaths)) {
            Application::setApplicationPhotos($appId, $photoPaths);
        }

        if ($isReupload) {
            $_SESSION['documents_saved_success'] = true;
        }
        $this->redirectTo($isReupload ? 'home' : 'loan-amount');
    }

    private function getApplicationUploadDir($appId) {
        $root = dirname(__DIR__);
        $dir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'applications' . DIRECTORY_SEPARATOR . (int) $appId;
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !@mkdir($dir, 0777, true)) {
                return null;
            }
        }
        return is_dir($dir) && is_writable($dir) ? $dir : null;
    }

    private function saveUploadedImage($baseDir, $fileKey, $prefix) {
        if ($baseDir === null || !isset($_FILES[$fileKey])) {
            return null;
        }
        $f = $_FILES[$fileKey];
        $tmp = is_array($f['tmp_name']) ? ($f['tmp_name'][0] ?? '') : ($f['tmp_name'] ?? '');
        $name = is_array($f['name']) ? ($f['name'][0] ?? '') : ($f['name'] ?? '');
        $error = is_array($f['error']) ? ($f['error'][0] ?? UPLOAD_ERR_NO_FILE) : ($f['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($tmp === '' || $error !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
            return null;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            $ext = 'jpg';
        }
        $safeName = $prefix . '.' . $ext;
        $dest = $baseDir . DIRECTORY_SEPARATOR . $safeName;
        if (!@move_uploaded_file($tmp, $dest)) {
            // Retry after ensuring directory is writable
            @chmod($baseDir, 0777);
            if (!@move_uploaded_file($tmp, $dest)) {
                return null;
            }
        }
        $appId = basename($baseDir);
        return 'uploads/applications/' . $appId . '/' . $safeName;
    }

    public function submitLoanAmount() {
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        if ($userId <= 0) {
            $this->redirectTo('signin');
            return;
        }
        $app = Application::getLatestByUser($userId);
        if (!$app) {
            $this->redirectTo('documents');
            return;
        }
        $amount = isset($_POST['requested_amount']) ? (float) $_POST['requested_amount'] : 0;
        $weeks = isset($_POST['repayment_weeks']) ? (int) $_POST['repayment_weeks'] : 12;
        if ($amount <= 0) {
            $this->redirectTo('loan-amount');
            return;
        }
        Application::updateLoanAmount($app['id'], $amount, $weeks);
        $this->redirectTo('guarantor');
    }

    public function submitGuarantor() {
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        if ($userId <= 0) {
            $this->redirectTo('signin');
            return;
        }
        $app = Application::getLatestByUser($userId);
        if (!$app) {
            $this->redirectTo('documents');
            return;
        }
        $fullName = isset($_POST['guarantor_name']) ? trim((string) $_POST['guarantor_name']) : '';
        $phone = isset($_POST['guarantor_phone']) ? trim((string) $_POST['guarantor_phone']) : '';
        $relationship = isset($_POST['relationship']) ? trim((string) $_POST['relationship']) : '';
        $occupation = isset($_POST['occupation']) ? trim((string) $_POST['occupation']) : '';
        if ($fullName === '' || $phone === '' || $relationship === '') {
            $this->redirectTo('guarantor');
            return;
        }
        Application::setGuarantor($app['id'], $fullName, $phone, $relationship, $occupation);
        $this->redirectTo('mfi');
    }

    public function submitMfi() {
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        if ($userId <= 0) {
            $this->redirectTo('signin');
            return;
        }
        $app = Application::getLatestByUser($userId);
        if (!$app) {
            $this->redirectTo('documents');
            return;
        }
        $mfiId = isset($_POST['mfi_id']) ? (int) $_POST['mfi_id'] : 0;
        $area = isset($_POST['area']) ? trim((string) $_POST['area']) : '';
        if ($mfiId <= 0) {
            $this->redirectTo('mfi');
            return;
        }
        Application::setMfi($app['id'], $mfiId, $area);
        $this->redirectTo('waiting');
    }
}
