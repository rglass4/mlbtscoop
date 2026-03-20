<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Pitching Leaderboard';
$leaders = db()->query('SELECT * FROM v_pitching_leaders ORDER BY era ASC NULLS LAST, strikeouts DESC, innings_pitched_outs DESC')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-4">
  <h1 class="h2 mb-1">Pitching Leaderboard</h1>
  <p class="text-body-secondary mb-0">Aggregated pitching lines across all imported games.</p>
</div>
<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-striped align-middle" data-sort-table>
      <thead><tr><th data-sort>Pitcher</th><th data-sort>Team</th><th data-sort>Games</th><th data-sort>IP (outs)</th><th data-sort>H</th><th data-sort>R</th><th data-sort>ER</th><th data-sort>BB</th><th data-sort>SO</th><th data-sort>ERA</th></tr></thead>
      <tbody>
        <?php foreach ($leaders as $row): ?>
          <tr><td><?= htmlspecialchars($row['player_name']) ?></td><td><?= htmlspecialchars($row['team_name']) ?></td><td><?= (int) $row['games_pitched'] ?></td><td><?= (int) $row['innings_pitched_outs'] ?></td><td><?= (int) $row['hits_allowed'] ?></td><td><?= (int) $row['runs_allowed'] ?></td><td><?= (int) $row['earned_runs'] ?></td><td><?= (int) $row['walks'] ?></td><td><?= (int) $row['strikeouts'] ?></td><td><?= htmlspecialchars(number_format((float) $row['era'], 2)) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
