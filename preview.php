<?php
require __DIR__ . '/includes/bootstrap.php';
ensure_session(true);
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !current_user() || !csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
    http_response_code(403);
    exit;
}
$max = (int) $config['about_max'];
if (isset($_POST['kind']) && $_POST['kind'] === 'news') {
    $u = current_user();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        exit;
    }
    $max = 20000;
}
$text = isset($_POST['text']) && is_string($_POST['text']) ? mb_substr($_POST['text'], 0, $max + 200, 'UTF-8') : '';
$text = clean_text($text, $max);
echo $text === '' ? '<p class="ptext empty">' . e(t('pu_empty')) . '</p>' : md_render($text);
