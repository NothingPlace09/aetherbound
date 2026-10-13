<?php
// Serves news cover images through PHP (img.php?f=name). Works even when the web server cannot read
// files in uploads/ directly (wrong file permissions, blocked folder, strict hosting rules).
$name = isset($_GET['f']) && is_string($_GET['f']) ? $_GET['f'] : '';
if (!preg_match('/^n_[a-f0-9]{16}\.(webp|jpg|png)$/', $name, $m)) {
    http_response_code(404);
    exit;
}
$path = __DIR__ . '/uploads/news/' . $name;
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit;
}
$types = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'png' => 'image/png'];
header('Content-Type: ' . $types[$m[1]]);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=31536000, immutable');
header('Content-Length: ' . filesize($path));
readfile($path);
