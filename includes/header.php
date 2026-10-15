<?php
$title = isset($pageTitle) && $pageTitle !== '' ? $pageTitle . ' · ' . $config['site_name'] : $config['site_name'];
$withBg = isset($withBg) ? $withBg : ($page === 'home');
$user = current_user();
?>
<!doctype html>
<html lang="<?= e($lang) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e(t('lead')) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e(t('lead')) ?>">
<meta property="og:type" content="website">
<meta name="theme-color" content="#130a1b">
<link rel="icon" type="image/png" href="assets/img/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;700&family=JetBrains+Mono:wght@600;700&family=Lilita+One&display=swap">
<link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body>
<script>try{if(sessionStorage.getItem("aeth_pre")){document.documentElement.classList.add("no-preload");}}catch(e){}</script>
<noscript><style>#preloader{display:none}</style></noscript>
<div id="preloader" aria-hidden="true">
  <div class="pre-core">
    <img src="assets/img/logo.webp" alt="" width="698" height="358">
    <div class="pre-bar"><i></i></div>
  </div>
</div>

<?php if ($withBg): ?>
<div class="bg-top<?= $page === 'home' ? '' : ' sub' ?>" aria-hidden="true"><div class="bg-img" id="bgimg"></div><div class="bg-shade"></div></div>
<?php endif; ?>
<canvas id="embers" aria-hidden="true"></canvas>
<div class="progress" id="progress" aria-hidden="true"></div>

<div class="nav-wrap" id="navwrap">
  <div class="nav-box" id="navbox">
    <nav class="nav" id="nav" aria-label="Main">
      <a class="brand" href="home" aria-label="<?= e($config['site_name']) ?>"><img class="avatar" src="assets/img/head.webp" alt="" width="34" height="34"><span>AETHER<b>BOUND</b></span></a>
      <div class="links" id="links">
        <?php foreach ($config['pages'] as $key => $item): ?>
          <a href="<?= e($item[0]) ?>"<?= $key === $page ? ' aria-current="page"' : '' ?>><?= e(t($item[1])) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="nav-right">
        <div class="langmenu" id="langmenu">
          <button type="button" class="lang-btn" id="langbtn" aria-haspopup="listbox" aria-expanded="false" aria-controls="langlist" aria-label="Language">
            <span><?= flag($lang) ?></span><span><?= e($languages[$lang]['code']) ?></span>
            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
          </button>
        </div>
        <?php if ($user): ?>
          <a class="userchip" href="profile" title="<?= e($user['username']) ?>"<?= $page === 'profile' ? ' aria-current="page"' : '' ?>><?= avatar_img($user, 24) ?><span><?= e($user['username']) ?></span></a>
          <?php if ($user['role'] === 'admin'): ?><a class="login" href="admin"<?= $page === 'admin' ? ' aria-current="page"' : '' ?>><?= e(t('nav_admin')) ?></a><?php endif; ?>
          <a class="login" href="logout?t=<?= e(csrf_token()) ?>"><?= e(t('nav_logout')) ?></a>
        <?php else: ?>
          <a class="login" href="login"<?= $page === 'login' ? ' aria-current="page"' : '' ?>><?= e(t('nav_login')) ?></a>
        <?php endif; ?>
        <button type="button" class="burger" id="burger" aria-label="Menu" aria-expanded="false" aria-controls="links">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
      </div>
    </nav>
    <ul class="lang-list" id="langlist" role="listbox" aria-label="Language">
      <?php foreach ($languages as $id => $info): ?>
        <li role="presentation"><a class="lang-opt" role="option" href="?lang=<?= e($id) ?>" aria-selected="<?= $id === $lang ? 'true' : 'false' ?>"><?= flag($id) ?><span><?= e($info['name']) ?></span></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
