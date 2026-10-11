<?php
// Formatting for "About me". Members type simple codes, this turns them into safe HTML.
// The text is escaped FIRST, so nothing a member types can ever become a tag: only the tags below are produced.
//
//   **bold**   *italic*   ||spoiler||   `code`   ```code block```
//   [text](https://link)   [color=#ff6aa8]text[/color]   [center]text[/center]
//   # H1   ## H2   ### H3   > quote   - list   --- divider

function md_render($text)
{
    $text = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], (string) $text);
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    $hold = [];
    $stash = function ($html) use (&$hold) {
        $hold[] = $html;
        return "\0" . (count($hold) - 1) . "\0";
    };

    // Code first: what is inside is never formatted.
    $text = preg_replace_callback('/```[ \t]*\n?(.*?)\n?```/s', function ($m) use ($stash) {
        return $stash('<pre><code>' . $m[1] . '</code></pre>');
    }, $text);
    $text = preg_replace_callback('/`([^`\n]+)`/', function ($m) use ($stash) {
        return $stash('<code>' . $m[1] . '</code>');
    }, $text);

    $inline = function ($s) use ($stash) {
        // Links: http(s) only (no javascript: etc.)
        $s = preg_replace_callback('/\[([^\]\n]{1,100})\]\((https?:\/\/[^\s()<>"]{1,300})\)/', function ($m) use ($stash) {
            return $stash('<a href="' . $m[2] . '" target="_blank" rel="noopener nofollow ugc">' . $m[1] . '</a>');
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

    // [center] ... [/center] centres everything inside it (it may span several lines).
    $out = '';
    $parts = preg_split('/(\[center\].*?\[\/center\])/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $part) {
        if (strncmp($part, '[center]', 8) === 0 && substr($part, -9) === '[/center]') {
            $out .= '<div class="md-center">' . $blocks(substr($part, 8, -9)) . '</div>';
        } else {
            $out .= $blocks($part);
        }
    }

    // Put the code back (a few passes, because a link text can contain inline code).
    for ($i = 0; $i < 3 && strpos($out, "\0") !== false; $i++) {
        $out = preg_replace_callback('/\0(\d+)\0/', function ($m) use ($hold) {
            return isset($hold[(int) $m[1]]) ? $hold[(int) $m[1]] : '';
        }, $out);
    }
    return $out;
}
