<?php
/**
 * TrustLoan – Action: send verification code (POST: phone). Redirects to signin&step=code.
 */
require_once __DIR__ . '/../Controllers/AuthController.php';
(new AuthController(isset($baseUrl) ? $baseUrl : ''))->sendCode();
