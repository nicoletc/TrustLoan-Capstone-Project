<?php
/**
 * TrustLoan – Logout action. Destroys session and redirects to home.
 */
$baseUrl = isset($baseUrl) ? $baseUrl : '';
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
header('Location: ' . $baseUrl . 'index.php');
exit;
