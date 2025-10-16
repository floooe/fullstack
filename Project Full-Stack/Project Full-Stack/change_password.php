<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password</title>
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
