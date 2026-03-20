<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: /import.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (attempt_login($username, $password)) {
        flash_set('success', 'Welcome back.');
        header('Location: /import.php');
        exit;
    }

    $error = 'Invalid username or password.';
}

$pageTitle = 'Admin Login';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card p-4">
      <h1 class="h3 mb-3">Admin Login</h1>
      <p class="text-body-secondary">Sign in to upload saved MLB The Show HTML box scores.</p>
      <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post">
        <div class="mb-3"><label class="form-label">Username</label><input class="form-control" type="text" name="username" required></div>
        <div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" required></div>
        <button class="btn btn-primary w-100" type="submit">Log in</button>
      </form>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
