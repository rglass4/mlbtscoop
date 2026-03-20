<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Batting Leaderboard';
$leaders = db()->query('SELECT * FROM v_batting_leaders ORDER BY batting_average DESC, total_hits DESC, total_rbi DESC')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-4">
  <h1 class="h2 mb-1">Batting Leaderboard</h1>
  <p class="text-body-secondary mb-0">Aggregated batting lines across all imported games.</p>
</div>
<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-striped align-middle" data-sort-table>
      <thead><tr><th data-sort>Player</th><th data-sort>Team</th><th data-sort>Games</th><th data-sort>AB</th><th data-sort>H</th><th data-sort>R</th><th data-sort>RBI</th><th data-sort>BB</th><th data-sort>SO</th><th data-sort>AVG</th></tr></thead>
      <tbody>
        <?php foreach ($leaders as $row): ?>
          <tr><td><?= htmlspecialchars($row['player_name']) ?></td><td><?= htmlspecialchars($row['team_name']) ?></td><td><?= (int) $row['games_played'] ?></td><td><?= (int) $row['total_at_bats'] ?></td><td><?= (int) $row['total_hits'] ?></td><td><?= (int) $row['total_runs'] ?></td><td><?= (int) $row['total_rbi'] ?></td><td><?= (int) $row['total_walks'] ?></td><td><?= (int) $row['total_strikeouts'] ?></td><td><?= htmlspecialchars(number_format((float) $row['batting_average'], 3)) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
