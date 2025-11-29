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
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { border-collapse: collapse; width: 60%; margin-top: 20px; }
        table, th, td { border: 1px solid #999; padding: 10px; text-align: center; }
        input[type=text] { padding: 8px; width: 250px; }
        button { padding: 8px 15px; cursor: pointer; }
    </style>
</head>
<body>

<h2>Selamat Datang, <?= $_SESSION['username'] ?> </h2>

<h3>Tambah Group Baru</h3>

<form method="POST">
    <label>Nama Group :</label><br>
    <input type="text" name="nama" required>
    <button type="submit" name="tambah">Tambah</button>
</form>

<?php if (!empty($error)) { echo "<p style='color:red;'>$error</p>"; } ?>

<h3>Daftar Group Anda</h3>

<table>
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
        <td><?= $row['nama']; ?></td>
        <td><a href="detail_grup.php?id=<?= $row['idgrup']; ?>">Detail</a></td>
    </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
