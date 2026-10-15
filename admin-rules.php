<?php
$page = 'admin';
require __DIR__ . '/includes/bootstrap.php';
ensure_session(true);
$me = require_admin();
$pdo = db();

$cid = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$cat = null;
if ($cid > 0) {
    $st = $pdo->prepare('SELECT * FROM rule_cats WHERE id = ?');
    $st->execute([$cid]);
    $cat = $st->fetch();
    if (!$cat) {
        flash('err', 'err_notfound');
        redirect('admin?tab=rules');
    }
}
$self = 'admin-rules' . ($cat ? '?id=' . $cid : '');

function rf($key, $max, $multi = false)
{
    $v = clean_text(isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '', $max);
    return $multi ? $v : str_replace("\n", ' ', $v);
}

function rf_order($key, $default)
{
    return isset($_POST[$key]) && $_POST[$key] !== '' ? max(0, min(9999, (int) $_POST[$key])) : $default;
}

function rf_kind()
{
    return isset($_POST['kind']) && $_POST['kind'] === 'table' ? 'table' : 'rules';
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
        if ($action === 'cat_save') {
            $title = rf('title_en', 60);
            $icon = isset($_POST['icon']) && in_array($_POST['icon'], rules_icons(), true) ? $_POST['icon'] : 'shield';
            if ($title === '') {
                flash('err', 'err_rules_title');
            } elseif ($cat) {
                $pdo->prepare('UPDATE rule_cats SET grp_en = ?, grp_it = ?, title_en = ?, title_it = ?, sub_en = ?, sub_it = ?, icon = ?, sort = ? WHERE id = ?')
                    ->execute([rf('grp_en', 40), rf('grp_it', 40), $title, rf('title_it', 60), rf('sub_en', 200), rf('sub_it', 200), $icon, rf_order('sort', (int) $cat['sort']), $cid]);
                rules_touch($pdo, $cid);
                flash('ok', 'ok_rules_saved');
            } else {
                $max = (int) $pdo->query('SELECT COALESCE(MAX(sort), 0) FROM rule_cats')->fetchColumn();
                $pdo->prepare("INSERT INTO rule_cats (slug, grp_en, grp_it, title_en, title_it, sub_en, sub_it, icon, sort, updated_at) VALUES (?,?,?,?,?,?,?,?,?, datetime('now'))")
                    ->execute([rules_slug($pdo, $title), rf('grp_en', 40), rf('grp_it', 40), $title, rf('title_it', 60), rf('sub_en', 200), rf('sub_it', 200), $icon, rf_order('sort', $max + 10)]);
                flash('ok', 'ok_rules_saved');
                redirect('admin-rules?id=' . (int) $pdo->lastInsertId());
            }
        } elseif ($cat) {
            $sid = isset($_POST['sec_id']) ? (int) $_POST['sec_id'] : 0;
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

            if ($action === 'sec_add') {
                $max = $pdo->prepare('SELECT COALESCE(MAX(sort), 0) FROM rule_secs WHERE cat_id = ?');
                $max->execute([$cid]);
                $pdo->prepare('INSERT INTO rule_secs (cat_id, kind, title_en, title_it, sort) VALUES (?,?,?,?,?)')
                    ->execute([$cid, rf_kind(), rf('title_en', 80), rf('title_it', 80), (int) $max->fetchColumn() + 10]);
                $anchor = '#s' . (int) $pdo->lastInsertId();
                rules_touch($pdo, $cid);
                flash('ok', 'ok_rules_saved');
            } elseif ($action === 'sec_save') {
                $n = $pdo->prepare('UPDATE rule_secs SET kind = ?, title_en = ?, title_it = ?, table_en = ?, table_it = ?, sort = ? WHERE id = ? AND cat_id = ?');
                $n->execute([rf_kind(), rf('title_en', 80), rf('title_it', 80), rf('table_en', 3000, true), rf('table_it', 3000, true), rf_order('sort', 0), $id, $cid]);
                $anchor = '#s' . $id;
                if ($n->rowCount()) {
                    rules_touch($pdo, $cid);
                }
                flash($n->rowCount() ? 'ok' : 'err', $n->rowCount() ? 'ok_rules_saved' : 'err_notfound');
            } elseif ($action === 'sec_delete') {
                $n = $pdo->prepare('DELETE FROM rule_secs WHERE id = ? AND cat_id = ?');
                $n->execute([$id, $cid]);
                if ($n->rowCount()) {
                    rules_touch($pdo, $cid);
                }
                flash($n->rowCount() ? 'ok' : 'err', $n->rowCount() ? 'ok_rules_deleted' : 'err_notfound');
            } elseif ($action === 'item_add' || $action === 'item_save') {
                $text = rf('text_en', 600, true);
                $anchor = '#s' . $sid;
                if ($text === '') {
                    flash('err', 'err_rules_text');
                } elseif ($action === 'item_add') {
                    $own = $pdo->prepare('SELECT 1 FROM rule_secs WHERE id = ? AND cat_id = ?');
                    $own->execute([$sid, $cid]);
                    if ($own->fetch()) {
                        $max = $pdo->prepare('SELECT COALESCE(MAX(sort), 0) FROM rule_items WHERE sec_id = ?');
                        $max->execute([$sid]);
                        $pdo->prepare('INSERT INTO rule_items (sec_id, title_en, title_it, text_en, text_it, sort) VALUES (?,?,?,?,?,?)')
                            ->execute([$sid, rf('title_en', 100), rf('title_it', 100), $text, rf('text_it', 600, true), (int) $max->fetchColumn() + 10]);
                        rules_touch($pdo, $cid);
                        flash('ok', 'ok_rules_saved');
                    } else {
                        flash('err', 'err_notfound');
                    }
                } else {
                    $n = $pdo->prepare('UPDATE rule_items SET title_en = ?, title_it = ?, text_en = ?, text_it = ?, sort = ?
                        WHERE id = ? AND sec_id IN (SELECT id FROM rule_secs WHERE cat_id = ?)');
                    $n->execute([rf('title_en', 100), rf('title_it', 100), $text, rf('text_it', 600, true), rf_order('sort', 0), $id, $cid]);
                    if ($n->rowCount()) {
                        rules_touch($pdo, $cid);
                    }
                    flash($n->rowCount() ? 'ok' : 'err', $n->rowCount() ? 'ok_rules_saved' : 'err_notfound');
                }
            } elseif ($action === 'item_delete') {
                $n = $pdo->prepare('DELETE FROM rule_items WHERE id = ? AND sec_id IN (SELECT id FROM rule_secs WHERE cat_id = ?)');
                $n->execute([$id, $cid]);
                $anchor = '#s' . $sid;
                if ($n->rowCount()) {
                    rules_touch($pdo, $cid);
                }
                flash($n->rowCount() ? 'ok' : 'err', $n->rowCount() ? 'ok_rules_deleted' : 'err_notfound');
            }
        }
    } catch (Exception $ex) {
        flash('err', 'err_db');
    }
    redirect($self . $anchor);
}

