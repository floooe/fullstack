<?php
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Gagal terhubung ke MySQL: " . $mysqli->connect_error);
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
$offset = ($page - 1) * $limit;

$totalResult = $mysqli->query("SELECT COUNT(*) AS total FROM mahasiswa");
$totalData = $totalResult->fetch_assoc()['total'];
$totalPage = ceil($totalData / $limit);

$sql = "SELECT * FROM mahasiswa ORDER BY nrp DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Mahasiswa</title>
  <style>
    body { font-family: sans-serif; }
    table { border-collapse: collapse; margin-bottom: 20px; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
    th { background-color: #f2f2f2; }
    .pagination a, .pagination b { display: inline-block; padding: 8px 12px; border: 1px solid #ddd; margin: 0 3px; text-decoration: none; color: #333; }
    .pagination b { background-color: #007BFF; color: white; border-color: #007BFF; }
    .pagination a:hover { background-color: #f2f2f2; }
    a { color: #007BFF; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>

<h2>Daftar Mahasiswa</h2>
<a href="tambah.php">Tambah Mahasiswa Baru</a>
<br><br>

<table>
  <tr>
    <th>No</th>
    <th>NRP</th>
    <th>Nama</th>
    <th>Jurusan</th> <th>Foto</th>
    <th>Aksi</th>
  </tr>

<?php
$no = $offset + 1;
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$no}</td>";
    echo "<td>" . htmlspecialchars($row['nrp']) . "</td>";
    echo "<td>" . htmlspecialchars($row['nama_mahasiswa']) . "</td>"; 
    echo "<td>" . htmlspecialchars($row['jurusan']) . "</td>"; 

    if (!empty($row['foto'])) {
        echo "<td><img src='../uploads/mahasiswa/" . htmlspecialchars($row['foto']) . "' height='75'></td>";
    } else {
        echo "<td>-</td>";
    }

    echo "<td>
            <a href='edit.php?id={$row['id']}'>Edit</a> | 
            <a href='hapus.php?id={$row['id']}' onclick=\"return confirm('Yakin ingin menghapus data ini?');\">Hapus</a>
          </td>";
    echo "</tr>";
    $no++;
}
?>
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
<?php
if ($page > 1) {
    echo "<a href='?page=1&limit=$limit'>First</a>";
    echo "<a href='?page=".($page-1)."&limit=$limit'>Prev</a>";
}

for ($i = 1; $i <= $totalPage; $i++) {
    if ($i == $page) {
        echo "<b>$i</b>";
    } else {
        echo "<a href='?page=$i&limit=$limit'>$i</a>";
    }
}

if ($page < $totalPage) {
    echo "<a href='?page=".($page+1)."&limit=$limit'>Next</a>";
    echo "<a href='?page=$totalPage&limit=$limit'>Last</a>";
}
?>
</div>

<?php
$stmt->close();
$mysqli->close();
?>

</body>
</html>