<?php
$page = 'profile';
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/skin.php';
ensure_session(true);
$me = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
    $anchor = '';
    $pdo = db();

    if (!csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        flash('err', 'err_csrf');
    } else {
        try {
            switch ($action) {
                case 'about':
                case 'signature':
                    $anchor = '#public';
                    $max = $action === 'about' ? (int) $config['about_max'] : 120;
                    $raw = isset($_POST[$action]) ? (string) $_POST[$action] : '';
                    if (mb_strlen($raw, 'UTF-8') > $max + 200) {
                        flash('err', 'err_text_long');
                        break;
                    }
                    $value = clean_text($raw, $max);
                    if ($action === 'signature') {
                        $value = str_replace("\n", ' ', $value);
                    }
                    $col = $action === 'about' ? 'bio' : 'signature';
                    $pdo->prepare('UPDATE users SET ' . $col . ' = ? WHERE id = ?')->execute([$value, $me['id']]);
                    flash('ok', 'ok_' . $action);
                    break;

                case 'about_remove':
                case 'signature_remove':
                    $anchor = '#public';
                    $col = $action === 'about_remove' ? 'bio' : 'signature';
                    $pdo->prepare('UPDATE users SET ' . $col . " = '' WHERE id = ?")->execute([$me['id']]);
                    flash('ok', 'ok_' . $action);
                    break;

                case 'skin_refresh':
                    $anchor = '#public';
                    skin_forget($me['username']);
                    skin_lookup($me['username'], true);
                    flash('ok', 'ok_skin_refresh');
                    break;

                case 'password':
                    $anchor = '#security';
                    $cur = (string) (isset($_POST['current_password']) ? $_POST['current_password'] : '');
                    $new = (string) (isset($_POST['new_password']) ? $_POST['new_password'] : '');
                    if ($cur === '' || $new === '') {
                        flash('err', 'err_fields');
                    } elseif (auth_blocked()) {
                        flash('err', 'err_throttle');
                    } else {
                        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
                        $st->execute([$me['id']]);
                        $row = $st->fetch();
                        if (!$row || !password_verify($cur, $row['password_hash'])) {
                            auth_fail();
                            flash('err', 'err_current_pw');
                        } elseif (!password_ok($new)) {
                            flash('err', 'err_password');
                        } else {
                            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                                ->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
                            session_regenerate_id(true);
                            flash('ok', 'ok_password');
                        }
                    }
                    break;
            }
        } catch (Exception $ex) {
            flash('err', 'err_db');
        }
    }
    redirect('profile' . $anchor);
}

