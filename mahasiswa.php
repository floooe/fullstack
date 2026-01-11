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

require_once __DIR__ . '/class/Mahasiswa.php';

$mahasiswaObj = new Mahasiswa();


$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$total = $mahasiswaObj->countAll();
$pages = max(1, (int)ceil($total / $limit));

$result = $mahasiswaObj->getAll($limit, $offset);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Mahasiswa (View Only)</title>
    <link rel="stylesheet" href="asset/style.css">
    <link rel="stylesheet" href="asset/mahasiswa.css">
    </head>
<body class="mahasiswa-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Daftar Mahasiswa</h2>
                <p class="page-subtitle note">(hanya bisa melihat, tanpa edit/hapus)</p>
            </div>
            <a href="home.php" class="btn btn-secondary btn-small">Kembali</a>
        </div>

        <div class="table-wrapper card-compact">
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
                            echo "<img src='uploads/mahasiswa/" . $nama_file_foto . "' height='70' alt='Foto Mahasiswa'>";
                        } else { echo "-"; } ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <form method="get" class="toolbar">
            <span class="page-subtitle">Tampilkan</span>
            <select name="limit" class="w-auto" onchange="this.form.submit()">
                <?php foreach([5,10,15,20] as $opt): ?>
                    <option value="<?=$opt?>" <?=($opt==$limit)?'selected':''?>><?=$opt?></option>
                <?php endforeach; ?>
            </select>
            <span class="page-subtitle">data per halaman</span>
        </form>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="btn btn-small" href='?page=1&limit=<?=$limit?>'>First</a>
                <a class="btn btn-small" href='?page=<?=($page-1)?>&limit=<?=$limit?>'>Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?= ($i == $page) ? "<span class=\"btn btn-small\">$i</span>" : "<a class='btn btn-small' href='?page=$i&limit=$limit'>$i</a>" ?>
            <?php endfor; ?>
            
            <?php if ($page < $pages): ?>
                <a class="btn btn-small" href='?page=<?=($page+1)?>&limit=<?=$limit?>'>Next</a>
                <a class="btn btn-small" href='?page=<?=$pages?>&limit=<?=$limit?>'>Last</a>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
