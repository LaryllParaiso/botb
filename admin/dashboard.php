<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/AdminController.php';

// AdminController constructor enforces admin role
new AdminController(Database::getInstance());

// Redirect to bands page by default
header('Location: /BOB_SYSTEM/admin/bands.php');
exit;
