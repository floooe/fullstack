<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: home.php');
    exit;
}

include __DIR__ . '/proses/koneksi.php';

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
    <link rel="stylesheet" href="asset/style.css">
    <link rel="stylesheet" href="asset/dosen.css">
</head>
<body class="dosen-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Data Dosen</h2>
                <p class="page-subtitle">Hanya tampilan, tanpa aksi edit atau hapus.</p>
            </div>
            <div class="toolbar">
                <a href="home.php" class="btn btn-secondary btn-small">Kembali</a>
            </div>
        </div>

        <div class="table-wrapper card-compact">
            <table>
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
                            <img src="uploads/dosen/<?= $nama_file ?>" width="75" alt="Foto Dosen">
                        <?php else: ?>
                            <span>-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </div>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="btn btn-small" href="?page=1&limit=<?= $limit ?>">First</a>
                <a class="btn btn-small" href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>">Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?= ($i == $page) ? "<span class='btn btn-small'>$i</span>" : "<a class='btn btn-small' href='?page=$i&limit=$limit'>$i</a>" ?>
            <?php endfor; ?>

            <?php if ($page < $pages): ?>
                <a class="btn btn-small" href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>">Next</a>
                <a class="btn btn-small" href="?page=<?= $pages ?>&limit=<?= $limit ?>">Last</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
