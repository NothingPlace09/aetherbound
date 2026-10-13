<?php
// Staff page: every member whose role is of type "staff", grouped by role and ordered by role priority.
$page = 'staff';
require __DIR__ . '/includes/bootstrap.php';

$groups = [];
try {
    $rows = db()->query("SELECT r.id AS rid, r.name AS role_name, r.color AS role_color, u.id, u.username, u.avatar
        FROM roles r JOIN users u ON u.role_id = r.id
        WHERE r.kind = 'staff'
        ORDER BY r.priority DESC, r.name COLLATE NOCASE, u.username COLLATE NOCASE")->fetchAll();
    foreach ($rows as $row) {
        $rid = (int) $row['rid'];
        if (!isset($groups[$rid])) {
            $color = preg_match('/^#[0-9a-fA-F]{6}$/', $row['role_color']) ? $row['role_color'] : '#ff6aa8';
            $groups[$rid] = ['name' => $row['role_name'], 'color' => $color, 'members' => []];
        }
        $groups[$rid]['members'][] = $row;
    }
} catch (Exception $ex) {
    $groups = [];
}

$withBg = true;
$pageTitle = t('nav_staff');
require __DIR__ . '/includes/header.php';
?>
<main>
  <div class="wrap staff-page">
    <?= page_banner('staff_page_badge', 'staff_page_title', 'staff_page_sub', $config['site_name'], 'users') ?>

<?php if (!$groups): ?>
    <div class="panel empty-panel"><?= e(t('staff_empty')) ?></div>
<?php else: foreach ($groups as $g): ?>
    <section class="staff-group" style="--rc:<?= e($g['color']) ?>">
      <h2 class="staff-h">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.5c0 4.6 3.1 8 7.5 9.5 4.4-1.5 7.5-4.9 7.5-9.5V6L12 3Z"/></svg>
        <?= e($g['name']) ?>
      </h2>
      <div class="staff-grid">
      <?php foreach ($g['members'] as $m): ?>
        <a class="staff-card" href="user.php?u=<?= e(rawurlencode($m['username'])) ?>">
          <span class="badge" style="--bc:<?= e($g['color']) ?>"><?= e($g['name']) ?></span>
          <?= avatar_img($m, 64, 'staff-face') ?>
          <strong class="staff-name"><?= e($m['username']) ?></strong>
        </a>
      <?php endforeach; ?>
      </div>
    </section>
<?php endforeach; endif; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
