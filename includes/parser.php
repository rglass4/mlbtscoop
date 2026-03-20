<?php

declare(strict_types=1);

function parse_saved_game_html(string $html): array
{
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $gameId = null;
    foreach ($xpath->query('//script[contains(., "page_path")]') as $scriptNode) {
        if (preg_match("#/games/(\d+)#", $scriptNode->textContent, $matches)) {
            $gameId = $matches[1];
            break;
        }
    }

    if ($gameId === null && preg_match('#/games/(\d+)#', $html, $matches)) {
        $gameId = $matches[1];
    }

    if ($gameId === null) {
        throw new RuntimeException('Could not locate game ID in the uploaded HTML file.');
    }

    $sectionBlocks = $xpath->query("//div[contains(@class,'section-block')]");
    if ($sectionBlocks->length < 2) {
        throw new RuntimeException('Expected summary and game log sections were not found.');
    }

    $summarySection = $sectionBlocks->item(0);
    $summaryHeading = trim(normalize_space($xpath->evaluate('string(.//h2)', $summarySection)));
    preg_match_all('/([A-Za-z0-9_.\-]+)/', $summaryHeading, $users);
    $playerNames = array_slice($users[1], 0, 2);

    $summaryWellText = normalize_space($xpath->evaluate("string(.//div[contains(@class,'well')])", $summarySection));
    $playedAt = null;
    $winnerSummary = '';
    if (preg_match('#(\d{1,2}/\d{1,2}/\d{4}\s+\d{1,2}:\d{2}[AP]M\s+[A-Z]{2,4})#', $summaryWellText, $playedAtMatch)) {
        $playedAt = trim($playedAtMatch[1]);
    }
    if (preg_match('/(W:\s*.*)$/', $summaryWellText, $summaryMatch)) {
        $winnerSummary = trim($summaryMatch[1]);
    }

    $summaryTable = $xpath->query('.//table[1]', $summarySection)->item(0);
    if (!$summaryTable) {
        throw new RuntimeException('Summary table missing from uploaded HTML.');
    }

    $teamRows = $xpath->query('.//tbody/tr[position()>1]', $summaryTable);
    $teams = [];
    foreach ($teamRows as $rowIndex => $row) {
        $cells = [];
        foreach ($xpath->query('./td|./th', $row) as $cell) {
            $cells[] = normalize_space($cell->textContent);
        }

        if (count($cells) < 16) {
            continue;
        }

        $inningValues = array_slice($cells, 4, 9);
        $totals = array_slice($cells, 13, 3);
        $teams[] = [
            'side' => $rowIndex === 0 ? 'away' : 'home',
            'team_name' => $cells[1],
            'username' => $cells[2],
            'result' => $cells[3],
            'innings' => $inningValues,
            'runs' => (int) zero_if_x($totals[0]),
            'hits' => (int) zero_if_x($totals[1]),
            'errors' => (int) zero_if_x($totals[2]),
        ];
    }

    $boxSections = $xpath->query("//div[contains(@class,'boxscore-box')]//div[contains(@class,'section-block')]");
    $battingLines = [];
    $pitchingLines = [];
    foreach ($boxSections as $sectionIndex => $boxSection) {
        $teamName = trim(normalize_space($xpath->evaluate('string(.//h3)', $boxSection)));
        $tables = $xpath->query('.//table', $boxSection);
        if ($tables->length < 2) {
            continue;
        }

        $battingTable = $tables->item(0);
        foreach ($xpath->query('.//tbody/tr[not(contains(@class,"totals"))]', $battingTable) as $row) {
            $cells = [];
            foreach ($xpath->query('./td', $row) as $cell) {
                $cells[] = normalize_space($cell->textContent);
            }
            if (count($cells) < 8) {
                continue;
            }
            [$playerName, $position] = split_player_and_position($cells[0]);
            $battingLines[] = [
                'team_name' => $teamName,
                'side' => $sectionIndex === 0 ? 'away' : 'home',
                'player_name' => $playerName,
                'position' => $position,
                'at_bats' => (int) $cells[1],
                'runs' => (int) $cells[2],
                'hits' => (int) $cells[3],
                'rbi' => (int) $cells[4],
                'walks' => (int) $cells[5],
                'strikeouts' => (int) $cells[6],
                'average' => normalize_decimal($cells[7]),
            ];
        }

        $pitchingTable = $tables->item(1);
        foreach ($xpath->query('.//tbody/tr[not(contains(@class,"totals"))]', $pitchingTable) as $row) {
            $cells = [];
            foreach ($xpath->query('./td', $row) as $cell) {
                $cells[] = normalize_space($cell->textContent);
            }
            if (count($cells) < 8) {
                continue;
            }
            [$pitcherName, $decision] = extract_pitcher_decision($cells[0]);
            $pitchingLines[] = [
                'team_name' => $teamName,
                'side' => $sectionIndex === 0 ? 'away' : 'home',
                'player_name' => $pitcherName,
                'decision' => $decision,
                'innings_pitched' => innings_to_outs($cells[1]),
                'hits_allowed' => (int) $cells[2],
                'runs_allowed' => (int) $cells[3],
                'earned_runs' => (int) $cells[4],
                'walks' => (int) $cells[5],
                'strikeouts' => (int) $cells[6],
                'era' => normalize_decimal($cells[7]),
            ];
        }
    }

    $gameLogSection = $sectionBlocks->item($sectionBlocks->length - 1);
    $gameLogHtml = inner_html($gameLogSection);
    $logParts = preg_split('/Perfect Contact Hits \(Perfect-Perfect\)/', $gameLogHtml);
    $playLogHtml = $logParts[0] ?? '';
    $perfectHtml = $logParts[1] ?? '';

    $playEvents = extract_play_events(strip_tags($playLogHtml, '<br>'));
    $perfectEvents = extract_perfect_events(strip_tags($perfectHtml, '<br>'));
    $metadata = extract_game_metadata($gameLogSection->textContent);

    return [
        'game' => [
            'external_game_id' => $gameId,
            'played_at_text' => $playedAt,
            'summary' => $winnerSummary,
            'away_team' => $teams[0]['team_name'] ?? 'Away',
            'home_team' => $teams[1]['team_name'] ?? 'Home',
            'away_user' => $teams[0]['username'] ?? ($playerNames[1] ?? 'Away'),
            'home_user' => $teams[1]['username'] ?? ($playerNames[0] ?? 'Home'),
            'away_runs' => $teams[0]['runs'] ?? 0,
            'home_runs' => $teams[1]['runs'] ?? 0,
            'away_hits' => $teams[0]['hits'] ?? 0,
            'home_hits' => $teams[1]['hits'] ?? 0,
            'away_errors' => $teams[0]['errors'] ?? 0,
            'home_errors' => $teams[1]['errors'] ?? 0,
            'winner_side' => ($teams[0]['result'] ?? '') === 'W' ? 'away' : 'home',
            'ballpark' => $metadata['ballpark'],
            'hitting_difficulty' => $metadata['hitting_difficulty'],
            'pitching_difficulty' => $metadata['pitching_difficulty'],
            'game_type' => $metadata['game_type'],
            'weather' => $metadata['weather'],
            'wind' => $metadata['wind'],
            'scheduled_first_pitch' => $metadata['scheduled_first_pitch'],
            'raw_html_sha1' => sha1($html),
        ],
        'inning_lines' => build_inning_lines($teams),
        'batting_lines' => $battingLines,
        'pitching_lines' => $pitchingLines,
        'play_events' => $playEvents,
        'perfect_perfect_events' => $perfectEvents,
    ];
}

