<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';

AuthMiddleware::requiereLogin();

require_once __DIR__ . '/../app/Views/ayudas/index.php';
