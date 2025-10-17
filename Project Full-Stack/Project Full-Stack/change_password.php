<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ubah Password</title>
    <link rel="stylesheet" href="../asset/change_password.css">
</head>
<body>
    <h2>Ubah Password</h2>
    <form action="proses/change_pass_proses.php" method="POST">
        Password baru: <input type="password" name="baru" required><br>
        Ulangi password: <input type="password" name="ulang" required><br>
        <button type="submit" name="ubah">Ubah Password</button>
    </form>
</body>
</html>
