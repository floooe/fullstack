<?php
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// --- Ambil parameter pagination ---
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- Hitung total data dosen ---
$resultTotal = $mysqli->query("SELECT COUNT(*) as total FROM dosen");
$totalData   = $resultTotal->fetch_assoc()['total'];
$totalPage   = ceil($totalData / $limit);

// --- Query data dosen sesuai pagination ---
$sql = "SELECT id, npk, nama, email, foto FROM dosen LIMIT ?, ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$res = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Dosen</title>
  <style>
    table { border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid black; padding: 6px 10px; }
    .pagination a { margin: 0 3px; text-decoration: none; }
    .pagination b { margin: 0 3px; }
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
    <th>Email</th>
    <th>Foto</th>
    <th>Aksi</th>
  </tr>

<?php
$no = $offset + 1;
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$no}</td>";
    echo "<td>".htmlspecialchars($row['npk'])."</td>";
    echo "<td>".htmlspecialchars($row['nama'])."</td>";
    echo "<td>".htmlspecialchars($row['email'])."</td>";
    
    if (!empty($row['foto'])) {
        echo "<td><img src='../uploads/dosen/".htmlspecialchars($row['foto'])."' height='75'></td>";
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

<?php
$stmt->close();
$mysqli->close();
?>

</body>
</html>