$pageTitle = t('pf_title');
require __DIR__ . '/includes/header.php';
$csrf = csrf_token();
$discordShort = preg_replace('~^https?://~', '', $config['discord_url']);
$extraScripts = ['assets/js/editor.js'];
?>
<main>
  <div class="wrap page">
    <div class="page-head">
      <span class="auth-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
        <?= e(t('pf_badge')) ?>
      </span>
      <div class="head-row">
        <div>
          <h1><?= e(t('pf_title')) ?></h1>
          <p><?= e(t('pf_sub')) ?></p>
        </div>
        <a class="link-soft" href="user?u=<?= e(rawurlencode($me['username'])) ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
          <?= e(t('pf_view_public')) ?>
        </a>
      </div>
    </div>

    <?= flash_html() ?>

    <div class="panel pcard">
      <?= avatar_img($me, 112, 'pavatar') ?>
      <div class="pinfo">
        <div class="pname"><h2><?= e($me['username']) ?></h2><?= role_badges($me) ?></div>
        <div class="pmail"><?= e($me['email']) ?></div>
        <div class="pmeta">
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/></svg><?= e(t('pf_member_since')) ?> <?= e(fmt_date($me['created_at'])) ?></span>
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?= e(seen_text($me['last_seen_at'])) ?></span>
        </div>
      </div>
      <?php if ($me['role'] === 'admin'): ?>
        <a class="btn sm" href="admin"><?= e(t('nav_admin')) ?> →</a>
      <?php endif; ?>
    </div>

    <div class="sec-title" id="public">
      <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></span>
      <div><h3><?= e(t('pf_public')) ?></h3><p><?= e(t('pf_public_sub')) ?></p></div>
    </div>

    <form class="panel" method="post" action="profile#public">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <h4 class="ptitle"><?= e(t('pf_about')) ?></h4>
      <div class="mdx" data-mdx data-preview-url="preview" data-csrf="<?= e($csrf) ?>" data-loading="<?= e(t('ed_loading')) ?>" data-max="<?= (int) $config['about_max'] ?>">
        <div class="mdx-top" role="tablist">
          <button type="button" class="mdx-tab" role="tab" data-mdx-tab="write" aria-selected="true"><?= e(t('ed_write')) ?></button>
          <button type="button" class="mdx-tab" role="tab" data-mdx-tab="preview" aria-selected="false"><?= e(t('ed_preview')) ?></button>
        </div>
        <div class="mdx-tools" role="toolbar" aria-label="<?= e(t('ed_tools')) ?>">
          <button type="button" data-cmd="bold" title="<?= e(t('ed_bold')) ?> (Ctrl+B)" aria-label="<?= e(t('ed_bold')) ?>"><b>B</b></button>
          <button type="button" data-cmd="italic" title="<?= e(t('ed_italic')) ?> (Ctrl+I)" aria-label="<?= e(t('ed_italic')) ?>"><i>I</i></button>
          <button type="button" data-cmd="spoiler" title="<?= e(t('ed_spoiler')) ?>" aria-label="<?= e(t('ed_spoiler')) ?>">SPO</button>
          <button type="button" data-cmd="link" title="<?= e(t('ed_link')) ?>" aria-label="<?= e(t('ed_link')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg></button>
          <button type="button" data-cmd="code" title="<?= e(t('ed_code')) ?>" aria-label="<?= e(t('ed_code')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m8 8-4 4 4 4M16 8l4 4-4 4"/></svg></button>
          <button type="button" data-cmd="quote" title="<?= e(t('ed_quote')) ?>" aria-label="<?= e(t('ed_quote')) ?>"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 7h4v5c0 3-1.6 4.8-4 5.2v-2c1.2-.4 1.8-1.2 1.9-2.4H7V7Zm7 0h4v5c0 3-1.6 4.8-4 5.2v-2c1.2-.4 1.8-1.2 1.9-2.4H14V7Z"/></svg></button>
          <button type="button" data-cmd="list" title="<?= e(t('ed_list')) ?>" aria-label="<?= e(t('ed_list')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1" fill="currentColor"/><circle cx="4.5" cy="12" r="1" fill="currentColor"/><circle cx="4.5" cy="18" r="1" fill="currentColor"/></svg></button>
          <button type="button" data-cmd="center" title="<?= e(t('ed_center')) ?>" aria-label="<?= e(t('ed_center')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M5 18h14"/></svg></button>
          <button type="button" data-cmd="h1" title="<?= e(t('ed_heading')) ?> 1" aria-label="<?= e(t('ed_heading')) ?> 1">H1</button>
          <button type="button" data-cmd="h2" title="<?= e(t('ed_heading')) ?> 2" aria-label="<?= e(t('ed_heading')) ?> 2">H2</button>
          <button type="button" data-cmd="h3" title="<?= e(t('ed_heading')) ?> 3" aria-label="<?= e(t('ed_heading')) ?> 3">H3</button>
          <button type="button" class="mdx-color" data-cmd="color" title="<?= e(t('ed_color')) ?>" aria-label="<?= e(t('ed_color')) ?>"><i></i></button>
          <input type="color" class="sr" value="#ff6aa8" data-mdx-color tabindex="-1" aria-hidden="true">
          <button type="button" data-cmd="hr" title="<?= e(t('ed_hr')) ?>" aria-label="<?= e(t('ed_hr')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg></button>
        </div>
        <label class="sr" for="about"><?= e(t('pf_about')) ?></label>
        <textarea id="about" name="about" rows="8" maxlength="<?= (int) $config['about_max'] ?>" data-mdx-input><?= e($me['bio']) ?></textarea>
        <div class="mdx-preview ptext md" data-mdx-preview hidden></div>
      </div>
      <p class="hint"><span data-mdx-count></span> · <?= e(sprintf(t('pf_about_hint'), (int) $config['about_max'])) ?></p>
      <div class="actions">
        <button class="btn sm" type="submit" name="action" value="about"><?= e(t('btn_save')) ?></button>
        <button class="btn sm danger" type="submit" name="action" value="about_remove"><?= e(t('btn_remove')) ?></button>
      </div>
    </form>

    <div class="grid-2 public-grid">
      <form class="panel" method="post" action="profile#public">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <h4 class="ptitle"><?= e(t('pf_signature')) ?></h4>
        <label class="sr" for="signature"><?= e(t('pf_signature')) ?></label>
        <textarea id="signature" name="signature" rows="5" maxlength="120"><?= e($me['signature']) ?></textarea>
        <p class="hint"><?= e(t('pf_signature_hint')) ?></p>
        <div class="actions">
          <button class="btn sm" type="submit" name="action" value="signature"><?= e(t('btn_save')) ?></button>
          <button class="btn sm danger" type="submit" name="action" value="signature_remove"><?= e(t('btn_remove')) ?></button>
        </div>
      </form>

      <div class="panel">
        <h4 class="ptitle"><?= e(t('pf_skin')) ?></h4>
        <div class="photo-box">
          <small><?= e(t('pf_current_image')) ?></small>
          <?= avatar_img($me, 112, 'photo-big') ?>
          <strong class="skin-name"><?= e($me['username']) ?></strong>
        </div>
        <?php $skin = skin_lookup($me['username']); ?>
        <p class="skin-status <?= $skin['state'] ?>"><?= e(sprintf(t('sk_' . $skin['state']), $me['username'])) ?></p>
        <p class="hint"><?= e(t('pf_skin_hint')) ?></p>
        <form method="post" action="profile#public" class="actions">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <button class="btn sm white" type="submit" name="action" value="skin_refresh"><?= e(t('sk_refresh')) ?></button>
        </form>
      </div>
    </div>

    <div class="sec-title" id="security">
      <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.5c0 4.6 3.1 8 7.5 9.5 4.4-1.5 7.5-4.9 7.5-9.5V6L12 3Z"/></svg></span>
      <div><h3><?= e(t('pf_security')) ?></h3><p><?= e(t('pf_security_sub')) ?></p></div>
    </div>

    <form class="panel narrow" method="post" action="profile#security">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <h4 class="ptitle"><?= e(t('pf_change_pw')) ?></h4>
      <label for="current_password"><?= e(t('f_current_pw')) ?></label>
      <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
      <label for="password"><?= e(t('f_new_pw')) ?></label>
      <input id="password" name="new_password" type="password" autocomplete="new-password" maxlength="72" required>
      <div class="pwreq" data-pwreq>
        <h4><?= e(t('pw_req')) ?></h4>
        <ul>
          <li data-rule="len"><i></i><?= e(t('pw_len')) ?></li>
          <li data-rule="upper"><i></i><?= e(t('pw_upper')) ?></li>
          <li data-rule="num"><i></i><?= e(t('pw_num')) ?></li>
        </ul>
      </div>
      <div class="actions"><button class="btn sm" type="submit" name="action" value="password"><?= e(t('btn_save')) ?></button></div>
    </form>

    <div class="panel notice">
      <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg></span>
      <div class="notice-tx">
        <h4><?= e(t('pf_changes_t')) ?></h4>
        <p><?= e(t('pf_changes_p')) ?></p>
      </div>
      <a class="btn sm" href="<?= e($config['discord_url']) ?>" target="_blank" rel="noopener"><?= e(t('forgot_btn')) ?></a>
    </div>

    <div class="panel notice danger">
      <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
      <div class="notice-tx">
        <h4><?= e(t('pf_logout_t')) ?></h4>
        <p><?= e(t('pf_logout_p')) ?></p>
      </div>
      <a class="btn sm danger" href="logout?t=<?= e($csrf) ?>"><?= e(t('nav_logout')) ?></a>
    </div>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
