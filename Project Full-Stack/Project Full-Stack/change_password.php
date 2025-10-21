<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
require_once __DIR__ . '/../../proses/url.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ubah Password</title>
    <link rel="stylesheet" href="<?= url_from_app('../asset/change_password.css') ?>">
</head>
<body>
    <h2>Ubah Password</h2>
    <form action="<?= url_from_app('../proses/change_password.php') ?>" method="POST">
        Password baru: <input type="password" name="baru" required><br>
        Ulangi password: <input type="password" name="ulang" required><br>
        <button type="submit" name="ubah">Ubah Password</button>
    </form>
</body>
</html>
