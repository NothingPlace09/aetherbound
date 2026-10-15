<?php
$page = 'rules';
$withBg = true;
require __DIR__ . '/includes/bootstrap.php';
$extraScripts = ['assets/js/rules.js'];
$groups = rules_load();
$pageTitle = t('nav_rules');
require __DIR__ . '/includes/header.php';
?>
<main>
  <div class="wrap">
    <?= page_banner('rules_badge', 'rules_title', 'rules_sub', $config['site_name'], 'book') ?>

<?php if (!$groups): ?>
    <div class="panel empty-panel soon-panel"><?= e(t('rules_empty')) ?></div>
<?php else: ?>
    <div class="rules-layout" data-rules>
      <aside class="rules-nav">
        <nav aria-label="<?= e(t('rules_nav')) ?>">
<?php foreach ($groups as $g): ?>
          <?php if ($g['label'] !== ''): ?>
          <p class="rn-label"><?= e($g['label']) ?></p>
<?php endif; ?>
<?php foreach ($g['items'] as $c): ?>
          <a class="rn-item" href="#r-<?= e($c['id']) ?>" data-rule-tab="<?= e($c['id']) ?>"><span class="rn-ic"><?= page_icon($c['icon'], 16) ?></span><span><?= e($c['title']) ?></span></a>
<?php endforeach; endforeach; ?>
        </nav>
      </aside>

      <div class="rules-main">
<?php foreach ($groups as $g): foreach ($g['items'] as $c): ?>
        <section class="panel rules-panel" id="r-<?= e($c['id']) ?>" data-rule-panel="<?= e($c['id']) ?>">
          <header class="rp-head">
            <span class="rp-ic"><?= page_icon($c['icon'], 24) ?></span>
            <div class="rp-title">
              <h2><?= e($c['title']) ?></h2>
              <p><?= e($c['sub']) ?></p>
            </div>
<?php if (!empty($c['updated'])): ?>
            <span class="rp-updated"><?= page_icon('clock', 13) ?><?= e(t('rules_updated')) ?> <?= e(fmt_date($c['updated'])) ?></span>
<?php endif; ?>
          </header>
<?php foreach ($c['sections'] as $si => $sec): ?>
          <div class="rp-sec">
            <h3 class="rp-sech"><i></i><?= e($sec['title']) ?></h3>
<?php if (isset($sec['table'])): ?>
            <div class="rt-wrap">
              <table class="rtable">
                <thead><tr><?php foreach ($sec['table']['head'] as $h): ?><th scope="col"><?= e($h) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
<?php foreach ($sec['table']['rows'] as $row): ?>
                  <tr><?php foreach ($row as $ci => $cell): ?><?= $ci === 0 ? '<th scope="row">' . e($cell) . '</th>' : '<td>' . e($cell) . '</td>' ?><?php endforeach; ?></tr>
<?php endforeach; ?>
                </tbody>
              </table>
            </div>
<?php else: ?>
            <ol class="rlist">
<?php foreach ($sec['rules'] as $ri => $r): ?>
              <li>
                <span class="rnum"><?= ($si + 1) . '.' . $ri ?></span>
                <div class="rbody">
<?php if (!empty($r['title'])): ?>
                  <strong class="rtitle"><?= e($r['title']) ?></strong>
<?php endif; ?>
                  <div class="rtext md"><?= md_render($r['text']) ?></div>
                </div>
              </li>
<?php endforeach; ?>
            </ol>
<?php endif; ?>
          </div>
<?php endforeach; ?>
        </section>
<?php endforeach; endforeach; ?>
      </div>
    </div>
<?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
