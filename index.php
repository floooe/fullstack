<?php
$mysqli = new mysqli("localhost", 'root', '', 'dbkampus');
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// --- Ambil parameter pagination ---
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- Hitung total data (gabungan dosen + mahasiswa) ---
$resultTotal = $mysqli->query("SELECT 
    (SELECT COUNT(*) FROM dosen) + (SELECT COUNT(*) FROM mahasiswa) as total");
$totalData = $resultTotal->fetch_assoc()['total'];
$totalPage = ceil($totalData / $limit);

// --- Query gabungan data dosen & mahasiswa ---
$sql = "
    SELECT 'dosen' as jenis, npk as id, nama, foto_extention, NULL as gender, NULL as tanggal_lahir, NULL as angkatan
    FROM dosen
    UNION ALL
    SELECT 'mahasiswa' as jenis, nrp as id, nama, foto_extention, gender, tanggal_lahir, angkatan
    FROM mahasiswa
    LIMIT ?, ?
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$res = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Dosen & Mahasiswa</title>
  <style>
    table { border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid black; padding: 6px 10px; }
    .pagination a { margin: 0 3px; text-decoration: none; }
    .pagination b { margin: 0 3px; }
  </style>
</head>
<body>

<h2>Daftar Dosen & Mahasiswa</h2>
<a href="tambah.php?jenis=dosen">Tambah Dosen</a> | 
<a href="tambah.php?jenis=mahasiswa">Tambah Mahasiswa</a>

<table>
  <tr>
    <th>Jenis</th>
    <th>ID (NPK/NRP)</th>
    <th>Nama</th>
    <th>Gender</th>
    <th>Tanggal Lahir</th>
    <th>Angkatan</th>
    <th>Foto</th>
    <th>Aksi</th>
  </tr>

<?php
while ($row = $res->fetch_assoc()) {
    $fotoPath = "uploads/" . $row['id'] . $row['foto_extention'];

    echo "<tr>";
    echo "<td>{$row['jenis']}</td>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['nama']}</td>";
    echo "<td>" . ($row['jenis']=='mahasiswa' ? $row['gender'] : '-') . "</td>";
    echo "<td>" . ($row['jenis']=='mahasiswa' ? $row['tanggal_lahir'] : '-') . "</td>";
    echo "<td>" . ($row['jenis']=='mahasiswa' ? $row['angkatan'] : '-') . "</td>";
    if (!empty($row['foto_extention'])) {
        echo "<td><img src='$fotoPath' height='75'></td>";
    } else {
        echo "<td>-</td>";
    }
    echo "<td>
            <a href='edit.php?jenis={$row['jenis']}&id={$row['id']}'>Edit</a> | 
            <a href='hapus.php?jenis={$row['jenis']}&id={$row['id']}'>Hapus</a>
          </td>";
    echo "</tr>";
}
?>
</table>

<!-- ComboBox pilih limit -->
<form method="get" style="margin-bottom:10px;">
  Tampilkan 
  <select name="limit" onchange="this.form.submit()">
    <?php foreach([5,10,15,20] as $opt): ?>
      <option value="<?=$opt?>" <?=($opt==$limit)?'selected':''?>><?=$opt?></option>
    <?php endforeach; ?>
  </select>
  data per halaman
</form>

<!-- Pagination -->
<div class="pagination">
<?php
if($page > 1){
    echo "<a href='?page=1&limit=$limit'>First</a>";
    echo "<a href='?page=".($page-1)."&limit=$limit'>Prev</a>";
}

for($i=1; $i <= $totalPage; $i++){
    if($i == $page){
        echo "<b>$i</b>";
    } else {
        echo "<a href='?page=$i&limit=$limit'>$i</a>";
    }
}

if($page < $totalPage){
    echo "<a href='?page=".($page+1)."&limit=$limit'>Next</a>";
    echo "<a href='?page=$totalPage&limit=$limit'>Last</a>";
}
?>
</div>

</body>
</html>

<?php
$stmt->close();
$mysqli->close();
?>
