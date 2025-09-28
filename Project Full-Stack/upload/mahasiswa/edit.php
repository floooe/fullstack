<?php
require '../koneksi.php';
$id = $_GET['id'];
$query = "SELECT * FROM mahasiswa WHERE id=$id";
$result = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nrp = $_POST['nrp'];
    $nama = $_POST['nama_mahasiswa'];
    $jurusan = $_POST['jurusan'];
    $foto_lama = $_POST['foto_lama'];
    
    $nama_foto = $foto_lama;

    //jika ada foto baru diupload
    if (!empty($_FILES['foto']['name'])) {
        //hapus foto lama kalau ada
        if (!empty($foto_lama) && file_exists("../uploads/mahasiswa/" . $foto_lama)) {
            unlink("../uploads/mahasiswa/" . $foto_lama);
        }

        //simpan foto baru
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nama_foto = $nrp . '.' . $ext; // nama file = nrp.ext
        $lokasi_upload = "../uploads/mahasiswa/" . $nama_foto;
        move_uploaded_file($_FILES['foto']['tmp_name'], $lokasi_upload);
    }

    $query_update = "UPDATE mahasiswa SET nrp='$nrp', nama_mahasiswa='$nama', jurusan='$jurusan', foto='$nama_foto' WHERE id=$id";
    if(mysqli_query($koneksi, $query_update)) {
        header("Location: index.php");
        exit;
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Mahasiswa</title>
</head>
<body>
    <h2>Edit Data Mahasiswa</h2>
    <form action="edit.php?id=<?= $id; ?>" method="POST" enctype="multipart/form-data">
        NRP: <input type="text" name="nrp" value="<?= htmlspecialchars($data['nrp']); ?>" required><br><br>
        Nama: <input type="text" name="nama_mahasiswa" value="<?= htmlspecialchars($data['nama_mahasiswa']); ?>" required><br><br>
        Jurusan: <input type="text" name="jurusan" value="<?= htmlspecialchars($data['jurusan']); ?>" required><br><br>
        
        Foto Saat Ini: <br>
        <?php if (!empty($data['foto'])): ?>
            <img src="../uploads/mahasiswa/<?= htmlspecialchars($data['foto']); ?>" width="100"><br>
        <?php else: ?>
            <span>Tidak ada foto</span><br>
        <?php endif; ?>

        Ganti Foto (kosongkan jika tidak ingin ganti): 
        <input type="file" name="foto" accept=".jpg,.jpeg,.png"><br><br>

        <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($data['foto']); ?>">
        <button type="submit">Update</button>
    </form>
</body>
</html>