function insert_import_log(PDO $pdo, ?int $userId, string $externalGameId, string $status, string $message, ?string $filename = null): void
{
    $stmt = $pdo->prepare('INSERT INTO import_logs (user_id, external_game_id, upload_filename, status, message) VALUES (:user_id, :external_game_id, :upload_filename, :status, :message)');
    $stmt->execute([
        'user_id' => $userId,
        'external_game_id' => $externalGameId,
        'upload_filename' => $filename,
        'status' => $status,
        'message' => $message,
    ]);
}

function import_parsed_game(PDO $pdo, array $parsed, int $userId, ?string $filename = null): int
{
    $externalGameId = $parsed['game']['external_game_id'];

    $checkStmt = $pdo->prepare('SELECT id FROM games WHERE external_game_id = :external_game_id LIMIT 1');
    $checkStmt->execute(['external_game_id' => $externalGameId]);
    if ($checkStmt->fetch()) {
        insert_import_log($pdo, $userId, $externalGameId, 'duplicate', 'Duplicate import rejected.', $filename);
        throw new RuntimeException('This game has already been imported.');
    }

    try {
        $pdo->beginTransaction();

        $gameStmt = $pdo->prepare(
            'INSERT INTO games (
                external_game_id, played_at_text, summary, away_team, home_team, away_user, home_user,
                away_runs, home_runs, away_hits, home_hits, away_errors, home_errors, winner_side,
                ballpark, hitting_difficulty, pitching_difficulty, game_type, weather, wind,
                scheduled_first_pitch, raw_html_sha1, imported_by_user_id
            ) VALUES (
                :external_game_id, :played_at_text, :summary, :away_team, :home_team, :away_user, :home_user,
                :away_runs, :home_runs, :away_hits, :home_hits, :away_errors, :home_errors, :winner_side,
                :ballpark, :hitting_difficulty, :pitching_difficulty, :game_type, :weather, :wind,
                :scheduled_first_pitch, :raw_html_sha1, :imported_by_user_id
            ) RETURNING id'
        );
        $gameStmt->execute($parsed['game'] + ['imported_by_user_id' => $userId]);
        $gameId = (int) $gameStmt->fetchColumn();

        $inningStmt = $pdo->prepare('INSERT INTO inning_lines (game_id, side, inning_number, runs_scored) VALUES (:game_id, :side, :inning_number, :runs_scored)');
        foreach ($parsed['inning_lines'] as $line) {
            $inningStmt->execute($line + ['game_id' => $gameId]);
        }

        $battingStmt = $pdo->prepare('INSERT INTO batting_lines (game_id, side, team_name, player_name, position, at_bats, runs, hits, rbi, walks, strikeouts, batting_average) VALUES (:game_id, :side, :team_name, :player_name, :position, :at_bats, :runs, :hits, :rbi, :walks, :strikeouts, :average)');
        foreach ($parsed['batting_lines'] as $line) {
            $battingStmt->execute($line + ['game_id' => $gameId]);
        }

        $pitchingStmt = $pdo->prepare('INSERT INTO pitching_lines (game_id, side, team_name, player_name, decision, innings_pitched_outs, hits_allowed, runs_allowed, earned_runs, walks, strikeouts, era) VALUES (:game_id, :side, :team_name, :player_name, :decision, :innings_pitched, :hits_allowed, :runs_allowed, :earned_runs, :walks, :strikeouts, :era)');
        foreach ($parsed['pitching_lines'] as $line) {
            $pitchingStmt->execute($line + ['game_id' => $gameId]);
        }

        $playStmt = $pdo->prepare('INSERT INTO play_events (game_id, inning_number, half, sequence_number, description, runs, hits, walks, errors, pitches, runners_left_on) VALUES (:game_id, :inning_number, :half, :sequence_number, :description, :runs, :hits, :walks, :errors, :pitches, :runners_left_on)');
        foreach ($parsed['play_events'] as $event) {
            $playStmt->execute($event + ['game_id' => $gameId]);
        }

        $perfectStmt = $pdo->prepare('INSERT INTO perfect_perfect_events (game_id, player_name, exit_velocity_mph, description) VALUES (:game_id, :player_name, :exit_velocity_mph, :description)');
        foreach ($parsed['perfect_perfect_events'] as $event) {
            $perfectStmt->execute($event + ['game_id' => $gameId]);
        }

        insert_import_log($pdo, $userId, $externalGameId, 'success', 'Game import completed successfully.', $filename);
        $pdo->commit();

        return $gameId;
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        insert_import_log($pdo, $userId, $externalGameId, 'failure', $throwable->getMessage(), $filename);
        throw $throwable;
    }
}

