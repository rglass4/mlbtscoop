CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    is_admin BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS games (
    id BIGSERIAL PRIMARY KEY,
    external_game_id VARCHAR(32) NOT NULL UNIQUE,
    played_at_text VARCHAR(100),
    summary TEXT,
    away_team VARCHAR(100) NOT NULL,
    home_team VARCHAR(100) NOT NULL,
    away_user VARCHAR(100),
    home_user VARCHAR(100),
    away_runs INTEGER NOT NULL DEFAULT 0,
    home_runs INTEGER NOT NULL DEFAULT 0,
    away_hits INTEGER NOT NULL DEFAULT 0,
    home_hits INTEGER NOT NULL DEFAULT 0,
    away_errors INTEGER NOT NULL DEFAULT 0,
    home_errors INTEGER NOT NULL DEFAULT 0,
    winner_side VARCHAR(8) CHECK (winner_side IN ('away', 'home')),
    ballpark VARCHAR(150),
    hitting_difficulty VARCHAR(50),
    pitching_difficulty VARCHAR(50),
    game_type VARCHAR(100),
    weather VARCHAR(150),
    wind VARCHAR(150),
    scheduled_first_pitch VARCHAR(50),
    raw_html_sha1 CHAR(40),
    imported_by_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    imported_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS inning_lines (
    id BIGSERIAL PRIMARY KEY,
    game_id BIGINT NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    side VARCHAR(8) NOT NULL CHECK (side IN ('away', 'home')),
    inning_number SMALLINT NOT NULL CHECK (inning_number BETWEEN 1 AND 20),
    runs_scored SMALLINT NOT NULL DEFAULT 0,
    UNIQUE (game_id, side, inning_number)
);

CREATE TABLE IF NOT EXISTS batting_lines (
    id BIGSERIAL PRIMARY KEY,
    game_id BIGINT NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    side VARCHAR(8) NOT NULL CHECK (side IN ('away', 'home')),
    team_name VARCHAR(100) NOT NULL,
    player_name VARCHAR(120) NOT NULL,
    position VARCHAR(20),
    at_bats INTEGER NOT NULL DEFAULT 0,
    runs INTEGER NOT NULL DEFAULT 0,
    hits INTEGER NOT NULL DEFAULT 0,
    rbi INTEGER NOT NULL DEFAULT 0,
    walks INTEGER NOT NULL DEFAULT 0,
    strikeouts INTEGER NOT NULL DEFAULT 0,
    doubles INTEGER NOT NULL DEFAULT 0,
    triples INTEGER NOT NULL DEFAULT 0,
    home_runs INTEGER NOT NULL DEFAULT 0,
    batting_average NUMERIC(6,3),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

ALTER TABLE batting_lines ADD COLUMN IF NOT EXISTS doubles INTEGER NOT NULL DEFAULT 0;
ALTER TABLE batting_lines ADD COLUMN IF NOT EXISTS triples INTEGER NOT NULL DEFAULT 0;
ALTER TABLE batting_lines ADD COLUMN IF NOT EXISTS home_runs INTEGER NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS pitching_lines (
    id BIGSERIAL PRIMARY KEY,
    game_id BIGINT NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    side VARCHAR(8) NOT NULL CHECK (side IN ('away', 'home')),
    team_name VARCHAR(100) NOT NULL,
    player_name VARCHAR(120) NOT NULL,
    decision VARCHAR(10),
    innings_pitched_outs INTEGER NOT NULL DEFAULT 0,
    hits_allowed INTEGER NOT NULL DEFAULT 0,
    runs_allowed INTEGER NOT NULL DEFAULT 0,
    earned_runs INTEGER NOT NULL DEFAULT 0,
    walks INTEGER NOT NULL DEFAULT 0,
    strikeouts INTEGER NOT NULL DEFAULT 0,
    era NUMERIC(7,3),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS play_events (
    id BIGSERIAL PRIMARY KEY,
    game_id BIGINT NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    inning_number SMALLINT NOT NULL,
    half VARCHAR(10) NOT NULL CHECK (half IN ('top', 'bottom')),
    sequence_number INTEGER NOT NULL,
    description TEXT NOT NULL,
    runs SMALLINT NOT NULL DEFAULT 0,
    hits SMALLINT NOT NULL DEFAULT 0,
    walks SMALLINT NOT NULL DEFAULT 0,
    errors SMALLINT NOT NULL DEFAULT 0,
    pitches SMALLINT,
    runners_left_on SMALLINT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS perfect_perfect_events (
    id BIGSERIAL PRIMARY KEY,
    game_id BIGINT NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    player_name VARCHAR(120) NOT NULL,
    exit_velocity_mph SMALLINT,
    description TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS import_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    external_game_id VARCHAR(32),
    upload_filename VARCHAR(255),
    status VARCHAR(20) NOT NULL CHECK (status IN ('success', 'failure', 'duplicate')),
    message TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_games_imported_at ON games(imported_at DESC);
CREATE INDEX IF NOT EXISTS idx_batting_lines_game_id ON batting_lines(game_id);
CREATE INDEX IF NOT EXISTS idx_pitching_lines_game_id ON pitching_lines(game_id);
CREATE INDEX IF NOT EXISTS idx_play_events_game_id ON play_events(game_id);
CREATE INDEX IF NOT EXISTS idx_perfect_events_game_id ON perfect_perfect_events(game_id);

-- Generate a password hash in PHP with:
-- php -r "echo password_hash('change-me', PASSWORD_DEFAULT), PHP_EOL;"
-- Then insert your admin user, for example:
-- INSERT INTO users (username, password_hash, is_admin) VALUES ('admin', '<generated-hash>', TRUE);
