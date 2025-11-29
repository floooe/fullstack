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
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color: white;
            margin: 0;
            padding: 20px;
        }
        h2 { 
            text-align: center;
            color: darkblue;
        }
        a {
            text-decoration: none;
            color: blue;
        }
        a:hover {
            text-decoration: underline;
        }
        .btn-add {
            padding: 8px 15px;
            background-color: green;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .btn-add:hover {
            background-color: darkgreen;
        }
        table { 
            border-collapse: collapse; 
            margin: 20px auto;
            width: 95%; 
            background-color: white;
        }
        th, td { 
            border: 1px solid lightgray; 
            padding: 10px 12px; 
            text-align: center; 
        }
        th { 
            background-color: lightgray; 
            color: black;
        }
        tr:nth-child(even) {
            background-color: white;
        }
        img {
            border-radius: 5px;
        }
        .pagination {
            text-align: center;
            margin-top: 15px;
        }
        .pagination a, .pagination b {
            display: inline-block;
            padding: 8px 12px;
            border: 1px solid lightgray;
            margin: 0 3px;
            text-decoration: none;
            color: black;
            border-radius: 4px;
        }
        .pagination b {
            background-color: blue;
            color: white;
            border-color: blue;
        }
    </style>
</head>
<body>

<h2>Daftar Mahasiswa</h2>
<div style="text-align:left;">
    <a href="../../home.php">Kembali</a>
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
            <a href='edit.php?nrp=<?= urlencode($data['nrp']); ?>'>Edit</a> | 
            <a href='hapus.php?nrp=<?= urlencode($data['nrp']); ?>' onclick="return confirm('Yakin ingin menghapus data ini?');">Hapus</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
            
<div class="add-button-container">
    <a href="tambah.php" class="btn-add">+ Tambah Mahasiswa Baru</a>
</div><br>

<form method="get" style="text-align:left; margin-bottom:10px;">
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
