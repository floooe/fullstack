<?php
session_start();
include "../../proses/koneksi.php";

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

// Fungsi generate kode unik
function generateKode($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($characters), 0, $length);
}

// Proses submit form tambah grup
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $kode_pendaftaran = generateKode();
    $username_pembuat = $_SESSION['username'];
    $tanggal = date("Y-m-d H:i:s");
    $jenis = "Privat"; // Default, bisa diubah sesuai kebutuhan
    $deskripsi = "";   // Default kosong

    $query = "INSERT INTO grup (username_pembuat, nama, deskripsi, tanggal_pembentukan, jenis, kode_pendaftaran)
              VALUES ('$username_pembuat', '$nama', '$deskripsi', '$tanggal', '$jenis', '$kode_pendaftaran')";
    
    $result = mysqli_query($conn, $query);

    if ($result) {
        $id = mysqli_insert_id($conn);
        header("Location: detail_grup.php?id=$id");
        exit;
    } else {
        $error = "Gagal menambahkan grup: " . mysqli_error($conn);
    }
}

// Ambil daftar grup milik dosen
$username = $_SESSION['username'];
$data = mysqli_query($conn, "SELECT * FROM grup WHERE username_pembuat='$username'");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Halaman Home Dosen</title>
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/dosen.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/group.css">
</head>
<body class="dosen-page group-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Selamat Datang, <?= $_SESSION['username'] ?> </h2>
                <p class="page-subtitle">Buat grup baru dan lihat daftar grup yang Anda kelola.</p>
            </div>
            <button type="button" class="btn btn-small" onclick="location.href='../../home.php'">Kembali ke Home</button>
        </div>

        <div class="card section">
            <h3>Tambah Group Baru</h3>
            <form method="POST" class="section max-420">
                <div class="field">
                    <label>Nama Group</label>
                    <input type="text" name="nama" required placeholder="Masukkan nama grup">
                </div>
                <button type="submit" name="tambah" class="btn btn-small">Tambah</button>
            </form>
            <?php if (!empty($error)) { echo "<p class='alert alert-danger mt-10'>" . htmlspecialchars($error) . "</p>"; } ?>
        </div>

        <div class="card section">
            <h3>Daftar Group Anda</h3>
            <div class="table-wrapper card-compact max-720">
                <table class="table-compact">
                    <tr>
                        <th>No</th>
                        <th>Nama Group</th>
                        <th>Aksi</th>
                    </tr>
                    <?php 
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($row['nama']); ?></td>
                        <td><button type="button" class="btn btn-small" onclick="location.href='detail_grup.php?id=<?= $row['idgrup']; ?>'">Detail</button></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
