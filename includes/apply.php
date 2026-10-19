<?php
const APPLY_DAYS = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];
const APPLY_SLOTS = ['morning' => 'Morning', 'afternoon' => 'Afternoon', 'evening' => 'Evening', 'never' => 'Never'];

require_once __DIR__ . '/apply_data.php';

function ap_t($v)
{
    return is_array($v) ? rules_pick($v['en'], $v['it']) : (string) $v;
}

function apply_icons()
{
    return ['users', 'bell', 'heart', 'shield', 'code', 'cube', 'monitor', 'star', 'pen', 'chat', 'globe', 'book'];
}

function apply_lines($text)
{
    $out = [];
    foreach (preg_split('/\R/', (string) $text) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function apply_clean_lines($text, $maxLines = 12, $maxLen = 240)
{
    $out = [];
    foreach (apply_lines(clean_text((string) $text, $maxLines * $maxLen)) as $line) {
        $out[] = mb_substr($line, 0, $maxLen, 'UTF-8');
        if (count($out) >= $maxLines) {
            break;
        }
    }
    return implode("\n", $out);
}

function apply_pairs($en, $it)
{
    $a = apply_lines($en);
    $b = apply_lines($it);
    $out = [];
    foreach ($a as $i => $line) {
        $out[] = ['en' => $line, 'it' => isset($b[$i]) ? $b[$i] : $line];
    }
    return $out;
}

function apply_tree($onlyActive = true)
{
    $pdo = db();
    $cats = $pdo->query('SELECT * FROM apply_cats ORDER BY sort, id')->fetchAll();
    $roles = [];
    $sql = 'SELECT * FROM apply_roles' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort, id';
    foreach ($pdo->query($sql) as $r) {
        $roles[(int) $r['cat_id']][] = $r;
    }
    $qs = [];
    foreach ($pdo->query('SELECT * FROM apply_questions ORDER BY sort, id') as $q) {
        $qs[(int) $q['role_id']][] = $q;
    }
    $tree = [];
    foreach ($cats as $c) {
        $cat = [
            'id'    => $c['slug'],
            'db_id' => (int) $c['id'],
            'icon'  => in_array($c['icon'], apply_icons(), true) ? $c['icon'] : 'users',
            'short' => ['en' => $c['short_en'], 'it' => $c['short_it'] !== '' ? $c['short_it'] : $c['short_en']],
            'title' => ['en' => $c['title_en'], 'it' => $c['title_it'] !== '' ? $c['title_it'] : $c['title_en']],
            'tag'   => ['en' => $c['tag_en'], 'it' => $c['tag_it'] !== '' ? $c['tag_it'] : $c['tag_en']],
            'desc'  => ['en' => $c['desc_en'], 'it' => $c['desc_it'] !== '' ? $c['desc_it'] : $c['desc_en']],
            'roles' => [],
        ];
        foreach (isset($roles[(int) $c['id']]) ? $roles[(int) $c['id']] : [] as $r) {
            $questions = [];
            foreach (isset($qs[(int) $r['id']]) ? $qs[(int) $r['id']] : [] as $q) {
                $questions[] = [
                    'id'       => (string) $q['id'],
                    'type'     => $q['type'] === 'text' ? 'text' : 'textarea',
                    'required' => (int) $q['required'] === 1,
                    'min'      => (int) $q['min'],
                    'max'      => (int) $q['max'],
                    'label'    => ['en' => $q['label_en'], 'it' => $q['label_it'] !== '' ? $q['label_it'] : $q['label_en']],
                    'help'     => $q['help_en'] !== '' ? ['en' => $q['help_en'], 'it' => $q['help_it'] !== '' ? $q['help_it'] : $q['help_en']] : null,
                ];
            }
            $cat['roles'][] = [
                'id'        => $r['slug'],
                'db_id'     => (int) $r['id'],
                'cat'       => $c['slug'],
                'cat_short' => $cat['short'],
                'icon'      => in_array($r['icon'], apply_icons(), true) ? $r['icon'] : 'users',
                'title'     => ['en' => $r['title_en'], 'it' => $r['title_it'] !== '' ? $r['title_it'] : $r['title_en']],
                'tag'       => ['en' => $r['tag_en'], 'it' => $r['tag_it'] !== '' ? $r['tag_it'] : $r['tag_en']],
                'desc'      => ['en' => $r['desc_en'], 'it' => $r['desc_it'] !== '' ? $r['desc_it'] : $r['desc_en']],
                'req'       => apply_pairs($r['req_en'], $r['req_it']),
                'questions' => $questions,
                'active'    => (int) $r['active'] === 1,
            ];
        }
        $tree[] = $cat;
    }
    return $tree;
}

function apply_find_cat($slug)
{
    foreach (apply_tree() as $c) {
        if ($c['id'] === $slug) {
            return $c;
        }
    }
    return null;
}

function apply_find_role($slug)
{
    foreach (apply_tree() as $c) {
        foreach ($c['roles'] as $r) {
            if ($r['id'] === $slug) {
                return $r;
            }
        }
    }
    return null;
}

function apply_rules()
{
    $st = db()->prepare("SELECT en, it FROM apply_settings WHERE k = 'rules'");
    $st->execute();
    $row = $st->fetch();
    return $row ? apply_pairs($row['en'], $row['it']) : [];
}

function apply_slug(PDO $pdo, $table, $title)
{
    $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');
    if ($base === '') {
        $base = 'item';
    }
    $slug = $base;
    for ($i = 2; $i < 200; $i++) {
        $st = $pdo->prepare('SELECT 1 FROM ' . $table . ' WHERE slug = ?');
        $st->execute([$slug]);
        if (!$st->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
    }
    return $base . '-' . bin2hex(random_bytes(2));
}

function apply_seed(PDO $pdo)
{
    $data = apply_seed_data();
    $join = function ($items, $lang) {
        return implode("\n", array_map(function ($i) use ($lang) { return $i[$lang]; }, $items));
    };
    $pdo->prepare("INSERT OR REPLACE INTO apply_settings (k, en, it) VALUES ('rules', ?, ?)")
        ->execute([$join($data['rules'], 'en'), $join($data['rules'], 'it')]);

    $cat = $pdo->prepare('INSERT INTO apply_cats (slug, icon, short_en, short_it, title_en, title_it, tag_en, tag_it, desc_en, desc_it, sort) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $role = $pdo->prepare('INSERT INTO apply_roles (cat_id, slug, icon, title_en, title_it, tag_en, tag_it, desc_en, desc_it, req_en, req_it, sort, active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)');
    $quest = $pdo->prepare('INSERT INTO apply_questions (role_id, label_en, label_it, help_en, help_it, type, required, min, max, sort) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $n = 0;
    foreach ($data['cats'] as $c) {
        $cat->execute([$c['slug'], $c['icon'], $c['short']['en'], $c['short']['it'], $c['title']['en'], $c['title']['it'], $c['tag']['en'], $c['tag']['it'], $c['desc']['en'], $c['desc']['it'], ++$n * 10]);
        $cid = (int) $pdo->lastInsertId();
        foreach ($c['roles'] as $ri => $r) {
            $role->execute([$cid, $r['slug'], $r['icon'], $r['title']['en'], $r['title']['it'], $r['tag']['en'], $r['tag']['it'], $r['desc']['en'], $r['desc']['it'], $join($r['req'], 'en'), $join($r['req'], 'it'), ($ri + 1) * 10]);
            $rid = (int) $pdo->lastInsertId();
            foreach ($r['questions'] as $qi => $q) {
                $h = $q['help'];
                $quest->execute([$rid, $q['label']['en'], $q['label']['it'], $h ? $h['en'] : '', $h ? $h['it'] : '', $q['type'], $q['required'], $q['min'], $q['max'], ($qi + 1) * 10]);
            }
        }
    }
}

function apply_webhook_url()
{
    $url = trim((string) $GLOBALS['config']['apply_webhook']);
    return preg_match('~^https://(?:(?:ptb|canary)\.)?discord(?:app)?\.com/api/(?:v\d+/)?webhooks/\d+/[A-Za-z0-9._-]+$~', $url) ? $url : '';
}

function apply_field($key, $max, $multi = false)
{
    $v = clean_text(isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '', $max);
    return $multi ? $v : trim(preg_replace('/\s+/', ' ', str_replace("\n", ' ', $v)));
}

function apply_validate(array $role)
{
    global $config;
    $err = [];
    $v = [];

    $v['nickname'] = apply_field('nickname', 16);
    if (!preg_match('/^[A-Za-z0-9_]{3,16}$/', $v['nickname'])) {
        $err['nickname'] = t('ap_e_nick');
    }

    $v['discord'] = apply_field('discord', 37);
    if (!preg_match('/^[A-Za-z0-9_.]{2,32}(#[0-9]{4})?$/', $v['discord'])) {
        $err['discord'] = t('ap_e_discord');
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
        $val = apply_field('q_' . $q['id'], (int) $q['max'], $q['type'] === 'textarea');
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

function apply_safe($s)
{
    return str_replace(['](', '<'], ['] (', "<\u{200B}"], $s);
}

function apply_embeds(array $role, array $v, array $user, $discordId, $code, $ts)
{
    $line = str_repeat("\u{2500}", 14);

    $applicant = $discordId ? '<@' . $discordId . '> (`' . $code . '`)' : '**' . $user['username'] . '** (`' . $code . '`)';
    $lines = [
        '**Applicant:** ' . $applicant,
        '**Website account:** `' . $user['username'] . '`',
        '**Submitted:** <t:' . $ts . ':R>',
        $line,
        '**Nickname:** ' . $v['nickname'],
        '**Discord nickname:** ' . apply_safe($v['discord']),
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
        $ans = $ans === '' ? '_No answer_' : apply_safe($ans);
        $label = rtrim($q['label']['en']);
        $lines[] = '**' . $label . (preg_match('/[?:.]$/', $label) ? '' : ':') . '**' . ($q['type'] === 'textarea' ? "\n" : ' ') . $ans;
    }

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
        $e = ['description' => mb_substr($c, 0, 4000, 'UTF-8'), 'color' => 0x12A8F5];
        if ($i === 0) {
            $e['title'] = "\u{1F4CB} " . $role['cat_short']['en'] . ' Application - ' . $role['title']['en'];
        }
        if ($i === count($chunks) - 1) {
            $e['footer'] = ['text' => 'Application ID: ' . $code . ' | ' . $GLOBALS['config']['site_name']];
            $e['timestamp'] = gmdate('c', $ts);
        }
        $embeds[] = $e;
    }
    return $embeds;
}

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
        'username'         => 'WaveMC Applications',
        'embeds'           => apply_embeds($role, $v, $user, $discordId, $code, time()),
        'allowed_mentions' => ['parse' => []],
    ]);
    if ($status < 200 || $status >= 300) {
        $pdo->prepare('DELETE FROM applications WHERE id = ?')->execute([$rowId]);
        return [false, t('ap_e_send')];
    }
    return [true, $code];
}
