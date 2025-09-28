<?php
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Gagal terhubung ke MySQL: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nrp = $_POST['nrp'];
    $nama = $_POST['nama_mahasiswa'];
    $jurusan = $_POST['jurusan'];
    
    $foto = $_FILES['foto'];
    $nama_foto = $nrp . '.' . pathinfo($foto['name'], PATHINFO_EXTENSION); // Nama file: nrp.jpg
    $lokasi_upload = "../uploads/mahasiswa/" . $nama_foto;

    if (move_uploaded_file($foto['tmp_name'], $lokasi_upload)) {
        $query = "INSERT INTO mahasiswa (nrp, nama_mahasiswa, jurusan, foto) VALUES ('$nrp', '$nama', '$jurusan', '$nama_foto')";
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
    <title>Tambah Mahasiswa</title>
</head>
<body>
    <h2>Tambah Data Mahasiswa</h2>
    <form action="tambah.php" method="POST" enctype="multipart/form-data">
        NRP: <input type="text" name="nrp" required><br><br>
        Nama: <input type="text" name="nama_mahasiswa" required><br><br>
        Jurusan: <input type="text" name="jurusan" required><br><br>
        Foto: <input type="file" name="foto" required><br><br>
        <button type="submit">Simpan</button>
    </form>
</body>
</html>