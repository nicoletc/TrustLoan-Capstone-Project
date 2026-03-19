<?php
require_once __DIR__ . '/../Controllers/AuthController.php';
(new AuthController(isset($baseUrl) ? $baseUrl : ''))->register();
