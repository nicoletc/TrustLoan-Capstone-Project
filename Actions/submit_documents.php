<?php
require_once __DIR__ . '/../Controllers/ApplicationController.php';
(new ApplicationController(isset($baseUrl) ? $baseUrl : ''))->submitDocuments();
