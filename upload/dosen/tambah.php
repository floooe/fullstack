<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../home.php');
    exit;
}
include "../../proses/koneksi.php";

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

    $mysqli->begin_transaction();
    try {
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
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/dosen.css">
</head>
<body class="dosen-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Tambah Data Dosen</h2>
                <p class="page-subtitle">Lengkapi data berikut untuk menambahkan dosen baru.</p>
            </div>
            <button type="button" class="btn btn-secondary btn-small" onclick="location.href='index.php'">Kembali</button>
        </div>

        <div class="card">
            <form action="tambah.php" method="POST" enctype="multipart/form-data" class="section">
                <div class="field">
                    <label for="npk">NPK</label>
                    <input type="text" id="npk" name="npk" required>
                </div>
                <div class="field">
                    <label for="nama">Nama</label>
                    <input type="text" id="nama" name="nama" required>
                </div>
                <div class="card card-compact card-dashed">
                    <strong>Akun Login</strong>
                    <p class="muted">Jika dikosongkan, username otomatis pakai NPK.</p>
                    <div class="field">
                        <label for="akun_username">Username</label>
                        <input type="text" id="akun_username" name="akun_username" placeholder="default: NPK">
                    </div>
                    <div class="field">
                        <label for="akun_password">Password</label>
                        <input type="password" id="akun_password" name="akun_password" required>
                    </div>
                </div>
                <div class="field">
                    <label for="foto">Foto</label>
                    <input type="file" id="foto" name="foto">
                </div>
                <button type="submit" class="btn">Simpan</button>
            </form>
        </div>
    </div>
</body>
</html>
