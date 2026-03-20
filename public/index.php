<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Dashboard';
$pdo = db();
$totals = $pdo->query('SELECT COUNT(*) AS games, COALESCE(SUM(away_runs + home_runs), 0) AS runs, COALESCE(SUM(away_hits + home_hits), 0) AS hits FROM games')->fetch();
$recentGames = $pdo->query('SELECT * FROM v_game_summary ORDER BY imported_at DESC LIMIT 10')->fetchAll();
$topBatters = $pdo->query('SELECT * FROM v_batting_leaders ORDER BY total_hits DESC, batting_average DESC LIMIT 5')->fetchAll();
$topPitchers = $pdo->query('SELECT * FROM v_pitching_leaders ORDER BY era ASC NULLS LAST, strikeouts DESC LIMIT 5')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
  <div>
    <h1 class="h2 mb-1">Co-op Box Score Dashboard</h1>
    <p class="text-body-secondary mb-0">Track imported MLB The Show game logs, leaderboards, and perfect-perfect events.</p>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card metric-card p-3 h-100"><div class="text-body-secondary">Imported Games</div><div class="display-6 fw-bold"><?= (int) ($totals['games'] ?? 0) ?></div></div></div>
  <div class="col-md-4"><div class="card metric-card p-3 h-100"><div class="text-body-secondary">Total Runs Logged</div><div class="display-6 fw-bold"><?= (int) ($totals['runs'] ?? 0) ?></div></div></div>
  <div class="col-md-4"><div class="card metric-card p-3 h-100"><div class="text-body-secondary">Total Hits Logged</div><div class="display-6 fw-bold"><?= (int) ($totals['hits'] ?? 0) ?></div></div></div>
</div>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Recent Games</h2>
        <a href="/games.php" class="btn btn-outline-light btn-sm">View all</a>
      </div>
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead><tr><th>Matchup</th><th>Score</th><th>Winner</th><th>Imported</th></tr></thead>
          <tbody>
            <?php foreach ($recentGames as $game): ?>
              <tr>
                <td><a href="/game.php?id=<?= (int) $game['id'] ?>"><?= htmlspecialchars($game['away_team']) ?> @ <?= htmlspecialchars($game['home_team']) ?></a></td>
                <td><?= (int) $game['away_runs'] ?>-<?= (int) $game['home_runs'] ?></td>
                <td><?= htmlspecialchars($game['winner_team']) ?></td>
                <td><?= htmlspecialchars((string) $game['imported_at']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$recentGames): ?>
              <tr><td colspan="4" class="text-body-secondary">No games imported yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card p-3 mb-4">
      <h2 class="h4 mb-3">Top Batters</h2>
      <ul class="list-group list-group-flush">
        <?php foreach ($topBatters as $batter): ?>
          <li class="list-group-item d-flex justify-content-between"><span><?= htmlspecialchars($batter['player_name']) ?></span><span><?= htmlspecialchars(number_format((float) $batter['batting_average'], 3)) ?> AVG</span></li>
        <?php endforeach; ?>
        <?php if (!$topBatters): ?><li class="list-group-item text-body-secondary">No batting data yet.</li><?php endif; ?>
      </ul>
    </div>
    <div class="card p-3">
      <h2 class="h4 mb-3">Top Pitchers</h2>
      <ul class="list-group list-group-flush">
        <?php foreach ($topPitchers as $pitcher): ?>
          <li class="list-group-item d-flex justify-content-between"><span><?= htmlspecialchars($pitcher['player_name']) ?></span><span><?= htmlspecialchars(number_format((float) $pitcher['era'], 2)) ?> ERA</span></li>
        <?php endforeach; ?>
        <?php if (!$topPitchers): ?><li class="list-group-item text-body-secondary">No pitching data yet.</li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
