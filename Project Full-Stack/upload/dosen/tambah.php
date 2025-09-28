<?php
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $npk = $_POST['npk'];
    $nama = $_POST['nama'];

    $check_stmt = $mysqli->prepare("SELECT npk FROM dosen WHERE npk = ?");
    $check_stmt->bind_param('s', $npk);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        die("ERROR: NPK '{$npk}' sudah terdaftar. Silakan gunakan NPK lain.");
    }
    $check_stmt->close();

    $foto_extension = null;
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = $_FILES['foto'];
        $foto_extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $nama_file_baru = $npk . '.' . $foto_extension;
        $lokasi_upload = "../../uploads/dosen/" . $nama_file_baru;

        if (!move_uploaded_file($foto['tmp_name'], $lokasi_upload)) {
            die("GAGAL UPLOAD FILE: Pastikan folder 'uploads/dosen' ada dan memiliki izin tulis.");
        }
    }

    $query = "INSERT INTO dosen (npk, nama, foto_extension) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sss', $npk, $nama, $foto_extension);
    
    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "DATABASE ERROR: " . $stmt->error;
    }
    
    $stmt->close();
}

$mysqli->close();
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
        Nama: <input type="text" name="nama" required><br><br>
        Foto: <input type="file" name="foto"><br><br>
        <button type="submit">Simpan</button>
    </form>

</body>
</html>