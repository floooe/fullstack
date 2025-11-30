<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}
// izinkan dosen maupun admin
if (!isset($_SESSION['level']) || !in_array($_SESSION['level'], ['admin','dosen'])) {
    header("Location: ../../home.php");
    exit;
}

include "../../proses/koneksi.php";

$errors = [];

// Deteksi casing enum kolom jenis (lower/title) agar penyimpanan cocok dengan DB
function detect_jenis_case($conn, $table = 'grup') {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM {$table} LIKE 'jenis'");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        if (!empty($row['Type']) && stripos($row['Type'], 'enum(') === 0) {
            if (stripos($row['Type'], "'Public'") !== false) {
                return 'title'; // Enum memakai Public/Private
            }
            if (stripos($row['Type'], "'public'") !== false) {
                return 'lower'; // Enum memakai public/private
            }
        }
    }
    return 'lower';
}
$jenisCase = detect_jenis_case($conn, 'grup');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['name'] ?? '');
    // Validasi jenis; jangan paksa default public
    $jenisInput = strtolower(trim($_POST['jenis'] ?? ''));
    if (!in_array($jenisInput, ['public', 'private'], true)) {
        $errors[] = "Pilih jenis grup (Public/Private).";
    }
    // Sesuaikan casing dengan enum di DB
    if ($jenisCase === 'title') {
        $jenis = $jenisInput === 'private' ? 'Private' : 'Public';
    } else {
        $jenis = $jenisInput;
    }
    $created_by = mysqli_real_escape_string($conn, $_SESSION['username']);
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));

    if ($nama === '') {
        $errors[] = "Nama grup wajib diisi.";
    }

    if (empty($errors)) {

        // Generate kode unik 6 huruf/angka
        $kode = strtoupper(substr(md5(time()), 0, 6));

        // Escape nama
        $nama_final = mysqli_real_escape_string($conn, $nama);

        // UNTUK DATABASE SESUAI STRUKTUR
        $sql = "INSERT INTO grup (username_pembuat, nama, jenis, kode_pendaftaran, tanggal_pembentukan, deskripsi)
                VALUES ('$created_by', '$nama_final', '$jenis', '$kode', NOW(), '$deskripsi')";

        if (mysqli_query($conn, $sql)) {
            $newId = mysqli_insert_id($conn);
            header("Location: group_detail.php?id=$newId&msg=Grup berhasil dibuat");
            exit;
        } else {
            $errors[] = "Gagal menyimpan grup: " . mysqli_error($conn);
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
            <button type="button" class="btn btn-secondary btn-small" onclick="location.href='../../home.php'">Kembali ke Home</button>
        </div>

        <?php if (!empty($errors)) { ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e) { echo "<p>" . htmlspecialchars($e) . "</p>"; } ?>
            </div>
        <?php } ?>

        <div class="card">
            <form method="post" class="section">
                <div class="field">
                    <label>Nama Group</label>
                    <input type="text" name="name" required placeholder="Mis. Pemrograman Web A">
                </div>
                <?php $oldJenis = strtolower($_POST['jenis'] ?? ''); ?>
                <div class="field">
                    <label>Jenis Group</label>
                    <select name="jenis" required>
                        <option value="" disabled <?= $oldJenis === '' ? 'selected' : ''; ?>>-- Pilih jenis --</option>
                        <option value="public" <?= $oldJenis === 'public' ? 'selected' : ''; ?>>Public</option>
                        <option value="private" <?= $oldJenis === 'private' ? 'selected' : ''; ?>>Private</option>
                    </select>
                </div>
                <div class="field">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3" placeholder="Keterangan singkat"></textarea>
                </div>
                <p class="muted">Tanggal pembuatan dicatat otomatis: <?= date('Y-m-d H:i'); ?> (waktu server).</p>
                <p class="muted">Kode pendaftaran dibuat otomatis dan bisa dilihat di halaman detail grup setelah tersimpan.</p>
                <button type="submit" class="btn">Simpan</button>
            </form>
        </div>
    </div>
</body>
</html>
