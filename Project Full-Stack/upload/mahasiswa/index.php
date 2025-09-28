<?php
require '/proses/koneksi.php';
$query = "SELECT * FROM mahasiswa";
$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Data Mahasiswa</title>
    </head>
<body>
    <h2>Data Mahasiswa</h2>
    <a href="tambah.php">Tambah Mahasiswa Baru</a>
    <br><br>
    <table border="1">
        <tr>
            <th>No</th>
            <th>NRP</th>
            <th>Nama</th>
            <th>Jurusan</th>
            <th>Foto</th>
            <th>Aksi</th>
        </tr>
        <?php $no = 1; while ($data = mysqli_fetch_assoc($result)) : ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($data['nrp']); ?></td>
            <td><?= htmlspecialchars($data['nama_mahasiswa']); ?></td>
            <td><?= htmlspecialchars($data['jurusan']); ?></td>
            <td>
                <img src="../uploads/mahasiswa/<?= htmlspecialchars($data['foto']); ?>" width="100">
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