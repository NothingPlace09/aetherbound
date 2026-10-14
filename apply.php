<?php
// Staff applications: list of roles -> role page (requirements + rules) -> 4-step form -> Discord webhook.
// The text of the roles is in includes/apply_data.php, the sending in includes/apply.php.
$page = 'apply';
$withBg = true;
require __DIR__ . '/includes/bootstrap.php';
ensure_session(true);

$user = current_user();
$csrf = csrf_token();
$roles = apply_roles();
$role = isset($_GET['role']) && is_string($_GET['role']) ? apply_find_role($_GET['role']) : null;
$open = apply_webhook_url() !== '';
$errors = [];
$old = [];
$formError = '';
$doneCode = '';

// "Application sent" page
if (isset($_GET['done']) && is_string($_GET['done']) && preg_match('/^[a-z0-9]{4}$/', $_GET['done']) && $user) {
    $st = db()->prepare('SELECT code FROM applications WHERE code = ? AND user_id = ?');
    $st->execute([$_GET['done'], (int) $user['id']]);
    $doneCode = (string) $st->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role) {
    if (!csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        $formError = t('err_csrf');
    } elseif (!$user) {
        $formError = t('ap_login_needed');
    } else {
        list($errors, $old) = apply_validate($role);
        if (!$errors) {
            list($ok, $res) = apply_submit($role, $old, $user);
            if ($ok) {
                header('Location: apply.php?done=' . $res);
                exit;
            }
            $formError = $res;
        }
    }
    // keep what the person typed
    if (!$old) {
        $old = ['nickname' => apply_field('nickname', 16), 'premium' => '', 'age' => '', 'tz' => '', 'avail' => [], 'about' => '', 'answers' => []];
    }
}
$val = function ($k, $default = '') use ($old) { return isset($old[$k]) ? $old[$k] : $default; };
$ans = function ($id) use ($old) { return isset($old['answers'][$id]) ? $old['answers'][$id] : ''; };
$err = function ($k) use ($errors) {
    return isset($errors[$k]) ? '<p class="ap-err" data-err>' . e($errors[$k]) . '</p>' : '';
};

$extraScripts = ['assets/js/apply.js'];
$pageTitle = $role ? ap_t($role['title']) : t('nav_apply');
require __DIR__ . '/includes/header.php';
?>
<main>
  <div class="wrap">
    <?= page_banner('apply_badge', 'apply_title', 'apply_sub', $config['site_name'], 'pen') ?>

<?php if ($doneCode !== ''): ?>
    <div class="panel ap-done">
      <span class="ap-ic big ok"><?= page_icon('check', 30) ?></span>
      <h2><?= e(t('ap_done_title')) ?></h2>
      <p><?= e(t('ap_done_text')) ?></p>
      <p class="ap-code"><?= e(t('ap_done_id')) ?> <strong><?= e($doneCode) ?></strong></p>
      <a class="btn sm white" href="apply.php"><?= e(t('ap_done_back')) ?></a>
    </div>

<?php elseif (!$role): ?>
    <div class="ap-head">
      <span class="ap-ic"><?= page_icon('users', 24) ?></span>
      <div><h2><?= e(t('ap_list_title')) ?></h2><p><?= e(t('ap_list_sub')) ?></p></div>
    </div>
    <?php if (!$open): ?><div class="flash warn"><?= e(t('ap_closed')) ?></div><?php endif; ?>

    <div class="ap-grid">
<?php foreach ($roles as $r): ?>
      <article class="panel ap-card">
        <span class="ap-ic"><?= page_icon($r['icon'], 24) ?></span>
        <h3><?= e(ap_t($r['title'])) ?></h3>
        <span class="badge ap-tag"><?= e(ap_t($r['tag'])) ?></span>
        <div class="rtext md ap-desc"><?= md_render(ap_t($r['desc'])) ?></div>
        <p class="ap-sub"><?= e(t('ap_requirements')) ?></p>
        <ul class="ap-req">
<?php foreach ($r['req'] as $q): ?>
          <li><?= page_icon('star', 16) ?><span class="md"><?= md_render(ap_t($q)) ?></span></li>
<?php endforeach; ?>
        </ul>
        <a class="btn" href="apply.php?role=<?= e($r['id']) ?>"><?= e(sprintf(t('ap_apply_as'), ap_t($r['title']))) ?></a>
      </article>
<?php endforeach; ?>
    </div>

