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
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/home.css">
</head>
<body class="home-page">
    <div class="page">
        <div class="card welcome-card">
            <div class="page-header">
                <div>
                    <h2 class="hero-title">Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                    <p class="hero-sub">Akses cepat ke pengelolaan grup, dosen, dan mahasiswa.</p>
                </div>
                <span class="badge">Level: <?php echo htmlspecialchars($_SESSION['level']); ?></span>
            </div>

            <div class="actions mt-6">
                <a href="change_password.php" class="btn btn-small">Ubah Password</a>
                <a href="logout.php" class="btn btn-secondary btn-small">Logout</a>
            </div>

            <?php if ($_SESSION['level'] === 'admin') { ?>
                <div class="links-grid">
                    <div class="tile">
                        <strong>Buat Group</strong>
                        <span>Mulai kolaborasi baru.</span>
                        <a class="btn btn-small" href="upload/dosen/create_Group.php">+ Buat Group</a>
                    </div>
                    <div class="tile">
                        <strong>Group Saya</strong>
                        <span>Kelola grup yang dibuat.</span>
                        <a class="btn btn-secondary btn-small" href="upload/dosen/groups.php">Lihat Grup</a>
                    </div>
                    <div class="tile">
                        <strong>Kelola Dosen</strong>
                        <span>Data lengkap dosen.</span>
                        <a class="btn btn-secondary btn-small" href="upload/dosen/index.php">Kelola</a>
                    </div>
                    <div class="tile">
                        <strong>Kelola Mahasiswa</strong>
                        <span>Data mahasiswa terbaru.</span>
                        <a class="btn btn-secondary btn-small" href="upload/mahasiswa/index.php">Kelola</a>
                    </div>
                </div>
            <?php } elseif ($_SESSION['level'] === 'dosen') { ?>
                <div class="links-grid">
                    <div class="tile">
                        <strong>Buat Group</strong>
                        <span>Beri akses mahasiswa.</span>
                        <a class="btn btn-small" href="upload/dosen/create_Group.php">+ Buat Group</a>
                    </div>
                    <div class="tile">
                        <strong>Group Saya</strong>
                        <span>Lihat dan atur grup Anda.</span>
                        <a class="btn btn-secondary btn-small" href="upload/dosen/groups.php">Kelola Grup</a>
                    </div>
                </div>
            <?php } elseif ($_SESSION['level'] === 'mahasiswa') { ?>
                <div class="links-grid">
                    <div class="tile">
                        <strong>Group Saya</strong>
                        <span>Lihat grup yang diikuti.</span>
                        <a class="btn btn-small" href="upload/mahasiswa/groups.php">Buka Grup</a>
                    </div>
                    <div class="tile">
                        <strong>Gabung Group</strong>
                        <span>Masukkan kode pendaftaran.</span>
                        <a class="btn btn-secondary btn-small" href="upload/mahasiswa/groups.php#join">Gabung</a>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>
