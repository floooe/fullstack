<?php
require '../koneksi.php';
$id = $_GET['id'];

$query_select = "SELECT foto FROM mahasiswa WHERE id=$id";
$result = mysqli_query($koneksi, $query_select);
$data = mysqli_fetch_assoc($result);
$nama_foto = $data['foto'];

if (file_exists("../uploads/mahasiswa/" . $nama_foto)) {
    unlink("../uploads/mahasiswa/" . $nama_foto);
}

$query_delete = "DELETE FROM mahasiswa WHERE id=$id";
if (mysqli_query($koneksi, $query_delete)) {
    header("Location: index.php");
    exit;
} else {
    echo "Error: " . mysqli_error($koneksi);
}
?>