<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Dashboard';
$pdo = db();
$totals = $pdo->query("
    SELECT
        COUNT(*) AS games,
        COALESCE(SUM(CASE WHEN away_team = 'Mustangs' THEN away_runs ELSE 0 END + CASE WHEN home_team = 'Mustangs' THEN home_runs ELSE 0 END), 0) AS runs,
        COALESCE(SUM(CASE WHEN away_team = 'Mustangs' THEN away_hits ELSE 0 END + CASE WHEN home_team = 'Mustangs' THEN home_hits ELSE 0 END), 0) AS hits
    FROM games
")->fetch();
$recentGames = $pdo->query('SELECT id, away_team, home_team, away_runs, home_runs, winner_side, played_at_text FROM games ORDER BY imported_at DESC, id DESC LIMIT 10')->fetchAll();
$topBatters = $pdo->query("
    SELECT
        player_name,
        SUM(at_bats) AS total_at_bats,
        SUM(hits) AS total_hits,
        SUM(walks) AS total_walks,
        SUM(doubles) AS total_doubles,
        SUM(triples) AS total_triples,
        SUM(home_runs) AS total_home_runs,
        CASE WHEN SUM(at_bats) + SUM(walks) = 0 THEN 0 ELSE ROUND((SUM(hits) + SUM(walks))::NUMERIC / (SUM(at_bats) + SUM(walks)), 3) END AS on_base_percentage,
        CASE WHEN SUM(at_bats) = 0 THEN 0 ELSE ROUND(((SUM(hits) - SUM(doubles) - SUM(triples) - SUM(home_runs)) + (2 * SUM(doubles)) + (3 * SUM(triples)) + (4 * SUM(home_runs)))::NUMERIC / SUM(at_bats), 3) END AS slugging_percentage,
        CASE WHEN SUM(at_bats) = 0 AND SUM(walks) = 0 THEN 0 ELSE ROUND(
            (CASE WHEN SUM(at_bats) + SUM(walks) = 0 THEN 0 ELSE (SUM(hits) + SUM(walks))::NUMERIC / (SUM(at_bats) + SUM(walks)) END) +
            (CASE WHEN SUM(at_bats) = 0 THEN 0 ELSE ((SUM(hits) - SUM(doubles) - SUM(triples) - SUM(home_runs)) + (2 * SUM(doubles)) + (3 * SUM(triples)) + (4 * SUM(home_runs)))::NUMERIC / SUM(at_bats) END)
        , 3) END AS ops
    FROM batting_lines
    WHERE team_name = 'Mustangs'
    GROUP BY player_name
    ORDER BY ops DESC, total_hits DESC, total_home_runs DESC, player_name ASC
    LIMIT 5
")->fetchAll();
$topPitchers = $pdo->query("
    SELECT
        player_name,
        SUM(innings_pitched_outs) AS innings_pitched_outs,
        SUM(strikeouts) AS strikeouts,
        CASE WHEN SUM(innings_pitched_outs) = 0 THEN NULL ELSE ROUND((SUM(earned_runs) * 27.0 / SUM(innings_pitched_outs))::NUMERIC, 2) END AS era
    FROM pitching_lines
    WHERE team_name = 'Mustangs'
    GROUP BY player_name
    ORDER BY era ASC NULLS LAST, strikeouts DESC, innings_pitched_outs DESC, player_name ASC
    LIMIT 5
")->fetchAll();

function format_game_date(?string $playedAtText): string
{
    if (!$playedAtText) {
        return '-';
    }

    $date = DateTimeImmutable::createFromFormat('n/j/Y g:iA T', $playedAtText);
    if (!$date) {
        return $playedAtText;
    }

    return strtolower($date->format('Y-m-d h:i A'));
}

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
          <thead><tr><th>Matchup</th><th>Score</th><th>Winner</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($recentGames as $game): ?>
              <tr>
                <td><a href="/game.php?id=<?= (int) $game['id'] ?>"><?= htmlspecialchars($game['away_team']) ?> @ <?= htmlspecialchars($game['home_team']) ?></a></td>
                <td><?= (int) $game['away_runs'] ?>-<?= (int) $game['home_runs'] ?></td>
                <td><?= htmlspecialchars($game['winner_side'] === 'away' ? $game['away_team'] : $game['home_team']) ?></td>
                <td><?= htmlspecialchars(format_game_date($game['played_at_text'])) ?></td>
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
          <li class="list-group-item d-flex justify-content-between"><span><?= htmlspecialchars($batter['player_name']) ?></span><span><?= htmlspecialchars(number_format((float) $batter['ops'], 3)) ?> OPS</span></li>
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
