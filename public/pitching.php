<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Pitching Leaderboard';
$pdo = db();
$trackedTeamsSql = sql_string_list($pdo, tracked_teams());
$trackedTeamsLabel = implode(' and ', tracked_teams());

function format_baseball_innings(int $outs): string
{
    return sprintf('%d.%d', intdiv($outs, 3), $outs % 3);
}

$leaders = $pdo->query("
    SELECT
        player_name,
        team_name,
        COUNT(DISTINCT game_id) AS games_pitched,
        SUM(innings_pitched_outs) AS innings_pitched_outs,
        SUM(CASE WHEN UPPER(COALESCE(decision, '')) LIKE 'W%' THEN 1 ELSE 0 END) AS wins,
        SUM(CASE WHEN UPPER(COALESCE(decision, '')) LIKE 'L%' THEN 1 ELSE 0 END) AS losses,
        SUM(CASE WHEN UPPER(COALESCE(decision, '')) LIKE 'H%' THEN 1 ELSE 0 END) AS holds,
        SUM(CASE WHEN UPPER(COALESCE(decision, '')) LIKE 'S%' THEN 1 ELSE 0 END) AS saves,
        SUM(hits_allowed) AS hits_allowed,
        SUM(runs_allowed) AS runs_allowed,
        SUM(earned_runs) AS earned_runs,
        SUM(walks) AS walks,
        SUM(strikeouts) AS strikeouts,
        CASE WHEN SUM(innings_pitched_outs) = 0 THEN NULL ELSE ROUND((SUM(earned_runs) * 27.0 / SUM(innings_pitched_outs))::NUMERIC, 2) END AS era,
        CASE WHEN SUM(innings_pitched_outs) = 0 THEN NULL ELSE ROUND(((SUM(hits_allowed) + SUM(walks)) * 3.0 / SUM(innings_pitched_outs))::NUMERIC, 2) END AS whip
    FROM pitching_lines
    WHERE team_name IN ($trackedTeamsSql)
    GROUP BY player_name, team_name
    ORDER BY era ASC NULLS LAST, strikeouts DESC, innings_pitched_outs DESC, player_name ASC
")->fetchAll();
$totals = $pdo->query("
    SELECT
        COUNT(DISTINCT game_id) AS games_pitched,
        SUM(innings_pitched_outs) AS innings_pitched_outs,
        SUM(CASE WHEN UPPER(COALESCE(decision, '')) LIKE 'W%' THEN 1 ELSE 0 END) AS wins,
        SUM(CASE WHEN UPPER(COALESCE(decision, '')) LIKE 'L%' THEN 1 ELSE 0 END) AS losses,
        SUM(CASE WHEN UPPER(COALESCE(decision, '')) LIKE 'H%' THEN 1 ELSE 0 END) AS holds,
        SUM(CASE WHEN UPPER(COALESCE(decision, '')) LIKE 'S%' THEN 1 ELSE 0 END) AS saves,
        SUM(hits_allowed) AS hits_allowed,
        SUM(runs_allowed) AS runs_allowed,
        SUM(earned_runs) AS earned_runs,
        SUM(walks) AS walks,
        SUM(strikeouts) AS strikeouts,
        CASE WHEN SUM(innings_pitched_outs) = 0 THEN NULL ELSE ROUND((SUM(earned_runs) * 27.0 / SUM(innings_pitched_outs))::NUMERIC, 2) END AS era,
        CASE WHEN SUM(innings_pitched_outs) = 0 THEN NULL ELSE ROUND(((SUM(hits_allowed) + SUM(walks)) * 3.0 / SUM(innings_pitched_outs))::NUMERIC, 2) END AS whip
    FROM pitching_lines
    WHERE team_name IN ($trackedTeamsSql)
")->fetch();
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-4">
  <h1 class="h2 mb-1">Pitching Leaderboard</h1>
  <p class="text-body-secondary mb-0">Aggregated pitching lines for <?= htmlspecialchars($trackedTeamsLabel) ?> across all imported games.</p>
</div>
<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-striped align-middle" data-sort-table>
      <thead><tr><th data-sort>Pitcher</th><th data-sort>Team</th><th data-sort>Games</th><th data-sort>IP</th><th data-sort>W</th><th data-sort>L</th><th data-sort>HLD</th><th data-sort>SV</th><th data-sort>H</th><th data-sort>R</th><th data-sort>ER</th><th data-sort>BB</th><th data-sort>SO</th><th data-sort>WHIP</th><th data-sort>ERA</th></tr></thead>
      <tbody>
        <?php foreach ($leaders as $row): ?>
          <tr><td><?= htmlspecialchars($row['player_name']) ?></td><td><?= htmlspecialchars($row['team_name']) ?></td><td><?= (int) $row['games_pitched'] ?></td><td><?= htmlspecialchars(format_baseball_innings((int) $row['innings_pitched_outs'])) ?></td><td><?= (int) $row['wins'] ?></td><td><?= (int) $row['losses'] ?></td><td><?= (int) $row['holds'] ?></td><td><?= (int) $row['saves'] ?></td><td><?= (int) $row['hits_allowed'] ?></td><td><?= (int) $row['runs_allowed'] ?></td><td><?= (int) $row['earned_runs'] ?></td><td><?= (int) $row['walks'] ?></td><td><?= (int) $row['strikeouts'] ?></td><td><?= $row['whip'] === null ? '-' : htmlspecialchars(number_format((float) $row['whip'], 2)) ?></td><td><?= $row['era'] === null ? '-' : htmlspecialchars(number_format((float) $row['era'], 2)) ?></td></tr>
        <?php endforeach; ?>
        <tr class="fw-semibold">
          <td>Totals</td>
          <td><?= htmlspecialchars($trackedTeamsLabel) ?></td>
          <td><?= (int) ($totals['games_pitched'] ?? 0) ?></td>
          <td><?= htmlspecialchars(format_baseball_innings((int) ($totals['innings_pitched_outs'] ?? 0))) ?></td>
          <td><?= (int) ($totals['wins'] ?? 0) ?></td>
          <td><?= (int) ($totals['losses'] ?? 0) ?></td>
          <td><?= (int) ($totals['holds'] ?? 0) ?></td>
          <td><?= (int) ($totals['saves'] ?? 0) ?></td>
          <td><?= (int) ($totals['hits_allowed'] ?? 0) ?></td>
          <td><?= (int) ($totals['runs_allowed'] ?? 0) ?></td>
          <td><?= (int) ($totals['earned_runs'] ?? 0) ?></td>
          <td><?= (int) ($totals['walks'] ?? 0) ?></td>
          <td><?= (int) ($totals['strikeouts'] ?? 0) ?></td>
          <td><?= ($totals['whip'] ?? null) === null ? '-' : htmlspecialchars(number_format((float) $totals['whip'], 2)) ?></td>
          <td><?= ($totals['era'] ?? null) === null ? '-' : htmlspecialchars(number_format((float) $totals['era'], 2)) ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
