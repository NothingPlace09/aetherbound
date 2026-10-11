<?php
// Sessions, CSRF protection, login throttling and the logged-in user.
require_once __DIR__ . '/db.php';

function ensure_session($create = false)
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    // Pages that only display the navbar do not open a session unless the visitor already has one.
    if (!$create && !isset($_COOKIE[session_name()])) {
        return false;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
    return true;
}

function current_user()
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (ensure_session(false) && !empty($_SESSION['uid'])) {
        try {
            $st = db()->prepare('SELECT u.id, u.username, u.email, u.role, u.role_id, u.avatar, u.bio, u.signature,
                    u.created_at, u.last_seen_at, r.name AS role_name, r.color AS role_color
                FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
            $st->execute([(int) $_SESSION['uid']]);
            $row = $st->fetch();
            $user = $row ? $row : null;
            // "Last online": refreshed at most once a minute.
            if ($user && (!$user['last_seen_at'] || time() - strtotime($user['last_seen_at'] . ' UTC') > 60)) {
                db()->prepare("UPDATE users SET last_seen_at = datetime('now') WHERE id = ?")->execute([$user['id']]);
                $user['last_seen_at'] = gmdate('Y-m-d H:i:s');
            }
        } catch (Exception $ex) {
            $user = null;
        }
    }
    return $user;
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/* Page that needs a logged-in member: guests are sent to the login page. */
function require_login()
{
    $user = current_user();
    if (!$user) {
        redirect('login.php');
    }
    return $user;
}

/* Page for admins only: everybody else is sent to their own profile (or the login page). */
function require_admin()
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        redirect('profile.php');
    }
    return $user;
}

/* One-shot message shown after a redirect: flash('ok', 'key') to set, flash() to read and clear. */
function flash($type = null, $key = null)
{
    ensure_session(true);
    if ($type !== null) {
        $_SESSION['flash'] = [$type, $key];
        return null;
    }
    $f = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token()
{
    ensure_session(true);
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_ok($token)
{
    ensure_session(true);
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/* Max 5 failed attempts per 10 minutes for each browser session. */
function auth_blocked()
{
    ensure_session(true);
    $now = time();
    $fails = isset($_SESSION['fails']) ? $_SESSION['fails'] : [];
    $_SESSION['fails'] = array_values(array_filter($fails, function ($t) use ($now) {
        return $t > $now - 600;
    }));
    return count($_SESSION['fails']) >= 5;
}

function auth_fail()
{
    ensure_session(true);
    $_SESSION['fails'][] = time();
}

function login_user($id)
{
    ensure_session(true);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $id;
    unset($_SESSION['fails']);
    $st = db()->prepare("UPDATE users SET last_login_at = datetime('now') WHERE id = ?");
    $st->execute([(int) $id]);
}

function logout_user()
{
    if (!ensure_session(false)) {
        return;
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function password_ok($pw)
{
    return strlen($pw) >= 8 && strlen($pw) <= 72
        && preg_match('/[A-Z]/', $pw)
        && preg_match('/[0-9]|[^A-Za-z0-9]/', $pw);
}
