<?php
$page = 'login';
$withBg = true;
require __DIR__ . '/includes/bootstrap.php';
ensure_session(true); // open the session before any output (CSRF token + throttle)

if (current_user()) {
    header('Location: profile.php');
    exit;
}

$error = null;
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) (isset($_POST['username']) ? $_POST['username'] : ''));
    $email = trim((string) (isset($_POST['email']) ? $_POST['email'] : ''));
    $password = (string) (isset($_POST['password']) ? $_POST['password'] : '');

    if (!csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        $error = t('err_csrf');
    } elseif ($username === '' || $email === '' || $password === '') {
        $error = t('err_fields');
    } elseif (!preg_match('/^[A-Za-z0-9_]{3,16}$/', $username)) {
        $error = t('err_username');
    } elseif (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('err_email');
    } elseif (!password_ok($password)) {
        $error = t('err_password');
    } elseif (auth_blocked()) {
        $error = t('err_throttle');
    } else {
        try {
            $pdo = db();
            $st = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
            $st->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
            login_user((int) $pdo->lastInsertId());
            header('Location: profile.php');
            exit;
        } catch (PDOException $ex) {
            auth_fail();
            // 23000 = UNIQUE constraint (username or email already used)
            $error = $ex->getCode() === '23000' ? t('err_taken') : t('err_db');
        } catch (Exception $ex) {
            $error = t('err_db');
        }
    }
}

$pageTitle = t('nav_signup');
require __DIR__ . '/includes/header.php';
?>
<main>
  <section class="auth wrap">
    <span class="auth-badge">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4.5 6v5.5c0 4.6 3.1 8 7.5 9.5 4.4-1.5 7.5-4.9 7.5-9.5V6L12 3Z"/></svg>
      <?= e(t('nav_signup')) ?>
    </span>
    <h1><?= e(t('auth_reg_title')) ?></h1>
    <p class="auth-sub"><?= e(t('auth_reg_sub')) ?></p>

    <div class="auth-card">
      <?php if ($error): ?><div class="auth-error" role="alert"><?= e($error) ?></div><?php endif; ?>

      <button type="button" class="btn-discord" data-soon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="7" width="19" height="10" rx="5"/><path d="M8 10.5v3M6.5 12h3M16 11h.01M18 13h.01"/></svg>
        <?= e(t('auth_discord_signup')) ?>
      </button>
      <div class="or"><span><?= e(t('auth_or')) ?></span></div>

      <form method="post" action="register.php" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label for="username"><?= e(t('f_username')) ?></label>
        <input id="username" name="username" type="text" autocomplete="username" maxlength="16" required value="<?= e($username) ?>">
        <label for="email"><?= e(t('f_email')) ?></label>
        <input id="email" name="email" type="email" autocomplete="email" maxlength="190" required value="<?= e($email) ?>">
        <label for="password"><?= e(t('f_password')) ?></label>
        <input id="password" name="password" type="password" autocomplete="new-password" maxlength="72" required>

        <div class="pwreq" data-pwreq>
          <h4><?= e(t('pw_req')) ?></h4>
          <ul>
            <li data-rule="len"><i></i><?= e(t('pw_len')) ?></li>
            <li data-rule="upper"><i></i><?= e(t('pw_upper')) ?></li>
            <li data-rule="num"><i></i><?= e(t('pw_num')) ?></li>
          </ul>
        </div>

        <button type="submit" class="btn block"><?= e(t('btn_create')) ?></button>
      </form>

      <p class="auth-foot"><?= e(t('have_account')) ?> <a href="login.php"><?= e(t('nav_login')) ?></a></p>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
