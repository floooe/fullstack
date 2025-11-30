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

        $cekStmt = $mysqli->prepare("SELECT username FROM akun WHERE username = ?");
        $cekStmt->bind_param('s', $akun_username_lama);
        $cekStmt->execute();
        $cekStmt->store_result();
        $adaAkunLama = $cekStmt->num_rows > 0;
        $cekStmt->close();

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
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/dosen.css">
</head>
<body class="dosen-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Edit Data Dosen</h2>
                <p class="page-subtitle">Perbarui informasi dosen dan akun login.</p>
            </div>
            <button type="button" class="btn btn-secondary btn-small" onclick="location.href='index.php'">Kembali</button>
        </div>

        <div class="card">
            <form action="edit.php?npk=<?= htmlspecialchars($data['npk']); ?>" method="POST" enctype="multipart/form-data" class="section">
                <div class="field">
                    <label for="npk">NPK</label>
                    <input type="text" id="npk" name="npk" value="<?= htmlspecialchars($data['npk']); ?>" required>
                </div>

                <div class="field">
                    <label for="nama">Nama</label>
                    <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($data['nama']); ?>" required>
                </div>

                <div class="card card-compact card-dashed">
                    <strong>Akun Login</strong>
                    <p class="muted">Biarkan password kosong jika tidak diubah.</p>
                    <?php $prefUser = htmlspecialchars($data['npk']); ?>
                    <div class="field">
                        <label for="akun_username">Username</label>
                        <input type="text" id="akun_username" name="akun_username" value="<?= $prefUser ?>" placeholder="default: NPK">
                        <input type="hidden" name="akun_username_lama" value="<?= $prefUser ?>">
                    </div>
                    <div class="field">
                        <label for="akun_password">Password Baru</label>
                        <input type="password" id="akun_password" name="akun_password" placeholder="kosongkan jika tidak ganti">
                    </div>
                </div>
                
                <div class="field">
                    <label>Foto Saat Ini</label>
                    <?php if (!empty($data['foto_extension'])): ?>
                        <img src="../../uploads/dosen/<?= htmlspecialchars($data['npk']) . '.' . htmlspecialchars($data['foto_extension']); ?>" class="thumb" height="90" alt="Foto Dosen">
                    <?php else: ?>
                        <span class="pill">Tidak ada foto</span>
                    <?php endif; ?>
                    <input type="hidden" name="ext_foto_lama" value="<?= htmlspecialchars($data['foto_extension']); ?>">
                </div>

                <div class="field">
                    <label for="foto">Ganti Foto (opsional)</label>
                    <input type="file" id="foto" name="foto">
                </div>

                <button type="submit" class="btn">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</body>
</html>
