<?php
/**
 * TrustLoan – Auth: request handling only. Calls User and VerificationCode.
 */
if (!defined('TRUSTLOAN_AUTH_CONTROLLER_LOADED')) {
    define('TRUSTLOAN_AUTH_CONTROLLER_LOADED', true);
}
require_once __DIR__ . '/../Classes/User.php';
require_once __DIR__ . '/../Classes/VerificationCode.php';

class AuthController {

    private $baseUrl;

    public function __construct($baseUrl = '') {
        $this->baseUrl = rtrim((string) $baseUrl, '/');
    }

    /**
     * Build redirect URL with page param so we never redirect to bare index.php.
     * @param string $page e.g. 'login', 'signin', 'home'
     * @param array $extra e.g. ['error' => 'invalid_code', 'existing' => '1']
     */
    private function redirectTo($page, array $extra = []) {
        $query = array_merge(['page' => $page], $extra);
        $path = $this->baseUrl ? $this->baseUrl . '/index.php' : 'index.php';
        $url = $path . '?' . http_build_query($query);
        header('Location: ' . $url);
        exit;
    }

    public function sendCode() {
        // Already logged in: restore session by redirecting to dashboard
        if (function_exists('is_logged_in') && is_logged_in()) {
            $this->redirectTo('home');
            return;
        }
        // Prefer full number from JS (intl-tel-input may only send national in name="phone")
        $phone = isset($_POST['phone_full']) && trim((string) $_POST['phone_full']) !== ''
            ? trim((string) $_POST['phone_full'])
            : (isset($_POST['phone']) ? trim((string) $_POST['phone']) : '');
        if ($phone === '') {
            $this->redirectTo('signin');
            return;
        }
        $normalised = User::normalisePhone($phone);
        if (strlen($normalised) < 9) {
            $this->redirectTo('signin');
            return;
        }
        // Existing member (id already in database): redirect to login instead of sending code
        $existingUser = User::getByPhone($normalised);
        if (!$existingUser && $phone !== $normalised) {
            $existingUser = User::getByPhone($phone);
        }
        if ($existingUser) {
            $this->redirectTo('login', ['existing_member' => '1']);
            return;
        }
        $code = VerificationCode::sendCode($phone);
        if ($code === false) {
            $this->redirectTo('signin');
            return;
        }
        $_SESSION['pending_phone'] = $normalised;
        $this->redirectTo('signin', ['step' => 'code']);
    }

    public function verifyCode() {
        $phone = isset($_POST['phone']) ? User::normalisePhone(trim((string) $_POST['phone'])) : '';
        $code = isset($_POST['code']) ? trim((string) $_POST['code']) : '';
        if ($phone === '' || $code === '') {
            $this->redirectTo('signin');
            return;
        }
        if (!VerificationCode::validate($phone, $code)) {
            $this->redirectTo('signin', ['error' => 'invalid_code']);
            return;
        }
        $user = User::getByPhone($phone);
        if ($user) {
            $_SESSION['pending_login_user_id'] = (int) $user['id'];
            unset($_SESSION['pending_phone']);
            $this->redirectTo('login', ['existing' => '1']);
            return;
        }
        $_SESSION['pending_phone'] = $phone;
        unset($_SESSION['pending_login_user_id']);
        $_SESSION['login_show_register'] = true; // so login page shows "Create your account" only after verify
        $this->redirectTo('login');
    }

    public function register() {
        $phone = isset($_SESSION['pending_phone']) ? (string) $_SESSION['pending_phone'] : '';
        if ($phone === '') {
            $this->redirectTo('signin');
            return;
        }
        $fullName = isset($_POST['full_name']) ? trim((string) $_POST['full_name']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $confirm = isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : '';
        if ($fullName === '' || $password !== $confirm || strlen($password) < 6) {
            $this->redirectTo('login', ['error' => 'validation']);
            return;
        }
        $userId = User::create($phone, $fullName, $password);
        if ($userId === false) {
            $this->redirectTo('login', ['error' => 'create']);
            return;
        }
        if (function_exists('session_regenerate_id')) {
            session_regenerate_id(true);
        }
        if (function_exists('clear_admin_session')) {
            clear_admin_session();
        }
        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = 2; // 1=admin, 2=customer; registration always gives 2
        $_SESSION['full_name'] = $fullName;
        $_SESSION['last_activity'] = time();
        unset($_SESSION['pending_phone'], $_SESSION['login_show_register']);
        $this->redirectTo('documents');
    }

    public function login() {
        $userId = isset($_SESSION['pending_login_user_id']) ? (int) $_SESSION['pending_login_user_id'] : 0;
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        if ($userId > 0 && $password !== '') {
            if (User::verifyPassword($userId, $password)) {
                $user = User::getById($userId);
                if ($user) {
                    if (function_exists('session_regenerate_id')) {
                        session_regenerate_id(true);
                    }
                    if (function_exists('clear_admin_session')) {
                        clear_admin_session();
                    }
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['role'] = isset($user['role']) ? (int) $user['role'] : 2;
                    $_SESSION['full_name'] = $user['full_name'] ?? '';
                    $_SESSION['phone'] = isset($user['phone']) ? (string) $user['phone'] : '';
                    $_SESSION['last_activity'] = time();
                    unset($_SESSION['pending_login_user_id'], $_SESSION['pending_phone']);
                    $this->redirectTo('home');
                    return;
                }
            }
        }
        $this->redirectTo('login', ['error' => 'invalid']);
    }

    /**
     * Direct login with full name + password (no verification code). Redirects to dashboard on success.
     */
    public function loginWithName() {
        $fullName = isset($_POST['full_name']) ? trim((string) $_POST['full_name']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        if ($fullName === '' || $password === '') {
            $this->redirectTo('login', ['error' => 'direct_validation']);
            return;
        }
        $candidates = User::getByFullName($fullName);
        $user = null;
        foreach ($candidates as $row) {
            if (isset($row['id']) && User::verifyPassword((int) $row['id'], $password)) {
                $user = $row;
                break;
            }
        }
        if (!$user || !isset($user['id'])) {
            $this->redirectTo('login', ['error' => 'invalid']);
            return;
        }
        if (function_exists('session_regenerate_id')) {
            session_regenerate_id(true);
        }
        if (function_exists('clear_admin_session')) {
            clear_admin_session();
        }
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['role'] = isset($user['role']) ? (int) $user['role'] : 2;
        $_SESSION['full_name'] = isset($user['full_name']) ? (string) $user['full_name'] : '';
        $_SESSION['phone'] = isset($user['phone']) ? (string) $user['phone'] : '';
        $_SESSION['last_activity'] = time();
        unset($_SESSION['pending_phone'], $_SESSION['pending_login_user_id']);
        $this->redirectTo('home');
    }
}
