<?php
function editor_box($id, $name, $value, $max, $csrf, $kind = '', $rows = 8)
{
    $svg = function ($inner, $w = '2.2') {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $w . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
    };
    $btn = function ($cmd, $labelKey, $inner, $extra = '') {
        return '<button type="button" data-cmd="' . $cmd . '" title="' . e(t($labelKey)) . $extra . '" aria-label="' . e(t($labelKey)) . '">' . $inner . '</button>';
    };
    ob_start();
    ?>
      <div class="mdx" data-mdx data-kind="<?= e($kind) ?>" data-preview-url="preview" data-csrf="<?= e($csrf) ?>" data-loading="<?= e(t('ed_loading')) ?>" data-max="<?= (int) $max ?>">
        <div class="mdx-top" role="tablist">
          <button type="button" class="mdx-tab" role="tab" data-mdx-tab="write" aria-selected="true"><?= e(t('ed_write')) ?></button>
          <button type="button" class="mdx-tab" role="tab" data-mdx-tab="preview" aria-selected="false"><?= e(t('ed_preview')) ?></button>
        </div>
        <div class="mdx-tools" role="toolbar" aria-label="<?= e(t('ed_tools')) ?>">
          <?= $btn('bold', 'ed_bold', '<b>B</b>', ' (Ctrl+B)') ?>
          <?= $btn('italic', 'ed_italic', '<i>I</i>', ' (Ctrl+I)') ?>
          <?= $btn('spoiler', 'ed_spoiler', 'SPO') ?>
          <?= $btn('link', 'ed_link', $svg('<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>')) ?>
          <?= $btn('code', 'ed_code', $svg('<path d="m8 8-4 4 4 4M16 8l4 4-4 4"/>')) ?>
          <?= $btn('quote', 'ed_quote', '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 7h4v5c0 3-1.6 4.8-4 5.2v-2c1.2-.4 1.8-1.2 1.9-2.4H7V7Zm7 0h4v5c0 3-1.6 4.8-4 5.2v-2c1.2-.4 1.8-1.2 1.9-2.4H14V7Z"/></svg>') ?>
          <?= $btn('list', 'ed_list', $svg('<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1" fill="currentColor"/><circle cx="4.5" cy="12" r="1" fill="currentColor"/><circle cx="4.5" cy="18" r="1" fill="currentColor"/>', '2.4')) ?>
          <?= $btn('center', 'ed_center', $svg('<path d="M4 6h16M7 12h10M5 18h14"/>', '2.4')) ?>
          <?= $btn('h1', 'ed_heading', 'H1') ?>
          <?= $btn('h2', 'ed_heading', 'H2') ?>
          <?= $btn('h3', 'ed_heading', 'H3') ?>
          <button type="button" class="mdx-color" data-cmd="color" title="<?= e(t('ed_color')) ?>" aria-label="<?= e(t('ed_color')) ?>"><i></i></button>
          <input type="color" class="sr" value="#12a8f5" data-mdx-color tabindex="-1" aria-hidden="true">
          <?= $btn('icon', 'ed_icon', $svg('<circle cx="12" cy="12" r="9"/><path d="M8.5 14a4 4 0 0 0 7 0M9 9.5h.01M15 9.5h.01"/>')) ?>
          <?= $btn('hr', 'ed_hr', $svg('<path d="M5 12h14"/>', '2.6')) ?>
        </div>
        <div class="mdx-icons" data-mdx-icons hidden>
          <div class="mdx-icolor">
            <label for="<?= e($id) ?>-ic"><?= e(t('ed_icon_color')) ?></label>
            <input type="color" id="<?= e($id) ?>-ic" value="#12a8f5" data-icon-color>
            <button type="button" class="mdx-icolor-off" data-icon-nocolor hidden><?= e(t('ed_icon_nocolor')) ?></button>
          </div>
          <?php foreach (md_icons() as $iname => $ipath): ?>
            <button type="button" data-icon="<?= e($iname) ?>" title=":<?= e($iname) ?>:" aria-label="<?= e($iname) ?>"><?= md_icon_svg($iname) ?></button>
          <?php endforeach; ?>
        </div>
        <label class="sr" for="<?= e($id) ?>"><?= e(t('ad_news_body')) ?></label>
        <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="<?= (int) $rows ?>" maxlength="<?= (int) $max ?>" data-mdx-input><?= e($value) ?></textarea>
        <div class="mdx-preview ptext md" data-mdx-preview hidden></div>
      </div>
      <p class="hint"><span data-mdx-count></span> · <?= e(t('ed_icon_hint')) ?></p>
    <?php
    return ob_get_clean();
}
