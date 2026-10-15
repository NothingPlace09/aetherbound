<?php
$page = 'login';
$withBg = true;
require __DIR__ . '/includes/bootstrap.php';
ensure_session(true);

if (current_user()) {
    header('Location: profile');
    exit;
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) (isset($_POST['email']) ? $_POST['email'] : ''));
    $password = (string) (isset($_POST['password']) ? $_POST['password'] : '');

    if (!csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        $error = t('err_csrf');
    } elseif ($email === '' || $password === '') {
        $error = t('err_fields');
    } elseif (auth_blocked()) {
        $error = t('err_throttle');
    } else {
        try {
            $st = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
            $st->execute([$email]);
            $row = $st->fetch();
            $hash = $row ? $row['password_hash'] : password_hash('placeholder', PASSWORD_DEFAULT);
            if (password_verify($password, $hash) && $row) {
                login_user($row['id']);
                header('Location: profile');
                exit;
            }
            auth_fail();
            $error = t('err_login');
        } catch (Exception $ex) {
            $error = t('err_db');
        }
    }
}

$pageTitle = t('nav_login');
require __DIR__ . '/includes/header.php';
$discordShort = preg_replace('~^https?://~', '', $config['discord_url']);
?>
<main>
  <section class="auth wrap">
    <span class="auth-badge">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.5c0 4.6 3.1 8 7.5 9.5 4.4-1.5 7.5-4.9 7.5-9.5V6L12 3Z"/></svg>
      <?= e(t('nav_login')) ?>
    </span>
    <h1><?= e(t('auth_login_title')) ?></h1>
    <p class="auth-sub"><?= e(t('auth_login_sub')) ?></p>

    <div class="auth-card">
      <?php if ($error): ?><div class="auth-error" role="alert"><?= e($error) ?></div><?php endif; ?>

      <button type="button" class="btn-discord" data-soon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="7" width="19" height="10" rx="5"/><path d="M8 10.5v3M6.5 12h3M16 11h.01M18 13h.01"/></svg>
        <?= e(t('auth_discord_login')) ?>
      </button>
      <div class="or"><span><?= e(t('auth_or')) ?></span></div>

      <form method="post" action="login" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label for="email"><?= e(t('f_email')) ?></label>
        <input id="email" name="email" type="email" autocomplete="email" required value="<?= e($email) ?>">
        <label for="password"><?= e(t('f_password')) ?></label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button type="submit" class="btn block"><?= e(t('nav_login')) ?></button>
      </form>

      <div class="forgot">
        <div class="forgot-head">
          <span class="forgot-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="m11 12 8-8M16 7l3 3"/></svg></span>
          <strong><?= e(t('forgot_t')) ?></strong>
        </div>
        <div class="forgot-body">
          <p><?= e(t('forgot_p')) ?></p>
          <a class="ticket" href="<?= e($config['discord_url']) ?>" target="_blank" rel="noopener">
            <span class="ticket-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="7" width="19" height="10" rx="5"/><path d="M8 10.5v3M6.5 12h3M16 11h.01M18 13h.01"/></svg></span>
            <span class="ticket-tx"><b><?= e(t('forgot_btn')) ?></b><small><?= e($discordShort) ?></small></span>
            <svg class="ticket-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </a>
        </div>
      </div>

      <p class="auth-foot"><?= e(t('no_account')) ?> <a href="register"><?= e(t('nav_signup')) ?></a></p>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
