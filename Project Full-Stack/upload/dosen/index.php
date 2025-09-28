<?php
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$totalResult = $mysqli->query("SELECT COUNT(npk) AS total FROM dosen");
$totalData = $totalResult->fetch_assoc()['total'];
$totalPage = ceil($totalData / $limit);

$sql = "SELECT npk, nama, foto_extension FROM dosen ORDER BY npk DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Dosen</title>
    <style>
        body { font-family: sans-serif; }
        table { border-collapse: collapse; margin-bottom: 20px; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
        th { background-color: #f2f2f2; }
        .pagination a, .pagination b { display: inline-block; padding: 8px 12px; border: 1px solid #ddd; margin: 0 3px; text-decoration: none; color: #333; }
        .pagination b { background-color: #007BFF; color: white; border-color: #007BFF; }
    </style>
</head>
<body>

<h2>Daftar Dosen</h2>
<a href="tambah.php">Tambah Dosen Baru</a>
<br><br>

<table>
    <tr>
        <th>No</th>
        <th>NPK</th>
        <th>Nama</th>
        <th>Foto</th>
        <th>Aksi</th>
    </tr>

    <?php
    $no = $offset + 1;
    while ($data = $result->fetch_assoc()) :
    ?>
    <tr>
        <td><?= $no++; ?></td>
        <td><?= htmlspecialchars($data['npk']); ?></td>
        <td><?= htmlspecialchars($data['nama']); ?></td>
        <td>
            <?php
            if (!empty($data['foto_extension'])) {
                $nama_file_foto = htmlspecialchars($data['npk']) . '.' . htmlspecialchars($data['foto_extension']);
                echo "<img src='../uploads/dosen/{$nama_file_foto}' height='75'>";
            } else {
                echo "-";
            }
            ?>
        </td>
        <td>
            <a href='edit.php?npk=<?= htmlspecialchars($data['npk']); ?>'>Edit</a> | 
            <a href='hapus.php?npk=<?= htmlspecialchars($data['npk']); ?>' onclick="return confirm('Yakin ingin menghapus data ini?');">Hapus</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<form method="get" style="margin-bottom:10px;">
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

    <?php for ($i = 1; $i <= $totalPage; $i++): ?>
        <?= ($i == $page) ? "<b>$i</b>" : "<a href='?page=$i&limit=$limit'>$i</a>" ?>
    <?php endfor; ?>
    
    <?php if ($page < $totalPage): ?>
        <a href='?page=<?=($page+1)?>&limit=<?=$limit?>'>Next</a>
        <a href='?page=<?=$totalPage?>&limit=<?=$limit?>'>Last</a>
    <?php endif; ?>
</div>

<?php
$stmt->close();
$mysqli->close();
?>

</body>
</html>