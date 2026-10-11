<?php
// Profile photo upload. Returns the stored file name, or null and sets $err to a translation key.
// With the GD extension the image is re-encoded as a 256x256 PNG (this also removes any hidden data).
function avatar_dir()
{
    return __DIR__ . '/../uploads/avatars';
}

function avatar_delete($name)
{
    if ($name && preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
        $path = avatar_dir() . '/' . $name;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function avatar_store(array $file, $uid, $maxBytes, &$err)
{
    $err = null;
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $err = 'err_avatar_none';
        return null;
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $err = 'err_avatar_size';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $err = 'err_avatar';
        return null;
    }
    if ($file['size'] > $maxBytes) {
        $err = 'err_avatar_size';
        return null;
    }

    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) {
        $err = 'err_avatar_type';
        return null;
    }
    if ($info[0] < 16 || $info[1] < 16 || $info[0] > 4096 || $info[1] > 4096) {
        $err = 'err_avatar_type';
        return null;
    }

    $dir = avatar_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        $err = 'err_avatar';
        return null;
    }
    $base = 'u' . (int) $uid . '_' . bin2hex(random_bytes(5));

    if (function_exists('imagecreatefromstring')) {
        $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if (!$src) {
            $err = 'err_avatar_type';
            return null;
        }
        $side = min($info[0], $info[1]);
        $sx = (int) (($info[0] - $side) / 2);
        $sy = (int) (($info[1] - $side) / 2);
        $dst = imagecreatetruecolor(256, 256);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, 256, 256, $side, $side);
        $name = $base . '.png';
        $ok = imagepng($dst, $dir . '/' . $name);
        imagedestroy($src);
        imagedestroy($dst);
        if (!$ok) {
            $err = 'err_avatar';
            return null;
        }
        return $name;
    }

    // No GD: keep the original file, with an extension taken from the real image type.
    $name = $base . '.' . $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        $err = 'err_avatar';
        return null;
    }
    return $name;
}
