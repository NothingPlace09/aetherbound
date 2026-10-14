<?php
// Staff applications: validation of the form and delivery to Discord (webhook). Page: apply.php

const APPLY_DAYS = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];
const APPLY_SLOTS = ['morning' => 'Morning', 'afternoon' => 'Afternoon', 'evening' => 'Evening', 'never' => 'Never'];

require_once __DIR__ . '/apply_data.php';

/* The webhook address, only if it really looks like a Discord webhook. */
function apply_webhook_url()
{
    $url = trim((string) $GLOBALS['config']['apply_webhook']);
    return preg_match('~^https://(?:(?:ptb|canary)\.)?discord(?:app)?\.com/api/(?:v\d+/)?webhooks/\d+/[A-Za-z0-9._-]+$~', $url) ? $url : '';
}

/* One line / long text coming from the form. */
function apply_field($key, $max, $multi = false)
{
    $v = clean_text(isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '', $max);
    return $multi ? $v : trim(preg_replace('/\s+/', ' ', str_replace("\n", ' ', $v)));
}

/* Checks the whole form. Returns [errors (field => message), values]. */
function apply_validate(array $role)
{
    global $config;
    $err = [];
    $v = [];

    $v['nickname'] = apply_field('nickname', 16);
    if (!preg_match('/^[A-Za-z0-9_]{3,16}$/', $v['nickname'])) {
        $err['nickname'] = t('ap_e_nick');
    }

    $v['premium'] = isset($_POST['premium']) && $_POST['premium'] === 'yes' ? 'yes' : (isset($_POST['premium']) && $_POST['premium'] === 'no' ? 'no' : '');
    if ($v['premium'] === '') {
        $err['premium'] = t('ap_e_required');
    }

    $age = isset($_POST['age']) ? trim((string) $_POST['age']) : '';
    $v['age'] = ctype_digit($age) ? (int) $age : 0;
    if ($v['age'] < 10 || $v['age'] > 99) {
        $err['age'] = t('ap_e_age');
    } elseif ($v['age'] < (int) $config['apply_min_age']) {
        $err['age'] = sprintf(t('ap_e_age_min'), (int) $config['apply_min_age']);
    }

    $v['tz'] = apply_field('tz', 60);
    if (!in_array($v['tz'], timezone_identifiers_list(), true)) {
        $err['tz'] = t('ap_e_tz');
    }

    $v['avail'] = [];
    $bad = false;
    foreach (APPLY_DAYS as $d => $name) {
        $got = isset($_POST['avail'][$d]) && is_array($_POST['avail'][$d]) ? $_POST['avail'][$d] : [];
        $slots = array_values(array_filter(array_keys(APPLY_SLOTS), function ($s) use ($got) { return in_array($s, $got, true); }));
        if (in_array('never', $slots, true)) {
            $slots = ['never'];
        }
        if (!$slots) {
            $bad = true;
        }
        $v['avail'][$d] = $slots;
    }
    if ($bad) {
        $err['avail'] = t('ap_e_avail');
    }

    $v['about'] = apply_field('about', 800, true);
    if (mb_strlen($v['about'], 'UTF-8') < 30) {
        $err['about'] = sprintf(t('ap_e_short'), 30);
    }

    $v['answers'] = [];
    foreach ($role['questions'] as $q) {
        $multi = $q['type'] === 'textarea';
        $val = apply_field('q_' . $q['id'], (int) $q['max'], $multi);
        $v['answers'][$q['id']] = $val;
        $len = mb_strlen($val, 'UTF-8');
        if ($q['required'] && $val === '') {
            $err['q_' . $q['id']] = t('ap_e_required');
        } elseif ($val !== '' && $len < (int) $q['min']) {
            $err['q_' . $q['id']] = sprintf(t('ap_e_short'), (int) $q['min']);
        }
    }

    $v['confirm'] = !empty($_POST['confirm']);
    if (!$v['confirm']) {
        $err['confirm'] = t('ap_e_confirm');
    }
    return [$err, $v];
}

/* Text typed by the applicant, made harmless inside a Discord embed (no fake links or mentions). */
function apply_safe($s)
{
    return str_replace(['](', '<'], ['] (', "<\u{200B}"], $s);
}

