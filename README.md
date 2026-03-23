# MLB The Show Co-op Tracker

A lightweight PHP 8+ website for importing saved MLB The Show HTML game pages, parsing them server-side, storing them in Supabase Postgres, and browsing public stats pages.

## Stack
- PHP 8+
- Supabase Postgres / PostgreSQL
- PDO
- Server-rendered PHP pages
- Bootstrap 5 + custom dark theme CSS
- PHP session authentication

## Project Structure

```text
/public
/includes
/sql
/storage/uploads
```

## Features

### Public pages
- Dashboard (`public/index.php`)
- Games list (`public/games.php`)
- Game detail (`public/game.php`)
- Batting leaderboard (`public/batting.php`)
- Pitching leaderboard (`public/pitching.php`)

### Admin pages
- Login (`public/login.php`)
- Logout (`public/logout.php`)
- Import with preview (`public/import.php`)

### Import flow
1. Log in as an admin user.
2. Upload a saved MLB The Show game HTML file.
3. The parser uses `DOMDocument` and `DOMXPath` to extract:
   - game metadata
   - inning lines
   - batting lines
   - pitching lines
   - play-by-play inning summaries
   - perfect-perfect events
   - configured import-time team renames
4. Review the preview.
5. Confirm the import to write everything in a single transaction.
6. Success, failure, and duplicate attempts are written to `import_logs`.

## Database setup

Run the schema and views scripts against your Supabase Postgres database:

```sql
-- in Supabase SQL editor or psql
\i sql/schema.sql
\i sql/views.sql
```

### Seed an admin user
Generate a password hash locally:

```bash
php -r "echo password_hash('change-me', PASSWORD_DEFAULT), PHP_EOL;"
```

Then insert the user:

```sql
INSERT INTO users (username, password_hash, is_admin)
VALUES ('admin', '<paste-generated-hash-here>', TRUE);
```

## Configuration

Copy `.env.example` to `.env` and update the database credentials:

```bash
cp .env.example .env
```

Environment variables used by the app:
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `DB_SSLMODE` (use `require` for Supabase, `prefer` for many local Postgres installs)
- `APP_NAME`
- `SESSION_NAME`
- `TEAM_RENAMES` (optional comma-separated rename map such as `Glory:Mustangs,Team X:Mustangs`)

## Local development

Serve the `public/` directory with PHP’s built-in server:

```bash
php -S 127.0.0.1:8000 -t public
```

Then visit:
- `http://127.0.0.1:8000/index.php`
- `http://127.0.0.1:8000/login.php`

## Deployment notes

This project is intentionally simple so it can run on a normal PHP host.

1. Upload the repository to your PHP 8+ hosting environment.
2. Point your web root to the `public/` directory.
3. Create `.env` with your Supabase connection details.
4. Run `sql/schema.sql` and `sql/views.sql` against Supabase Postgres.
5. Insert an admin user with a hashed password.
6. Ensure `storage/uploads/` is writable by PHP if you want to retain uploaded originals.

## Parser notes

The sample parser is tuned for saved game pages shaped like the provided MLB The Show HTML. Because SDS can change markup over time, the parsing logic is intentionally defensive and centered on the summary table, box score tables, and game log text blocks.

If your saved files differ, adjust the selectors and regex helpers inside `includes/parser.php`.
