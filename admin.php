<?php
// Admin panel: create roles and manage registered members. Admins only.
$page = 'admin';
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/avatar.php';
require_once __DIR__ . '/includes/news.php';
ensure_session(true);
$me = require_admin();
$pdo = db();

$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['users', 'news', 'rules'], true) ? $_GET['tab'] : 'roles';
$perPage = 20;

function valid_role_name($n)
{
    return (bool) preg_match('/^[\p{L}\p{N} _-]{2,24}$/u', $n);
}

function valid_color($c)
{
    return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $c);
}

/* Role type: "public" (just a badge) or "staff" (also listed on the Staff page). */
function clean_kind($k)
{
    return $k === 'staff' ? 'staff' : 'public';
}

/* Priority on the Staff page: whole number 0-999, higher = shown first. */
function clean_priority($p)
{
    return max(0, min(999, (int) $p));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
    $back = 'admin.php?tab=roles';

    if (!csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        flash('err', 'err_csrf');
    } else {
        try {
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $name = trim((string) (isset($_POST['name']) ? $_POST['name'] : ''));
            $color = strtolower(trim((string) (isset($_POST['color']) ? $_POST['color'] : '')));
            $kind = clean_kind(isset($_POST['kind']) ? (string) $_POST['kind'] : '');
            $priority = clean_priority(isset($_POST['priority']) ? $_POST['priority'] : 0);

            switch ($action) {
                case 'role_create':
                case 'role_update':
                    if (!valid_role_name($name)) {
                        flash('err', 'err_role_name');
                    } elseif (!valid_color($color)) {
                        flash('err', 'err_role_color');
                    } elseif ($action === 'role_create') {
                        $pdo->prepare('INSERT INTO roles (name, color, kind, priority) VALUES (?, ?, ?, ?)')->execute([$name, $color, $kind, $priority]);
                        flash('ok', 'ok_role_created');
                    } else {
                        $n = $pdo->prepare('UPDATE roles SET name = ?, color = ?, kind = ?, priority = ? WHERE id = ?');
                        $n->execute([$name, $color, $kind, $priority, $id]);
                        flash($n->rowCount() ? 'ok' : 'err', $n->rowCount() ? 'ok_role_saved' : 'err_notfound');
                    }
                    break;

                case 'role_delete':
                    // Members with this role are left without a role (ON DELETE SET NULL).
                    $pdo->prepare('UPDATE users SET role_id = NULL WHERE role_id = ?')->execute([$id]);
                    $n = $pdo->prepare('DELETE FROM roles WHERE id = ?');
                    $n->execute([$id]);
                    flash($n->rowCount() ? 'ok' : 'err', $n->rowCount() ? 'ok_role_deleted' : 'err_notfound');
                    break;

                case 'rules_cat_delete':
                    $back = 'admin.php?tab=rules';
                    $n = $pdo->prepare('DELETE FROM rule_cats WHERE id = ?');
                    $n->execute([$id]);
                    flash($n->rowCount() ? 'ok' : 'err', $n->rowCount() ? 'ok_rules_deleted' : 'err_notfound');
                    break;

                case 'news_delete':
                    $back = 'admin.php?tab=news';
                    $st = $pdo->prepare('SELECT image FROM news WHERE id = ?');
                    $st->execute([$id]);
                    $old = $st->fetch();
                    $n = $pdo->prepare('DELETE FROM news WHERE id = ?');
                    $n->execute([$id]);
                    if ($n->rowCount()) {
                        news_image_delete($old['image']);
                    }
                    flash($n->rowCount() ? 'ok' : 'err', $n->rowCount() ? 'ok_news_deleted' : 'err_notfound');
                    break;

                case 'news_toggle':
                    $back = 'admin.php?tab=news';
                    $n = $pdo->prepare('UPDATE news SET published = 1 - published WHERE id = ?');
                    $n->execute([$id]);
                    flash($n->rowCount() ? 'ok' : 'err', $n->rowCount() ? 'ok_news_saved' : 'err_notfound');
                    break;

                case 'user_role':
                    $back = 'admin.php?tab=users' . (isset($_POST['back']) ? $_POST['back'] : '');
                    $roleId = isset($_POST['role_id']) && $_POST['role_id'] !== '' ? (int) $_POST['role_id'] : null;
                    if ($roleId !== null) {
                        $chk = $pdo->prepare('SELECT 1 FROM roles WHERE id = ?');
                        $chk->execute([$roleId]);
                        if (!$chk->fetch()) {
                            flash('err', 'err_notfound');
                            break;
                        }
                    }
                    $n = $pdo->prepare('UPDATE users SET role_id = ? WHERE id = ?');
                    $n->execute([$roleId, $id]);
                    flash($n->rowCount() ? 'ok' : 'err', $n->rowCount() ? 'ok_user_role' : 'err_notfound');
                    break;

                case 'user_delete':
                    $back = 'admin.php?tab=users' . (isset($_POST['back']) ? $_POST['back'] : '');
                    $st = $pdo->prepare('SELECT id, role, avatar FROM users WHERE id = ?');
                    $st->execute([$id]);
                    $target = $st->fetch();
                    if (!$target) {
                        flash('err', 'err_notfound');
                    } elseif ((int) $target['id'] === (int) $me['id'] || $target['role'] === 'admin') {
                        flash('err', 'err_forbidden'); // admins cannot be deleted from here
                    } else {
                        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
                        avatar_delete($target['avatar']);
                        flash('ok', 'ok_user_deleted');
                    }
                    break;
            }
        } catch (PDOException $ex) {
            flash('err', $ex->getCode() === '23000' ? 'err_role_taken' : 'err_db');
        } catch (Exception $ex) {
            flash('err', 'err_db');
        }
    }
    // $back only ever contains our own "&q=..&p=.." suffix (see the forms below): keep it harmless.
    if (!preg_match('/^admin\.php\?tab=(roles|users|news|rules)(&(q|p)=[^&\s]*)*$/', $back)) {
        $back = 'admin.php?tab=roles';
    }
    redirect($back);
}

