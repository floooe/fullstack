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
    <meta charset="utf-8">
    <title>Home</title>
    <link rel="stylesheet" href="asset/home.css">
</head>
<body>
    <h2>Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p>Level: <?php echo htmlspecialchars($_SESSION['level']); ?></p>

    <a href="change_password.php">Ubah Password</a> |
    <a href="logout.php">Logout</a>

    <?php if ($_SESSION['level'] === 'admin') { ?>
        <hr>
        <a href="upload/dosen/create_Group.php" class="btn-add">+ Buat Group</a> |
        <a href="upload/dosen/groups.php">Group Saya</a> |
        <a href="upload/dosen/index.php">Kelola Dosen</a> |
        <a href="upload/mahasiswa/index.php">Kelola Mahasiswa</a>
    <?php } elseif ($_SESSION['level'] === 'dosen') { ?>
        <hr>
        <a href="upload/dosen/create_Group.php" class="btn-add">+ Buat Group</a> |
        <a href="upload/dosen/groups.php">Group Saya</a>
    <?php } elseif ($_SESSION['level'] === 'mahasiswa') { ?>
        <hr>
        <a href="upload/mahasiswa/groups.php">Group Saya</a> |
        <a href="upload/mahasiswa/groups.php#join">Gabung Group</a>
    <?php } ?>
</body>
</html>
