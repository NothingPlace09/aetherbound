<?php
// Local database: a single SQLite file stored in /database (created on first use).
// Needs the PHP extension pdo_sqlite, which XAMPP enables by default.
// The schema is versioned with PRAGMA user_version: older databases are upgraded automatically.
const DB_SCHEMA_VERSION = 5;

function db()
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dir = __DIR__ . '/../database';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . $dir . '/aetherbound.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');

    db_migrate($pdo);

    return $pdo;
}

function db_current_version(PDO $pdo)
{
    return (int) $pdo->query('PRAGMA user_version')->fetchColumn();
}

function db_migrate(PDO $pdo)
{
    if (db_current_version($pdo) >= DB_SCHEMA_VERSION) {
        return;
    }

    $pdo->exec('BEGIN IMMEDIATE');
    try {
        // Another request may have finished the upgrade while we waited for the lock.
        $from = db_current_version($pdo);
        if ($from >= DB_SCHEMA_VERSION) {
            $pdo->exec('COMMIT');
            return;
        }

        // Passwords are never stored in plain text: only the password_hash() result.
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT NOT NULL UNIQUE COLLATE NOCASE,
            email         TEXT NOT NULL UNIQUE COLLATE NOCASE,
            password_hash TEXT NOT NULL,
            discord_id    TEXT UNIQUE,
            role          TEXT NOT NULL DEFAULT \'member\',
            created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_login_at TEXT
        )');

        // Custom roles created by the admin (badge shown next to the username).
        $pdo->exec('CREATE TABLE IF NOT EXISTS roles (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            name       TEXT NOT NULL UNIQUE COLLATE NOCASE,
            color      TEXT NOT NULL DEFAULT \'#ff6aa8\',
            kind       TEXT NOT NULL DEFAULT \'public\',
            priority   INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        // Roles added in schema 2: kind = 'public' (badge only) or 'staff' (also listed on staff.php),
        // priority = order on the staff page (higher number first).
        $haveRole = [];
        foreach ($pdo->query('PRAGMA table_info(roles)') as $col) {
            $haveRole[$col['name']] = true;
        }
        if (!isset($haveRole['kind'])) {
            $pdo->exec("ALTER TABLE roles ADD COLUMN kind TEXT NOT NULL DEFAULT 'public'");
        }
        if (!isset($haveRole['priority'])) {
            $pdo->exec('ALTER TABLE roles ADD COLUMN priority INTEGER NOT NULL DEFAULT 0');
        }

        // Profile columns added after the first release.
        // users.role stays the access level ('member' or 'admin'); users.role_id is the custom role badge.
        $have = [];
        foreach ($pdo->query('PRAGMA table_info(users)') as $col) {
            $have[$col['name']] = true;
        }
        $add = [
            'role_id'      => 'INTEGER REFERENCES roles(id) ON DELETE SET NULL',
            'avatar'       => 'TEXT',
            'bio'          => "TEXT NOT NULL DEFAULT ''",
            'signature'    => "TEXT NOT NULL DEFAULT ''",
            'last_seen_at' => 'TEXT',
        ];
        foreach ($add as $name => $def) {
            if (!isset($have[$name])) {
                $pdo->exec('ALTER TABLE users ADD COLUMN ' . $name . ' ' . $def);
            }
        }

        // News posts (schema 3). category: news | update | event. published 0 = draft (admins only).
        $pdo->exec('CREATE TABLE IF NOT EXISTS news (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            title      TEXT NOT NULL,
            excerpt    TEXT NOT NULL DEFAULT \'\',
            body       TEXT NOT NULL DEFAULT \'\',
            category   TEXT NOT NULL DEFAULT \'news\',
            image      TEXT,
            published  INTEGER NOT NULL DEFAULT 1,
            author_id  INTEGER REFERENCES users(id) ON DELETE SET NULL,
            views      INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT
        )');
        $pdo->exec('CREATE INDEX IF NOT EXISTS news_created ON news (created_at)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS news_likes (
            news_id INTEGER NOT NULL REFERENCES news(id) ON DELETE CASCADE,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            PRIMARY KEY (news_id, user_id)
        )');

        // Rules page (schema 4): categories > sections > rules, written in English and Italian.
        // kind 'rules' = numbered list of rules, kind 'table' = a table (cells separated by |, first line = header).
        $pdo->exec('CREATE TABLE IF NOT EXISTS rule_cats (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            slug       TEXT NOT NULL UNIQUE,
            grp_en     TEXT NOT NULL DEFAULT \'\',
            grp_it     TEXT NOT NULL DEFAULT \'\',
            title_en   TEXT NOT NULL,
            title_it   TEXT NOT NULL DEFAULT \'\',
            sub_en     TEXT NOT NULL DEFAULT \'\',
            sub_it     TEXT NOT NULL DEFAULT \'\',
            icon       TEXT NOT NULL DEFAULT \'shield\',
            sort       INTEGER NOT NULL DEFAULT 0,
            updated_at TEXT
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS rule_secs (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            cat_id   INTEGER NOT NULL REFERENCES rule_cats(id) ON DELETE CASCADE,
            kind     TEXT NOT NULL DEFAULT \'rules\',
            title_en TEXT NOT NULL DEFAULT \'\',
            title_it TEXT NOT NULL DEFAULT \'\',
            table_en TEXT NOT NULL DEFAULT \'\',
            table_it TEXT NOT NULL DEFAULT \'\',
            sort     INTEGER NOT NULL DEFAULT 0
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS rule_items (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            sec_id   INTEGER NOT NULL REFERENCES rule_secs(id) ON DELETE CASCADE,
            title_en TEXT NOT NULL DEFAULT \'\',
            title_it TEXT NOT NULL DEFAULT \'\',
            text_en  TEXT NOT NULL DEFAULT \'\',
            text_it  TEXT NOT NULL DEFAULT \'\',
            sort     INTEGER NOT NULL DEFAULT 0
        )');
        if ($from < 4 && !(int) $pdo->query('SELECT COUNT(*) FROM rule_cats')->fetchColumn()) {
            require_once __DIR__ . '/rules.php';
            rules_seed($pdo); // first time only: fills the tables with the starting rules from rules_data.php
        }

        // Staff applications (schema 5): a record of every application sent to Discord (also used for the cooldown).
        $pdo->exec('CREATE TABLE IF NOT EXISTS applications (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            code       TEXT NOT NULL UNIQUE,
            user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            role       TEXT NOT NULL,
            answers    TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_applications_user ON applications(user_id, role, created_at)');

        // The admin account is only seeded on the first setup, so upgrades never reset its password.
        if ($from < 2) {
            $config = require __DIR__ . '/config.php';
            db_seed_admin($pdo, $config['admin_seed']);
        }

        $pdo->exec('PRAGMA user_version = ' . DB_SCHEMA_VERSION);
        $pdo->exec('COMMIT');
    } catch (Exception $ex) {
        $pdo->exec('ROLLBACK');
        throw $ex;
    }
}

/* Creates the admin account. If that email is already registered, the account becomes the admin. */
function db_seed_admin(PDO $pdo, array $seed)
{
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute([$seed['email']]);
    $row = $st->fetch();

    if ($row) {
        $up = $pdo->prepare("UPDATE users SET role = 'admin', password_hash = ? WHERE id = ?");
        $up->execute([$seed['password_hash'], $row['id']]);
        return;
    }

    $ins = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
    for ($i = 0; $i < 20; $i++) {
        $name = $i === 0 ? $seed['username'] : substr($seed['username'], 0, 14) . $i;
        try {
            $ins->execute([$name, $seed['email'], $seed['password_hash']]);
            return;
        } catch (PDOException $ex) {
            if ($ex->getCode() !== '23000') {
                throw $ex;
            }
            // username already used by someone else: try the next variation
        }
    }
}
