<?php
// Admin: write or edit a news post. Admins only.
$page = 'admin';
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/news.php';
require_once __DIR__ . '/includes/editor_box.php';
ensure_session(true);
$me = require_admin();
$pdo = db();

const NEWS_BODY_MAX = 20000;

$nid = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$post = null;
if ($nid > 0) {
    $st = $pdo->prepare('SELECT * FROM news WHERE id = ?');
    $st->execute([$nid]);
    $post = $st->fetch();
    if (!$post) {
        flash('err', 'err_notfound');
        redirect('admin.php?tab=news');
    }
}

$form = [
    'title'     => $post ? $post['title'] : '',
    'excerpt'   => $post ? $post['excerpt'] : '',
    'body'      => $post ? $post['body'] : '',
    'category'  => $post ? $post['category'] : 'news',
    'published' => $post ? (int) $post['published'] : 1,
];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['title'] = str_replace("\n", ' ', clean_text(isset($_POST['title']) ? $_POST['title'] : '', 120));
    $form['excerpt'] = str_replace("\n", ' ', clean_text(isset($_POST['excerpt']) ? $_POST['excerpt'] : '', 220));
    $form['body'] = clean_text(isset($_POST['body']) ? $_POST['body'] : '', NEWS_BODY_MAX);
    $form['category'] = news_clean_category(isset($_POST['category']) ? $_POST['category'] : '');
    $form['published'] = isset($_POST['published']) ? 1 : 0;
    $removeImg = isset($_POST['remove_image']);

    if (!csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        $error = 'err_csrf';
    } elseif ($form['title'] === '') {
        $error = 'err_news_title';
    } elseif ($form['body'] === '') {
        $error = 'err_news_body';
    }

    $newImg = null;
    if (!$error && !empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $newImg = news_image_store($_FILES['image'], $imgErr);
        if (!$newImg) {
            $error = $imgErr;
        }
    }

    if (!$error) {
        if ($form['excerpt'] === '') {
            $form['excerpt'] = news_excerpt_from_body($form['body']);
        }
        try {
            if ($post) {
                $image = $post['image'];
                if ($newImg) {
                    $image = $newImg;
                } elseif ($removeImg) {
                    $image = null;
                }
                $pdo->prepare("UPDATE news SET title = ?, excerpt = ?, body = ?, category = ?, image = ?, published = ?, updated_at = datetime('now') WHERE id = ?")
                    ->execute([$form['title'], $form['excerpt'], $form['body'], $form['category'], $image, $form['published'], $nid]);
                if ($post['image'] && $post['image'] !== $image) {
                    news_image_delete($post['image']);
                }
            } else {
                $pdo->prepare('INSERT INTO news (title, excerpt, body, category, image, published, author_id) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$form['title'], $form['excerpt'], $form['body'], $form['category'], $newImg, $form['published'], $me['id']]);
            }
            flash('ok', 'ok_news_saved');
            redirect('admin.php?tab=news');
        } catch (Exception $ex) {
            if ($newImg) {
                news_image_delete($newImg);
            }
            $error = 'err_db';
        }
    }
}

$pageTitle = t($post ? 'ad_news_edit' : 'ad_news_new');
$extraScripts = ['assets/js/editor.js'];
require __DIR__ . '/includes/header.php';
$csrf = csrf_token();
$img = $post ? news_image_url($post) : null;
?>
<main>
  <div class="wrap page">
    <div class="page-head">
      <span class="auth-badge"><?= page_icon('news') ?><?= e(t('ad_badge')) ?></span>
      <div class="head-row">
        <div>
          <h1><?= e(t($post ? 'ad_news_edit' : 'ad_news_new')) ?></h1>
        </div>
        <a class="link-soft" href="admin.php?tab=news">← <?= e(t('ad_tab_news')) ?></a>
      </div>
    </div>

    <?php if ($error): ?><div class="flash err" role="alert"><?= e(t($error)) ?></div><?php endif; ?>

    <form class="panel news-form" method="post" action="admin-news.php<?= $post ? '?id=' . $nid : '' ?>" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="MAX_FILE_SIZE" value="<?= NEWS_IMAGE_MAX ?>">

      <label for="ntitle"><?= e(t('ad_news_title')) ?></label>
      <input id="ntitle" name="title" type="text" maxlength="120" required value="<?= e($form['title']) ?>">

      <div class="nf-row">
        <div>
          <label for="ncat"><?= e(t('ad_news_category')) ?></label>
          <select id="ncat" name="category">
            <?php foreach (news_categories() as $c): ?>
              <option value="<?= e($c) ?>"<?= $form['category'] === $c ? ' selected' : '' ?>><?= e(t('cat_' . $c)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="nf-grow">
          <label for="nimg"><?= e(t('ad_news_image')) ?></label>
          <input id="nimg" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="nf-file">
        </div>
      </div>
      <p class="hint"><?= e(t('ad_news_image_hint')) ?></p>
      <?php if ($img): ?>
        <div class="nf-current">
          <img src="<?= e($img) ?>" alt="" width="160" height="90">
          <label class="nf-check"><input type="checkbox" name="remove_image" value="1"> <?= e(t('ad_news_image_remove')) ?></label>
        </div>
      <?php endif; ?>

      <label for="nexc"><?= e(t('ad_news_excerpt')) ?></label>
      <textarea id="nexc" name="excerpt" rows="2" maxlength="220" style="min-height:0"><?= e($form['excerpt']) ?></textarea>
      <p class="hint"><?= e(t('ad_news_excerpt_hint')) ?></p>

      <label for="nbody"><?= e(t('ad_news_body')) ?></label>
      <?= editor_box('nbody', 'body', $form['body'], NEWS_BODY_MAX, $csrf, 'news', 14) ?>

      <label class="nf-check pub"><input type="checkbox" name="published" value="1"<?= $form['published'] ? ' checked' : '' ?>> <?= e(t('ad_news_published')) ?></label>
      <p class="hint"><?= e(t('ad_news_draft_hint')) ?></p>

      <div class="actions">
        <button class="btn sm" type="submit"><?= e(t('ad_news_save')) ?></button>
        <a class="btn sm white" href="admin.php?tab=news"><?= e(t('btn_cancel')) ?></a>
      </div>
    </form>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
