<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/services/AuthService.php';

$authService = new AuthService(Database::getInstance());
$authService->logout();

header('Location: /BOB_SYSTEM/index.php');
exit;
