<footer>
  <div class="wrap">
    <div class="foot">
      <div>
        <div class="logo"><img class="avatar" src="assets/img/head.webp" alt="" width="38" height="38"><span>AETHER<b>BOUND</b>SMP</span></div>
        <p><?= e(t('foot_tag')) ?></p>
        <button type="button" class="btn sm" data-copy><?= e($config['server_ip']) ?></button>
      </div>
      <div>
        <h4><?= e(t('foot_nav')) ?></h4>
        <ul>
          <?php foreach ($config['pages'] as $key => $item): ?>
            <li><a href="<?= e($item[0]) ?>"<?= $key === $page ? ' aria-current="page"' : '' ?>><?= e(t($item[1])) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h4><?= e(t('foot_connect')) ?></h4>
        <ul>
          <li><a href="<?= e($config['discord_url']) ?>" target="_blank" rel="noopener">Discord</a></li>
        </ul>
      </div>
    </div>
    <div class="legal">© <?= date('Y') ?> <?= e($config['site_name']) ?> · Not affiliated with Mojang AB or Microsoft.</div>
  </div>
</footer>

<div id="toast" role="status" aria-live="polite"></div>

<script>
window.AETHER = <?= json_encode([
    'ip'       => $config['server_ip'],
    'copied'   => t('copied'),
    'copyfail' => t('copyfail'),
    'soon'     => t('discord_soon'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
</script>
<script src="<?= e(asset_url('assets/js/main.js')) ?>"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $src): ?>
<script src="<?= e(asset_url($src)) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