<?php else: ?>
    <div class="ap-top">
      <a class="btn sm white" href="apply.php"><?= page_icon('back', 14) ?> <?= e(t('ap_back')) ?></a>
      <div class="ap-role"><span class="ap-ic sm"><?= page_icon($role['icon'], 18) ?></span><strong><?= e(ap_t($role['title'])) ?></strong></div>
    </div>

    <details class="panel ap-info" open>
      <summary><?= page_icon('info', 22) ?><span><?= e(t('ap_info_title')) ?></span></summary>
      <div class="ap-box">
        <h4><?= page_icon('star', 18) ?> <?= e(t('ap_requirements')) ?></h4>
        <ul class="ap-req">
<?php foreach ($role['req'] as $q): ?>
          <li><?= page_icon('star', 16) ?><span class="md"><?= md_render(ap_t($q)) ?></span></li>
<?php endforeach; ?>
        </ul>
      </div>
      <div class="ap-box">
        <h4><?= page_icon('alert', 18) ?> <?= e(t('ap_rules')) ?></h4>
        <ul class="ap-req warn">
<?php foreach (apply_rules() as $q): ?>
          <li><?= page_icon('alert', 16) ?><span class="md"><?= md_render(ap_t($q)) ?></span></li>
<?php endforeach; ?>
        </ul>
      </div>
    </details>

<?php if (!$user): ?>
    <div class="panel ap-login">
      <p><?= e(t('ap_login_needed')) ?></p>
      <a class="btn" href="login.php"><?= e(t('nav_login')) ?></a>
    </div>
<?php elseif (!$open): ?>
    <div class="flash warn"><?= e(t('ap_closed')) ?></div>
<?php else: ?>
    <form class="panel ap-form" method="post" action="apply.php?role=<?= e($role['id']) ?>" data-apply data-avail-error="<?= e(t('ap_e_avail')) ?>" novalidate>
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <h2><?= e(t('ap_form_title')) ?> <span class="ap-hl">&mdash; <?= e(ap_t($role['title'])) ?></span></h2>
      <div class="ap-prog" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
      <?php if ($formError !== ''): ?><div class="flash err" role="alert"><?= e($formError) ?></div><?php endif; ?>

      <!-- Step 1 -->
      <section class="ap-step" data-step="1">
        <h3><?= e(t('ap_s1_title')) ?></h3>
        <p class="ap-lead"><?= e(t('ap_s1_sub')) ?></p>

        <div class="ap-q">
          <label for="f-nickname"><?= e(t('ap_nick')) ?> <b>*</b></label>
          <p class="ap-help"><?= e(t('ap_nick_help')) ?></p>
          <input id="f-nickname" name="nickname" type="text" maxlength="16" required pattern="[A-Za-z0-9_]{3,16}" autocomplete="off" value="<?= e($val('nickname', isset($_POST['nickname']) ? '' : $user['username'])) ?>">
          <?= $err('nickname') ?>
        </div>

        <fieldset class="ap-q">
          <legend><?= e(t('ap_premium')) ?> <b>*</b></legend>
          <p class="ap-help"><?= e(t('ap_premium_help')) ?></p>
          <div class="ap-choice">
            <label class="ap-chip"><input type="radio" name="premium" value="yes" required<?= $val('premium') === 'yes' ? ' checked' : '' ?>><?= page_icon('check', 16) ?><span><?= e(t('ap_yes')) ?></span></label>
            <label class="ap-chip"><input type="radio" name="premium" value="no"<?= $val('premium') === 'no' ? ' checked' : '' ?>><?= page_icon('x', 16) ?><span><?= e(t('ap_no')) ?></span></label>
          </div>
          <?= $err('premium') ?>
        </fieldset>

        <div class="ap-q">
          <label for="f-age"><?= e(t('ap_age')) ?> <b>*</b></label>
          <p class="ap-help"><?= e(sprintf(t('ap_age_help'), (int) $config['apply_min_age'])) ?></p>
          <input id="f-age" name="age" type="number" inputmode="numeric" min="<?= (int) $config['apply_min_age'] ?>" max="99" required value="<?= e($val('age')) ?>">
          <?= $err('age') ?>
        </div>

        <div class="ap-q">
          <label for="f-tz"><?= e(t('ap_tz')) ?> <b>*</b></label>
          <p class="ap-help"><?= e(t('ap_tz_help')) ?></p>
          <input id="f-tz" name="tz" type="text" list="tzlist" required autocomplete="off" placeholder="Europe/Rome" value="<?= e($val('tz')) ?>">
          <datalist id="tzlist"><?php foreach (timezone_identifiers_list() as $tz): ?><option value="<?= e($tz) ?>"><?php endforeach; ?></datalist>
          <?= $err('tz') ?>
        </div>
      </section>

      <!-- Step 2 -->
      <section class="ap-step" data-step="2">
        <h3><?= e(t('ap_s2_title')) ?></h3>
        <p class="ap-lead"><?= e(t('ap_avail_help')) ?></p>
        <div class="ap-q" data-avail>
