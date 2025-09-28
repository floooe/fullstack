<?php
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

if (!isset($_GET['nrp'])) {
    die("Error: NRP mahasiswa tidak ditemukan.");
}
$nrp_asli = $_GET['nrp'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nrp_baru = $_POST['nrp'];
    $nama_baru = $_POST['nama'];
    $ext_foto_lama = $_POST['ext_foto_lama']; 
    
    $ext_foto_final = $ext_foto_lama;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        if (!empty($ext_foto_lama)) {
            $file_foto_lama = "../../uploads/mahasiswa/" . $nrp_asli . '.' . $ext_foto_lama;
            if (file_exists($file_foto_lama)) {
                unlink($file_foto_lama);
            }
        }

        $foto_baru = $_FILES['foto'];
        $ext_foto_final = pathinfo($foto_baru['name'], PATHINFO_EXTENSION);
        $nama_file_baru = $nrp_baru . '.' . $ext_foto_final;
        $lokasi_upload = "../../uploads/mahasiswa/" . $nama_file_baru;
        
        if (!move_uploaded_file($foto_baru['tmp_name'], $lokasi_upload)) {
            die("Gagal Upload Foto Baru.");
        }
    }

    $query = "UPDATE mahasiswa SET nrp = ?, nama = ?, foto_extention = ? WHERE nrp = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssss', $nrp_baru, $nama_baru, $ext_foto_final, $nrp_asli);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        die("DATABASE ERROR: " . $stmt->error);
    }
    $stmt->close();
}

$query = "SELECT nrp, nama, foto_extention FROM mahasiswa WHERE nrp = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('s', $nrp_asli);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Data mahasiswa dengan NRP tersebut tidak ditemukan.");
}
$data = $result->fetch_assoc();
$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Mahasiswa</title>
</head>
<body>
    <h2>Edit Data Mahasiswa</h2>
    
    <form action="edit.php?nrp=<?= htmlspecialchars($data['nrp']); ?>" method="POST" enctype="multipart/form-data">
        NRP: <input type="text" name="nrp" value="<?= htmlspecialchars($data['nrp']); ?>" required><br><br>
        Nama: <input type="text" name="nama" value="<?= htmlspecialchars($data['nama']); ?>" required><br><br>
        
        Foto Saat Ini:<br>
        <?php if (!empty($data['foto_extention'])): ?>
            <img src="../uploads/mahasiswa/<?= htmlspecialchars($data['nrp']) . '.' . htmlspecialchars($data['foto_extention']); ?>" height="100">
        <?php else: ?>
            <span>Tidak ada foto</span>
        <?php endif; ?>
        <br><br>
        
        Ganti Foto (kosongkan jika tidak ingin diubah):<br>
        <input type="file" name="foto"><br><br>
        
        <input type="hidden" name="ext_foto_lama" value="<?= htmlspecialchars($data['foto_extention']); ?>">
        
        <button type="submit">Update Data</button>
    </form>
</body>
</html>