<?php
require '../koneksi.php';
$id = $_GET['id'];

$query_select = "SELECT foto FROM dosen WHERE id=$id";
$result = mysqli_query($koneksi, $query_select);
$data = mysqli_fetch_assoc($result);
$nama_foto = $data['foto'];

if ($nama_foto && file_exists("../uploads/dosen/" . $nama_foto)) {
    unlink("../uploads/dosen/" . $nama_foto);
}

$query_delete = "DELETE FROM dosen WHERE id=$id";
if (mysqli_query($koneksi, $query_delete)) {
    header("Location: index.php");
    exit;
} else {
    echo "Error: " . mysqli_error($koneksi);
}
?>