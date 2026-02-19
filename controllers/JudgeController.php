<?php
require_once __DIR__ . '/BaseController.php';

/**
 * JudgeController — Liskov Substitution: extends BaseController
 * Handles judge-specific access control
 */
class JudgeController extends BaseController
{
    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->requireRole('judge');
    }
}
