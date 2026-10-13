<?php
// Placeholder page: the banner is ready, replace the "coming soon" panel with the real content.
$page = 'apply';
$withBg = true;
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = t('nav_apply');
require __DIR__ . '/includes/header.php';
?>
<main>
  <div class="wrap">
    <?= page_banner('apply_badge', 'apply_title', 'apply_sub', $config['site_name'], 'pen') ?>
    <div class="panel empty-panel soon-panel"><?= e(t('soon')) ?></div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
