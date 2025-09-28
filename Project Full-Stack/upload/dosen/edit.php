<?php
require '../koneksi.php';
$id = $_GET['id'];
$query = "SELECT * FROM dosen WHERE id=$id";
$result = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $npk = $_POST['npk'];
    $nama = $_POST['nama_dosen'];
    $email = $_POST['email'];
    $foto_lama = $_POST['foto_lama'];
    
    $nama_foto = $foto_lama;

    //jika ada foto baru diupload
    if (!empty($_FILES['foto']['name'])) {
        //hapus foto lama kalau ada
        if (!empty($foto_lama) && file_exists("../uploads/dosen/" . $foto_lama)) {
            unlink("../uploads/dosen/" . $foto_lama);
        }
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed_ext)) {
            die("Format file tidak didukung. Hanya JPG, JPEG, atau PNG.");
        }
        $nama_foto = $npk . '.' . $ext;
        $lokasi_upload = "../uploads/dosen/" . $nama_foto;
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $lokasi_upload)) {
            echo "Gagal upload foto!";
        }
    }

    $query_update = "UPDATE dosen SET npk='$npk', nama_dosen='$nama', email='$email', foto='$nama_foto' WHERE id=$id";
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
    <title>Edit Dosen</title>
</head>
<body>
    <h2>Edit Data Dosen</h2>
    <form action="edit.php?id=<?= $id; ?>" method="POST" enctype="multipart/form-data">
        NPK: <input type="text" name="npk" value="<?= htmlspecialchars($data['npk']); ?>" required><br><br>
        Nama Dosen: <input type="text" name="nama_dosen" value="<?= htmlspecialchars($data['nama_dosen']); ?>" required><br><br>
        Email: <input type="email" name="email" value="<?= htmlspecialchars($data['email']); ?>" required><br><br>

        Foto Saat Ini: <br>
        <?php if (!empty($data['foto'])): ?>
            <img src="../uploads/dosen/<?= htmlspecialchars($data['foto']); ?>" width="100"><br>
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