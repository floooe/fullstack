<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../home.php');
    exit;
}

require_once "../../class/Dosen.php";
$dosen = new Dosen();

if (!isset($_GET['npk'])) {
    die("Error: NPK dosen tidak ditemukan.");
}
$npk_asli = $_GET['npk'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $npk_baru = trim($_POST['npk']);
    $nama_baru = trim($_POST['nama']);

    if ($npk_baru === '' || $nama_baru === '') {
        die("NPK dan Nama wajib diisi.");
    }

    // Cegah NPK duplikat
    if ($npk_baru !== $npk_asli && $dosen->getByNpk($npk_baru)) {
        die("NPK baru sudah digunakan.");
    }

    $ext_foto_lama  = $_POST['ext_foto_lama'] ?? '';
    $ext_foto_final = $ext_foto_lama;

    // Ambil username akun lama yang VALID
    $akun = $dosen->getByNpk($npk_asli);
    $akun_username_lama = $akun['username'] ?? $npk_asli;

    $akun_username_baru = trim($_POST['akun_username']) !== ''
        ? trim($_POST['akun_username'])
        : $npk_baru;

    $akun_password = $_POST['akun_password'] ?? '';

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {

        if (!empty($ext_foto_lama)) {
            $foto_lama = "../../uploads/dosen/{$npk_asli}.{$ext_foto_lama}";
            if (file_exists($foto_lama)) {
                unlink($foto_lama);
            }
        }

        $ext_foto_final = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nama_file_baru = "{$npk_baru}.{$ext_foto_final}";
        $lokasi_upload  = "../../uploads/dosen/{$nama_file_baru}";

        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $lokasi_upload)) {
            die("Gagal upload foto.");
        }
    }

    try {
        $dosen->updateFull([
            'npk_lama'   => $npk_asli,
            'npk_baru'   => $npk_baru,
            'nama'       => $nama_baru,
            'foto_ext'   => $ext_foto_final,
            'akun_lama'  => $akun_username_lama,
            'akun_baru'  => $akun_username_baru,
            'password'   => $akun_password
        ]);

        header("Location: index.php?msg=updated");
        exit;

    } catch (Exception $e) {
        die("DATABASE ERROR: " . $e->getMessage());
    }
}

$data = $dosen->getByNpk($npk_asli);
if (!$data) {
    die("Data dosen tidak ditemukan.");
}

//username akun valid untuk form
$akun = $dosen->getByNpk($npk_asli);
$prefUser = $akun['username'] ?? $data['npk'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Dosen</title>
    <link rel="stylesheet" href="/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/asset/dosen.css">
</head>

<body class="dosen-page">
<div class="page">

    <div class="page-header">
        <div>
            <h2 class="page-title">Edit Data Dosen</h2>
            <p class="page-subtitle">Perbarui informasi dosen dan akun login.</p>
        </div>
        <button type="button" class="btn btn-secondary btn-small"
            onclick="location.href='index.php'">Kembali</button>
    </div>

    <div class="card">
        <form method="POST" enctype="multipart/form-data" class="section">

            <div class="field">
                <label>NPK</label>
                <input type="text" name="npk" value="<?= htmlspecialchars($data['npk']); ?>" required>
            </div>

            <div class="field">
                <label>Nama</label>
                <input type="text" name="nama" value="<?= htmlspecialchars($data['nama']); ?>" required>
            </div>

            <div class="card card-compact card-dashed">
                <strong>Akun Login</strong>
                <p class="muted">Biarkan password kosong jika tidak diubah.</p>

                <div class="field">
                    <label>Username</label>
                    <input type="text" name="akun_username" value="<?= htmlspecialchars($prefUser); ?>">
                </div>

                <div class="field">
                    <label>Password Baru</label>
                    <input type="password" name="akun_password">
                </div>
            </div>

            <div class="field">
                <label>Foto Saat Ini</label>
                <?php if (!empty($data['foto_extension'])): ?>
                    <img src="../../uploads/dosen/<?= htmlspecialchars($data['npk']) . '.' . htmlspecialchars($data['foto_extension']); ?>"
                         height="90">
                <?php else: ?>
                    <span>-</span>
                <?php endif; ?>
                <input type="hidden" name="ext_foto_lama" value="<?= htmlspecialchars($data['foto_extension']); ?>">
            </div>

            <div class="field">
                <label>Ganti Foto</label>
                <input type="file" name="foto">
            </div>

            <button type="submit" class="btn">Simpan Perubahan</button>

        </form>
    </div>

</div>
</body>
</html>
