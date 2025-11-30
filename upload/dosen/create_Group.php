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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['name'] ?? '');
    $jenis = ($_POST['jenis'] ?? 'public') === 'private' ? 'Private' : 'Public';
    $created_by = mysqli_real_escape_string($conn, $_SESSION['username']);

    if ($nama === '') {
        $errors[] = "Nama grup wajib diisi.";
    }

    if (empty($errors)) {

        // Generate kode unik 6 huruf/angka
        $kode = strtoupper(substr(md5(time()), 0, 6));

        // Escape nama
        $nama_final = mysqli_real_escape_string($conn, $nama);

        // UNTUK DATABASE SESUAI STRUKTUR
        $sql = "INSERT INTO grup (username_pembuat, nama, jenis, kode_pendaftaran, tanggal_pembentukan)
                VALUES ('$created_by', '$nama_final', '$jenis', '$kode', NOW())";

        if (mysqli_query($conn, $sql)) {
            header("Location: groups.php?success=1&kode=$kode");
            exit;
        } else {
            $errors[] = "Gagal menyimpan grup: " . mysqli_error($conn);
        }
    }
}
?>

?>
<!DOCTYPE html>
<html>
<head>
    <title>Buat Group</title>
    <link rel="stylesheet" href="../../asset/style.css">
    <style>
        .form-group { margin-bottom: 10px; }
        label { display: block; font-weight: bold; }
        input[type=text], select, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
    </style>
</head>
<body>
    <h2>Buat Group Baru</h2>
    <p><a href="groups.php">Kembali ke Group Saya</a></p>

    <?php if (!empty($errors)) { ?>
        <div style="color:red;">
            <?php foreach ($errors as $e) { echo "<p>" . htmlspecialchars($e) . "</p>"; } ?>
        </div>
    <?php } ?>

    <form method="post">
        <div class="form-group">
            <label>Nama Group</label>
            <input type="text" name="name" required placeholder="Mis. Pemrograman Web A">
        </div>
        <div class="form-group">
            <label>Jenis Group</label>
            <select name="jenis">
                <option value="public">Public</option>
                <option value="private">Private</option>
            </select>
        </div>
        <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="description" rows="3" placeholder="Keterangan singkat"></textarea>
        </div>
        <button type="submit">Simpan</button>
    </form>
</body>
</html>
