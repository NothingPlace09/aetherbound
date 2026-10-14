<?php
// Loaded at the top of every page: config, language, helpers.
$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/markdown.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/rules.php';
require_once __DIR__ . '/apply.php';

/* ---- Language: ?lang=xx sets a cookie, then falls back to cookie, browser, default ---- */
$languages = $config['languages'];
$lang = $config['default_lang'];

if (isset($_GET['lang']) && is_string($_GET['lang']) && isset($languages[$_GET['lang']])) {
    setcookie('aeth_lang', $_GET['lang'], [
        'expires'  => time() + 31536000,
        'path'     => '/',
        'samesite' => 'Lax',
    ]);
    $query = $_GET;
    unset($query['lang']);
    $path = '/' . ltrim((string) strtok($_SERVER['REQUEST_URI'], '?'), '/');
    header('Location: ' . $path . ($query ? '?' . http_build_query($query) : ''));
    exit;
}

if (isset($_COOKIE['aeth_lang']) && isset($languages[$_COOKIE['aeth_lang']])) {
    $lang = $_COOKIE['aeth_lang'];
} elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $part) {
        $code = strtolower(substr(trim($part), 0, 2));
        if (isset($languages[$code])) {
            $lang = $code;
            break;
        }
    }
}

/* ---- Translations (English is the fallback for missing keys) ---- */
$GLOBALS['T'] = require __DIR__ . '/../lang/en.php';
if ($lang !== 'en') {
    $GLOBALS['T'] = array_merge($GLOBALS['T'], require __DIR__ . '/../lang/' . $lang . '.php');
}

/* ---- Helpers ---- */
function t($key)
{
    return isset($GLOBALS['T'][$key]) ? $GLOBALS['T'][$key] : $key;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flag($id)
{
    $shapes = [
        'en' => '<rect width="24" height="16" fill="#012169"/><path d="M0 0 24 16M24 0 0 16" stroke="#fff" stroke-width="3.2"/><path d="M0 0 24 16M24 0 0 16" stroke="#C8102E" stroke-width="1.1"/><path d="M12 0v16M0 8h24" stroke="#fff" stroke-width="5.4"/><path d="M12 0v16M0 8h24" stroke="#C8102E" stroke-width="3.2"/>',
        'it' => '<rect width="8" height="16" fill="#009246"/><rect x="8" width="8" height="16" fill="#fff"/><rect x="16" width="8" height="16" fill="#CE2B37"/>',
        'fr' => '<rect width="8" height="16" fill="#0055A4"/><rect x="8" width="8" height="16" fill="#fff"/><rect x="16" width="8" height="16" fill="#EF4135"/>',
        'de' => '<rect width="24" height="5.4" fill="#000"/><rect y="5.3" width="24" height="5.4" fill="#DD0000"/><rect y="10.6" width="24" height="5.4" fill="#FFCE00"/>',
        'es' => '<rect width="24" height="16" fill="#AA151B"/><rect y="4" width="24" height="8" fill="#F1BF00"/>',
    ];
    $inner = isset($shapes[$id]) ? $shapes[$id] : '';
    return '<svg class="flag" viewBox="0 0 24 16" width="24" height="16" aria-hidden="true">' . $inner . '</svg>';
}

/* Discord counts from the public invite API, cached on disk. Returns nulls if unavailable. */
function discord_stats(array $config)
{
    $empty = ['online' => null, 'members' => null];
    if (!preg_match('~(?:discord\.gg|discord\.com/invite)/([A-Za-z0-9-]+)~', $config['discord_url'], $m)) {
        return $empty;
    }
    $file = __DIR__ . '/../cache/discord.json';
    $cached = null;
    if (is_file($file)) {
        $decoded = json_decode((string) @file_get_contents($file), true);
        if (is_array($decoded) && isset($decoded['members'])) {
            $cached = $decoded;
            if (time() - filemtime($file) < (int) $config['discord_cache_seconds']) {
                return $cached;
            }
        }
    }

    $url = 'https://discord.com/api/v10/invites/' . $m[1] . '?with_counts=true';
    $raw = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_USERAGENT      => 'AetherboundSite/1.0',
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
    } elseif (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => ['timeout' => 3, 'header' => "User-Agent: AetherboundSite/1.0\r\n"]]);
        $raw = @file_get_contents($url, false, $ctx);
    }

    $data = $raw ? json_decode($raw, true) : null;
    if (is_array($data) && isset($data['approximate_member_count'])) {
        $out = [
            'online'  => (int) (isset($data['approximate_presence_count']) ? $data['approximate_presence_count'] : 0),
            'members' => (int) $data['approximate_member_count'],
        ];
        @file_put_contents($file, json_encode($out), LOCK_EX);
        return $out;
    }
    return $cached ? $cached : $empty;
}

