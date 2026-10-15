<?php
const DB_SCHEMA_VERSION = 6;

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
        $from = db_current_version($pdo);
        if ($from >= DB_SCHEMA_VERSION) {
            $pdo->exec('COMMIT');
            return;
        }

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

        $pdo->exec('CREATE TABLE IF NOT EXISTS roles (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            name       TEXT NOT NULL UNIQUE COLLATE NOCASE,
            color      TEXT NOT NULL DEFAULT \'#ff6aa8\',
            kind       TEXT NOT NULL DEFAULT \'public\',
            priority   INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

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
            rules_seed($pdo);
        }

        $pdo->exec('CREATE TABLE IF NOT EXISTS applications (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            code       TEXT NOT NULL UNIQUE,
            user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            role       TEXT NOT NULL,
            answers    TEXT NOT NULL DEFAULT \'\',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_applications_user ON applications(user_id, role, created_at)');

        $pdo->exec('CREATE TABLE IF NOT EXISTS apply_cats (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            slug     TEXT NOT NULL UNIQUE,
            icon     TEXT NOT NULL DEFAULT \'users\',
            short_en TEXT NOT NULL DEFAULT \'\',
            short_it TEXT NOT NULL DEFAULT \'\',
            title_en TEXT NOT NULL,
            title_it TEXT NOT NULL DEFAULT \'\',
            tag_en   TEXT NOT NULL DEFAULT \'\',
            tag_it   TEXT NOT NULL DEFAULT \'\',
            desc_en  TEXT NOT NULL DEFAULT \'\',
            desc_it  TEXT NOT NULL DEFAULT \'\',
            sort     INTEGER NOT NULL DEFAULT 0
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS apply_roles (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            cat_id   INTEGER NOT NULL REFERENCES apply_cats(id) ON DELETE CASCADE,
            slug     TEXT NOT NULL UNIQUE,
            icon     TEXT NOT NULL DEFAULT \'users\',
            title_en TEXT NOT NULL,
            title_it TEXT NOT NULL DEFAULT \'\',
            tag_en   TEXT NOT NULL DEFAULT \'\',
            tag_it   TEXT NOT NULL DEFAULT \'\',
            desc_en  TEXT NOT NULL DEFAULT \'\',
            desc_it  TEXT NOT NULL DEFAULT \'\',
            req_en   TEXT NOT NULL DEFAULT \'\',
            req_it   TEXT NOT NULL DEFAULT \'\',
            sort     INTEGER NOT NULL DEFAULT 0,
            active   INTEGER NOT NULL DEFAULT 1
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS apply_questions (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            role_id  INTEGER NOT NULL REFERENCES apply_roles(id) ON DELETE CASCADE,
            label_en TEXT NOT NULL,
            label_it TEXT NOT NULL DEFAULT \'\',
            help_en  TEXT NOT NULL DEFAULT \'\',
            help_it  TEXT NOT NULL DEFAULT \'\',
            type     TEXT NOT NULL DEFAULT \'textarea\',
            required INTEGER NOT NULL DEFAULT 1,
            min      INTEGER NOT NULL DEFAULT 0,
            max      INTEGER NOT NULL DEFAULT 700,
            sort     INTEGER NOT NULL DEFAULT 0
        )');
        $pdo->exec('CREATE TABLE IF NOT EXISTS apply_settings (
            k  TEXT PRIMARY KEY,
            en TEXT NOT NULL DEFAULT \'\',
            it TEXT NOT NULL DEFAULT \'\'
        )');
        if ($from < 6 && !(int) $pdo->query('SELECT COUNT(*) FROM apply_cats')->fetchColumn()) {
            require_once __DIR__ . '/apply.php';
            apply_seed($pdo);
        }

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
        }
    }
}
