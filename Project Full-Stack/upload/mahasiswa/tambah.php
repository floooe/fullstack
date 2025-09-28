<?php
// 1. KONEKSI DATABASE
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

// 2. PROSES FORM JIKA DISUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Ambil data dari form
    $nrp = $_POST['nrp'];
    $nama = $_POST['nama'];

    // LANGKAH VALIDASI: Cek dulu apakah NRP sudah ada
    $check_stmt = $mysqli->prepare("SELECT nrp FROM mahasiswa WHERE nrp = ?");
    $check_stmt->bind_param('s', $nrp);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // Jika num_rows > 0, artinya NRP sudah ada
        die("ERROR: NRP '{$nrp}' sudah terdaftar. Silakan gunakan NRP lain.");
    }
    $check_stmt->close();
    // Jika NRP aman, lanjutkan proses di bawah

    $foto_extension = null;
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = $_FILES['foto'];
        $foto_extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $nama_file_baru = $nrp . '.' . $foto_extension;
        $lokasi_upload = "../../uploads/mahasiswa/" . $nama_file_baru;

        if (!move_uploaded_file($foto['tmp_name'], $lokasi_upload)) {
            die("GAGAL UPLOAD FILE: Pastikan folder 'uploads/mahasiswa' ada dan memiliki izin tulis.");
        }
    }

    // SIMPAN DATA KE DATABASE
    $query = "INSERT INTO mahasiswa (nrp, nama, foto_extention) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    if ($stmt === false) {
        die("Gagal menyiapkan statement: " . $mysqli->error);
    }
    
    $stmt->bind_param('sss', $nrp, $nama, $foto_extension);
    
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
    <title>Tambah Mahasiswa</title>
</head>
<body>
    <h2>Tambah Data Mahasiswa</h2>
    
    <form action="tambah.php" method="POST" enctype="multipart/form-data">
        NRP: <input type="text" name="nrp" required><br><br>
        Nama: <input type="text" name="nama" required><br><br> Foto: <input type="file" name="foto"><br><br>
        <button type="submit">Simpan</button>
    </form>

</body>
</html>