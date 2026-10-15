<?php
$page = 'news';
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/news.php';
ensure_session(true);
$me = current_user();
$isAdmin = $me && $me['role'] === 'admin';
$pdo = db();

$nid = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($me && csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        try {
            $chk = $pdo->prepare('SELECT 1 FROM news WHERE id = ? AND published = 1');
            $chk->execute([$pid]);
            if ($chk->fetch()) {
                $del = $pdo->prepare('DELETE FROM news_likes WHERE news_id = ? AND user_id = ?');
                $del->execute([$pid, $me['id']]);
                if (!$del->rowCount()) {
                    $pdo->prepare('INSERT INTO news_likes (news_id, user_id) VALUES (?, ?)')->execute([$pid, $me['id']]);
                }
            }
        } catch (Exception $ex) {
        }
    }
    redirect('news?id=' . $pid . '#likes');
}

$post = null;
$posts = [];
$pages = 1;
$p = 1;

if ($nid > 0) {
    $st = $pdo->prepare('SELECT n.*, (SELECT COUNT(*) FROM news_likes l WHERE l.news_id = n.id) AS likes FROM news n WHERE n.id = ?');
    $st->execute([$nid]);
    $post = $st->fetch();
    if ($post && !$post['published'] && !$isAdmin) {
        $post = null;
    }
    if (!$post) {
        http_response_code(404);
    } elseif ($post['published']) {
        if (empty($_SESSION['nv'][$nid])) {
            $_SESSION['nv'][$nid] = 1;
            $pdo->prepare('UPDATE news SET views = views + 1 WHERE id = ?')->execute([$nid]);
            $post['views']++;
        }
    }
    $liked = false;
    if ($post && $me) {
        $lk = $pdo->prepare('SELECT 1 FROM news_likes WHERE news_id = ? AND user_id = ?');
        $lk->execute([$nid, $me['id']]);
        $liked = (bool) $lk->fetch();
    }
} else {
    $perPage = 8;
    $total = (int) $pdo->query('SELECT COUNT(*) FROM news WHERE published = 1')->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $p = min($pages, max(1, isset($_GET['p']) ? (int) $_GET['p'] : 1));
    $posts = $pdo->query('SELECT n.id, n.title, n.excerpt, n.category, n.image, n.views, n.created_at,
            (SELECT COUNT(*) FROM news_likes l WHERE l.news_id = n.id) AS likes
        FROM news n WHERE n.published = 1 ORDER BY n.created_at DESC, n.id DESC
        LIMIT ' . $perPage . ' OFFSET ' . (($p - 1) * $perPage))->fetchAll();
}

$withBg = $nid <= 0;
$pageTitle = $post ? $post['title'] : t('nav_news');
require __DIR__ . '/includes/header.php';
?>
<main>
<?php if ($nid > 0): ?>
  <div class="wrap article-wrap">
    <a class="back-btn" href="news"><?= news_icon('back') ?><?= e(t('news_all')) ?></a>
  <?php if (!$post): ?>
    <div class="panel empty-panel"><?= e(t('news_not_found')) ?></div>
  <?php else: $img = news_image_url($post); ?>
    <article class="article">
      <div class="art-cover<?= $img ? '' : ' none' ?>">
        <?php if ($img): ?><img src="<?= e($img) ?>" alt="" width="1400" height="788" onerror="this.onerror=null;this.src='assets/img/logo.webp';this.parentNode.classList.add('none')"><?php else: ?><img class="ph" src="assets/img/logo.webp" alt="" width="698" height="358"><?php endif; ?>
      </div>
      <div class="news-meta">
        <span class="news-tag"><?= e(t('cat_' . news_clean_category($post['category']))) ?></span>
        <?php if (!$post['published']): ?><span class="news-tag draft"><?= e(t('news_draft')) ?></span><?php endif; ?>
        <span class="news-date"><?= e(news_date($post['created_at'])) ?></span>
        <span class="news-stat" title="<?= e(t('news_views')) ?>"><?= news_icon('eye') ?><?= (int) $post['views'] ?></span>
      </div>
      <h1 class="art-title"><?= e($post['title']) ?></h1>
      <?php if ($post['excerpt'] !== ''): ?><p class="art-excerpt"><?= e($post['excerpt']) ?></p><?php endif; ?>
      <div class="art-body md ptext"><?= md_render($post['body']) ?></div>
      <?php if ($isAdmin): ?><p class="art-admin"><a href="admin-news?id=<?= (int) $post['id'] ?>"><?= e(t('ad_news_edit_btn')) ?></a></p><?php endif; ?>
      <div class="art-foot" id="likes">
      <?php if ($post['published']): ?>
        <?php if ($me): ?>
        <form method="post" action="news">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
          <button type="submit" class="like-btn<?= $liked ? ' on' : '' ?>" aria-pressed="<?= $liked ? 'true' : 'false' ?>" aria-label="<?= e(t('news_like')) ?>"><?= news_icon('heart') ?><span><?= (int) $post['likes'] ?></span></button>
        </form>
        <?php else: ?>
        <a class="like-btn" href="login" title="<?= e(t('news_login_like')) ?>" aria-label="<?= e(t('news_login_like')) ?>"><?= news_icon('heart') ?><span><?= (int) $post['likes'] ?></span></a>
        <span class="hint"><?= e(t('news_login_like')) ?></span>
        <?php endif; ?>
      <?php endif; ?>
      </div>
    </article>
  <?php endif; ?>
  </div>
<?php else: ?>
  <div class="wrap">
    <?= page_banner('news_badge', 'news_title', 'news_sub', $config['site_name'], 'news') ?>

  <?php if (!$posts): ?>
    <div class="panel empty-panel news-empty"><?= e(t('news_empty')) ?></div>
  <?php else: ?>
    <div class="news-list">
    <?php foreach ($posts as $n): $img = news_image_url($n); ?>
      <a class="news-card" href="news?id=<?= (int) $n['id'] ?>">
        <div class="news-thumb<?= $img ? '' : ' none' ?>">
          <?php if ($img): ?><img src="<?= e($img) ?>" alt="" loading="lazy" width="320" height="200" onerror="this.onerror=null;this.src='assets/img/head.webp';this.parentNode.classList.add('none')"><?php else: ?><img src="assets/img/head.webp" alt="" loading="lazy" width="96" height="96"><?php endif; ?>
        </div>
        <div class="news-info">
          <div class="news-meta">
            <span class="news-tag"><?= e(t('cat_' . news_clean_category($n['category']))) ?></span>
            <span class="news-date"><?= e(news_date($n['created_at'])) ?></span>
            <span class="news-stat" title="<?= e(t('news_views')) ?>"><?= news_icon('eye') ?><?= (int) $n['views'] ?></span>
            <span class="news-stat" title="<?= e(t('news_likes')) ?>"><?= news_icon('heart') ?><?= (int) $n['likes'] ?></span>
          </div>
          <h2><?= e($n['title']) ?></h2>
          <?php if ($n['excerpt'] !== ''): ?><p><?= e($n['excerpt']) ?></p><?php endif; ?>
          <span class="news-more"><?= e(t('news_read_more')) ?><?= news_icon('arrow') ?></span>
        </div>
      </a>
    <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="pager" aria-label="<?= e(t('ad_page')) ?>">
      <?php if ($p > 1): ?><a class="btn sm" href="news?p=<?= $p - 1 ?>">← <?= e(t('ad_prev')) ?></a><?php endif; ?>
      <span><?= e(t('ad_page')) ?> <?= $p ?> / <?= $pages ?></span>
      <?php if ($p < $pages): ?><a class="btn sm" href="news?p=<?= $p + 1 ?>"><?= e(t('ad_next')) ?> →</a><?php endif; ?>
    </nav>
    <?php endif; ?>
  <?php endif; ?>
  </div>
<?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
