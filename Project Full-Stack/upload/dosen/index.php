<?php
include "../../../proses/koneksi.php";
require_once "../../../proses/url.php";
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ' . url_from_app('index.php'));
    exit;
}
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ' . url_from_app('home.php'));
    exit;
}

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
    <link rel="stylesheet" href="<?= url_from_app('../asset/style.css') ?>">
</head>
<body>
    <h2>Data Dosen</h2>
    <a href="tambah.php" class="btn-add">+ Tambah Dosen</a> |
    <a href="<?= url_from_app('home.php') ?>">Kembali</a>

    <table border="1">
        <tr><th>No</th><th>NPK</th><th>Nama</th><th>Foto</th><th>Aksi</th></tr>
        <?php 
        $no = $start + 1; 
        while ($data = mysqli_fetch_array($q)) { ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($data['npk']); ?></td>
            <td><?= htmlspecialchars($data['nama']); ?></td>
            <td>
                <?php if (!empty($data['foto_extension'])): 
                    $nama_file = htmlspecialchars($data['npk']) . '.' . htmlspecialchars($data['foto_extension']);
                ?>
                    <img src="<?= url_from_app('../uploads/dosen/' . $nama_file) ?>" width="75" alt="Foto Dosen">
                <?php else: ?>
                    <span>Tidak ada foto</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="edit.php?npk=<?= urlencode($data['npk']); ?>">Edit</a> | 
                <a href="hapus.php?npk=<?= urlencode($data['npk']); ?>" onclick="return confirm('Yakin ingin menghapus data ini?');">Hapus</a>
            </td>
        </tr>
        <?php } ?>
    </table>

    <div class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++) { ?>
            <a href="?page=<?= $i; ?>"><?= $i; ?></a>
        <?php } ?>
    </div>
</body>
</html>
