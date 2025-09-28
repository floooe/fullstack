<?php
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

if (!isset($_GET['npk'])) {
    die("Error: NPK dosen tidak ditemukan.");
}
$npk_asli = $_GET['npk'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $npk_baru = $_POST['npk'];
    $nama_baru = $_POST['nama'];
    $ext_foto_lama = $_POST['ext_foto_lama'];
    
    $ext_foto_final = $ext_foto_lama;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        if (!empty($ext_foto_lama)) {
            $file_foto_lama = "../../uploads/dosen/" . $npk_asli . '.' . $ext_foto_lama;
            if (file_exists($file_foto_lama)) {
                unlink($file_foto_lama);
            }
        }

        $foto_baru = $_FILES['foto'];
        $ext_foto_final = pathinfo($foto_baru['name'], PATHINFO_EXTENSION);
        $nama_file_baru = $npk_baru . '.' . $ext_foto_final;
        $lokasi_upload = "../../uploads/dosen/" . $nama_file_baru;
        
        if (!move_uploaded_file($foto_baru['tmp_name'], $lokasi_upload)) {
            die("Gagal Upload Foto Baru.");
        }
    }

    $query = "UPDATE dosen SET npk = ?, nama = ?, foto_extension = ? WHERE npk = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssss', $npk_baru, $nama_baru, $ext_foto_final, $npk_asli);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        die("DATABASE ERROR: " . $stmt->error);
    }
    $stmt->close();
}

$query = "SELECT npk, nama, foto_extension FROM dosen WHERE npk = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('s', $npk_asli);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Data dosen dengan NPK tersebut tidak ditemukan.");
}
$data = $result->fetch_assoc();
$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Dosen</title>
</head>
<body>
    <h2>Edit Data Dosen</h2>
    
    <form action="edit.php?npk=<?= htmlspecialchars($data['npk']); ?>" method="POST" enctype="multipart/form-data">
        NPK: <input type="text" name="npk" value="<?= htmlspecialchars($data['npk']); ?>" required><br><br>
        Nama: <input type="text" name="nama" value="<?= htmlspecialchars($data['nama']); ?>" required><br><br>
        
        Foto Saat Ini:<br>
        <?php if (!empty($data['foto_extension'])): ?>
            <img src="../uploads/dosen/<?= htmlspecialchars($data['npk']) . '.' . htmlspecialchars($data['foto_extension']); ?>" height="100">
        <?php else: ?>
            <span>Tidak ada foto</span>
        <?php endif; ?>
        <br><br>
        
        Ganti Foto (kosongkan jika tidak ingin diubah):<br>
        <input type="file" name="foto"><br><br>
        
        <input type="hidden" name="ext_foto_lama" value="<?= htmlspecialchars($data['foto_extension']); ?>">
        
        <button type="submit">Update Data</button>
    </form>
</body>
</html>