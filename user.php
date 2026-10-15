<?php
$page = 'user';
require __DIR__ . '/includes/bootstrap.php';

$name = isset($_GET['u']) && is_string($_GET['u']) ? trim($_GET['u']) : '';
$member = null;
if ($name !== '' && strlen($name) <= 32) {
    try {
        $st = db()->prepare('SELECT u.id, u.username, u.role, u.avatar, u.bio, u.signature, u.created_at, u.last_seen_at,
                r.name AS role_name, r.color AS role_color
            FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.username = ?');
        $st->execute([$name]);
        $member = $st->fetch() ?: null;
    } catch (Exception $ex) {
        $member = null;
    }
}

if (!$member) {
    http_response_code(404);
}
$pageTitle = $member ? $member['username'] : t('pu_not_found');
require __DIR__ . '/includes/header.php';
$mine = $member && $user && (int) $user['id'] === (int) $member['id'];
?>
<main>
  <div class="wrap page">
<?php if (!$member): ?>
    <section class="stub">
      <h1><?= e(t('pu_not_found')) ?></h1>
      <p><?= e(t('pu_not_found_p')) ?></p>
      <a class="btn" href="home"><?= e(t('nav_home')) ?></a>
    </section>
<?php else: ?>
    <div class="panel pcard public">
      <?= avatar_img($member, 112, 'pavatar') ?>
      <div class="pinfo">
        <div class="pname"><h1><?= e($member['username']) ?></h1><?= role_badges($member) ?></div>
        <div class="pmeta">
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/></svg><?= e(t('pu_joined')) ?> <?= e(fmt_date($member['created_at'])) ?></span>
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?= e(seen_text($member['last_seen_at'])) ?></span>
        </div>
      </div>
      <?php if ($mine): ?><a class="btn sm" href="profile"><?= e(t('pu_edit')) ?></a><?php endif; ?>
    </div>

    <div class="grid-2 public-grid">
      <section class="panel">
        <h4 class="ptitle"><?= e(t('pf_about')) ?></h4>
        <?php if ($member['bio'] === ''): ?>
          <p class="ptext empty"><?= e(t('pu_empty')) ?></p>
        <?php else: ?>
          <div class="ptext md"><?= md_render($member['bio']) ?></div>
        <?php endif; ?>
      </section>
      <section class="panel">
        <h4 class="ptitle"><?= e(t('pf_signature')) ?></h4>
        <p class="ptext<?= $member['signature'] === '' ? ' empty' : '' ?>"><?= $member['signature'] === '' ? e(t('pu_empty')) : e($member['signature']) ?></p>
      </section>
    </div>
<?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