$secs = [];
$items = [];
if ($cat) {
    $st = $pdo->prepare('SELECT * FROM rule_secs WHERE cat_id = ? ORDER BY sort, id');
    $st->execute([$cid]);
    $secs = $st->fetchAll();
    $st = $pdo->prepare('SELECT i.* FROM rule_items i JOIN rule_secs s ON s.id = i.sec_id WHERE s.cat_id = ? ORDER BY i.sort, i.id');
    $st->execute([$cid]);
    foreach ($st as $r) {
        $items[(int) $r['sec_id']][] = $r;
    }
}
$v = $cat ? $cat : ['grp_en' => '', 'grp_it' => '', 'title_en' => '', 'title_it' => '', 'sub_en' => '', 'sub_it' => '', 'icon' => 'shield', 'sort' => ''];

function ar_hidden($csrf, $action, array $more = [])
{
    $h = '<input type="hidden" name="csrf" value="' . e($csrf) . '"><input type="hidden" name="action" value="' . e($action) . '">';
    foreach ($more as $k => $val) {
        $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($val) . '">';
    }
    return $h;
}

function ar_kind_select($kind)
{
    return '<select name="kind" aria-label="' . e(t('ar_kind')) . '">'
        . '<option value="rules"' . ($kind === 'table' ? '' : ' selected') . '>' . e(t('ar_kind_rules')) . '</option>'
        . '<option value="table"' . ($kind === 'table' ? ' selected' : '') . '>' . e(t('ar_kind_table')) . '</option></select>';
}

