<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
logout_user();
flash_set('success', 'You have been logged out.');
header('Location: /index.php');
