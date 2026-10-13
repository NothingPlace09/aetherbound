<?php
// One-time admin password reset. Open this file once in the browser, then it deletes itself.
// Only the password hash is stored here, never the password.
require __DIR__ . '/includes/bootstrap.php';
$hash = '$2y$10$dISZogzH9fjj3r3XFuSvj.GU0ldwc6sQILd.lJ7qyEUidcAduDTWi';
$email = $config['admin_seed']['email'];
$pdo = db();
$st = $pdo->prepare("UPDATE users SET password_hash = ?, role = 'admin' WHERE email = ?");
$st->execute([$hash, $email]);
header('Content-Type: text/plain; charset=utf-8');
if ($st->rowCount()) {
    @unlink(__FILE__);
    echo "OK: password reset for " . $email . ".\nThis file has been deleted" . (file_exists(__FILE__) ? " - DELETE IT MANUALLY NOW." : ".") . "\n";
} else {
    echo "Admin account not found for " . $email . ". Nothing changed.\n";
}
