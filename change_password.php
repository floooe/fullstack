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
    <link rel="stylesheet" href="/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/asset/change_password.css">
</head>
<body>
    <div class="container">
        <h2>Ubah Password</h2>
        <p>Pastikan password baru mudah diingat namun sulit ditebak.</p>
        <form action="proses/change_password.php" method="POST">
            <div class="field">
                <label>Password baru</label>
                <input type="password" name="baru" placeholder="Password baru" required>
            </div>
            <div class="field">
                <label>Ulangi password</label>
                <input type="password" name="ulang" placeholder="Konfirmasi password" required>
            </div>
            <button type="submit" name="ubah" class="btn btn-block">Simpan Perubahan</button>
        </form>
        <p class="mt-12" style="text-align:center; margin-bottom:0;">
            <a href="home.php" class="btn btn-block" style="margin-top:10px;">Kembali ke Home</a>
        </p>
    </div>
</body>
</html>
