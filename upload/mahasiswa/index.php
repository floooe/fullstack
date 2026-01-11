<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: ../../index.php');
    exit;
}

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../home.php');
    exit;
}

require_once "../../class/Mahasiswa.php";

$mahasiswa = new Mahasiswa();

$limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$page   = isset($_GET['page'])  ? (int)$_GET['page']  : 1;
$page   = max(1, $page);
$limit  = max(1, $limit);
$offset = ($page - 1) * $limit;

$totalData = $mahasiswa->countAll();
$totalPage = max(1, ceil($totalData / $limit));

$result = $mahasiswa->getAll($limit, $offset);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Mahasiswa</title>
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/mahasiswa.css">
</head>

<body class="mahasiswa-page">

<div class="page">
    <div class="page-header">
        <div>
            <h2 class="page-title">Daftar Mahasiswa</h2>
            <p class="page-subtitle">Kelola data mahasiswa lengkap dengan foto.</p>
        </div>
        <div class="toolbar">
            <button class="btn btn-small" onclick="location.href='tambah.php'">+ Tambah Mahasiswa</button>
            <button class="btn btn-small" onclick="location.href='../../home.php'">Kembali</button>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="table-wrapper card-compact">
        <table class="table-compact">
            <tr>
                <th>No</th>
                <th>NRP</th>
                <th>Nama</th>
                <th>Gender</th>
                <th>Tanggal Lahir</th>
                <th>Angkatan</th>
                <th>Foto</th>
                <th>Aksi</th>
            </tr>

            <?php
            $no = $offset + 1;
            if ($result->num_rows === 0):
            ?>
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data mahasiswa</td>
                </tr>
            <?php
            else:
                while ($row = $result->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['nrp']); ?></td>
                    <td><?= htmlspecialchars($row['nama']); ?></td>
                    <td><?= htmlspecialchars($row['gender']); ?></td>
                    <td><?= htmlspecialchars($row['tanggal_lahir']); ?></td>
                    <td><?= htmlspecialchars($row['angkatan']); ?></td>
                    <td>
                        <?php if (!empty($row['foto_extention'])): ?>
                            <img src="../../uploads/mahasiswa/<?= htmlspecialchars($row['nrp']) . '.' . htmlspecialchars($row['foto_extention']); ?>"
                                 height="70">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="toolbar">
                            <button class="btn btn-small"
                                onclick="location.href='edit.php?nrp=<?= urlencode($row['nrp']); ?>'">
                                Edit
                            </button>
                            <button class="btn btn-danger btn-small"
                                onclick="if(confirm('Yakin ingin menghapus data ini?')) location.href='hapus.php?nrp=<?= urlencode($row['nrp']); ?>'">
                                Hapus
                            </button>
                        </div>
                    </td>
                </tr>
            <?php
                endwhile;
            endif;
            ?>
        </table>
    </div>

    <form method="get" class="toolbar mt-10">
        <span class="page-subtitle">Tampilkan</span>
        <select name="limit" onchange="this.form.submit()">
            <?php foreach ([5,10,15,20] as $opt): ?>
                <option value="<?= $opt ?>" <?= $opt == $limit ? 'selected' : '' ?>>
                    <?= $opt ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="page-subtitle">data per halaman</span>
    </form>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a class="btn btn-small" href="?page=1&limit=<?= $limit ?>">First</a>
            <a class="btn btn-small" href="?page=<?= $page-1 ?>&limit=<?= $limit ?>">Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPage; $i++): ?>
            <?= ($i == $page)
                ? "<span class='btn btn-small'>$i</span>"
                : "<a class='btn btn-small' href='?page=$i&limit=$limit'>$i</a>" ?>
        <?php endfor; ?>

        <?php if ($page < $totalPage): ?>
            <a class="btn btn-small" href="?page=<?= $page+1 ?>&limit=<?= $limit ?>">Next</a>
            <a class="btn btn-small" href="?page=<?= $totalPage ?>&limit=<?= $limit ?>">Last</a>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
