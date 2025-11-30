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
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        $foto = $_FILES['foto'];
        $foto_extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $nama_file_baru = $nrp . '.' . $foto_extension;
        $lokasi_upload = "../../uploads/mahasiswa/" . $nama_file_baru;

        if (!move_uploaded_file($foto['tmp_name'], $lokasi_upload)) {
            die("GAGAL UPLOAD FILE: Pastikan folder 'uploads/mahasiswa' ada dan memiliki izin tulis.");
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
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/mahasiswa.css">
</head>
<body class="mahasiswa-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Tambah Data Mahasiswa</h2>
                <p class="page-subtitle">Lengkapi data mahasiswa berikut.</p>
            </div>
            <button type="button" class="btn btn-small" onclick="location.href='index.php'">Kembali</button>
        </div>

        <div class="card">
            <form action="tambah.php" method="POST" enctype="multipart/form-data" class="section">
                <div class="field">
                    <label for="nrp">NRP</label>
                    <input type="text" name="nrp" id="nrp" required>
                </div>

                <div class="field">
                    <label for="nama">Nama</label>
                    <input type="text" name="nama" id="nama" required>
                </div>
 
                <div class="card card-compact card-dashed">
                    <strong>Akun Login</strong>
                    <p class="muted">Jika dikosongkan, username otomatis pakai NRP.</p>
                    <div class="field">
                        <label for="akun_username">Username</label>
                        <input type="text" id="akun_username" name="akun_username" placeholder="default: NRP">
                    </div>
                    <div class="field">
                        <label for="akun_password">Password</label>
                        <input type="password" id="akun_password" name="akun_password" required>
                    </div>
                </div>

                <div class="field">
                    <label for="gender">Jenis Kelamin</label>
                    <select name="gender" id="gender" required>
                        <option value="">-- Pilih Gender --</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>

                <div class="field">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" id="tanggal_lahir" required>
                </div>

                <div class="field">
                    <label for="angkatan">Angkatan</label>
                    <input type="text" name="angkatan" id="angkatan" required placeholder="contoh: 2022">
                </div>

                <div class="field">
                    <label for="foto">Foto</label>
                    <input type="file" name="foto" id="foto">
                </div>

                <button type="submit" class="btn">Simpan</button>
            </form>
        </div>
    </div>
</body>
</html>
