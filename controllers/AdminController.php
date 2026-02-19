<?php
require_once __DIR__ . '/BaseController.php';

/**
 * AdminController — Liskov Substitution: extends BaseController
 * Handles admin-specific access control
 */
class AdminController extends BaseController
{
    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->requireRole('admin');
    }
}