$csrf = csrf_token();
$pageTitle = t('ad_tab_rules');
require __DIR__ . '/includes/header.php';
?>
<main>
  <div class="wrap page">
    <div class="page-head">
      <span class="auth-badge"><?= page_icon('book') ?><?= e(t('ad_badge')) ?></span>
      <div class="head-row">
        <div><h1><?= e($cat ? $cat['title_en'] : t('ar_new_cat')) ?></h1></div>
        <div class="head-links">
          <?php if ($cat): ?><a class="link-soft" href="rules#r-<?= e($cat['slug']) ?>"><?= e(t('ar_view_page')) ?> →</a><?php endif; ?>
          <a class="link-soft" href="admin?tab=rules">← <?= e(t('ad_tab_rules')) ?></a>
        </div>
      </div>
    </div>

    <?= flash_html() ?>

    <form class="panel ar-cat" method="post" action="<?= e($self) ?>">
      <?= ar_hidden($csrf, 'cat_save') ?>
      <h4 class="ptitle"><?= e(t('ar_cat_details')) ?></h4>
      <div class="ar-grid">
        <div class="fld"><small><?= e(t('ar_title')) ?> (EN) *</small><input type="text" name="title_en" maxlength="60" required value="<?= e($v['title_en']) ?>"></div>
        <div class="fld"><small><?= e(t('ar_title')) ?> (IT)</small><input type="text" name="title_it" maxlength="60" value="<?= e($v['title_it']) ?>"></div>
        <div class="fld"><small><?= e(t('ar_sub')) ?> (EN)</small><input type="text" name="sub_en" maxlength="200" value="<?= e($v['sub_en']) ?>"></div>
        <div class="fld"><small><?= e(t('ar_sub')) ?> (IT)</small><input type="text" name="sub_it" maxlength="200" value="<?= e($v['sub_it']) ?>"></div>
        <div class="fld"><small><?= e(t('ar_group')) ?> (EN)</small><input type="text" name="grp_en" maxlength="40" value="<?= e($v['grp_en']) ?>" placeholder="Minecraft"></div>
        <div class="fld"><small><?= e(t('ar_group')) ?> (IT)</small><input type="text" name="grp_it" maxlength="40" value="<?= e($v['grp_it']) ?>" placeholder="Minecraft"></div>
        <div class="fld"><small><?= e(t('ar_icon')) ?></small>
          <select name="icon">
            <?php foreach (rules_icons() as $ic): ?><option value="<?= e($ic) ?>"<?= $v['icon'] === $ic ? ' selected' : '' ?>><?= e(t('ico_' . $ic)) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="fld"><small><?= e(t('ar_order')) ?></small><input type="number" name="sort" min="0" max="9999" value="<?= e($v['sort']) ?>" inputmode="numeric" placeholder="<?= e(t('ar_order_auto')) ?>"></div>
      </div>
      <p class="hint"><?= e(t('ar_cat_hint')) ?></p>
      <div class="actions"><button class="btn sm" type="submit"><?= e(t('ad_news_save')) ?></button></div>
    </form>

<?php if ($cat): ?>
    <div class="sec-title ar-title"><div><h3><?= e(t('ar_sections_title')) ?></h3><p><?= e(t('ar_format_hint')) ?></p></div></div>

<?php foreach ($secs as $s): $sid = (int) $s['id']; ?>
    <div class="panel ar-sec" id="s<?= $sid ?>">
      <form class="ar-secform" method="post" action="<?= e($self) ?>">
        <?= ar_hidden($csrf, 'sec_save', ['id' => $sid]) ?>
        <div class="ar-grid ar-grid-sec">
          <div class="fld"><small><?= e(t('ar_kind')) ?></small><?= ar_kind_select($s['kind']) ?></div>
          <div class="fld"><small><?= e(t('ar_sec_title')) ?> (EN)</small><input type="text" name="title_en" maxlength="80" value="<?= e($s['title_en']) ?>"></div>
          <div class="fld"><small><?= e(t('ar_sec_title')) ?> (IT)</small><input type="text" name="title_it" maxlength="80" value="<?= e($s['title_it']) ?>"></div>
          <div class="fld fld-prio"><small><?= e(t('ar_order')) ?></small><input type="number" name="sort" min="0" max="9999" value="<?= (int) $s['sort'] ?>" inputmode="numeric"></div>
        </div>
<?php if ($s['kind'] === 'table'): ?>
        <div class="ar-grid">
          <div class="fld"><small><?= e(t('ar_table')) ?> (EN)</small><textarea name="table_en" rows="6" maxlength="3000" spellcheck="false"><?= e($s['table_en']) ?></textarea></div>
          <div class="fld"><small><?= e(t('ar_table')) ?> (IT)</small><textarea name="table_it" rows="6" maxlength="3000" spellcheck="false"><?= e($s['table_it']) ?></textarea></div>
        </div>
        <p class="hint"><?= e(t('ar_table_hint')) ?></p>
<?php else: ?>
        <input type="hidden" name="table_en" value="<?= e($s['table_en']) ?>"><input type="hidden" name="table_it" value="<?= e($s['table_it']) ?>">
