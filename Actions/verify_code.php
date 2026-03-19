<?php
/**
 * TrustLoan – Action: verify code (POST: phone, code). Redirects to login or signin.
 */
require_once __DIR__ . '/../Controllers/AuthController.php';
(new AuthController(isset($baseUrl) ? $baseUrl : ''))->verifyCode();
