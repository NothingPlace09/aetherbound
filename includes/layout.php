<?php
// Shared page pieces. page_banner() is the mini banner shown at the top of every page:
// badge + big title + subtitle, over the lobby background (set $withBg = true before including header.php).

/* Asset URL with a version taken from the file date: after you upload a new CSS/JS file, browsers and hosts
   stop serving the old cached copy. */
function asset_url($path)
{
    $file = __DIR__ . '/../' . $path;
    return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
}

function page_icon($name, $size = 14)
{
    $icons = [
        'shield' => '<path d="M12 3 4.5 6v5.5c0 4.6 3.1 8 7.5 9.5 4.4-1.5 7.5-4.9 7.5-9.5V6L12 3Z"/>',
        'news'   => '<path d="M4 5h13a1 1 0 0 1 1 1v13a2 2 0 0 0 2 0V9h1"/><path d="M4 5v13a2 2 0 0 0 2 2h14"/><path d="M8 9h6M8 13h6M8 17h4"/>',
        'book'   => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5v-16Z"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>',
        'users'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.3c2.2.7 3.5 2.6 3.5 5.7"/>',
        'pen'    => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/>',
        'globe'  => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3Z"/>',
        'chat'   => '<path d="M5 5h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-7l-5 4v-4H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/><path d="M8 10h8M8 13h5"/>',
        'ban'    => '<circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/>',
        'mute'   => '<path d="M4 9v6h4l5 4V5L8 9H4Z"/><path d="m17 9 4 6m0-6-4 6"/>',
        'cube'   => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
        'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];
    $path = isset($icons[$name]) ? $icons[$name] : $icons['shield'];
    return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

/* $subArg: optional value for a %s in the subtitle (for example the site name). */
function page_banner($badgeKey, $titleKey, $subKey, $subArg = null, $icon = 'shield')
{
    $sub = $subArg !== null ? sprintf(t($subKey), $subArg) : t($subKey);
    return '<header class="pg-hero">'
        . '<span class="auth-badge">' . page_icon($icon) . e(t($badgeKey)) . '</span>'
        . '<h1>' . e(t($titleKey)) . '</h1>'
        . '<p>' . e($sub) . '</p>'
        . '</header>';
}
