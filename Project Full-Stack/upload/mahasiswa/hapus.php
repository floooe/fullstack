<?php
require '../koneksi.php';
$id = $_GET['id'];

// 1. Ambil nama file foto dari database
$query_select = "SELECT foto FROM mahasiswa WHERE id=$id";
$result = mysqli_query($koneksi, $query_select);
$data = mysqli_fetch_assoc($result);
$nama_foto = $data['foto'];

// 2. Hapus file foto dari folder uploads
if (file_exists("../uploads/mahasiswa/" . $nama_foto)) {
    unlink("../uploads/mahasiswa/" . $nama_foto);
}

// 3. Hapus data dari database
$query_delete = "DELETE FROM mahasiswa WHERE id=$id";
if (mysqli_query($koneksi, $query_delete)) {
    header("Location: index.php");
    exit;
} else {
    echo "Error: " . mysqli_error($koneksi);
}
?>