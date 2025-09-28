<?php
require '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $npk = $_POST['npk'];
    $nama = $_POST['nama_dosen'];
    $email = $_POST['email'];
    
    $foto = $_FILES['foto'];
    $nama_foto = $npk . '.' . pathinfo($foto['name'], PATHINFO_EXTENSION); 
    $lokasi_upload = "../uploads/dosen/" . $nama_foto;

    if (move_uploaded_file($foto['tmp_name'], $lokasi_upload)) {
        $query = "INSERT INTO dosen (npk, nama_dosen, email, foto) VALUES ('$npk', '$nama', '$email', '$nama_foto')";
        if (mysqli_query($koneksi, $query)) {
            header("Location: index.php");
            exit;
        } else {
            echo "Error: " . mysqli_error($koneksi);
        }
    } else {
        echo "Gagal mengupload foto.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Dosen</title>
</head>
<body>
    <h2>Tambah Data Dosen</h2>
    <form action="tambah.php" method="POST" enctype="multipart/form-data">
        NPK: <input type="text" name="npk" required><br><br>
        Nama Dosen: <input type="text" name="nama_dosen" required><br><br>
        Email: <input type="email" name="email" required><br><br>
        Foto: <input type="file" name="foto" required><br><br>
        <button type="submit">Simpan</button>
    </form>
</body>
</html>