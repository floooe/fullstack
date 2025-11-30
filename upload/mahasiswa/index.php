<?php
// URL helper removed; use direct relative paths
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../../index.php');
    exit;
}
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../home.php');
    exit;
}
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total data
$totalResult = $mysqli->query("SELECT COUNT(nrp) AS total FROM mahasiswa");
$totalData = $totalResult->fetch_assoc()['total'];
$totalPage = ceil($totalData / $limit);

// Ambil data mahasiswa
$sql = "SELECT nrp, nama, gender, tanggal_lahir, angkatan, foto_extention 
        FROM mahasiswa ORDER BY nrp DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
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
            <button type="button" class="btn btn-small" onclick="location.href='tambah.php'">+ Tambah Mahasiswa Baru</button>
            <button type="button" class="btn btn-small" onclick="location.href='../../home.php'">Kembali</button>
        </div>
    </div>

    <?php if (isset($_GET['msg'])) { ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php } ?>

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
            while ($data = $result->fetch_assoc()) :
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars($data['nrp']); ?></td>
                <td><?= htmlspecialchars($data['nama']); ?></td>
                <td><?= htmlspecialchars($data['gender']); ?></td>
                <td><?= htmlspecialchars($data['tanggal_lahir']); ?></td>
                <td><?= htmlspecialchars($data['angkatan']); ?></td>
                <td>
                    <?php
                    if (!empty($data['foto_extention'])) {
                        $nama_file_foto = htmlspecialchars($data['nrp']) . '.' . htmlspecialchars($data['foto_extention']);
                        echo "<img src='../../uploads/mahasiswa/" . $nama_file_foto . "' height='70'>";
                    } else {
                        echo "-";
                    }
                    ?>
                </td>
                <td>
                    <div class="toolbar">
                        <button type="button" class="btn btn-small" onclick="location.href='edit.php?nrp=<?= urlencode($data['nrp']); ?>'">Edit</button>
                        <button type="button" class="btn btn-danger btn-small" onclick="if(confirm('Yakin ingin menghapus data ini?')) location.href='hapus.php?nrp=<?= urlencode($data['nrp']); ?>'">Hapus</button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
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
        <?php if ($page > 1): ?>
            <a class="btn btn-secondary btn-small" href='?page=1&limit=<?=$limit?>'>First</a>
            <a class="btn btn-secondary btn-small" href='?page=<?=($page-1)?>&limit=<?=$limit?>'>Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPage; $i++): ?>
            <?= ($i == $page) ? "<span class=\"btn btn-small\">$i</span>" : "<a class='btn btn-secondary btn-small' href='?page=$i&limit=$limit'>$i</a>" ?>
        <?php endfor; ?>
        
        <?php if ($page < $totalPage): ?>
            <a class="btn btn-secondary btn-small" href='?page=<?=($page+1)?>&limit=<?=$limit?>'>Next</a>
            <a class="btn btn-secondary btn-small" href='?page=<?=$totalPage?>&limit=<?=$limit?>'>Last</a>
        <?php endif; ?>
    </div>
</div>

<?php
$stmt->close();
$mysqli->close();
?>

</body>
</html>
