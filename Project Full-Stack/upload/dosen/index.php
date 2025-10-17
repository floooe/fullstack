<?php
include "../../../proses/koneksi.php";
session_start();

$limit = 5;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

$q = mysqli_query($conn, "SELECT * FROM dosen LIMIT $start, $limit");
$total = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM dosen"));
$pages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Dosen</title>
    <link rel="stylesheet" href="../../asset/style.css">
</head>
<body>
    <h2>Data Dosen</h2>
    <a href="tambah.php" class="btn-add">+ Tambah Dosen</a> |
    <a href="../../home.php">Kembali</a>
    <table border="1">
        <tr><th>No</th><th>NPK</th><th>Nama</th><th>Foto</th><th>Aksi</th></tr>
        <?php $no = $start + 1; while ($data = mysqli_fetch_array($q)) { ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= $data['npk']; ?></td>
            <td><?= $data['nama']; ?></td>
            <td><img src="../../uploads/dosen/<?= $data['foto']; ?>" width="60"></td>
            <td>
                <a href="edit.php?npk=<?= $data['npk']; ?>">Edit</a> | 
                <a href="hapus.php?npk=<?= $data['npk']; ?>">Hapus</a>
            </td>
        </tr>
        <?php } ?>
    </table>
    <div class="pagination">
        <?php for ($i=1; $i<=$pages; $i++) { ?>
            <a href="?page=<?= $i; ?>"><?= $i; ?></a>
        <?php } ?>
    </div>
</body>
</html>