function build_inning_lines(array $teams): array
{
    $lines = [];
    foreach ($teams as $team) {
        foreach ($team['innings'] as $index => $runs) {
            $lines[] = [
                'side' => $team['side'],
                'inning_number' => $index + 1,
                'runs_scored' => (int) zero_if_x($runs),
            ];
        }
    }
    return $lines;
}

function extract_play_events(string $plainText): array
{
    $text = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5);
    $text = preg_replace('/\s+/', ' ', $text ?? '');
    preg_match_all('/Inning\s+(\d+):\s*(.*?)((?=Inning\s+\d+:)|(?=Game Log Legend)|$)/i', $text, $matches, PREG_SET_ORDER);

    $events = [];
    $sequence = 1;
    foreach ($matches as $inningMatch) {
        $inning = (int) $inningMatch[1];
        $inningText = trim($inningMatch[2]);
        preg_match_all('/([A-Za-z0-9 .\-]+) batting\.(.*?)Runs:\s*(\d+) Hits:\s*(\d+) Walks:\s*(\d+) Errors:\s*(\d+) Pitches:\s*(\d+)(?: Runners Left On:\s*(\d+))?/i', $inningText, $halfMatches, PREG_SET_ORDER);
        foreach ($halfMatches as $index => $halfMatch) {
            $events[] = [
                'inning_number' => $inning,
                'half' => $index === 0 ? 'top' : 'bottom',
                'sequence_number' => $sequence++,
                'description' => trim($halfMatch[1] . ' batting. ' . trim($halfMatch[2])),
                'runs' => (int) $halfMatch[3],
                'hits' => (int) $halfMatch[4],
                'walks' => (int) $halfMatch[5],
                'errors' => (int) $halfMatch[6],
                'pitches' => (int) $halfMatch[7],
                'runners_left_on' => isset($halfMatch[8]) ? (int) $halfMatch[8] : null,
            ];
        }
    }

    return $events;
}

