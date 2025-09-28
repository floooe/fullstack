<?php
require '../koneksi.php';
$query = "SELECT * FROM dosen";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Data Dosen</title>
    </head>
<body>
    <h2>Data Dosen</h2>
    <a href="tambah.php">Tambah Dosen Baru</a>
    <br><br>
    <table border="1">
        <tr>
            <th>No</th>
            <th>NPK</th>
            <th>Nama Dosen</th>
            <th>Email</th>
            <th>Foto</th>
            <th>Aksi</th>
        </tr>
        <?php $no = 1; while ($data = mysqli_fetch_assoc($result)) : ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($data['npk']); ?></td>
            <td><?= htmlspecialchars($data['nama_dosen']); ?></td>
            <td><?= htmlspecialchars($data['email']); ?></td>
            <td>
                <img src="../uploads/dosen/<?= htmlspecialchars($data['foto']); ?>" width="100">
            </td>
            <td>
                <a href="edit.php?id=<?= $data['id']; ?>">Edit</a> |
                <a href="hapus.php?id=<?= $data['id']; ?>" onclick="return confirm('Yakin ingin menghapus data ini?');">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>