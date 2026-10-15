<?php
const RULES_LANGS = ['en', 'it'];

function rules_icons()
{
    return ['shield', 'book', 'chat', 'ban', 'mute', 'globe', 'cube', 'clock', 'users', 'pen'];
}

function rules_pick($en, $it)
{
    $lang = isset($GLOBALS['lang']) ? $GLOBALS['lang'] : 'en';
    return ($lang === 'it' && trim((string) $it) !== '') ? $it : $en;
}

function rules_parse_table($text)
{
    $head = [];
    $rows = [];
    foreach (preg_split('/\n/', str_replace("\r", '', (string) $text)) as $i => $line) {
        if (trim($line) === '') {
            continue;
        }
        $cells = array_map('trim', explode('|', $line));
        if (!$head) {
            $head = $cells;
        } else {
            $rows[] = $cells;
        }
    }
    return ['head' => $head, 'rows' => $rows];
}

function rules_load()
{
    $pdo = db();
    $cats = $pdo->query('SELECT * FROM rule_cats ORDER BY sort, id')->fetchAll();
    $secs = [];
    foreach ($pdo->query('SELECT * FROM rule_secs ORDER BY sort, id') as $s) {
        $secs[(int) $s['cat_id']][] = $s;
    }
    $items = [];
    foreach ($pdo->query('SELECT * FROM rule_items ORDER BY sort, id') as $r) {
        $items[(int) $r['sec_id']][] = $r;
    }

    $groups = [];
    foreach ($cats as $c) {
        $label = rules_pick($c['grp_en'], $c['grp_it']);
        $last = count($groups) - 1;
        if ($last < 0 || $groups[$last]['key'] !== $c['grp_en']) {
            $groups[] = ['key' => $c['grp_en'], 'label' => $label, 'items' => []];
            $last = count($groups) - 1;
        }
        $sections = [];
        foreach (isset($secs[(int) $c['id']]) ? $secs[(int) $c['id']] : [] as $s) {
            $sec = ['title' => rules_pick($s['title_en'], $s['title_it'])];
            if ($s['kind'] === 'table') {
                $sec['table'] = rules_parse_table(rules_pick($s['table_en'], $s['table_it']));
            } else {
                $sec['rules'] = [];
                foreach (isset($items[(int) $s['id']]) ? $items[(int) $s['id']] : [] as $r) {
                    $sec['rules'][] = [
                        'title' => rules_pick($r['title_en'], $r['title_it']),
                        'text'  => rules_pick($r['text_en'], $r['text_it']),
                    ];
                }
            }
            $sections[] = $sec;
        }
        $groups[$last]['items'][] = [
            'id'       => $c['slug'],
            'icon'     => in_array($c['icon'], rules_icons(), true) ? $c['icon'] : 'shield',
            'title'    => rules_pick($c['title_en'], $c['title_it']),
            'sub'      => rules_pick($c['sub_en'], $c['sub_it']),
            'updated'  => $c['updated_at'],
            'sections' => $sections,
        ];
    }
    return $groups;
}

function rules_touch(PDO $pdo, $catId)
{
    $pdo->prepare("UPDATE rule_cats SET updated_at = datetime('now') WHERE id = ?")->execute([(int) $catId]);
}

function rules_slug(PDO $pdo, $title, $exceptId = 0)
{
    $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');
    if ($base === '') {
        $base = 'rules';
    }
    $slug = $base;
    for ($i = 2; $i < 200; $i++) {
        $st = $pdo->prepare('SELECT 1 FROM rule_cats WHERE slug = ? AND id <> ?');
        $st->execute([$slug, (int) $exceptId]);
        if (!$st->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
    }
    return $base . '-' . bin2hex(random_bytes(2));
}

function rules_seed(PDO $pdo)
{
    require_once __DIR__ . '/rules_data.php';
    $cat = $pdo->prepare('INSERT INTO rule_cats (slug, grp_en, grp_it, title_en, title_it, sub_en, sub_it, icon, sort, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $sec = $pdo->prepare('INSERT INTO rule_secs (cat_id, kind, title_en, title_it, table_en, table_it, sort) VALUES (?,?,?,?,?,?,?)');
    $itm = $pdo->prepare('INSERT INTO rule_items (sec_id, title_en, title_it, text_en, text_it, sort) VALUES (?,?,?,?,?,?)');
    $table = function ($t, $lang) {
        $lines = [implode(' | ', array_map(function ($h) use ($lang) { return $h[$lang]; }, $t['head']))];
        foreach ($t['rows'] as $row) {
            $lines[] = implode(' | ', array_map(function ($c) use ($lang) { return $c[$lang]; }, $row));
        }
        return implode("\n", $lines);
    };
    $n = 0;
    foreach (rules_seed_data() as $g) {
        foreach ($g['items'] as $c) {
            $cat->execute([$c['id'], $g['label']['en'], $g['label']['it'], $c['title']['en'], $c['title']['it'], $c['sub']['en'], $c['sub']['it'], $c['icon'], ++$n * 10, $c['updated'] . ' 00:00:00']);
            $cid = (int) $pdo->lastInsertId();
            foreach ($c['sections'] as $si => $s) {
                $isTable = isset($s['table']);
                $sec->execute([$cid, $isTable ? 'table' : 'rules', $s['title']['en'], $s['title']['it'],
                    $isTable ? $table($s['table'], 'en') : '', $isTable ? $table($s['table'], 'it') : '', ($si + 1) * 10]);
                $sid = (int) $pdo->lastInsertId();
                if (!$isTable) {
                    foreach ($s['rules'] as $ri => $r) {
                        $t = isset($r['title']) ? $r['title'] : ['en' => '', 'it' => ''];
                        $itm->execute([$sid, $t['en'], $t['it'], $r['text']['en'], $r['text']['it'], ($ri + 1) * 10]);
                    }
                }
            }
        }
    }
}