/* ---- Data for the page ---- */
$countUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$countRoles = (int) $pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn();
$roles = $pdo->query('SELECT r.id, r.name, r.color, r.kind, r.priority, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS members
    FROM roles r ORDER BY (r.kind = \'staff\') DESC, r.priority DESC, r.name COLLATE NOCASE')->fetchAll();

$countNews = (int) $pdo->query('SELECT COUNT(*) FROM news')->fetchColumn();
$newsRows = [];
if ($tab === 'news') {
    $newsRows = $pdo->query('SELECT n.id, n.title, n.category, n.image, n.published, n.views, n.created_at,
            (SELECT COUNT(*) FROM news_likes l WHERE l.news_id = n.id) AS likes
        FROM news n ORDER BY n.created_at DESC, n.id DESC LIMIT 200')->fetchAll();
}

$ruleCats = [];
if ($tab === 'rules') {
    $ruleCats = $pdo->query('SELECT c.id, c.slug, c.grp_en, c.title_en, c.icon, c.sort, c.updated_at,
            (SELECT COUNT(*) FROM rule_secs s WHERE s.cat_id = c.id) AS secs,
            (SELECT COUNT(*) FROM rule_items i JOIN rule_secs s ON s.id = i.sec_id WHERE s.cat_id = c.id) AS items
        FROM rule_cats c ORDER BY c.sort, c.id')->fetchAll();
}

$q = isset($_GET['q']) && is_string($_GET['q']) ? trim(mb_substr($_GET['q'], 0, 60, 'UTF-8')) : '';
$p = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$members = [];
$pages = 1;
if ($tab === 'users') {
    $where = '';
    $args = [];
    if ($q !== '') {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
        $where = " WHERE u.username LIKE ? ESCAPE '\\' OR u.email LIKE ? ESCAPE '\\'";
        $args = [$like, $like];
    }
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM users u' . $where);
    $cnt->execute($args);
    $total = (int) $cnt->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $p = min($p, $pages);
    $st = $pdo->prepare('SELECT u.id, u.username, u.email, u.role, u.role_id, u.avatar, u.created_at, u.last_seen_at, u.last_login_at,
            r.name AS role_name, r.color AS role_color
        FROM users u LEFT JOIN roles r ON r.id = u.role_id' . $where . '
        ORDER BY u.id DESC LIMIT ' . $perPage . ' OFFSET ' . (($p - 1) * $perPage));
    $st->execute($args);
    $members = $st->fetchAll();
}
$back = ($q !== '' ? '&q=' . rawurlencode($q) : '') . ($p > 1 ? '&p=' . $p : '');

$pageTitle = t('nav_admin');
require __DIR__ . '/includes/header.php';
$csrf = csrf_token();
?>
<main>
  <div class="wrap page">
    <div class="page-head">
      <span class="auth-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.5c0 4.6 3.1 8 7.5 9.5 4.4-1.5 7.5-4.9 7.5-9.5V6L12 3Z"/></svg>
        <?= e(t('ad_badge')) ?>
      </span>
      <div class="head-row">
        <div>
          <h1><?= e(t('ad_title')) ?></h1>
          <p><?= e(t('ad_sub')) ?></p>
        </div>
        <a class="link-soft" href="profile.php">← <?= e(t('pf_title')) ?></a>
      </div>
    </div>

    <?= flash_html() ?>

    <div class="stats admin-stats">
      <div class="stat"><strong><?= $countUsers ?></strong><span><?= e(t('ad_stat_users')) ?></span></div>
      <div class="stat"><strong><?= $countRoles ?></strong><span><?= e(t('ad_stat_roles')) ?></span></div>
      <div class="stat"><strong><?= $countNews ?></strong><span><?= e(t('ad_stat_news')) ?></span></div>
    </div>

    <div class="tabs admin-tabs">
      <a class="tab" href="admin.php?tab=roles" aria-selected="<?= $tab === 'roles' ? 'true' : 'false' ?>"><?= e(t('ad_tab_roles')) ?></a>
      <a class="tab" href="admin.php?tab=users" aria-selected="<?= $tab === 'users' ? 'true' : 'false' ?>"><?= e(t('ad_tab_users')) ?></a>
      <a class="tab" href="admin.php?tab=rules" aria-selected="<?= $tab === 'rules' ? 'true' : 'false' ?>"><?= e(t('ad_tab_rules')) ?></a>
      <a class="tab" href="admin.php?tab=news" aria-selected="<?= $tab === 'news' ? 'true' : 'false' ?>"><?= e(t('ad_tab_news')) ?></a>
    </div>

<?php if ($tab === 'roles'): ?>
    <form class="panel" method="post" action="admin.php?tab=roles">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="action" value="role_create">
      <h4 class="ptitle"><?= e(t('ad_role_new')) ?></h4>
      <div class="role-new">
        <div class="f-name">
          <label for="rname"><?= e(t('ad_role_name')) ?></label>
          <input id="rname" name="name" type="text" maxlength="24" required placeholder="Helper" data-role-name>
        </div>
        <div class="f-color">
          <label for="rcolor"><?= e(t('ad_role_color')) ?></label>
          <input id="rcolor" name="color" type="color" value="#22c55e" data-role-color>
        </div>
        <div class="f-kind">
          <label for="rkind"><?= e(t('ad_role_kind')) ?></label>
          <select id="rkind" name="kind">
            <option value="public"><?= e(t('ad_kind_public')) ?></option>
            <option value="staff"><?= e(t('ad_kind_staff')) ?></option>
          </select>
        </div>
        <div class="f-prio">
          <label for="rprio"><?= e(t('ad_role_priority')) ?></label>
          <input id="rprio" name="priority" type="number" min="0" max="999" value="0" inputmode="numeric">
        </div>
        <div class="f-prev"><span class="badge" style="--bc:#22c55e" data-role-preview>Helper</span></div>
        <button class="btn sm" type="submit"><?= e(t('ad_role_create')) ?></button>
      </div>
      <p class="hint"><?= e(t('ad_role_hint')) ?></p>
    </form>

    <div class="sec-title">
      <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.2"/></svg></span>
      <div><h3><?= e(t('ad_role_list')) ?></h3></div>
    </div>

    <?php if (!$roles): ?>
      <div class="panel empty-panel"><?= e(t('ad_role_none')) ?></div>
    <?php else: ?>
      <div class="role-list">
      <?php foreach ($roles as $r): ?>
        <div class="panel role-row">
          <form class="role-edit" method="post" action="admin.php?tab=roles">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="role_update">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <div class="fld fld-color"><small><?= e(t('ad_role_color')) ?></small><input type="color" name="color" value="<?= e($r['color']) ?>" aria-label="<?= e(t('ad_role_color')) ?>"></div>
            <div class="fld fld-grow"><small><?= e(t('ad_role_name')) ?></small><input type="text" name="name" value="<?= e($r['name']) ?>" maxlength="24" required aria-label="<?= e(t('ad_role_name')) ?>"></div>
            <div class="fld"><small><?= e(t('ad_role_kind')) ?></small>
              <select name="kind" aria-label="<?= e(t('ad_role_kind')) ?>">
                <option value="public"<?= $r['kind'] === 'staff' ? '' : ' selected' ?>><?= e(t('ad_kind_public')) ?></option>
                <option value="staff"<?= $r['kind'] === 'staff' ? ' selected' : '' ?>><?= e(t('ad_kind_staff')) ?></option>
              </select>
            </div>
            <div class="fld fld-prio"><small><?= e(t('ad_role_priority')) ?></small><input type="number" name="priority" min="0" max="999" value="<?= (int) $r['priority'] ?>" inputmode="numeric" aria-label="<?= e(t('ad_role_priority')) ?>"></div>
            <span class="members"><?= (int) $r['members'] ?> <?= e(t('ad_role_members')) ?></span>
            <button class="btn sm" type="submit"><?= e(t('btn_save')) ?></button>
          </form>
          <form method="post" action="admin.php?tab=roles" data-confirm="<?= e(t('ad_role_delete_q')) ?>">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="role_delete">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="btn sm danger" type="submit"><?= e(t('ad_role_delete')) ?></button>
          </form>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>

<?php elseif ($tab === 'rules'): ?>
    <div class="admin-bar"><a class="btn sm" href="admin-rules.php">+ <?= e(t('ar_new_cat')) ?></a><a class="link-soft" href="rules.php"><?= e(t('ar_view_page')) ?> →</a></div>
    <p class="hint admin-hint"><?= e(t('ar_list_hint')) ?></p>

    <?php if (!$ruleCats): ?>
      <div class="panel empty-panel"><?= e(t('ar_none')) ?></div>
    <?php else: ?>
      <div class="news-admin">
      <?php foreach ($ruleCats as $rc): ?>
        <div class="panel news-row rule-row">
          <span class="rn-ic"><?= page_icon($rc['icon'], 16) ?></span>
          <div class="nr-info">
            <a class="nr-title" href="admin-rules.php?id=<?= (int) $rc['id'] ?>"><?= e($rc['title_en']) ?></a>
            <div class="nr-meta">
              <?php if ($rc['grp_en'] !== ''): ?><span class="news-tag"><?= e($rc['grp_en']) ?></span><?php endif; ?>
              <span><?= (int) $rc['secs'] ?> <?= e(t('ar_sections')) ?></span>
              <span><?= (int) $rc['items'] ?> <?= e(t('ar_rules')) ?></span>
              <span>#<?= (int) $rc['sort'] ?></span>
            </div>
          </div>
          <div class="nr-actions">
            <a class="btn sm white" href="admin-rules.php?id=<?= (int) $rc['id'] ?>"><?= e(t('ad_news_edit_btn')) ?></a>
            <form method="post" action="admin.php?tab=rules" data-confirm="<?= e(t('ar_delete_cat_q')) ?>">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="action" value="rules_cat_delete">
              <input type="hidden" name="id" value="<?= (int) $rc['id'] ?>">
              <button class="btn sm danger" type="submit"><?= e(t('ad_news_delete')) ?></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>

<?php elseif ($tab === 'news'): ?>
    <div class="admin-bar"><a class="btn sm" href="admin-news.php">+ <?= e(t('ad_news_new')) ?></a></div>

    <?php if (!$newsRows): ?>
      <div class="panel empty-panel"><?= e(t('ad_news_none')) ?></div>
    <?php else: ?>
      <div class="news-admin">
      <?php foreach ($newsRows as $n): $nimg = news_image_url($n); ?>
        <div class="panel news-row">
          <div class="nr-thumb<?= $nimg ? '' : ' none' ?>"><img src="<?= e($nimg ? $nimg : 'assets/img/head.webp') ?>" alt="" width="96" height="60" loading="lazy"></div>
          <div class="nr-info">
            <a class="nr-title" href="news.php?id=<?= (int) $n['id'] ?>"><?= e($n['title']) ?></a>
            <div class="nr-meta">
              <span class="news-tag"><?= e(t('cat_' . news_clean_category($n['category']))) ?></span>
              <span class="news-tag <?= $n['published'] ? 'live' : 'draft' ?>"><?= e(t($n['published'] ? 'ad_news_live' : 'news_draft')) ?></span>
              <span><?= e(news_date($n['created_at'])) ?></span>
              <span class="news-stat"><?= news_icon('eye') ?><?= (int) $n['views'] ?></span>
              <span class="news-stat"><?= news_icon('heart') ?><?= (int) $n['likes'] ?></span>
            </div>
          </div>
          <div class="nr-actions">
            <a class="btn sm white" href="admin-news.php?id=<?= (int) $n['id'] ?>"><?= e(t('ad_news_edit_btn')) ?></a>
            <form method="post" action="admin.php?tab=news">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="action" value="news_toggle">
              <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
              <button class="btn sm white" type="submit"><?= e(t($n['published'] ? 'ad_news_unpublish' : 'ad_news_publish')) ?></button>
            </form>
            <form method="post" action="admin.php?tab=news" data-confirm="<?= e(t('ad_news_delete_q')) ?>">
              <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
              <input type="hidden" name="action" value="news_delete">
              <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
              <button class="btn sm danger" type="submit"><?= e(t('ad_news_delete')) ?></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>

<?php else: ?>
    <form class="panel search" method="get" action="admin.php">
      <input type="hidden" name="tab" value="users">
      <label class="sr" for="q"><?= e(t('ad_users_search')) ?></label>
      <input id="q" name="q" type="search" value="<?= e($q) ?>" maxlength="60" placeholder="<?= e(t('ad_users_search')) ?>">
      <button class="btn sm" type="submit"><?= e(t('ad_users_find')) ?></button>
    </form>

    <?php if (!$members): ?>
      <div class="panel empty-panel"><?= e(t('ad_users_none')) ?></div>
    <?php else: ?>
      <div class="user-list">
      <?php foreach ($members as $m): $isAdmin = $m['role'] === 'admin'; $isMe = (int) $m['id'] === (int) $me['id']; ?>
        <div class="panel user-row">
          <?= avatar_img($m, 52, 'uavatar') ?>
          <div class="uinfo">
            <div class="uname"><a href="user.php?u=<?= e(rawurlencode($m['username'])) ?>"><?= e($m['username']) ?></a><?= role_badges($m) ?><?= $isMe ? '<span class="you">' . e(t('ad_user_you')) . '</span>' : '' ?></div>
            <div class="umail"><?= e($m['email']) ?></div>
            <div class="udates"><?= e(t('ad_user_joined')) ?> <?= e(fmt_date($m['created_at'])) ?> · <?= e(seen_text($m['last_seen_at'] ?: $m['last_login_at'])) ?></div>
          </div>
          <form class="urole" method="post" action="admin.php?tab=users">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="user_role">
            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
            <input type="hidden" name="back" value="<?= e($back) ?>">
            <label class="sr" for="role<?= (int) $m['id'] ?>"><?= e(t('ad_user_role')) ?></label>
            <select id="role<?= (int) $m['id'] ?>" name="role_id">
              <option value=""><?= e(t('ad_user_no_role')) ?></option>
              <?php foreach ($roles as $r): ?>
                <option value="<?= (int) $r['id'] ?>"<?= (int) $m['role_id'] === (int) $r['id'] ? ' selected' : '' ?>><?= e($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn sm" type="submit"><?= e(t('btn_save')) ?></button>
          </form>
          <?php if (!$isAdmin && !$isMe): ?>
          <form method="post" action="admin.php?tab=users" data-confirm="<?= e(t('ad_user_delete_q')) ?>">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="user_delete">
            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
            <input type="hidden" name="back" value="<?= e($back) ?>">
            <button class="btn sm danger" type="submit"><?= e(t('ad_user_delete')) ?></button>
          </form>
          <?php else: ?>
          <span class="no-del"></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      </div>

      <?php if ($pages > 1): ?>
      <nav class="pager" aria-label="<?= e(t('ad_page')) ?>">
        <?php $qs = $q !== '' ? '&q=' . rawurlencode($q) : ''; ?>
        <?php if ($p > 1): ?><a class="btn sm" href="admin.php?tab=users<?= $qs ?>&p=<?= $p - 1 ?>">← <?= e(t('ad_prev')) ?></a><?php endif; ?>
        <span><?= e(t('ad_page')) ?> <?= $p ?> / <?= $pages ?></span>
        <?php if ($p < $pages): ?><a class="btn sm" href="admin.php?tab=users<?= $qs ?>&p=<?= $p + 1 ?>"><?= e(t('ad_next')) ?> →</a><?php endif; ?>
      </nav>
      <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
