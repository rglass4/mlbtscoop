<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Games';
$pdo = db();
$games = $pdo->query('SELECT * FROM v_game_summary ORDER BY imported_at DESC, id DESC')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h2 mb-1">Games</h1>
    <p class="text-body-secondary mb-0">Every imported co-op box score, sorted by most recent import.</p>
  </div>
</div>
<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-striped align-middle" data-sort-table>
      <thead>
        <tr>
          <th data-sort>Game ID</th>
          <th data-sort>Matchup</th>
          <th data-sort>Score</th>
          <th data-sort>Winner</th>
          <th data-sort>Ballpark</th>
          <th data-sort>Imported</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($games as $game): ?>
          <tr>
            <td><?= htmlspecialchars($game['external_game_id']) ?></td>
            <td><a href="/game.php?id=<?= (int) $game['id'] ?>"><?= htmlspecialchars($game['away_team']) ?> @ <?= htmlspecialchars($game['home_team']) ?></a></td>
            <td><?= (int) $game['away_runs'] ?>-<?= (int) $game['home_runs'] ?></td>
            <td><?= htmlspecialchars($game['winner_team']) ?></td>
            <td><?= htmlspecialchars((string) $game['ballpark']) ?></td>
            <td><?= htmlspecialchars((string) $game['imported_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$games): ?>
          <tr><td colspan="6" class="text-body-secondary">No games have been imported yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
