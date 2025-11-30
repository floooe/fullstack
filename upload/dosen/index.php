<?php
include "../../proses/koneksi.php";
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../../index.php');
    exit;
}
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../home.php');
    exit;
}
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$start = ($page - 1) * $limit;
$q = mysqli_query($conn, "SELECT * FROM dosen LIMIT $start, $limit");
$total = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM dosen"));
$pages = max(1, (int)ceil($total / $limit));

?>

<!DOCTYPE html>
<html>

<head>
    <title>Data Dosen</title>
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/dosen.css">
</head>

<body class="dosen-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Data Dosen</h2>
                <p class="page-subtitle">Tambah, ubah, atau hapus data dosen.</p>
            </div>
            <div class="toolbar">
                <button type="button" class="btn btn-small" onclick="location.href='tambah.php'">+ Tambah Dosen</button>
                <button type="button" class="btn btn-small" onclick="location.href='../../home.php'">Kembali</button>
            </div>
        </div>

        <?php if (isset($_GET['msg'])) { ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php } ?>

        <div class="table-wrapper card-compact">
            <table>
                <tr>
                    <th>No</th>
                    <th>NPK</th>
                    <th>Nama</th>
                    <th>Foto</th>
                    <th>Aksi</th>
                </tr> <?php $no = $start + 1;
                while ($data = mysqli_fetch_array($q)) { ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($data['npk']); ?></td>
                        <td><?= htmlspecialchars($data['nama']); ?></td>
                        <td> <?php if (!empty($data['foto_extension'])):
                            $nama_file = htmlspecialchars($data['npk']) . '.' . htmlspecialchars($data['foto_extension']); ?>
                                <img src="../../uploads/dosen/<?= $nama_file ?>" width="75" alt="Foto Dosen"> <?php else: ?> <span>Tidak
                                    ada foto</span> <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <button type="button" class="btn btn-small" onclick="location.href='edit.php?npk=<?= urlencode($data['npk']); ?>'">Edit</button>
                            <button type="button" class="btn btn-danger btn-small" onclick="if(confirm('Yakin ingin menghapus data ini?')) location.href='hapus.php?npk=<?= urlencode($data['npk']); ?>'">Hapus</button>
                        </td>
                    </tr> <?php } ?>
            </table>
        </div>
    <form method="get" class="toolbar mt-10">
        <span class="page-subtitle">Tampilkan</span>
        <select name="limit" class="w-auto" onchange="this.form.submit()">
            <?php foreach([5,10,15,20] as $opt): ?>
                <option value="<?=$opt?>" <?=($opt==$limit)?'selected':''?>><?=$opt?></option>
            <?php endforeach; ?>
        </select>
        <span class="page-subtitle">data per halaman</span>
    </form>
        <div class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++) { ?>
                <a class="btn btn-small" href="?page=<?= $i; ?>&limit=<?= $limit; ?>"><?= $i; ?></a>
            <?php } ?>
        </div>
    </div>
</body>

</html>
