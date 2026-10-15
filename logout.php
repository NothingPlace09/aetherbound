<?php
require __DIR__ . '/includes/bootstrap.php';

if (ensure_session(false) && csrf_ok(isset($_GET['t']) ? $_GET['t'] : '')) {
    logout_user();
}
header('Location: home');
exit;
