<?php
// Placeholder page. Replace the <section> below with the real content.
$page = 'apply';
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = t('nav_apply');
require __DIR__ . '/includes/header.php';
?>
<main>
  <section class="wrap stub">
    <h1><?= e(t('nav_apply')) ?></h1>
    <p><?= e(t('soon')) ?></p>
    <a class="btn" href="index.php"><?= e(t('nav_home')) ?></a>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
