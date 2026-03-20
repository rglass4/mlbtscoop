<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$flash = flash_get();
$appName = app_env('APP_NAME', 'MLB The Show Co-op Tracker');
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? $appName) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark border-bottom border-secondary-subtle sticky-top app-nav">
  <div class="container">
    <a class="navbar-brand fw-bold text-uppercase" href="/index.php"><?= htmlspecialchars($appName) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link <?= str_contains($currentPath, 'index.php') || $currentPath === '/' ? 'active' : '' ?>" href="/index.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= str_contains($currentPath, 'games.php') || str_contains($currentPath, 'game.php') ? 'active' : '' ?>" href="/games.php">Games</a></li>
        <li class="nav-item"><a class="nav-link <?= str_contains($currentPath, 'batting.php') ? 'active' : '' ?>" href="/batting.php">Batting</a></li>
        <li class="nav-item"><a class="nav-link <?= str_contains($currentPath, 'pitching.php') ? 'active' : '' ?>" href="/pitching.php">Pitching</a></li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <?php if (is_logged_in()): ?>
          <li class="nav-item"><a class="nav-link <?= str_contains($currentPath, 'import.php') ? 'active' : '' ?>" href="/import.php">Import</a></li>
          <li class="nav-item"><span class="nav-link text-light-subtle">Admin: <?= htmlspecialchars((string) current_user()['username']) ?></span></li>
          <li class="nav-item"><a class="nav-link" href="/logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link <?= str_contains($currentPath, 'login.php') ? 'active' : '' ?>" href="/login.php">Admin Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<main class="container py-4">
  <?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> border-0 shadow-sm"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>