function extract_perfect_events(string $plainText): array
{
    $events = [];
    $text = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5);
    preg_match_all('/([A-Za-z0-9 .\'\-]+):\s*(\d+) mph\s*\((.*?)\)/', $text, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $events[] = [
            'player_name' => trim($match[1]),
            'exit_velocity_mph' => (int) $match[2],
            'description' => trim($match[3]),
        ];
    }
    return $events;
}

function extract_game_metadata(string $text): array
{
    $metadata = [
        'ballpark' => null,
        'hitting_difficulty' => null,
        'pitching_difficulty' => null,
        'game_type' => null,
        'weather' => null,
        'wind' => null,
        'scheduled_first_pitch' => null,
    ];

    $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
    $decoded = str_replace(['™', '&trade;'], '', $decoded);
    if (preg_match('/([A-Za-z0-9 .\'&\-]+) \(\d+ ft elevation\)/', $decoded, $match)) {
        $metadata['ballpark'] = trim($match[1]);
    }
    if (preg_match('/Hitting Difficulty is ([^.]+)\./', $decoded, $match)) {
        $metadata['hitting_difficulty'] = trim($match[1]);
    }
    if (preg_match('/Pitching Difficulty is ([^.]+)\./', $decoded, $match)) {
        $metadata['pitching_difficulty'] = trim($match[1]);
    }
    if (preg_match('/(\d{4} Online Game)/', $decoded, $match)) {
        $metadata['game_type'] = trim($match[1]);
    }
    if (preg_match('/Weather:\s*([^\n]+?)(?:No Wind|Wind:|Scheduled First Pitch:|Game Scores:|UMPIRES|$)/', $decoded, $match)) {
        $metadata['weather'] = trim(rtrim($match[1], '. '));
    }
    if (preg_match('/(No Wind|Wind:[^\n]+)/', $decoded, $match)) {
        $metadata['wind'] = trim($match[1]);
    }
    if (preg_match('/Scheduled First Pitch:\s*([^\n]+?)(?:Game Scores:|UMPIRES|$)/', $decoded, $match)) {
        $metadata['scheduled_first_pitch'] = trim(rtrim($match[1], '. '));
    }

    return $metadata;
}

function inner_html(DOMNode $node): string
{
    $html = '';
    foreach ($node->childNodes as $child) {
        $html .= $node->ownerDocument->saveHTML($child);
    }
    return $html;
}

function normalize_space(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', $value) ?? '');
}

function split_player_and_position(string $value): array
{
    $parts = array_map('trim', explode(',', $value, 2));
    return [$parts[0] ?? $value, $parts[1] ?? null];
}

function extract_pitcher_decision(string $value): array
{
    if (preg_match('/^(.*?)\s*\((.*?)\)$/', trim($value), $match)) {
        return [trim($match[1]), trim($match[2])];
    }
    return [trim($value), null];
}

function innings_to_outs(string $innings): int
{
    [$whole, $fraction] = array_pad(explode('.', trim($innings), 2), 2, '0');
    return ((int) $whole * 3) + (int) $fraction;
}

function normalize_decimal(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    if (str_starts_with($value, '.')) {
        return '0' . $value;
    }
    return $value;
}

function zero_if_x(string $value): string
{
    $trimmed = trim($value);
    return strtoupper($trimmed) === 'X' ? '0' : $trimmed;
}
