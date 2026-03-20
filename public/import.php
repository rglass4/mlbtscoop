<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/parser.php';

require_login();
start_session_if_needed();
$pdo = db();
$error = null;
$preview = $_SESSION['import_preview'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'upload';

    if ($action === 'upload') {
        if (!isset($_FILES['game_html']) || $_FILES['game_html']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please upload a valid HTML file.';
        } else {
            $tmpPath = $_FILES['game_html']['tmp_name'];
            $html = file_get_contents($tmpPath);
            if ($html === false) {
                $error = 'Could not read the uploaded file.';
            } else {
                try {
                    $parsed = parse_saved_game_html($html);
                    $check = $pdo->prepare('SELECT id FROM games WHERE external_game_id = :external_game_id LIMIT 1');
                    $check->execute(['external_game_id' => $parsed['game']['external_game_id']]);
                    if ($check->fetch()) {
                        throw new RuntimeException('That game ID already exists in the database.');
                    }
                    $filename = basename((string) $_FILES['game_html']['name']);
                    $storagePath = dirname(__DIR__) . '/storage/uploads/' . time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
                    move_uploaded_file($tmpPath, $storagePath);
                    $_SESSION['import_preview'] = ['parsed' => $parsed, 'filename' => $filename, 'storage_path' => $storagePath];
                    $preview = $_SESSION['import_preview'];
                    flash_set('info', 'Preview generated. Review the parsed data and confirm import.');
                    header('Location: /import.php');
                    exit;
                } catch (Throwable $throwable) {
                    $error = $throwable->getMessage();
                }
            }
        }
    }

    if ($action === 'confirm' && $preview) {
        try {
            $gameId = import_parsed_game($pdo, $preview['parsed'], (int) current_user()['id'], $preview['filename']);
            unset($_SESSION['import_preview']);
            flash_set('success', sprintf('Game %s imported successfully.', $preview['parsed']['game']['external_game_id']));
            header('Location: /game.php?id=' . $gameId);
            exit;
        } catch (Throwable $throwable) {
            $error = $throwable->getMessage();
        }
    }

    if ($action === 'clear') {
        unset($_SESSION['import_preview']);
        $preview = null;
        flash_set('success', 'Import preview cleared.');
        header('Location: /import.php');
        exit;
    }
}

$pageTitle = 'Import Game HTML';
require __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="card p-4">
      <h1 class="h3 mb-3">Import Saved HTML</h1>
      <p class="text-body-secondary">Upload an MLB The Show saved game HTML page. The parser will preview the extracted data before writing anything to Postgres.</p>
      <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload">
        <div class="mb-3">
          <label class="form-label">Saved HTML file</label>
          <input class="form-control" type="file" name="game_html" accept=".html,.htm,text/html" required>
        </div>
        <button class="btn btn-primary" type="submit">Parse for preview</button>
      </form>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
          <h2 class="h4 mb-1">Preview</h2>
          <p class="text-body-secondary mb-0">No database writes happen until you confirm.</p>
        </div>
        <?php if ($preview): ?>
          <form method="post" class="d-flex gap-2">
            <input type="hidden" name="action" value="confirm">
            <button class="btn btn-success" type="submit">Confirm Import</button>
          </form>
        <?php endif; ?>
      </div>
      <?php if ($preview): $parsed = $preview['parsed']; ?>
        <div class="mb-3">
          <span class="badge-soft">Game <?= htmlspecialchars($parsed['game']['external_game_id']) ?></span>
          <h3 class="h5 mt-2 mb-1"><?= htmlspecialchars($parsed['game']['away_team']) ?> @ <?= htmlspecialchars($parsed['game']['home_team']) ?></h3>
          <p class="text-body-secondary mb-0"><?= htmlspecialchars((string) $parsed['game']['played_at_text']) ?> · <?= htmlspecialchars((string) $preview['filename']) ?></p>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-4"><div class="card p-3"><div class="text-body-secondary">Batting Lines</div><div class="h3 mb-0"><?= count($parsed['batting_lines']) ?></div></div></div>
          <div class="col-md-4"><div class="card p-3"><div class="text-body-secondary">Pitching Lines</div><div class="h3 mb-0"><?= count($parsed['pitching_lines']) ?></div></div></div>
          <div class="col-md-4"><div class="card p-3"><div class="text-body-secondary">Play Events</div><div class="h3 mb-0"><?= count($parsed['play_events']) ?></div></div></div>
        </div>
        <div class="table-responsive mb-3">
          <table class="table table-striped align-middle"><thead><tr><th>Side</th><th>Team</th><th>User</th><th>R</th><th>H</th><th>E</th></tr></thead><tbody><tr><td>Away</td><td><?= htmlspecialchars($parsed['game']['away_team']) ?></td><td><?= htmlspecialchars($parsed['game']['away_user']) ?></td><td><?= (int) $parsed['game']['away_runs'] ?></td><td><?= (int) $parsed['game']['away_hits'] ?></td><td><?= (int) $parsed['game']['away_errors'] ?></td></tr><tr><td>Home</td><td><?= htmlspecialchars($parsed['game']['home_team']) ?></td><td><?= htmlspecialchars($parsed['game']['home_user']) ?></td><td><?= (int) $parsed['game']['home_runs'] ?></td><td><?= (int) $parsed['game']['home_hits'] ?></td><td><?= (int) $parsed['game']['home_errors'] ?></td></tr></tbody></table>
        </div>
        <div class="d-flex gap-2">
          <form method="post"><input type="hidden" name="action" value="confirm"><button class="btn btn-success" type="submit">Confirm Import</button></form>
          <form method="post"><input type="hidden" name="action" value="clear"><button class="btn btn-outline-light" type="submit">Clear Preview</button></form>
        </div>
      <?php else: ?>
        <p class="text-body-secondary mb-0">Upload a saved HTML file to see the parsed preview here.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