<?php $slotIcon = ['morning' => 'bell', 'afternoon' => 'flame', 'evening' => 'star', 'never' => 'x'];
foreach (APPLY_DAYS as $d => $dayName): $picked = isset($old['avail'][$d]) ? $old['avail'][$d] : []; ?>
          <fieldset class="ap-day">
            <legend><?= e(t('ap_day_' . $d)) ?></legend>
            <div class="ap-slots">
<?php foreach (APPLY_SLOTS as $s => $slotName): ?>
              <label class="ap-chip<?= $s === 'never' ? ' never' : '' ?>"><input type="checkbox" name="avail[<?= $d ?>][]" value="<?= $s ?>"<?= in_array($s, $picked, true) ? ' checked' : '' ?>><?= page_icon($slotIcon[$s], 16) ?><span><?= e(t('ap_slot_' . $s)) ?></span></label>
<?php endforeach; ?>
            </div>
          </fieldset>
<?php endforeach; ?>
          <?= $err('avail') ?>
        </div>
      </section>

      <!-- Step 3 -->
      <section class="ap-step" data-step="3">
        <h3><?= e(t('ap_s3_title')) ?></h3>
        <p class="ap-lead"><?= e(t('ap_s3_sub')) ?></p>

        <div class="ap-q">
          <label for="f-about"><?= e(t('ap_about')) ?> <b>*</b></label>
          <p class="ap-help"><?= e(t('ap_about_help')) ?></p>
          <textarea id="f-about" name="about" rows="5" maxlength="800" minlength="30" required><?= e($val('about')) ?></textarea>
          <p class="ap-min"><?= e(sprintf(t('ap_min_chars'), 30)) ?></p>
          <?= $err('about') ?>
        </div>

<?php foreach ($role['questions'] as $q): $fid = 'f-q-' . $q['id']; ?>
        <div class="ap-q">
          <label for="<?= e($fid) ?>"><?= e(ap_t($q['label'])) ?><?= $q['required'] ? ' <b>*</b>' : '' ?></label>
<?php if (!empty($q['help'])): ?><p class="ap-help"><?= e(ap_t($q['help'])) ?></p><?php endif; ?>
<?php if ($q['type'] === 'textarea'): ?>
          <textarea id="<?= e($fid) ?>" name="q_<?= e($q['id']) ?>" rows="4" maxlength="<?= (int) $q['max'] ?>"<?= $q['min'] ? ' minlength="' . (int) $q['min'] . '"' : '' ?><?= $q['required'] ? ' required' : '' ?>><?= e($ans($q['id'])) ?></textarea>
<?php else: ?>
          <input id="<?= e($fid) ?>" name="q_<?= e($q['id']) ?>" type="text" maxlength="<?= (int) $q['max'] ?>"<?= $q['min'] ? ' minlength="' . (int) $q['min'] . '"' : '' ?><?= $q['required'] ? ' required' : '' ?> value="<?= e($ans($q['id'])) ?>">
<?php endif; ?>
<?php if ($q['min']): ?><p class="ap-min"><?= e(sprintf(t('ap_min_chars'), (int) $q['min'])) ?></p><?php endif; ?>
          <?= $err('q_' . $q['id']) ?>
        </div>
<?php endforeach; ?>
      </section>

      <!-- Step 4 -->
      <section class="ap-step" data-step="4">
        <h3><?= e(t('ap_s4_title')) ?></h3>
        <p class="ap-lead"><?= e(t('ap_s4_sub')) ?></p>
        <dl class="ap-review" data-review></dl>
        <label class="ap-confirm"><input type="checkbox" name="confirm" value="1" required<?= !empty($old['confirm']) ? ' checked' : '' ?>><span><?= e(t('ap_confirm')) ?></span></label>
        <?= $err('confirm') ?>
      </section>

      <div class="ap-nav">
        <button type="button" class="btn sm ghost" data-prev><?= page_icon('back', 14) ?> <?= e(t('ap_prev')) ?></button>
        <button type="button" class="btn" data-next><?= e(t('ap_next')) ?> <?= page_icon('next', 14) ?></button>
        <button type="submit" class="btn" data-submit data-sending="<?= e(t('ap_sending')) ?>"><?= e(t('ap_submit')) ?></button>
      </div>
    </form>
<?php endif; ?>
<?php endif; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
