<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../Project Full-Stack/home.php');
    exit;
}
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

if (!isset($_GET['nrp'])) {
    die("Error: NRP mahasiswa tidak ditemukan.");
}
$nrp_asli = $_GET['nrp'];

//ambil data
$query = "SELECT nrp, nama, gender, tanggal_lahir, angkatan, foto_extention FROM mahasiswa WHERE nrp = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('s', $nrp_asli);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Data mahasiswa dengan NRP tersebut tidak ditemukan.");
}
$data = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nrp_baru = $_POST['nrp'];
    $nama_baru = $_POST['nama'];
    $gender_baru = $_POST['gender'];
    $tanggal_lahir_baru = $_POST['tanggal_lahir'];
    $angkatan_baru = $_POST['angkatan'];
    $ext_foto_lama = $_POST['ext_foto_lama']; 
    
    $ext_foto_final = $ext_foto_lama;

    // Proses upload foto baru
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

    //update data mahasiswa
    $query = "UPDATE mahasiswa 
              SET nrp = ?, nama = ?, gender = ?, tanggal_lahir = ?, angkatan = ?, foto_extention = ? 
              WHERE nrp = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sssssss', $nrp_baru, $nama_baru, $gender_baru, $tanggal_lahir_baru, $angkatan_baru, $ext_foto_final, $nrp_asli);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        die("DATABASE ERROR: " . $stmt->error);
    }
    $stmt->close();
}
$mysqli->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Mahasiswa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: lightgray;
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: darkblue;
            margin-bottom: 20px;
        }
        form {
            max-width: 400px;
            margin: auto;
            background-color: white;
            padding: 20px;
            border: 2px solid lightblue;
            border-radius: 8px;
        }
        label {
            font-weight: bold;
            color: black;
        }
        button {
            background-color: green;
            color: white;
            border: none;
            padding: 10px 15px; 
            border-radius: 4px;
        }
        button:hover {
            background-color: darkgreen;
        }
        span {
            color: red;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
    <h2>Edit Data Mahasiswa</h2>

    <form action="edit.php?nrp=<?= htmlspecialchars($data['nrp']); ?>" method="POST" enctype="multipart/form-data">
        <label for="nrp">NRP:</label><br>
        <input type="text" id="nrp" name="nrp" value="<?= htmlspecialchars($data['nrp']); ?>" required><br><br>

        <label for="nama">Nama:</label><br>
        <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($data['nama']); ?>" required><br><br>

        <label for="gender">Jenis Kelamin:</label><br>
        <select id="gender" name="gender" required>
            <option value="">-- Pilih Gender --</option>
            <option value="Pria" <?= $data['gender'] == 'Pria' ? 'selected' : '' ?>>Pria</option>
            <option value="Wanita" <?= $data['gender'] == 'Wanita' ? 'selected' : '' ?>>Wanita</option>
        </select><br><br>

        <label for="tanggal_lahir">Tanggal Lahir:</label><br>
        <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= htmlspecialchars($data['tanggal_lahir']); ?>"><br><br>

        <label for="angkatan">Angkatan:</label><br>
        <input type="text" id="angkatan" name="angkatan" value="<?= htmlspecialchars($data['angkatan']); ?>" placeholder="contoh: 2022"><br><br>

        <label>Foto Saat Ini:</label>
        <?php if (!empty($data['foto_extention'])): ?>
            <div class="foto-preview">
                <img src="../../uploads/mahasiswa/<?= htmlspecialchars($data['nrp']) . '.' . htmlspecialchars($data['foto_extention']); ?>" class="thumb" alt="Foto Mahasiswa">
            </div>
        <?php else: ?>
            <div class="no-photo">Tidak ada foto</div>
        <?php endif; ?>
        <input type="hidden" name="ext_foto_lama" value="<?= htmlspecialchars($data['foto_extention']); ?>">

        <label for="foto">Ganti Foto (opsional):</label>
        <input type="file" id="foto" name="foto">

        <button type="submit">🔄 Update Data</button>
    </form>

    <a href="index.php" class="back-link">← Kembali ke Daftar Mahasiswa</a>
</div>

</body>
</html>
