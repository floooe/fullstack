<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../home.php');
    exit;
}
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $nrp = $_POST['nrp'];
    $nama = $_POST['nama'];
    $gender = $_POST['gender'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $angkatan = $_POST['angkatan'];
    $akun_username = isset($_POST['akun_username']) && $_POST['akun_username'] !== '' ? $_POST['akun_username'] : $nrp;
    $akun_password = $_POST['akun_password'] ?? '';

    if (trim($akun_password) === '') {
        die("ERROR: Password akun harus diisi.");
    }

    $check_stmt = $mysqli->prepare("SELECT nrp FROM mahasiswa WHERE nrp = ?");
    $check_stmt->bind_param('s', $nrp);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        die("ERROR: NRP '{$nrp}' sudah terdaftar. Silakan gunakan NRP lain.");
    }
    $check_stmt->close();

    $foto_extension = null;
    
    //up foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $foto = $_FILES['foto'];
        $foto_extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $nama_file_baru = $nrp . '.' . $foto_extension;
        $lokasi_upload = "../../uploads/mahasiswa/" . $nama_file_baru;

        if (!move_uploaded_file($foto['tmp_name'], $lokasi_upload)) {
            die("GAGAL UPLOAD FILE: Pastikan folder 'uploads/mahasiswa' ada dan memiliki izin tulis.");
        }
    }

    // Pastikan username akun unik
    $check_akun = $mysqli->prepare("SELECT username FROM akun WHERE username = ?");
    $check_akun->bind_param('s', $akun_username);
    $check_akun->execute();
    $check_akun->store_result();
    if ($check_akun->num_rows > 0) {
        if (isset($lokasi_upload) && file_exists($lokasi_upload)) {
            @unlink($lokasi_upload);
        }
        die("ERROR: Username akun '{$akun_username}' sudah ada. Gunakan username lain.");
    }
    $check_akun->close();

    // Transaksi: simpan mahasiswa + akun
    $mysqli->begin_transaction();
    try {
        // cek apakah tabel mahasiswa punya kolom akun_username
        $hasAkunCol = false;
        $colRes = $mysqli->query("SHOW COLUMNS FROM mahasiswa");
        while ($c = $colRes->fetch_assoc()) {
            if ($c['Field'] === 'akun_username') { $hasAkunCol = true; break; }
        }
        $colRes->free();

        if ($hasAkunCol) {
            $query = "INSERT INTO mahasiswa (nrp, nama, gender, tanggal_lahir, angkatan, foto_extention, akun_username) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($query);
            if ($stmt === false) { throw new Exception($mysqli->error); }
            $stmt->bind_param('sssssss', $nrp, $nama, $gender, $tanggal_lahir, $angkatan, $foto_extension, $akun_username);
        } else {
            $query = "INSERT INTO mahasiswa (nrp, nama, gender, tanggal_lahir, angkatan, foto_extention) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($query);
            if ($stmt === false) { throw new Exception($mysqli->error); }
            $stmt->bind_param('ssssss', $nrp, $nama, $gender, $tanggal_lahir, $angkatan, $foto_extension);
        }
        if (!$stmt->execute()) { throw new Exception($stmt->error); }
        $stmt->close();

        $queryAkun = "INSERT INTO akun (username, password, isadmin) VALUES (?, MD5(?), 0)";
        $stmtA = $mysqli->prepare($queryAkun);
        $stmtA->bind_param('ss', $akun_username, $akun_password);
        if (!$stmtA->execute()) { throw new Exception($stmtA->error); }
        $stmtA->close();

        $mysqli->commit();
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $mysqli->rollback();
        if (isset($lokasi_upload) && file_exists($lokasi_upload)) {
            @unlink($lokasi_upload);
        }
        die("DATABASE ERROR: " . $e->getMessage());
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Mahasiswa</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            background-color: lightgray;
            margin: 0;
            padding: 40px;
        }
        h2{
            text-align: center;
            color: darkblue;
            margin-bottom: 20px;
        }
        form{
            max-width: 400px;
            margin: auto;
            background: white;
            padding: 20px 35px;
            border-radius: 6px;
            box-shadow: 0 0 8px gray;
        }
        label{
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: black;
        }
        input[type="text"], input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid gray;
            border-radius: 4px;
        }
        button{
            display: block;
            width: 100%;
            background-color: green;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s ease-in-out, transform 0.1s;
        }
        button:hover{
            background-color: darkgreen;
        }
    </style>
</head>
<body>
    <h2>Tambah Data Mahasiswa</h2>
    
    <form action="tambah.php" method="POST" enctype="multipart/form-data">
        <label for="nrp">NRP:</label>
        <input type="text" name="nrp" id="nrp" required>

         <label for="nama">Nama:</label>
         <input type="text" name="nama" id="nama" required>
 
         <fieldset style="margin:15px 0; padding:10px; border:1px solid #ddd;">
             <legend>Akun Login</legend>
             <small>Jika dikosongkan, username otomatis pakai NRP.</small><br>
             <label for="akun_username">Username</label>
             <input type="text" id="akun_username" name="akun_username" placeholder="default: NRP"><br><br>
             <label for="akun_password">Password</label>
             <input type="password" id="akun_password" name="akun_password" required>
         </fieldset>

        <label for="gender">Jenis Kelamin:</label>
        <select name="gender" id="gender" required>
            <option value="">-- Pilih Gender --</option>
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
        </select><br><br>

        <label for="tanggal_lahir">Tanggal Lahir:</label>
        <input type="date" name="tanggal_lahir" id="tanggal_lahir" required><br><br>

        <label for="angkatan">Angkatan:</label>
        <input type="text" name="angkatan" id="angkatan" required placeholder="contoh: 2022">

        <label for="foto">Foto:</label>
        <input type="file" name="foto" id="foto">

        <button type="submit">💾 Simpan</button>
    </form>
</body>
</html>
