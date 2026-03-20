CREATE OR REPLACE VIEW v_game_summary AS
SELECT
    g.id,
    g.external_game_id,
    g.played_at_text,
    g.away_team,
    g.home_team,
    g.away_user,
    g.home_user,
    g.away_runs,
    g.home_runs,
    g.away_hits,
    g.home_hits,
    g.away_errors,
    g.home_errors,
    CASE WHEN g.winner_side = 'away' THEN g.away_team ELSE g.home_team END AS winner_team,
    g.ballpark,
    g.hitting_difficulty,
    g.pitching_difficulty,
    g.game_type,
    g.weather,
    g.imported_at
FROM games g;

CREATE OR REPLACE VIEW v_batting_leaders AS
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
    CASE WHEN SUM(at_bats) = 0 THEN 0 ELSE ROUND(SUM(hits)::NUMERIC / SUM(at_bats), 3) END AS batting_average
FROM batting_lines
GROUP BY player_name, team_name;

CREATE OR REPLACE VIEW v_pitching_leaders AS
SELECT
    player_name,
    team_name,
    COUNT(DISTINCT game_id) AS games_pitched,
    SUM(innings_pitched_outs) AS innings_pitched_outs,
    SUM(hits_allowed) AS hits_allowed,
    SUM(runs_allowed) AS runs_allowed,
    SUM(earned_runs) AS earned_runs,
    SUM(walks) AS walks,
    SUM(strikeouts) AS strikeouts,
    CASE WHEN SUM(innings_pitched_outs) = 0 THEN NULL ELSE ROUND((SUM(earned_runs) * 27.0 / SUM(innings_pitched_outs))::NUMERIC, 2) END AS era
FROM pitching_lines
GROUP BY player_name, team_name;
