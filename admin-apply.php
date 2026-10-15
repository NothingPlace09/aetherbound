<?php
$page = 'admin';
require __DIR__ . '/includes/bootstrap.php';
ensure_session(true);
$me = require_admin();
$pdo = db();

$isCat = isset($_GET['cat']);
$isRole = isset($_GET['role']);
if (!$isCat && !$isRole) {
    redirect('admin?tab=apply');
}
$rawId = $isCat ? (string) $_GET['cat'] : (string) $_GET['role'];
$eid = ctype_digit($rawId) ? (int) $rawId : 0;
$isNew = $eid === 0;

$cat = null;
$role = null;
if ($isCat && !$isNew) {
    $st = $pdo->prepare('SELECT * FROM apply_cats WHERE id = ?');
    $st->execute([$eid]);
    $cat = $st->fetch();
    if (!$cat) {
        flash('err', 'err_notfound');
        redirect('admin?tab=apply');
    }
}
if ($isRole && !$isNew) {
    $st = $pdo->prepare('SELECT * FROM apply_roles WHERE id = ?');
    $st->execute([$eid]);
    $role = $st->fetch();
    if (!$role) {
        flash('err', 'err_notfound');
        redirect('admin?tab=apply');
    }
}
$cats = $pdo->query('SELECT id, title_en FROM apply_cats ORDER BY sort, id')->fetchAll();
$newCat = isset($_GET['incat']) && ctype_digit((string) $_GET['incat']) ? (int) $_GET['incat'] : 0;
$self = 'admin-apply?' . ($isCat ? 'cat=' : 'role=') . ($isNew ? 'new' . ($newCat ? '&incat=' . $newCat : '') : $eid);

function aa_f($key, $max, $multi = false)
{
    $v = clean_text(isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '', $max);
    return $multi ? $v : trim(preg_replace('/\s+/', ' ', str_replace("\n", ' ', $v)));
}

function aa_n($key, $default, $min = 0, $max = 9999)
{
    return isset($_POST[$key]) && $_POST[$key] !== '' && is_numeric($_POST[$key]) ? max($min, min($max, (int) $_POST[$key])) : $default;
}

function aa_icon()
{
    return isset($_POST['icon']) && in_array($_POST['icon'], apply_icons(), true) ? $_POST['icon'] : 'users';
}

