<?php

declare(strict_types=1);

function app_env(string $key, ?string $default = null): ?string
{
    static $loaded = false;

    if (!$loaded) {
        $envPath = dirname(__DIR__) . '/.env';
        if (is_readable($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$envKey, $envValue] = explode('=', $line, 2);
                $envKey = trim($envKey);
                $envValue = trim($envValue);
                $envValue = trim($envValue, "\"'");

                if (getenv($envKey) === false) {
                    putenv(sprintf('%s=%s', $envKey, $envValue));
                    $_ENV[$envKey] = $envValue;
                }
            }
        }

        $loaded = true;
    }

    $value = getenv($key);
    return $value === false ? $default : $value;
}

function db(): PDO
{
    static $pdo = null;
    static $schemaEnsured = false;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = app_env('DB_HOST', '127.0.0.1');
    $port = app_env('DB_PORT', '5432');
    $dbname = app_env('DB_NAME', 'mlbts');
    $user = app_env('DB_USER', 'postgres');
    $password = app_env('DB_PASSWORD', 'postgres');
    $sslmode = app_env('DB_SSLMODE', 'disable');

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', $host, $port, $dbname, $sslmode);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $user, $password, $options);
    if (!$schemaEnsured) {
        ensure_runtime_schema($pdo);
        $schemaEnsured = true;
    }
    return $pdo;
}

function ensure_runtime_schema(PDO $pdo): void
{
    $pdo->exec('ALTER TABLE batting_lines ADD COLUMN IF NOT EXISTS doubles INTEGER NOT NULL DEFAULT 0');
    $pdo->exec('ALTER TABLE batting_lines ADD COLUMN IF NOT EXISTS triples INTEGER NOT NULL DEFAULT 0');
    $pdo->exec('ALTER TABLE batting_lines ADD COLUMN IF NOT EXISTS home_runs INTEGER NOT NULL DEFAULT 0');
}
