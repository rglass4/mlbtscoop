<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Batting Leaderboard';
$leaders = db()->query("
    SELECT
        player_name,
        team_name,
        COUNT(DISTINCT game_id) AS games_played,
        SUM(at_bats) AS total_at_bats,
        SUM(runs) AS total_runs,
        SUM(hits) AS total_hits,
        SUM(rbi) AS total_rbi,
        SUM(walks) AS total_walks,
        SUM(strikeouts) AS total_strikeouts,
        SUM(doubles) AS total_doubles,
        SUM(triples) AS total_triples,
        SUM(home_runs) AS total_home_runs,
        CASE WHEN SUM(at_bats) = 0 THEN 0 ELSE ROUND(SUM(hits)::NUMERIC / SUM(at_bats), 3) END AS batting_average,
        CASE WHEN SUM(at_bats) + SUM(walks) = 0 THEN 0 ELSE ROUND((SUM(hits) + SUM(walks))::NUMERIC / (SUM(at_bats) + SUM(walks)), 3) END AS on_base_percentage,
        CASE WHEN SUM(at_bats) = 0 THEN 0 ELSE ROUND(((SUM(hits) - SUM(doubles) - SUM(triples) - SUM(home_runs)) + (2 * SUM(doubles)) + (3 * SUM(triples)) + (4 * SUM(home_runs)))::NUMERIC / SUM(at_bats), 3) END AS slugging_percentage,
        CASE WHEN SUM(at_bats) = 0 AND SUM(walks) = 0 THEN 0 ELSE ROUND(
            (CASE WHEN SUM(at_bats) + SUM(walks) = 0 THEN 0 ELSE (SUM(hits) + SUM(walks))::NUMERIC / (SUM(at_bats) + SUM(walks)) END) +
            (CASE WHEN SUM(at_bats) = 0 THEN 0 ELSE ((SUM(hits) - SUM(doubles) - SUM(triples) - SUM(home_runs)) + (2 * SUM(doubles)) + (3 * SUM(triples)) + (4 * SUM(home_runs)))::NUMERIC / SUM(at_bats) END)
        , 3) END AS ops
    FROM batting_lines
    WHERE team_name = 'Mustangs'
    GROUP BY player_name, team_name
    ORDER BY ops DESC, total_hits DESC, total_rbi DESC, player_name ASC
")->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-4">
  <h1 class="h2 mb-1">Batting Leaderboard</h1>
  <p class="text-body-secondary mb-0">Aggregated Mustangs batting lines across all imported games.</p>
</div>
<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-striped align-middle" data-sort-table>
      <thead><tr><th data-sort>Player</th><th data-sort>Team</th><th data-sort>Games</th><th data-sort>AB</th><th data-sort>H</th><th data-sort>2B</th><th data-sort>3B</th><th data-sort>HR</th><th data-sort>R</th><th data-sort>RBI</th><th data-sort>BB</th><th data-sort>SO</th><th data-sort>AVG</th><th data-sort>OBP</th><th data-sort>SLG</th><th data-sort>OPS</th></tr></thead>
      <tbody>
        <?php foreach ($leaders as $row): ?>
          <tr><td><?= htmlspecialchars($row['player_name']) ?></td><td><?= htmlspecialchars($row['team_name']) ?></td><td><?= (int) $row['games_played'] ?></td><td><?= (int) $row['total_at_bats'] ?></td><td><?= (int) $row['total_hits'] ?></td><td><?= (int) $row['total_doubles'] ?></td><td><?= (int) $row['total_triples'] ?></td><td><?= (int) $row['total_home_runs'] ?></td><td><?= (int) $row['total_runs'] ?></td><td><?= (int) $row['total_rbi'] ?></td><td><?= (int) $row['total_walks'] ?></td><td><?= (int) $row['total_strikeouts'] ?></td><td><?= htmlspecialchars(number_format((float) $row['batting_average'], 3)) ?></td><td><?= htmlspecialchars(number_format((float) $row['on_base_percentage'], 3)) ?></td><td><?= htmlspecialchars(number_format((float) $row['slugging_percentage'], 3)) ?></td><td><?= htmlspecialchars(number_format((float) $row['ops'], 3)) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
