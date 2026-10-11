<?php
require __DIR__ . '/includes/bootstrap.php';

// The link in the navbar carries the CSRF token, so other sites cannot log you out.
if (ensure_session(false) && csrf_ok(isset($_GET['t']) ? $_GET['t'] : '')) {
    logout_user();
}
header('Location: index.php');
exit;
