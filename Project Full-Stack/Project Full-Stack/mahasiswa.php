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
$offset = ($page - 1) * $limit;

$totalRes = mysqli_query($conn, 'SELECT COUNT(nrp) AS total FROM mahasiswa');
$totalRow = mysqli_fetch_assoc($totalRes);
$total = (int)$totalRow['total'];
$pages = max(1, (int)ceil($total / $limit));

$sql = "SELECT nrp, nama, gender, tanggal_lahir, angkatan, foto_extention FROM mahasiswa ORDER BY nrp DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Mahasiswa (View Only)</title>
    <link rel="stylesheet" href="<?= url_from_app('../asset/style.css') ?>">
    <style>
        .note { color: #555; font-size: 0.95em; margin-left: 10px; }
        table { width: 98%; }
    </style>
    </head>
<body>

<h2>Daftar Mahasiswa</h2>
<div style="text-align:left; margin-bottom:10px;">
    <a href="<?= url_from_app('home.php') ?>">Kembali</a>
    <span class="note">(hanya bisa melihat, tanpa edit/hapus)</span>
    </div>

<table>
    <tr>
        <th>No</th>
        <th>NRP</th>
        <th>Nama</th>
        <th>Gender</th>
        <th>Tanggal Lahir</th>
        <th>Angkatan</th>
        <th>Foto</th>
    </tr>

    <?php $no = $offset + 1; while ($data = mysqli_fetch_assoc($result)) : ?>
    <tr>
        <td><?= $no++; ?></td>
        <td><?= htmlspecialchars($data['nrp']); ?></td>
        <td><?= htmlspecialchars($data['nama']); ?></td>
        <td><?= htmlspecialchars($data['gender']); ?></td>
        <td><?= htmlspecialchars($data['tanggal_lahir']); ?></td>
        <td><?= htmlspecialchars($data['angkatan']); ?></td>
        <td>
            <?php if (!empty($data['foto_extention'])) { 
                $nama_file_foto = htmlspecialchars($data['nrp']) . '.' . htmlspecialchars($data['foto_extention']);
                echo "<img src='" . url_from_app('../uploads/mahasiswa/' . $nama_file_foto) . "' height='70' alt='Foto Mahasiswa'>";
            } else { echo "-"; } ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<form method="get" style="text-align:left; margin:10px 0;">
    Tampilkan 
    <select name="limit" onchange="this.form.submit()">
        <?php foreach([5,10,15,20] as $opt): ?>
            <option value="<?=$opt?>" <?=($opt==$limit)?'selected':''?>><?=$opt?></option>
        <?php endforeach; ?>
    </select>
    data per halaman
</form>

<div class="pagination">
    <?php if ($page > 1): ?>
        <a href='?page=1&limit=<?=$limit?>'>First</a>
        <a href='?page=<?=($page-1)?>&limit=<?=$limit?>'>Prev</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <?= ($i == $page) ? "<b>$i</b>" : "<a href='?page=$i&limit=$limit'>$i</a>" ?>
    <?php endfor; ?>
    
    <?php if ($page < $pages): ?>
        <a href='?page=<?=($page+1)?>&limit=<?=$limit?>'>Next</a>
        <a href='?page=<?=$pages?>&limit=<?=$limit?>'>Last</a>
    <?php endif; ?>
</div>

</body>
</html>
