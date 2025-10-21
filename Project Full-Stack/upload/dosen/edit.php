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

if (!isset($_GET['npk'])) {
    die("Error: NPK dosen tidak ditemukan.");
}
$npk_asli = $_GET['npk'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $npk_baru = $_POST['npk'];
    $nama_baru = $_POST['nama'];
    $ext_foto_lama = $_POST['ext_foto_lama'];
    $ext_foto_final = $ext_foto_lama;

    $akun_username_lama = isset($_POST['akun_username_lama']) ? trim($_POST['akun_username_lama']) : $npk_asli;
    $akun_username_baru = isset($_POST['akun_username']) && trim($_POST['akun_username']) !== ''
        ? trim($_POST['akun_username'])
        : $npk_baru;
    $akun_password = $_POST['akun_password'] ?? '';

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

    $mysqli->begin_transaction();
    try {
        $query = "UPDATE dosen SET npk = ?, nama = ?, foto_extension = ? WHERE npk = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ssss', $npk_baru, $nama_baru, $ext_foto_final, $npk_asli);
        if (!$stmt->execute()) { throw new Exception($stmt->error); }
        $stmt->close();

        // Update/insert akun
        $cekStmt = $mysqli->prepare("SELECT username FROM akun WHERE username = ?");
        $cekStmt->bind_param('s', $akun_username_lama);
        $cekStmt->execute();
        $cekStmt->store_result();
        $adaAkunLama = $cekStmt->num_rows > 0;
        $cekStmt->close();

        // Jika ganti username, pastikan unik
        if ($akun_username_baru !== $akun_username_lama) {
            $cekBaru = $mysqli->prepare("SELECT username FROM akun WHERE username = ?");
            $cekBaru->bind_param('s', $akun_username_baru);
            $cekBaru->execute();
            $cekBaru->store_result();
            if ($cekBaru->num_rows > 0) {
                throw new Exception("Username akun baru sudah digunakan");
            }
            $cekBaru->close();
        }

        if ($adaAkunLama) {
            if (trim($akun_password) !== '') {
                $u = $mysqli->prepare("UPDATE akun SET username = ?, password = MD5(?), isadmin = 0 WHERE username = ?");
                $u->bind_param('sss', $akun_username_baru, $akun_password, $akun_username_lama);
            } else {
                $u = $mysqli->prepare("UPDATE akun SET username = ?, isadmin = 0 WHERE username = ?");
                $u->bind_param('ss', $akun_username_baru, $akun_username_lama);
            }
            if (!$u->execute()) { throw new Exception($u->error); }
            $u->close();
        } else {
            // Akun belum ada: hanya buat jika password diberikan
            if (trim($akun_password) !== '') {
                $ins = $mysqli->prepare("INSERT INTO akun (username, password, isadmin) VALUES (?, MD5(?), 0)");
                $ins->bind_param('ss', $akun_username_baru, $akun_password);
                if (!$ins->execute()) { throw new Exception($ins->error); }
                $ins->close();
            }
        }

        $mysqli->commit();
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $mysqli->rollback();
        die("DATABASE ERROR: " . $e->getMessage());
    }
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Dosen</title>
</head>
<body>
    <div class="container">
        <h2>Edit Data Dosen</h2>
        
        <form action="edit.php?npk=<?= htmlspecialchars($data['npk']); ?>" method="POST" enctype="multipart/form-data">
            <label for="npk">NPK</label>
            <input type="text" id="npk" name="npk" value="<?= htmlspecialchars($data['npk']); ?>" required><br><br>

            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($data['nama']); ?>" required><br><br>
            <fieldset style="margin:15px 0; padding:10px; border:1px solid #ddd;">
                <legend>Akun Login</legend>
                <small>Biarkan password kosong jika tidak diubah.</small><br>
                <?php $prefUser = htmlspecialchars($data['npk']); ?>
                <label for="akun_username">Username</label>
                <input type="text" id="akun_username" name="akun_username" value="<?= $prefUser ?>" placeholder="default: NPK"><br><br>
                <input type="hidden" name="akun_username_lama" value="<?= $prefUser ?>">
                <label for="akun_password">Password Baru</label>
                <input type="password" id="akun_password" name="akun_password" placeholder="kosongkan jika tidak ganti">
            </fieldset>
            
            <label>Foto Saat Ini</label>
            <?php if (!empty($data['foto_extension'])): ?>
                <img src="../../uploads/dosen/<?= htmlspecialchars($data['npk']) . '.' . htmlspecialchars($data['foto_extension']); ?>" class="thumb">
            <?php else: ?>
                <span class="no-photo">Tidak ada foto</span>
            <?php endif; ?>
            <br><br>

            <label for="foto">Ganti Foto (opsional)</label>
            <input type="file" id="foto" name="foto">

            <input type="hidden" name="ext_foto_lama" value="<?= htmlspecialchars($data['foto_extension']); ?>"><br><br>

            <button type="submit">🔄 Update Data</button><br>
        </form>

        <a href="index.php" class="back-link">← Kembali ke Daftar Dosen</a>
    </div>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            padding: 25px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #444;
        }
        .no-photo {
            display: inline-block;
            padding: 8px 12px;
            background: #eee;
            color: #666;
            border-radius: 4px;
            font-size: 13px;
        }
        button {
            display: block;
            width: 100%;
            padding: 10px;
            background: #007BFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s ease-in-out;
        }
    </style>
</body>
</html>
