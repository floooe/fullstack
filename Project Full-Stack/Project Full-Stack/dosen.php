<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

// Hanya admin yang boleh mengakses halaman ini
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: home.php');
    exit;
}

include '../../proses/koneksi.php';
require_once __DIR__ . '/../../proses/url.php';

$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start = ($page - 1) * $limit;

$totalRes = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM dosen');
$totalRow = mysqli_fetch_assoc($totalRes);
$total = (int)$totalRow['total'];
$pages = max(1, (int)ceil($total / $limit));

$q = mysqli_query($conn, "SELECT npk, nama, foto_extension FROM dosen ORDER BY npk ASC LIMIT $start, $limit");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Dosen (View Only)</title>
    <link rel="stylesheet" href="<?= url_from_app('../asset/style.css') ?>">
    <style>
        .note { color: #555; font-size: 0.95em; margin-left: 10px; }
    </style>
</head>
<body>
    <h2>Data Dosen</h2>
    <div style="text-align:left; margin-bottom:10px;">
        <a href="<?= url_from_app('home.php') ?>">Kembali</a>
        <span class="note">(hanya bisa melihat, tanpa edit/hapus)</span>
    </div>

    <table border="1">
        <tr>
            <th>No</th>
            <th>NPK</th>
            <th>Nama</th>
            <th>Foto</th>
        </tr>
        <?php $no = $start + 1; while ($row = mysqli_fetch_assoc($q)) { ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($row['npk']); ?></td>
            <td><?= htmlspecialchars($row['nama']); ?></td>
            <td>
                <?php if (!empty($row['foto_extension'])): 
                    $nama_file = htmlspecialchars($row['npk']) . '.' . htmlspecialchars($row['foto_extension']);
                ?>
                    <img src="<?= url_from_app('../uploads/dosen/' . $nama_file) ?>" width="75" alt="Foto Dosen">
                <?php else: ?>
                    <span>-</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php } ?>
    </table>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=1&limit=<?= $limit ?>">First</a>
            <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>">Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?= ($i == $page) ? "<b>$i</b>" : "<a href='?page=$i&limit=$limit'>$i</a>" ?>
        <?php endfor; ?>

        <?php if ($page < $pages): ?>
            <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>">Next</a>
            <a href="?page=<?= $pages ?>&limit=<?= $limit ?>">Last</a>
        <?php endif; ?>
    </div>
</body>
</html>
