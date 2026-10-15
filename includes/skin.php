<?php
const SKIN_TTL_UUID = 86400;
const SKIN_TTL_NONE = 3600;
const SKIN_TTL_ERROR = 90;
const SKIN_TTL_FACE = 21600;

function skin_dir()
{
    $dir = __DIR__ . '/../cache/skins';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function skin_http($url, $timeout = 4)
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_USERAGENT      => 'AetherboundSite/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$body === false ? 0 : $code, $body === false ? '' : (string) $body];
    }
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'ignore_errors' => true, 'header' => "User-Agent: AetherboundSite/1.0\r\n"]]);
        $body = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (isset($http_response_header[0]) && preg_match('~\s(\d{3})\s~', $http_response_header[0], $m)) {
            $code = (int) $m[1];
        }
        return [$body === false ? 0 : $code, $body === false ? '' : (string) $body];
    }
    return [0, ''];
}

function skin_valid_name($name)
{
    return is_string($name) && preg_match('/^[A-Za-z0-9_]{1,16}$/', $name);
}

function skin_cache_read($file, $ttlKey = null)
{
    if (!is_file($file)) {
        return null;
    }
    $data = json_decode((string) @file_get_contents($file), true);
    if (!is_array($data) || !isset($data['state'], $data['t'])) {
        return null;
    }
    $ttl = $data['state'] === 'premium' ? SKIN_TTL_UUID : ($data['state'] === 'none' ? SKIN_TTL_NONE : SKIN_TTL_ERROR);
    return time() - (int) $data['t'] < $ttl ? $data : null;
}

function skin_lookup($name, $force = false)
{
    if (!skin_valid_name($name)) {
        return ['state' => 'none'];
    }
    $file = skin_dir() . '/n_' . strtolower($name) . '.json';
    if (!$force && ($hit = skin_cache_read($file))) {
        return $hit;
    }

    $apis = $GLOBALS['config']['skin_apis'];
    $result = ['state' => 'error'];
    foreach ($apis['profile'] as $tpl) {
        list($code, $body) = skin_http(str_replace('{name}', rawurlencode($name), $tpl));
        if ($code === 200) {
            $j = json_decode($body, true);
            $id = is_array($j) ? (isset($j['id']) ? $j['id'] : (isset($j['data']['player']['raw_id']) ? $j['data']['player']['raw_id'] : '')) : '';
            if (preg_match('/^[0-9a-fA-F]{32}$/', (string) $id)) {
                $result = ['state' => 'premium', 'uuid' => strtolower($id), 'name' => isset($j['name']) ? (string) $j['name'] : $name];
                break;
            }
        } elseif ($code === 404 || $code === 204) {
            $result = ['state' => 'none'];
            break;
        }
    }
    $result['t'] = time();
    if ($result['state'] === 'error' && is_file($file)) {
        $old = json_decode((string) @file_get_contents($file), true);
        if (is_array($old) && $old['state'] === 'premium') {
            $old['t'] = time() - SKIN_TTL_UUID + 600;
            @file_put_contents($file, json_encode($old), LOCK_EX);
            return $old;
        }
    }
    @file_put_contents($file, json_encode($result), LOCK_EX);
    return $result;
}

function skin_download($uuid)
{
    $apis = $GLOBALS['config']['skin_apis'];
    list($code, $body) = skin_http(str_replace('{uuid}', $uuid, $apis['session']));
    if ($code === 200) {
        $j = json_decode($body, true);
        if (isset($j['properties']) && is_array($j['properties'])) {
            foreach ($j['properties'] as $prop) {
                if (isset($prop['name'], $prop['value']) && $prop['name'] === 'textures') {
                    $tex = json_decode((string) base64_decode($prop['value']), true);
                    $url = isset($tex['textures']['SKIN']['url']) ? (string) $tex['textures']['SKIN']['url'] : '';
                    if (preg_match('~^https?://textures\.minecraft\.net/texture/[A-Za-z0-9]+$~', $url) || !empty($apis['allow_any_texture_host'])) {
                        list($c2, $png) = skin_http($url);
                        if ($c2 === 200 && strncmp($png, "\x89PNG", 4) === 0) {
                            return $png;
                        }
                    }
                }
            }
        }
    }
    foreach ($apis['skin_fallbacks'] as $tpl) {
        list($c, $png) = skin_http(str_replace('{uuid}', $uuid, $tpl));
        if ($c === 200 && strncmp($png, "\x89PNG", 4) === 0) {
            return $png;
        }
    }
    return null;
}

function skin_cut_face($png)
{
    if (!function_exists('imagecreatefromstring')) {
        return null;
    }
    $src = @imagecreatefromstring($png);
    if (!$src) {
        return null;
    }
    $w = imagesx($src);
    $h = imagesy($src);
    if ($w < 64 || $h < 32 || $w % 64 !== 0) {
        imagedestroy($src);
        return null;
    }
    $k = (int) ($w / 64);
    $face = imagecreatetruecolor(8 * $k, 8 * $k);
    imagealphablending($face, true);
    imagesavealpha($face, true);
    imagefill($face, 0, 0, imagecolorallocatealpha($face, 0, 0, 0, 127));
    imagecopy($face, $src, 0, 0, 8 * $k, 8 * $k, 8 * $k, 8 * $k);
    imagecopy($face, $src, 0, 0, 40 * $k, 8 * $k, 8 * $k, 8 * $k);
    imagedestroy($src);
    ob_start();
    imagepng($face);
    $out = ob_get_clean();
    imagedestroy($face);
    return $out;
}

function skin_face_file($uuid, $force = false)
{
    $file = skin_dir() . '/f_' . $uuid . '.png';
    $fresh = is_file($file) && time() - filemtime($file) < SKIN_TTL_FACE;
    if ($fresh && !$force) {
        return $file;
    }
    $png = skin_download($uuid);
    $face = $png ? skin_cut_face($png) : null;
    if ($face) {
        @file_put_contents($file, $face, LOCK_EX);
        return $file;
    }
    if (is_file($file)) {
        @touch($file, time() - SKIN_TTL_FACE + 600);
        return $file;
    }
    return null;
}

function skin_forget($name)
{
    if (!skin_valid_name($name)) {
        return;
    }
    $file = skin_dir() . '/n_' . strtolower($name) . '.json';
    $data = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
    if (is_array($data) && !empty($data['uuid']) && preg_match('/^[0-9a-f]{32}$/', $data['uuid'])) {
        @unlink(skin_dir() . '/f_' . $data['uuid'] . '.png');
    }
    @unlink($file);
}
