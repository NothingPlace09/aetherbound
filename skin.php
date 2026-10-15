<?php
$config = require __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/skin.php';

$name = isset($_GET['u']) && is_string($_GET['u']) ? $_GET['u'] : '';
$size = isset($_GET['s']) ? max(16, min(256, (int) $_GET['s'])) : 64;
$steve = __DIR__ . '/assets/img/steve-face.png';

$known = false;
if (skin_valid_name($name)) {
    try {
        $st = db()->prepare('SELECT 1 FROM users WHERE username = ?');
        $st->execute([$name]);
        $known = (bool) $st->fetch();
    } catch (Exception $ex) {
        $known = false;
    }
}

$file = $steve;
$ttl = 1800;
if ($known) {
    $info = skin_lookup($name);
    if ($info['state'] === 'premium') {
        $face = skin_face_file($info['uuid']);
        if ($face) {
            $file = $face;
            $ttl = 600;
        } elseif (!function_exists('imagecreatefromstring')) {
            header('Location: ' . str_replace(['{name}', '{size}'], [$info['uuid'], $size], $config['skin_face_url']));
            exit;
        } else {
            $ttl = 60;
        }
    } elseif ($info['state'] === 'error') {
        $ttl = 60;
    }
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=' . $ttl);
header('X-Content-Type-Options: nosniff');

if (function_exists('imagecreatefrompng')) {
    $src = @imagecreatefrompng($file);
    if ($src) {
        $dst = imagecreatetruecolor($size, $size);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresized($dst, $src, 0, 0, 0, 0, $size, $size, imagesx($src), imagesy($src));
        imagepng($dst);
        exit;
    }
}
readfile($file);