/* The Discord embed(s): same look as the other staff applications, one "Label: answer" line per question. */
function apply_embeds(array $role, array $v, array $user, $discordId, $code, $ts)
{
    $line = "\u{2500}\u{2500}\u{2500}\u{2500}\u{2500}\u{2500}\u{2500}\u{2500}\u{2500}\u{2500}\u{2500}\u{2500}\u{2500}\u{2500}";
    $roleName = $role['title']['en'];

    $applicant = $discordId ? '<@' . $discordId . '> (`' . $code . '`)' : '**' . $user['username'] . '** (`' . $code . '`)';
    $lines = [
        '**Applicant:** ' . $applicant,
        '**Website account:** `' . $user['username'] . '`',
        '**Submitted:** <t:' . $ts . ':R>',
        $line,
        '**Nickname:** ' . $v['nickname'],
        '**Minecraft Premium:** ' . ($v['premium'] === 'yes' ? 'Yes' : 'No'),
        '**Age:** ' . $v['age'],
        '**Time zone:** ' . $v['tz'],
    ];
    $av = [];
    foreach (APPLY_DAYS as $d => $name) {
        $av[] = "\u{2022} " . substr($name, 0, 3) . ': ' . implode(', ', array_map(function ($s) { return APPLY_SLOTS[$s]; }, $v['avail'][$d]));
    }
    $lines[] = "**Availability:**\n" . implode("\n", $av);
    $lines[] = $line;
    $lines[] = "**About:**\n" . apply_safe($v['about']);
    foreach ($role['questions'] as $q) {
        $ans = $v['answers'][$q['id']];
        if ($ans === '') {
            $ans = '_No answer_';
        } else {
            $ans = apply_safe($ans);
        }
        $label = rtrim($q['label']['en']);
        $lines[] = '**' . $label . (preg_match('/[?:.]$/', $label) ? '' : ':') . '**' . ($q['type'] === 'textarea' ? "\n" : ' ') . $ans;
    }

    // split in chunks that fit the Discord limit (4096 characters per embed)
    $chunks = [''];
    foreach ($lines as $l) {
        $i = count($chunks) - 1;
        if ($chunks[$i] !== '' && mb_strlen($chunks[$i] . "\n" . $l, 'UTF-8') > 3800) {
            $chunks[] = $l;
        } else {
            $chunks[$i] .= ($chunks[$i] === '' ? '' : "\n") . $l;
        }
    }
    $chunks = array_slice($chunks, 0, 3);

    $embeds = [];
    foreach ($chunks as $i => $c) {
        $e = ['description' => mb_substr($c, 0, 4000, 'UTF-8'), 'color' => 0xE8387F];
        if ($i === 0) {
            $e['title'] = "\u{1F4CB} Staff Application - " . $roleName;
        }
        if ($i === count($chunks) - 1) {
            $e['footer'] = ['text' => 'Application ID: ' . $code . ' | ' . $GLOBALS['config']['site_name']];
            $e['timestamp'] = gmdate('c', $ts);
        }
        $embeds[] = $e;
    }
    return $embeds;
}

/* POST a JSON message to the webhook. Returns the HTTP status (204 = delivered, 0 = no connection). */
function apply_webhook_post($url, array $payload)
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return 0;
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    }
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/json\r\n", 'content' => $json, 'timeout' => 8, 'ignore_errors' => true]]);
        @file_get_contents($url, false, $ctx);
        if (isset($http_response_header[0]) && preg_match('~\s(\d{3})\s~', $http_response_header[0], $m)) {
            return (int) $m[1];
        }
    }
    return 0;
}

/* Sends an application. Returns [true, code] or [false, error message]. */
function apply_submit(array $role, array $v, array $user)
{
    global $config;
    $pdo = db();
    $uid = (int) $user['id'];

    $st = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND role = ? AND created_at > datetime('now', ?)");
    $st->execute([$uid, $role['id'], '-' . (int) $config['apply_cooldown_hours'] . ' hours']);
    if ((int) $st->fetchColumn() > 0) {
        return [false, sprintf(t('ap_e_cooldown'), (int) $config['apply_cooldown_hours'])];
    }
    $st = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND created_at > datetime('now', '-24 hours')");
    $st->execute([$uid]);
    if ((int) $st->fetchColumn() >= (int) $config['apply_daily_limit']) {
        return [false, t('ap_e_limit')];
    }

    $url = apply_webhook_url();
    if ($url === '') {
        return [false, t('ap_closed')];
    }

    // unique short code of the application
    $alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
    for ($try = 0; $try < 20; $try++) {
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $ex = $pdo->prepare('SELECT 1 FROM applications WHERE code = ?');
        $ex->execute([$code]);
        if (!$ex->fetch()) {
            break;
        }
    }
    $pdo->prepare('INSERT INTO applications (user_id, code, role, answers) VALUES (?,?,?,?)')
        ->execute([$uid, $code, $role['id'], json_encode($v, JSON_UNESCAPED_UNICODE)]);
    $rowId = (int) $pdo->lastInsertId();

    $d = $pdo->prepare('SELECT discord_id FROM users WHERE id = ?');
    $d->execute([$uid]);
    $discordId = (string) $d->fetchColumn();
    $discordId = preg_match('/^\d{15,22}$/', $discordId) ? $discordId : '';

    $status = apply_webhook_post($url, [
        'username'         => 'AetherBound Applications',
        'embeds'           => apply_embeds($role, $v, $user, $discordId, $code, time()),
        'allowed_mentions' => ['parse' => []],
    ]);
    if ($status < 200 || $status >= 300) {
        // not delivered: forget it, so the member can try again at once
        $pdo->prepare('DELETE FROM applications WHERE id = ?')->execute([$rowId]);
        return [false, t('ap_e_send')];
    }
    return [true, $code];
}
