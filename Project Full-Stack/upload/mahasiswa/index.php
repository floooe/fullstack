<?php
require '/proses/koneksi.php';
$query = "SELECT * FROM mahasiswa";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Mahasiswa</title>
  <style>
    table { border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid black; padding: 6px 10px; }
    .pagination a { margin: 0 3px; text-decoration: none; }
    .pagination b { margin: 0 3px; }
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
    <th>Email</th>
    <th>Foto</th>
    <th>Aksi</th>
  </tr>

<?php
$no = $offset + 1;
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$no}</td>";
    echo "<td>{$row['nrp']}</td>";
    echo "<td>{$row['nama']}</td>";
    echo "<td>{$row['email']}</td>";

    if (!empty($row['foto'])) {
        echo "<td><img src='uploads/mahasiswa/{$row['foto']}' height='75'></td>";
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
