<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
$pdo = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM games WHERE id = :id');
$stmt->execute(['id' => $id]);
$game = $stmt->fetch();
if (!$game) {
    http_response_code(404);
    $pageTitle = 'Game not found';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="alert alert-danger">Game not found.</div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$pageTitle = sprintf('Game %s', $game['external_game_id']);
$inningStmt = $pdo->prepare('SELECT side, inning_number, runs_scored FROM inning_lines WHERE game_id = :game_id ORDER BY side, inning_number');
$inningStmt->execute(['game_id' => $id]);
$innings = $inningStmt->fetchAll();
$battingStmt = $pdo->prepare('SELECT * FROM batting_lines WHERE game_id = :game_id ORDER BY side, id');
$battingStmt->execute(['game_id' => $id]);
$batting = $battingStmt->fetchAll();
$pitchingStmt = $pdo->prepare('SELECT * FROM pitching_lines WHERE game_id = :game_id ORDER BY side, id');
$pitchingStmt->execute(['game_id' => $id]);
$pitching = $pitchingStmt->fetchAll();
$playStmt = $pdo->prepare('SELECT * FROM play_events WHERE game_id = :game_id ORDER BY sequence_number');
$playStmt->execute(['game_id' => $id]);
$plays = $playStmt->fetchAll();
$perfectStmt = $pdo->prepare('SELECT * FROM perfect_perfect_events WHERE game_id = :game_id ORDER BY id');
$perfectStmt->execute(['game_id' => $id]);
$perfectEvents = $perfectStmt->fetchAll();
require __DIR__ . '/../includes/header.php';
function format_baseball_innings(int $outs): string
{
    return sprintf('%d.%d', intdiv($outs, 3), $outs % 3);
}

$inningBySide = ['away' => [], 'home' => []];
foreach ($innings as $inning) {
    $inningBySide[$inning['side']][$inning['inning_number']] = $inning['runs_scored'];
}
$battingBySide = ['away' => [], 'home' => []];
foreach ($batting as $row) { $battingBySide[$row['side']][] = $row; }
$pitchingBySide = ['away' => [], 'home' => []];
foreach ($pitching as $row) { $pitchingBySide[$row['side']][] = $row; }
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <span class="badge-soft mb-2">Game <?= htmlspecialchars($game['external_game_id']) ?></span>
    <h1 class="h2 mb-1"><?= htmlspecialchars($game['away_team']) ?> @ <?= htmlspecialchars($game['home_team']) ?></h1>
    <p class="text-body-secondary mb-0"><?= htmlspecialchars((string) $game['played_at_text']) ?> · <?= htmlspecialchars((string) $game['ballpark']) ?> · <?= htmlspecialchars((string) $game['weather']) ?></p>
  </div>
  <div class="card p-3 text-center">
    <div class="small text-body-secondary">Final</div>
    <div class="display-6 fw-bold"><?= (int) $game['away_runs'] ?> - <?= (int) $game['home_runs'] ?></div>
    <div class="small">Winner: <?= htmlspecialchars($game['winner_side'] === 'away' ? $game['away_team'] : $game['home_team']) ?></div>
  </div>
</div>
<div class="card p-3 mb-4">
  <h2 class="h4 mb-3">Line Score</h2>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Team</th><?php for ($i = 1; $i <= 9; $i++): ?><th><?= $i ?></th><?php endfor; ?><th>R</th><th>H</th><th>E</th></tr></thead>
      <tbody>
        <tr><td><?= htmlspecialchars($game['away_team']) ?></td><?php for ($i = 1; $i <= 9; $i++): ?><td><?= (int) ($inningBySide['away'][$i] ?? 0) ?></td><?php endfor; ?><td><?= (int) $game['away_runs'] ?></td><td><?= (int) $game['away_hits'] ?></td><td><?= (int) $game['away_errors'] ?></td></tr>
        <tr><td><?= htmlspecialchars($game['home_team']) ?></td><?php for ($i = 1; $i <= 9; $i++): ?><td><?= (int) ($inningBySide['home'][$i] ?? 0) ?></td><?php endfor; ?><td><?= (int) $game['home_runs'] ?></td><td><?= (int) $game['home_hits'] ?></td><td><?= (int) $game['home_errors'] ?></td></tr>
      </tbody>
    </table>
  </div>
</div>
<div class="row g-4">
  <?php foreach (['away' => $game['away_team'], 'home' => $game['home_team']] as $side => $teamName): ?>
    <div class="col-lg-6">
      <div class="card p-3 mb-4">
        <h2 class="h4 mb-3"><?= htmlspecialchars($teamName) ?> Batting</h2>
        <div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Batter</th><th>Pos</th><th>AB</th><th>R</th><th>H</th><th>RBI</th><th>BB</th><th>SO</th><th>AVG</th></tr></thead><tbody><?php foreach ($battingBySide[$side] as $row): ?><tr><td><?= htmlspecialchars($row['player_name']) ?></td><td><?= htmlspecialchars((string) $row['position']) ?></td><td><?= (int) $row['at_bats'] ?></td><td><?= (int) $row['runs'] ?></td><td><?= (int) $row['hits'] ?></td><td><?= (int) $row['rbi'] ?></td><td><?= (int) $row['walks'] ?></td><td><?= (int) $row['strikeouts'] ?></td><td><?= htmlspecialchars(number_format((float) $row['batting_average'], 3)) ?></td></tr><?php endforeach; ?></tbody></table></div>
      </div>
      <div class="card p-3">
        <h2 class="h4 mb-3"><?= htmlspecialchars($teamName) ?> Pitching</h2>
        <div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Pitcher</th><th>Decision</th><th>IP</th><th>H</th><th>R</th><th>ER</th><th>BB</th><th>SO</th><th>ERA</th></tr></thead><tbody><?php foreach ($pitchingBySide[$side] as $row): ?><tr><td><?= htmlspecialchars($row['player_name']) ?></td><td><?= htmlspecialchars((string) $row['decision']) ?></td><td><?= htmlspecialchars(format_baseball_innings((int) $row['innings_pitched_outs'])) ?></td><td><?= (int) $row['hits_allowed'] ?></td><td><?= (int) $row['runs_allowed'] ?></td><td><?= (int) $row['earned_runs'] ?></td><td><?= (int) $row['walks'] ?></td><td><?= (int) $row['strikeouts'] ?></td><td><?= htmlspecialchars(number_format((float) $row['era'], 3)) ?></td></tr><?php endforeach; ?></tbody></table></div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<div class="row g-4 mt-1">
  <div class="col-lg-8">
    <div class="card p-3">
      <h2 class="h4 mb-3">Play-by-Play</h2>
      <div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Inning</th><th>Half</th><th>Description</th><th>R</th><th>H</th><th>LOB</th></tr></thead><tbody><?php foreach ($plays as $play): ?><tr><td><?= (int) $play['inning_number'] ?></td><td><?= htmlspecialchars($play['half']) ?></td><td><?= htmlspecialchars($play['description']) ?></td><td><?= (int) $play['runs'] ?></td><td><?= (int) $play['hits'] ?></td><td><?= htmlspecialchars((string) $play['runners_left_on']) ?></td></tr><?php endforeach; ?></tbody></table></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card p-3 mb-4">
      <h2 class="h4 mb-3">Perfect-Perfect</h2>
      <ul class="list-group list-group-flush"><?php foreach ($perfectEvents as $event): ?><li class="list-group-item"><strong><?= htmlspecialchars($event['player_name']) ?></strong><br><span class="text-body-secondary"><?= (int) $event['exit_velocity_mph'] ?> mph · <?= htmlspecialchars($event['description']) ?></span></li><?php endforeach; ?><?php if (!$perfectEvents): ?><li class="list-group-item text-body-secondary">No perfect-perfect events recorded.</li><?php endif; ?></ul>
    </div>
    <div class="card p-3">
      <h2 class="h4 mb-3">Game Notes</h2>
      <p class="mb-2"><strong>Summary:</strong> <?= htmlspecialchars((string) $game['summary']) ?></p>
      <p class="mb-2"><strong>Hitting Difficulty:</strong> <?= htmlspecialchars((string) $game['hitting_difficulty']) ?></p>
      <p class="mb-2"><strong>Pitching Difficulty:</strong> <?= htmlspecialchars((string) $game['pitching_difficulty']) ?></p>
      <p class="mb-0"><strong>First Pitch:</strong> <?= htmlspecialchars((string) $game['scheduled_first_pitch']) ?></p>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