<?php endif; ?>
        <div class="actions">
          <button class="btn sm" type="submit"><?= e(t('ar_save_sec')) ?></button>
          <button class="btn sm danger" type="submit" form="dsec<?= $sid ?>"><?= e(t('ar_delete_sec')) ?></button>
        </div>
      </form>
      <form id="dsec<?= $sid ?>" method="post" action="<?= e($self) ?>" data-confirm="<?= e(t('ar_delete_sec_q')) ?>"><?= ar_hidden($csrf, 'sec_delete', ['id' => $sid]) ?></form>

<?php if ($s['kind'] !== 'table'): ?>
      <div class="ar-items">
<?php foreach (isset($items[$sid]) ? $items[$sid] : [] as $i => $r): $rid = (int) $r['id']; ?>
        <form class="ar-item" method="post" action="<?= e($self) ?>">
          <?= ar_hidden($csrf, 'item_save', ['id' => $rid, 'sec_id' => $sid]) ?>
          <span class="rnum"><?= $i + 1 ?></span>
          <div class="ar-item-body">
            <div class="ar-grid">
              <div class="fld"><small><?= e(t('ar_rule_title')) ?> (EN)</small><input type="text" name="title_en" maxlength="100" value="<?= e($r['title_en']) ?>"></div>
              <div class="fld"><small><?= e(t('ar_rule_title')) ?> (IT)</small><input type="text" name="title_it" maxlength="100" value="<?= e($r['title_it']) ?>"></div>
              <div class="fld"><small><?= e(t('ar_rule_text')) ?> (EN) *</small><textarea name="text_en" rows="3" maxlength="600" required><?= e($r['text_en']) ?></textarea></div>
              <div class="fld"><small><?= e(t('ar_rule_text')) ?> (IT)</small><textarea name="text_it" rows="3" maxlength="600"><?= e($r['text_it']) ?></textarea></div>
            </div>
            <div class="actions">
              <div class="fld fld-prio"><small><?= e(t('ar_order')) ?></small><input type="number" name="sort" min="0" max="9999" value="<?= (int) $r['sort'] ?>" inputmode="numeric"></div>
              <button class="btn sm" type="submit"><?= e(t('ad_news_save')) ?></button>
              <button class="btn sm danger" type="submit" form="ditem<?= $rid ?>"><?= e(t('ad_news_delete')) ?></button>
            </div>
          </div>
        </form>
        <form id="ditem<?= $rid ?>" method="post" action="<?= e($self) ?>" data-confirm="<?= e(t('ar_delete_rule_q')) ?>"><?= ar_hidden($csrf, 'item_delete', ['id' => $rid, 'sec_id' => $sid]) ?></form>
<?php endforeach; ?>

        <form class="ar-item ar-add" method="post" action="<?= e($self) ?>">
          <?= ar_hidden($csrf, 'item_add', ['sec_id' => $sid]) ?>
          <span class="rnum">+</span>
          <div class="ar-item-body">
            <div class="ar-grid">
              <div class="fld"><small><?= e(t('ar_rule_title')) ?> (EN)</small><input type="text" name="title_en" maxlength="100"></div>
              <div class="fld"><small><?= e(t('ar_rule_title')) ?> (IT)</small><input type="text" name="title_it" maxlength="100"></div>
              <div class="fld"><small><?= e(t('ar_rule_text')) ?> (EN) *</small><textarea name="text_en" rows="2" maxlength="600" required></textarea></div>
              <div class="fld"><small><?= e(t('ar_rule_text')) ?> (IT)</small><textarea name="text_it" rows="2" maxlength="600"></textarea></div>
            </div>
            <div class="actions"><button class="btn sm white" type="submit">+ <?= e(t('ar_add_rule')) ?></button></div>
          </div>
        </form>
      </div>
<?php endif; ?>
    </div>
<?php endforeach; ?>

    <form class="panel ar-sec ar-newsec" method="post" action="<?= e($self) ?>">
      <?= ar_hidden($csrf, 'sec_add') ?>
      <h4 class="ptitle">+ <?= e(t('ar_add_sec')) ?></h4>
      <div class="ar-grid ar-grid-sec">
        <div class="fld"><small><?= e(t('ar_kind')) ?></small><?= ar_kind_select('rules') ?></div>
        <div class="fld"><small><?= e(t('ar_sec_title')) ?> (EN)</small><input type="text" name="title_en" maxlength="80" required></div>
        <div class="fld"><small><?= e(t('ar_sec_title')) ?> (IT)</small><input type="text" name="title_it" maxlength="80"></div>
        <div class="fld"><small>&nbsp;</small><button class="btn sm" type="submit"><?= e(t('ar_add_sec')) ?></button></div>
      </div>
    </form>
<?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
