<?php
function md_icons()
{
    return [
        'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'clock'     => '<path d="M7 3h10M7 21h10M8 3v4l4 5-4 5v4M16 3v4l-4 5 4 5v4"/>',
        'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.3c2.2.7 3.5 2.6 3.5 5.7"/>',
        'send'      => '<path d="M22 2 11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7Z"/>',
        'star'      => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3Z"/>',
        'heart'     => '<path d="M12 20.5s-8-4.6-8-10.7A4.6 4.6 0 0 1 12 7a4.6 4.6 0 0 1 8 2.8c0 6.1-8 10.7-8 10.7Z"/>',
        'check'     => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16 9.5"/>',
        'warn'      => '<path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 10v4M12 17h.01"/>',
        'gift'      => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M5 12v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8M7.5 8a2.5 2.5 0 0 1 0-5C10 3 12 8 12 8s2-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
        'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'trophy'    => '<path d="M8 4h8v5a4 4 0 0 1-8 0V4Z"/><path d="M8 6H4v1a4 4 0 0 0 4 4M16 6h4v1a4 4 0 0 1-4 4M12 13v4M8 21h8M10 17h4"/>',
        'bell'      => '<path d="M6 16v-5a6 6 0 0 1 12 0v5l2 2H4l2-2Z"/><path d="M10 21h4"/>',
        'gamepad'   => '<rect x="2.5" y="7" width="19" height="10" rx="5"/><path d="M8 10.5v3M6.5 12h3M16 11h.01M18 13h.01"/>',
        'gem'       => '<path d="M6 3h12l4 6-10 12L2 9l4-6Z"/><path d="M2 9h20M9 3l3 6 3-6M12 21 9 9M12 21l3-12"/>',
        'fire'      => '<path d="M12 22a7 7 0 0 0 7-7c0-3-2-5-3-7-1 1.5-2 2-3 2 0-3-1-5-3-7-1 3-5 5-5 11a7 7 0 0 0 7 8Z"/>',
        'crown'     => '<path d="M3 8l4 4 5-7 5 7 4-4-2 11H5L3 8Z"/>',
        'pin'       => '<path d="M12 21s7-6 7-11a7 7 0 0 0-14 0c0 5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'lock'      => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'shield'    => '<path d="M12 3 4.5 6v5.5c0 4.6 3.1 8 7.5 9.5 4.4-1.5 7.5-4.9 7.5-9.5V6L12 3Z"/>',
        'book'      => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5v-16Z"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>',
        'link'      => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'megaphone' => '<path d="M3 11v3a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1Z"/><path d="M15 9a4 4 0 0 1 0 6M18 6a8 8 0 0 1 0 12"/>',
        'sword'     => '<path d="M14.5 17.5 3 6V3h3l11.5 11.5M13 19l6-6M16 16l4 4M19 21l2-2"/>',
        'zap'       => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>',
        'cube'      => '<path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z"/><path d="M3 7l9 5 9-5M12 12v10"/>',
        'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'eye'       => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    ];
}

function md_icon_svg($name, $color = null)
{
    $all = md_icons();
    if (!isset($all[$name])) {
        return '';
    }
    $style = ($color !== null && preg_match('/^#[0-9a-fA-F]{6}$/', $color)) ? ' style="color:' . $color . '"' : '';
    return '<svg class="md-ic"' . $style . ' width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $all[$name] . '</svg>';
}

function md_render($text)
{
    $text = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], (string) $text);
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    $hold = [];
    $stash = function ($html) use (&$hold) {
        $hold[] = $html;
        return "\0" . (count($hold) - 1) . "\0";
    };

    $text = preg_replace_callback('/```[ \t]*\n?(.*?)\n?```/s', function ($m) use ($stash) {
        return $stash('<pre><code>' . $m[1] . '</code></pre>');
    }, $text);
    $text = preg_replace_callback('/`([^`\n]+)`/', function ($m) use ($stash) {
        return $stash('<code>' . $m[1] . '</code>');
    }, $text);

    $inline = function ($s) use ($stash) {
        $s = preg_replace_callback('/\[([^\]\n]{1,100})\]\((https?:\/\/[^\s()<>"]{1,300})\)/', function ($m) use ($stash) {
            return $stash('<a href="' . $m[2] . '" target="_blank" rel="noopener nofollow ugc">' . $m[1] . '</a>');
        }, $s);
        $s = preg_replace_callback('/:([a-z]{2,12})(?:#([0-9a-fA-F]{6}))?:/', function ($m) use ($stash) {
            $svg = md_icon_svg($m[1], isset($m[2]) && $m[2] !== '' ? '#' . $m[2] : null);
            return $svg === '' ? $m[0] : $stash($svg);
        }, $s);
        $s = preg_replace('/\[color=(#[0-9a-fA-F]{6})\](.+?)\[\/color\]/s', '<span style="color:$1">$2</span>', $s);
        $s = preg_replace('/\*\*(?!\s)(.+?)(?<!\s)\*\*/s', '<strong>$1</strong>', $s);
        $s = preg_replace('/\*(?![\s*])([^*\n]+?)(?<![\s*])\*/', '<em>$1</em>', $s);
        $s = preg_replace('/\|\|(?!\s)(.+?)(?<!\s)\|\|/s', '<span class="md-spoiler" tabindex="0" role="button">$1</span>', $s);
        return $s;
    };

    $blocks = function ($src) use ($inline) {
        $out = '';
        $para = [];
        $list = [];
        $quote = [];
        $flush = function () use (&$out, &$para, &$list, &$quote, $inline) {
            if ($quote) {
                $out .= '<blockquote>' . nl2br($inline(implode("\n", $quote)), false) . '</blockquote>';
                $quote = [];
            }
            if ($list) {
                $out .= '<ul>';
                foreach ($list as $li) {
                    $out .= '<li>' . $inline($li) . '</li>';
                }
                $out .= '</ul>';
                $list = [];
            }
            if ($para) {
                $out .= '<div class="md-p">' . nl2br($inline(implode("\n", $para)), false) . '</div>';
                $para = [];
            }
        };
        foreach (explode("\n", $src) as $line) {
            $l = rtrim($line, " \t");
            if (preg_match('/^(#{1,3})[ \t]+(.+)$/', $l, $m)) {
                $flush();
                $n = strlen($m[1]);
                $out .= '<h' . $n . ' class="md-h">' . $inline($m[2]) . '</h' . $n . '>';
            } elseif (preg_match('/^(-{3,}|\*{3,})$/', $l)) {
                $flush();
                $out .= '<hr>';
            } elseif (preg_match('/^&gt;[ \t]?(.*)$/', $l, $m)) {
                if ($list || $para) {
                    $flush();
                }
                $quote[] = $m[1];
            } elseif (preg_match('/^[-*][ \t]+(.+)$/', $l, $m)) {
                if ($quote || $para) {
                    $flush();
                }
                $list[] = $m[1];
            } elseif (trim($l, " \t") === '') {
                $flush();
            } else {
                if ($quote || $list) {
                    $flush();
                }
                $para[] = $l;
            }
        }
        $flush();
        return $out;
    };

    $out = '';
    $parts = preg_split('/(\[center\].*?\[\/center\])/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $part) {
        if (strncmp($part, '[center]', 8) === 0 && substr($part, -9) === '[/center]') {
            $out .= '<div class="md-center">' . $blocks(substr($part, 8, -9)) . '</div>';
        } else {
            $out .= $blocks($part);
        }
    }

    for ($i = 0; $i < 3 && strpos($out, "\0") !== false; $i++) {
        $out = preg_replace_callback('/\0(\d+)\0/', function ($m) use ($hold) {
            return isset($hold[(int) $m[1]]) ? $hold[(int) $m[1]] : '';
        }, $out);
    }
    return $out;
}