/* ---- Profile helpers ---- */

/* Picture of a member: the face of their Minecraft skin, served by skin.php. Works on any row with a "username" key. */
function avatar_url(array $u, $size = 128)
{
    if (!empty($u['username']) && preg_match('/^[A-Za-z0-9_]{1,16}$/', $u['username'])) {
        return 'skin.php?u=' . rawurlencode($u['username']) . '&s=' . (int) $size;
    }
    return 'assets/img/head.webp';
}

/* <img> for a member's picture, with the default head if the skin service is unreachable. */
function avatar_img(array $u, $px, $class = '', $id = '')
{
    return '<img' . ($class !== '' ? ' class="' . e($class) . '"' : '') . ($id !== '' ? ' id="' . e($id) . '"' : '')
        . ' src="' . e(avatar_url($u, $px * 2)) . '" alt="" width="' . (int) $px . '" height="' . (int) $px . '"'
        . ' loading="lazy" onerror="this.onerror=null;this.src=\'assets/img/head.webp\'">';
}

/* Colour badges next to a username: the custom role (if any) and ADMIN for admins. */
function role_badges(array $u)
{
    $out = '';
    if (!empty($u['role']) && $u['role'] === 'admin') {
        $out .= '<span class="badge admin">' . e(t('role_admin')) . '</span>';
    }
    if (!empty($u['role_name'])) {
        $color = (!empty($u['role_color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $u['role_color'])) ? $u['role_color'] : '#ff6aa8';
        $out .= '<span class="badge" style="--bc:' . $color . '">' . e($u['role_name']) . '</span>';
    }
    return $out;
}

/* Formats a UTC timestamp from the database in the visitor's language. */
function fmt_date($sqlUtc, $withTime = false)
{
    if (!$sqlUtc) {
        return '—';
    }
    $ts = strtotime($sqlUtc . ' UTC');
    if ($ts === false) {
        return '—';
    }
    if (class_exists('IntlDateFormatter')) {
        $f = new IntlDateFormatter($GLOBALS['lang'], IntlDateFormatter::MEDIUM, $withTime ? IntlDateFormatter::SHORT : IntlDateFormatter::NONE);
        $s = $f->format($ts);
        if ($s !== false) {
            return $s;
        }
    }
    return date($withTime ? 'Y-m-d H:i' : 'Y-m-d', $ts);
}

/* "Online now" if seen in the last 5 minutes, otherwise the date of the last visit. */
function fmt_seen($sqlUtc)
{
    if ($sqlUtc && time() - strtotime($sqlUtc . ' UTC') < 300) {
        return t('pf_online_now');
    }
    return fmt_date($sqlUtc, true);
}

/* Full phrase: "Online now" or "Last online <date>". */
function seen_text($sqlUtc)
{
    if ($sqlUtc && time() - strtotime($sqlUtc . ' UTC') < 300) {
        return t('pf_online_now');
    }
    return t('pf_last_online') . ' ' . fmt_date($sqlUtc, true);
}

/* Cleans free text typed by a member: unified line breaks, no control characters, max length. */
function clean_text($s, $max)
{
    $s = str_replace(["\r\n", "\r"], "\n", (string) $s);
    $s = preg_replace('/[^\P{C}\n]+/u', '', $s);
    $s = trim((string) $s);
    return mb_substr($s, 0, $max, 'UTF-8');
}

/* Shows (and clears) the flash message set before a redirect. */
function flash_html()
{
    $f = flash();
    if (!$f) {
        return '';
    }
    return '<div class="flash ' . ($f[0] === 'ok' ? 'ok' : 'err') . '" role="status">' . e(t($f[1])) . '</div>';
}
