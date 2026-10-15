<?php
$page = 'home';
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/header.php';

$ip = $config['server_ip'];
$port = $config['bedrock_port'];
$stats = discord_stats($config);
$fmt = function ($n) {
    return $n === null ? '—' : number_format($n);
};

$icons = [
    'java'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg>',
    'mobile' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18h2"/></svg>',
    'pad'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="7" width="19" height="10" rx="5"/><path d="M8 10.5v3M6.5 12h3M16 11h.01M18 13h.01"/></svg>',
];
$platforms = [
    'java'   => ['Java (PC)',        'java',   'java',    's3_java',    false],
    'mobile' => ['Mobile',           'mobile', 'bedrock', 's3_mobile',  true],
    'ps'     => ['PlayStation',      'pad',    'bedrock', 's3_console', true],
    'xbox'   => ['Xbox',             'pad',    'bedrock', 's3_console', true],
    'switch' => ['Nintendo Switch',  'pad',    'bedrock', 's3_console', true],
];
?>
<main>
  <header class="hero wrap" id="home">
    <span class="pill"><i></i><span><?= e(t('pill')) ?></span></span>
    <img class="hero-logo" src="assets/img/logo.webp" alt="<?= e($config['site_name']) ?>" width="698" height="358">
    <p class="lead"><?= e(t('lead')) ?></p>
    <div class="cta">
      <button type="button" class="btn" data-copy>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="12" height="12" rx="2.5"/><path d="M5 15V6a2 2 0 0 1 2-2h8"/></svg>
        <span><?= e(t('play_now')) ?></span>
      </button>
      <a class="btn white" href="<?= e($config['discord_url']) ?>" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/></svg>
        <span><?= e(t('join_discord')) ?></span>
      </a>
    </div>
    <div class="ip-line"><span><?= e(t('server_ip')) ?></span><code><?= e($ip) ?></code></div>
  </header>

  <div class="wrap">
    <div class="banner">
      <div class="banner-art"><img src="assets/img/head.webp" alt="Aetherbound explorer with binoculars"></div>
      <div>
        <span class="tag"><i></i><span><?= e(t('tag')) ?></span></span>
        <h2><?= e(t('banner_title')) ?></h2>
        <p><?= e(t('banner_text')) ?></p>
        <div class="row">
          <a class="btn white" href="#play"><?= e(t('banner_a')) ?></a>
          <a class="btn" href="<?= e($config['discord_url']) ?>" target="_blank" rel="noopener"><?= e(t('banner_b')) ?></a>
        </div>
      </div>
    </div>
  </div>

  <section class="wrap" id="play">
    <div class="sec-head">
      <h2><?= e(t('play_title')) ?></h2>
      <p><?= e(t('play_sub')) ?></p>
    </div>
    <div class="tabs" role="tablist" id="tabs" aria-label="Platform">
      <?php foreach ($platforms as $id => $p): ?>
        <button type="button" class="tab" role="tab" data-tab="<?= e($id) ?>" aria-selected="<?= $id === 'java' ? 'true' : 'false' ?>"><?= $icons[$p[1]] ?><span><?= e($p[0]) ?></span></button>
      <?php endforeach; ?>
    </div>
    <div id="steps">
      <?php foreach ($platforms as $id => $p): ?>
        <?php $bedrock = ($p[2] === 'bedrock'); ?>
        <div class="steps" role="tabpanel" data-panel="<?= e($id) ?>"<?= $id === 'java' ? '' : ' hidden' ?>>
          <article class="step">
            <div class="num">1</div>
            <h3><?= e(t('s1_t')) ?></h3>
            <p><?= e(t('s1_p')) ?></p>
            <div class="slot">
              <button type="button" class="btn" data-copy><?= e($ip) ?></button>
              <?php if ($p[4]): ?><span class="chip mono"><?= e(t('port')) ?>: <?= e($port) ?></span><?php endif; ?>
            </div>
          </article>
          <article class="step">
            <div class="num">2</div>
            <h3><?= e(t('s2_t')) ?></h3>
            <p><?= e(t($bedrock ? 's2_bed' : 's2_java')) ?></p>
            <div class="slot"><span class="chip"><?= e(t($bedrock ? 'chip_bed' : 'chip_java')) ?></span></div>
          </article>
          <article class="step">
            <div class="num">3</div>
            <h3><?= e(t('s3_t')) ?></h3>
            <p><?= e(t($p[3])) ?></p>
            <div class="slot"><span class="chip"><?= e(t('chip_go')) ?></span></div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="wrap" id="community">
    <div class="sec-head">
      <h2><?= e(t('com_title')) ?></h2>
      <p><?= e(t('com_sub')) ?></p>
    </div>
    <div class="community">
      <img class="avatar" src="assets/img/head.webp" alt="" width="56" height="56">
      <h3><?= e($config['site_name']) ?></h3>
      <p><?= e(t('com_text')) ?></p>
      <div class="stats">
        <div class="stat"><strong><?= e($fmt($stats['online'])) ?></strong><span><i class="dot"></i><span><?= e(t('st_online')) ?></span></span></div>
        <div class="stat"><strong><?= e($fmt($stats['members'])) ?></strong><span><?= e(t('st_members')) ?></span></div>
      </div>
      <a class="btn" href="<?= e($config['discord_url']) ?>" target="_blank" rel="noopener">
        <span><?= e(t('join_discord')) ?></span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </a>
    </div>
    <div class="perks">
      <div class="perk">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.4"/><path d="M17 14.2c2.5 0 4 1.8 4 4.3"/></svg>
        <div><h3><?= e(t('p1_t')) ?></h3><p><?= e(t('p1_p')) ?></p></div>
      </div>
      <div class="perk">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4V5Z"/></svg>
        <div><h3><?= e(t('p2_t')) ?></h3><p><?= e(t('p2_p')) ?></p></div>
      </div>
    </div>
  </section>

  <div class="divider" aria-hidden="true"></div>

  <section class="wrap staff" id="staff">
    <div class="sec-head">
      <h2><?= e(t('staff_title')) ?></h2>
      <p><?= e(t('staff_sub')) ?></p>
    </div>
    <a class="btn" href="staff">
      <span><?= e(t('staff_btn')) ?></span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </a>
  </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