function aa_hidden($csrf, $action, array $more = [])
{
    $h = '<input type="hidden" name="csrf" value="' . e($csrf) . '"><input type="hidden" name="action" value="' . e($action) . '">';
    foreach ($more as $k => $v) {
        $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    return $h;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
    $anchor = '';
    $dest = $self;
    if (!csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        flash('err', 'err_csrf');
        redirect($self);
    }
    try {
        if ($action === 'cat_save' && $isCat) {
            $title = aa_f('title_en', 60);
            if ($title === '') {
                flash('err', 'err_apply_title');
            } else {
                $vals = [aa_icon(), aa_f('short_en', 30) ?: $title, aa_f('short_it', 30), $title, aa_f('title_it', 60), aa_f('tag_en', 40), aa_f('tag_it', 40), aa_f('desc_en', 300), aa_f('desc_it', 300)];
                if ($cat) {
                    $pdo->prepare('UPDATE apply_cats SET icon = ?, short_en = ?, short_it = ?, title_en = ?, title_it = ?, tag_en = ?, tag_it = ?, desc_en = ?, desc_it = ?, sort = ? WHERE id = ?')
                        ->execute(array_merge($vals, [aa_n('sort', (int) $cat['sort']), $eid]));
                    flash('ok', 'ok_apply_saved');
                } else {
                    $max = (int) $pdo->query('SELECT COALESCE(MAX(sort), 0) FROM apply_cats')->fetchColumn();
                    $pdo->prepare('INSERT INTO apply_cats (icon, short_en, short_it, title_en, title_it, tag_en, tag_it, desc_en, desc_it, sort, slug) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                        ->execute(array_merge($vals, [aa_n('sort', $max + 10), apply_slug($pdo, 'apply_cats', $title)]));
                    flash('ok', 'ok_apply_saved');
                    redirect('admin-apply?cat=' . (int) $pdo->lastInsertId());
                }
            }
        } elseif ($isRole) {
            $rid = $role ? $eid : 0;
            if ($action === 'role_save') {
                $title = aa_f('title_en', 60);
                $catId = aa_n('cat_id', 0);
                $ok = $pdo->prepare('SELECT 1 FROM apply_cats WHERE id = ?');
                $ok->execute([$catId]);
                if ($title === '') {
                    flash('err', 'err_apply_title');
                } elseif (!$ok->fetch()) {
                    flash('err', 'err_notfound');
                } else {
                    $reqEn = apply_clean_lines(isset($_POST['req_en']) ? $_POST['req_en'] : '', 14, 240);
                    $reqIt = apply_clean_lines(isset($_POST['req_it']) ? $_POST['req_it'] : '', 14, 240);
                    $vals = [$catId, aa_icon(), $title, aa_f('title_it', 60), aa_f('tag_en', 40), aa_f('tag_it', 40), aa_f('desc_en', 400), aa_f('desc_it', 400), $reqEn, $reqIt, empty($_POST['active']) ? 0 : 1];
                    if ($role) {
                        $pdo->prepare('UPDATE apply_roles SET cat_id = ?, icon = ?, title_en = ?, title_it = ?, tag_en = ?, tag_it = ?, desc_en = ?, desc_it = ?, req_en = ?, req_it = ?, active = ?, sort = ? WHERE id = ?')
                            ->execute(array_merge($vals, [aa_n('sort', (int) $role['sort']), $eid]));
                        flash('ok', 'ok_apply_saved');
                    } else {
                        $mx = $pdo->prepare('SELECT COALESCE(MAX(sort), 0) FROM apply_roles WHERE cat_id = ?');
                        $mx->execute([$catId]);
                        $pdo->prepare('INSERT INTO apply_roles (cat_id, icon, title_en, title_it, tag_en, tag_it, desc_en, desc_it, req_en, req_it, active, sort, slug) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
                            ->execute(array_merge($vals, [aa_n('sort', (int) $mx->fetchColumn() + 10), apply_slug($pdo, 'apply_roles', $title)]));
                        flash('ok', 'ok_apply_saved');
                        redirect('admin-apply?role=' . (int) $pdo->lastInsertId());
                    }
                }
            } elseif ($role && in_array($action, ['q_add', 'q_save', 'q_delete'], true)) {
                $anchor = '#questions';
                $qid = isset($_POST['qid']) ? (int) $_POST['qid'] : 0;
                if ($action === 'q_delete') {
                    $n = $pdo->prepare('DELETE FROM apply_questions WHERE id = ? AND role_id = ?');
                    $n->execute([$qid, $eid]);
                    flash($n->rowCount() ? 'ok' : 'err', $n->rowCount() ? 'ok_apply_deleted' : 'err_notfound');
                } else {
                    $label = aa_f('label_en', 160);
                    $type = isset($_POST['type']) && $_POST['type'] === 'text' ? 'text' : 'textarea';
                    $max = aa_n('max', $type === 'text' ? 200 : 700, 10, $type === 'text' ? 300 : 800);
                    $min = min(aa_n('min', 0, 0, 500), $max);
                    $req = empty($_POST['required']) ? 0 : 1;
                    $cnt = $pdo->prepare('SELECT COUNT(*) FROM apply_questions WHERE role_id = ?');
                    $cnt->execute([$eid]);
                    if ($label === '') {
                        flash('err', 'err_apply_label');
                    } elseif ($action === 'q_add' && (int) $cnt->fetchColumn() >= 12) {
                        flash('err', 'err_apply_maxq');
                    } elseif ($action === 'q_add') {
                        $mx = $pdo->prepare('SELECT COALESCE(MAX(sort), 0) FROM apply_questions WHERE role_id = ?');
                        $mx->execute([$eid]);
                        $pdo->prepare('INSERT INTO apply_questions (role_id, label_en, label_it, help_en, help_it, type, required, min, max, sort) VALUES (?,?,?,?,?,?,?,?,?,?)')
                            ->execute([$eid, $label, aa_f('label_it', 160), aa_f('help_en', 200), aa_f('help_it', 200), $type, $req, $min, $max, (int) $mx->fetchColumn() + 10]);
                        flash('ok', 'ok_apply_saved');
                    } else {
                        $n = $pdo->prepare('UPDATE apply_questions SET label_en = ?, label_it = ?, help_en = ?, help_it = ?, type = ?, required = ?, min = ?, max = ?, sort = ? WHERE id = ? AND role_id = ?');
                        $n->execute([$label, aa_f('label_it', 160), aa_f('help_en', 200), aa_f('help_it', 200), $type, $req, $min, $max, aa_n('sort', 0), $qid, $eid]);
                        flash($n->rowCount() ? 'ok' : 'err', $n->rowCount() ? 'ok_apply_saved' : 'err_notfound');
                    }
                }
            }
        }
    } catch (Exception $ex) {
        flash('err', 'err_db');
    }
    redirect($dest . $anchor);
}

$questions = [];
if ($role) {
    $st = $pdo->prepare('SELECT * FROM apply_questions WHERE role_id = ? ORDER BY sort, id');
    $st->execute([$eid]);
    $questions = $st->fetchAll();
}
$blank = ['icon' => 'users', 'short_en' => '', 'short_it' => '', 'title_en' => '', 'title_it' => '', 'tag_en' => '', 'tag_it' => '', 'desc_en' => '', 'desc_it' => '', 'sort' => '',
    'cat_id' => $newCat ? $newCat : ($cats ? $cats[0]['id'] : 0), 'req_en' => '', 'req_it' => '', 'active' => 1, 'slug' => ''];
$v = $isCat ? ($cat ? $cat : $blank) : ($role ? $role : $blank);

$csrf = csrf_token();
$pageTitle = t('ad_tab_apply');
require __DIR__ . '/includes/header.php';

$iconSelect = function ($current) {
    $h = '<select name="icon">';
    foreach (apply_icons() as $ic) {
        $h .= '<option value="' . e($ic) . '"' . ($current === $ic ? ' selected' : '') . '>' . e(t('ico_' . $ic)) . '</option>';
    }
    return $h . '</select>';
};
?>
<main>
  <div class="wrap page">
    <div class="page-head">
      <span class="auth-badge"><?= page_icon('pen') ?><?= e(t('ad_badge')) ?></span>
      <div class="head-row">
        <div><h1><?= e($isNew ? t($isCat ? 'aa_new_cat' : 'aa_new_role') : ($isCat ? $cat['title_en'] : $role['title_en'])) ?></h1></div>
        <div class="head-links">
          <?php if ($role): ?><a class="link-soft" href="apply?role=<?= e($role['slug']) ?>"><?= e(t('ar_view_page')) ?> →</a><?php endif; ?>
          <a class="link-soft" href="admin?tab=apply">← <?= e(t('ad_tab_apply')) ?></a>
        </div>
      </div>
    </div>

    <?= flash_html() ?>

<?php if ($isCat): ?>
    <form class="panel ar-cat" method="post" action="<?= e($self) ?>">
      <?= aa_hidden($csrf, 'cat_save') ?>
      <h4 class="ptitle"><?= e(t('aa_cat_details')) ?></h4>
      <div class="ar-grid">
        <div class="fld"><small><?= e(t('ar_title')) ?> (EN) *</small><input type="text" name="title_en" maxlength="60" required value="<?= e($v['title_en']) ?>"></div>
        <div class="fld"><small><?= e(t('ar_title')) ?> (IT)</small><input type="text" name="title_it" maxlength="60" value="<?= e($v['title_it']) ?>"></div>
        <div class="fld"><small><?= e(t('aa_short')) ?> (EN)</small><input type="text" name="short_en" maxlength="30" value="<?= e($v['short_en']) ?>" placeholder="Staff"></div>
        <div class="fld"><small><?= e(t('aa_short')) ?> (IT)</small><input type="text" name="short_it" maxlength="30" value="<?= e($v['short_it']) ?>"></div>
        <div class="fld"><small><?= e(t('aa_tag')) ?> (EN)</small><input type="text" name="tag_en" maxlength="40" value="<?= e($v['tag_en']) ?>"></div>
        <div class="fld"><small><?= e(t('aa_tag')) ?> (IT)</small><input type="text" name="tag_it" maxlength="40" value="<?= e($v['tag_it']) ?>"></div>
        <div class="fld"><small><?= e(t('ar_sub')) ?> (EN)</small><textarea name="desc_en" rows="3" maxlength="300"><?= e($v['desc_en']) ?></textarea></div>
        <div class="fld"><small><?= e(t('ar_sub')) ?> (IT)</small><textarea name="desc_it" rows="3" maxlength="300"><?= e($v['desc_it']) ?></textarea></div>
        <div class="fld"><small><?= e(t('ar_icon')) ?></small><?= $iconSelect($v['icon']) ?></div>
        <div class="fld"><small><?= e(t('ar_order')) ?></small><input type="number" name="sort" min="0" max="9999" value="<?= e($v['sort']) ?>" inputmode="numeric" placeholder="<?= e(t('ar_order_auto')) ?>"></div>
      </div>
      <p class="hint"><?= e(t('aa_cat_hint')) ?></p>
      <div class="actions"><button class="btn sm" type="submit"><?= e(t('ad_news_save')) ?></button></div>
    </form>

<?php else: ?>
    <form class="panel ar-cat" method="post" action="<?= e($self) ?>">
      <?= aa_hidden($csrf, 'role_save') ?>
      <h4 class="ptitle"><?= e(t('aa_role_details')) ?></h4>
      <div class="ar-grid">
        <div class="fld"><small><?= e(t('ar_title')) ?> (EN) *</small><input type="text" name="title_en" maxlength="60" required value="<?= e($v['title_en']) ?>"></div>
        <div class="fld"><small><?= e(t('ar_title')) ?> (IT)</small><input type="text" name="title_it" maxlength="60" value="<?= e($v['title_it']) ?>"></div>
        <div class="fld"><small><?= e(t('aa_tag')) ?> (EN)</small><input type="text" name="tag_en" maxlength="40" value="<?= e($v['tag_en']) ?>"></div>
        <div class="fld"><small><?= e(t('aa_tag')) ?> (IT)</small><input type="text" name="tag_it" maxlength="40" value="<?= e($v['tag_it']) ?>"></div>
        <div class="fld"><small><?= e(t('ar_sub')) ?> (EN)</small><textarea name="desc_en" rows="3" maxlength="400"><?= e($v['desc_en']) ?></textarea></div>
        <div class="fld"><small><?= e(t('ar_sub')) ?> (IT)</small><textarea name="desc_it" rows="3" maxlength="400"><?= e($v['desc_it']) ?></textarea></div>
        <div class="fld"><small><?= e(t('aa_req')) ?> (EN)</small><textarea name="req_en" rows="7" maxlength="3400"><?= e($v['req_en']) ?></textarea></div>
        <div class="fld"><small><?= e(t('aa_req')) ?> (IT)</small><textarea name="req_it" rows="7" maxlength="3400"><?= e($v['req_it']) ?></textarea></div>
        <div class="fld"><small><?= e(t('aa_category')) ?></small>
          <select name="cat_id"><?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) $v['cat_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['title_en']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="fld"><small><?= e(t('ar_icon')) ?></small><?= $iconSelect($v['icon']) ?></div>
        <div class="fld"><small><?= e(t('ar_order')) ?></small><input type="number" name="sort" min="0" max="9999" value="<?= e($v['sort']) ?>" inputmode="numeric" placeholder="<?= e(t('ar_order_auto')) ?>"></div>
        <div class="fld"><small>&nbsp;</small><label class="chk"><input type="checkbox" name="active" value="1"<?= (int) $v['active'] === 1 ? ' checked' : '' ?>><span><?= e(t('aa_active')) ?></span></label></div>
      </div>
      <p class="hint"><?= e(t('aa_role_hint')) ?></p>
      <div class="actions"><button class="btn sm" type="submit"><?= e(t('ad_news_save')) ?></button></div>
    </form>

<?php if ($role): ?>
    <div class="sec-title ar-title" id="questions"><div><h3><?= e(t('aa_questions_title')) ?></h3><p><?= e(t('aa_questions_hint')) ?></p></div></div>
    <div class="panel ar-sec">
      <div class="ar-items">
<?php foreach ($questions as $i => $q): $qid = (int) $q['id']; ?>
        <form class="ar-item" method="post" action="<?= e($self) ?>">
          <?= aa_hidden($csrf, 'q_save', ['qid' => $qid]) ?>
          <span class="rnum"><?= $i + 1 ?></span>
          <div class="ar-item-body">
            <div class="ar-grid">
              <div class="fld"><small><?= e(t('aa_label')) ?> (EN) *</small><input type="text" name="label_en" maxlength="160" required value="<?= e($q['label_en']) ?>"></div>
              <div class="fld"><small><?= e(t('aa_label')) ?> (IT)</small><input type="text" name="label_it" maxlength="160" value="<?= e($q['label_it']) ?>"></div>
              <div class="fld"><small><?= e(t('aa_help')) ?> (EN)</small><input type="text" name="help_en" maxlength="200" value="<?= e($q['help_en']) ?>"></div>
              <div class="fld"><small><?= e(t('aa_help')) ?> (IT)</small><input type="text" name="help_it" maxlength="200" value="<?= e($q['help_it']) ?>"></div>
            </div>
            <div class="actions aa-qrow">
              <div class="fld"><small><?= e(t('ar_kind')) ?></small>
                <select name="type"><option value="textarea"<?= $q['type'] === 'text' ? '' : ' selected' ?>><?= e(t('aa_type_long')) ?></option><option value="text"<?= $q['type'] === 'text' ? ' selected' : '' ?>><?= e(t('aa_type_short')) ?></option></select>
              </div>
              <div class="fld fld-prio"><small><?= e(t('aa_min')) ?></small><input type="number" name="min" min="0" max="500" value="<?= (int) $q['min'] ?>"></div>
              <div class="fld fld-prio"><small><?= e(t('aa_max')) ?></small><input type="number" name="max" min="10" max="800" value="<?= (int) $q['max'] ?>"></div>
              <div class="fld fld-prio"><small><?= e(t('ar_order')) ?></small><input type="number" name="sort" min="0" max="9999" value="<?= (int) $q['sort'] ?>"></div>
              <label class="chk"><input type="checkbox" name="required" value="1"<?= (int) $q['required'] === 1 ? ' checked' : '' ?>><span><?= e(t('aa_required')) ?></span></label>
              <button class="btn sm" type="submit"><?= e(t('ad_news_save')) ?></button>
              <button class="btn sm danger" type="submit" form="dq<?= $qid ?>"><?= e(t('ad_news_delete')) ?></button>
            </div>
          </div>
        </form>
        <form id="dq<?= $qid ?>" method="post" action="<?= e($self) ?>" data-confirm="<?= e(t('aa_delete_q_q')) ?>"><?= aa_hidden($csrf, 'q_delete', ['qid' => $qid]) ?></form>
<?php endforeach; ?>

        <form class="ar-item ar-add" method="post" action="<?= e($self) ?>">
          <?= aa_hidden($csrf, 'q_add') ?>
          <span class="rnum">+</span>
          <div class="ar-item-body">
            <div class="ar-grid">
              <div class="fld"><small><?= e(t('aa_label')) ?> (EN) *</small><input type="text" name="label_en" maxlength="160" required></div>
              <div class="fld"><small><?= e(t('aa_label')) ?> (IT)</small><input type="text" name="label_it" maxlength="160"></div>
              <div class="fld"><small><?= e(t('aa_help')) ?> (EN)</small><input type="text" name="help_en" maxlength="200"></div>
              <div class="fld"><small><?= e(t('aa_help')) ?> (IT)</small><input type="text" name="help_it" maxlength="200"></div>
            </div>
            <div class="actions aa-qrow">
              <div class="fld"><small><?= e(t('ar_kind')) ?></small>
                <select name="type"><option value="textarea"><?= e(t('aa_type_long')) ?></option><option value="text"><?= e(t('aa_type_short')) ?></option></select>
              </div>
              <div class="fld fld-prio"><small><?= e(t('aa_min')) ?></small><input type="number" name="min" min="0" max="500" value="0"></div>
              <div class="fld fld-prio"><small><?= e(t('aa_max')) ?></small><input type="number" name="max" min="10" max="800" value="700"></div>
              <label class="chk"><input type="checkbox" name="required" value="1" checked><span><?= e(t('aa_required')) ?></span></label>
              <button class="btn sm white" type="submit">+ <?= e(t('aa_add_q')) ?></button>
            </div>
          </div>
        </form>
      </div>
      <p class="hint"><?= e(t('aa_fixed_hint')) ?></p>
    </div>
<?php endif; ?>
<?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
