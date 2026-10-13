<?php
// News helpers: categories, dates, excerpts and cover images.
const NEWS_IMAGE_MAX = 4194304; // 4 MB

function news_categories()
{
    return ['news', 'update', 'event'];
}

function news_clean_category($c)
{
    return in_array($c, news_categories(), true) ? $c : 'news';
}

function news_date($sqlUtc)
{
    $ts = $sqlUtc ? strtotime($sqlUtc . ' UTC') : false;
    if ($ts === false) {
        return '—';
    }
    if (class_exists('IntlDateFormatter')) {
        $f = new IntlDateFormatter($GLOBALS['lang'], IntlDateFormatter::LONG, IntlDateFormatter::NONE);
        $s = $f->format($ts);
        if ($s !== false) {
            return $s;
        }
    }
    return date('j M Y', $ts);
}

/* Short summary taken from the start of the text when the admin leaves the summary empty. */
function news_excerpt_from_body($body, $max = 200)
{
    $s = preg_replace('/```.*?```/s', ' ', (string) $body);
    $s = preg_replace('/\[([^\]\n]*)\]\(https?:[^)\s]*\)/', '$1', $s);
    $s = preg_replace('/\[\/?(?:color(?:=#[0-9a-fA-F]{6})?|center)\]/', '', $s);
    $s = preg_replace('/^[#>\-\*\s]+/m', '', $s);
    $s = preg_replace_callback('/:([a-z]{2,12})(?:#[0-9a-fA-F]{6})?:/', function ($m) {
        return isset(md_icons()[$m[1]]) ? '' : $m[0];
    }, $s);
    $s = str_replace(['**', '||', '`', '*'], '', $s);
    $s = trim(preg_replace('/\s+/u', ' ', $s));
    if (mb_strlen($s, 'UTF-8') > $max) {
        $s = rtrim(mb_substr($s, 0, $max - 1, 'UTF-8')) . '…';
    }
    return $s;
}

function news_image_dir()
{
    return __DIR__ . '/../uploads/news';
}

function news_image_url(array $row)
{
    return !empty($row['image']) && preg_match('/^n_[a-f0-9]{16}\.(webp|jpg|png)$/', $row['image']) ? 'img.php?f=' . $row['image'] : null;
}

function news_image_delete($name)
{
    if ($name && preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
        $path = news_image_dir() . '/' . $name;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/* Cover image upload. Returns the stored file name, or null and sets $err to a translation key.
   With GD the picture is re-encoded (max 1400 px wide), which also strips any hidden data. */
function news_image_store(array $file, &$err)
{
    $err = null;
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $big = isset($file['error']) && ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE);
        $err = $big ? 'err_news_image_size' : 'err_news_image';
        return null;
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        $err = 'err_news_image';
        return null;
    }
    if ($file['size'] > NEWS_IMAGE_MAX) {
        $err = 'err_news_image_size';
        return null;
    }
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]]) || $info[0] < 100 || $info[1] < 60 || $info[0] > 8000 || $info[1] > 8000) {
        $err = 'err_news_image_type';
        return null;
    }
    $dir = news_image_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        $err = 'err_news_image';
        return null;
    }
    $base = 'n_' . bin2hex(random_bytes(8));

    if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
        $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if ($src) {
            $w = $info[0];
            $h = $info[1];
            $nw = min($w, 1400);
            $nh = (int) round($h * $nw / $w);
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $name = $base . '.webp';
            $ok = @imagewebp($dst, $dir . '/' . $name, 86);
            imagedestroy($src);
            imagedestroy($dst);
            // Some servers write an empty or broken file: only accept it if it can be read back as an image.
            if ($ok && is_file($dir . '/' . $name) && filesize($dir . '/' . $name) > 0 && @getimagesize($dir . '/' . $name)) {
                @chmod($dir . '/' . $name, 0644);
                return $name;
            }
            @unlink($dir . '/' . $name);
        }
    }

    // No GD / WebP support: keep the original, with the extension of its real type.
    $name = $base . '.' . $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name) || !is_file($dir . '/' . $name) || filesize($dir . '/' . $name) === 0) {
        $err = 'err_news_image';
        return null;
    }
    @chmod($dir . '/' . $name, 0644); // uploads can be created with private permissions (0600)
    return $name;
}

function news_icon($name)
{
    $p = [
        'eye'   => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'heart' => '<path d="M12 20.5s-8-4.6-8-10.7A4.6 4.6 0 0 1 12 7a4.6 4.6 0 0 1 8 2.8c0 6.1-8 10.7-8 10.7Z"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'back'  => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
    ];
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[$name] . '</svg>';
}
