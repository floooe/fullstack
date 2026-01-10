<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_SESSION['level']) || !in_array($_SESSION['level'], ['admin', 'dosen'])) {
    header("Location: ../../home.php");
    exit;
}

require_once "../../class/Group.php";

$groupObj = new Grup();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['name'] ?? '');
    $jenis = strtolower(trim($_POST['jenis'] ?? ''));
    $deskripsi = trim($_POST['description'] ?? '');

    if ($nama === '') {
        $errors[] = "Nama grup wajib diisi.";
    }

    if (!in_array($jenis, ['public', 'private'])) {
        $errors[] = "Jenis grup harus Public atau Private.";
    }

    if (empty($errors)) {

        $result = $groupObj->createGroup(
            $nama,
            $jenis,
            $deskripsi,
            $_SESSION['username']
        );

        if ($result) {
            header("Location: groups.php?msg=Grup berhasil dibuat");
            exit;
        } else {
            $errors[] = "Gagal membuat grup.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Buat Group</title>
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/dosen.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/group.css">
</head>
<body class="dosen-page group-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Buat Group Baru</h2>
                <p class="page-subtitle">Susun grup dan bagikan kode pendaftaran ke anggota.</p>
            </div>
            <button type="button" class="btn btn-secondary btn-small"
                onclick="location.href='groups.php'">Kembali</button>
        </div>

        <?php if (!empty($errors)) { ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e) { ?>
                    <p><?= htmlspecialchars($e); ?></p>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="card">
            <form method="post" class="section">
                <div class="field">
                    <label>Nama Group</label>
                    <input type="text" name="name" required placeholder="Mis. Pemrograman Web A"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>Jenis Group</label>
                    <select name="jenis" required>
                        <option value="" disabled <?= empty($_POST['jenis']) ? 'selected' : '' ?>>-- Pilih jenis --</option>
                        <option value="public" <?= ($_POST['jenis'] ?? '') === 'public' ? 'selected' : '' ?>>Public</option>
                        <option value="private" <?= ($_POST['jenis'] ?? '') === 'private' ? 'selected' : '' ?>>Private</option>
                    </select>
                </div>

                <div class="field">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3"
                        placeholder="Keterangan singkat"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <p class="muted">
                    Tanggal pembuatan dan kode pendaftaran dibuat otomatis oleh sistem.
                </p>

                <button type="submit" class="btn">Simpan</button>
            </form>
        </div>
    </div>
</body>
</html>
