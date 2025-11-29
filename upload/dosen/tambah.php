<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../home.php');
    exit;
}
include "../../proses/koneksi.php";
//move_uploaded_file($_FILES['foto']['tmp_name'], "../../uploads/dosen/" . $nama_file); 
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $npk = $_POST['npk'];
    $nama = $_POST['nama'];
    $akun_username = isset($_POST['akun_username']) && $_POST['akun_username'] !== '' ? $_POST['akun_username'] : $npk;
    $akun_password = $_POST['akun_password'] ?? '';

    if (trim($akun_password) === '') {
        die("ERROR: Password akun harus diisi.");
    }

    $check_stmt = $mysqli->prepare("SELECT npk FROM dosen WHERE npk = ?");
    $check_stmt->bind_param('s', $npk);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        die("ERROR: NPK '{$npk}' sudah terdaftar. Silakan gunakan NPK lain.");
    }
    $check_stmt->close();

    //default = null
    $foto_extension = null;
    
    //proses up foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = $_FILES['foto'];
        $foto_extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $nama_file_baru = $npk . '.' . $foto_extension;
        $lokasi_upload = "../../uploads/dosen/" . $nama_file_baru;

        if (!move_uploaded_file($foto['tmp_name'], $lokasi_upload)) {
            die("GAGAL UPLOAD FILE: Pastikan folder 'uploads/dosen' ada dan memiliki izin tulis.");
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

    // Transaksi: simpan dosen + akun
    $mysqli->begin_transaction();
    try {
        // cek apakah tabel dosen punya kolom akun_username
        $hasAkunCol = false;
        $colRes = $mysqli->query("SHOW COLUMNS FROM dosen");
        while ($c = $colRes->fetch_assoc()) {
            if ($c['Field'] === 'akun_username') { $hasAkunCol = true; break; }
        }
        $colRes->free();

        if ($hasAkunCol) {
            $query = "INSERT INTO dosen (npk, nama, foto_extension, akun_username) VALUES (?, ?, ?, ?)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('ssss', $npk, $nama, $foto_extension, $akun_username);
        } else {
            $query = "INSERT INTO dosen (npk, nama, foto_extension) VALUES (?, ?, ?)";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param('sss', $npk, $nama, $foto_extension);
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Dosen</title>
</head>
<body>
    <div class="container">
        <h2>Tambah Data Dosen</h2>
        
        <form action="tambah.php" method="POST" enctype="multipart/form-data">
            <label for="npk">NPK</label>
            <input type="text" id="npk" name="npk" required><br><br>
            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" required><br><br>
            <fieldset style="margin:15px 0; padding:10px; border:1px solid #ddd;">
                <legend>Akun Login</legend>
                <small>Jika dikosongkan, username otomatis pakai NPK.</small><br>
                <label for="akun_username">Username</label>
                <input type="text" id="akun_username" name="akun_username" placeholder="default: NPK"><br><br>
                <label for="akun_password">Password</label>
                <input type="password" id="akun_password" name="akun_password" required>
            </fieldset>
            <label for="foto">Foto</label>
            <input type="file" id="foto" name="foto"><br><br>
            <button type="submit">💾 Simpan</button>
        </form>

        <a href="index.php" class="back-link">← Kembali ke Daftar Dosen</a>
    </div>

    <style>
        body{
            font-family: Arial, sans-serif;
            background-color: white;
            margin: 0;
            padding: 20px;
        }
        .container{
            max-width: 500px;
            margin: 40px auto;
            padding: 25px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2{
            text-align: center;
            margin-bottom: 25px;
            color: #252020ff;
        }
        label{
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: grey;
        }
        button{
            display: block;
            width: 100%;
            padding: 10px;
            background-color: blue;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s ease-in-out;
        }
        button:hover{
            background-color: blue;
        }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: solid blue;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</body>
</html>
