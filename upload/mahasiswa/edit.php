<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../home.php');
    exit;
}

require_once "../../class/Mahasiswa.php";
$mhs = new Mahasiswa();

if (!isset($_GET['nrp'])) {
    die("NRP tidak ditemukan.");
}

$nrp_asli = $_GET['nrp'];
$data = $mhs->getByNrp($nrp_asli);

if (!$data) {
    die("Data mahasiswa tidak ditemukan.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nrp_baru = $_POST['nrp'];
    $akun_lama = $_POST['akun_username_lama'] ?? $nrp_asli;
    $akun_baru = trim($_POST['akun_username']) !== '' ? trim($_POST['akun_username']) : $nrp_baru;
    $password  = $_POST['akun_password'] ?? '';

    $foto_ext = $_POST['ext_foto_lama'];

    if (!empty($_FILES['foto']['name'])) {
        if ($foto_ext) {
            @unlink("../../uploads/mahasiswa/{$nrp_asli}.{$foto_ext}");
        }
        $foto_ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        move_uploaded_file(
            $_FILES['foto']['tmp_name'],
            "../../uploads/mahasiswa/{$nrp_baru}.{$foto_ext}"
        );
    }

    try {
        $mhs->updateFull($nrp_asli, [
            'nrp' => $nrp_baru,
            'nama' => $_POST['nama'],
            'gender' => $_POST['gender'],
            'tanggal_lahir' => $_POST['tanggal_lahir'],
            'angkatan' => $_POST['angkatan'],
            'foto_ext' => $foto_ext,
            'akun_lama' => $akun_lama,
            'akun_baru' => $akun_baru,
            'password' => $password
        ]);

        header("Location: index.php?msg=updated");
        exit;

    } catch (Exception $e) {
        die("DATABASE ERROR: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Mahasiswa</title>
    <link rel="stylesheet" href="/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/asset/mahasiswa.css">
</head>
<body class="mahasiswa-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Edit Data Mahasiswa</h2>
                <p class="page-subtitle">Perbarui profil mahasiswa dan akun login.</p>
            </div>
            <button type="button" class="btn btn-small" onclick="location.href='index.php'">Kembali</button>
        </div>

        <div class="card">
            <form action="edit.php?nrp=<?= htmlspecialchars($data['nrp']); ?>" method="POST" enctype="multipart/form-data" class="section">
                <div class="field">
                    <label for="nrp">NRP</label>
                    <input type="text" id="nrp" name="nrp" value="<?= htmlspecialchars($data['nrp']); ?>" required>
                </div>

                <div class="field">
                    <label for="nama">Nama</label>
                    <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($data['nama']); ?>" required>
                </div>

                <div class="card card-compact card-dashed">
                    <strong>Akun Login</strong>
                    <p class="muted">Biarkan password kosong jika tidak diubah.</p>
                    <?php $prefUser = htmlspecialchars($data['nrp']); ?>
                    <div class="field">
                        <label for="akun_username">Username</label>
                        <input type="text" id="akun_username" name="akun_username" value="<?= $prefUser ?>" placeholder="default: NRP">
                        <input type="hidden" name="akun_username_lama" value="<?= $prefUser ?>">
                    </div>
                    <div class="field">
                        <label for="akun_password">Password Baru</label>
                        <input type="password" id="akun_password" name="akun_password" placeholder="kosongkan jika tidak ganti">
                    </div>
                </div>

                <div class="field">
                    <label for="gender">Jenis Kelamin</label>
                    <select id="gender" name="gender" required>
                        <option value="">-- Pilih Gender --</option>
                        <option value="Pria" <?= $data['gender'] == 'Pria' ? 'selected' : '' ?>>Pria</option>
                        <option value="Wanita" <?= $data['gender'] == 'Wanita' ? 'selected' : '' ?>>Wanita</option>
                    </select>
                </div>

                <div class="field">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= htmlspecialchars($data['tanggal_lahir']); ?>">
                </div>

                <div class="field">
                    <label for="angkatan">Angkatan</label>
                    <input type="text" id="angkatan" name="angkatan" value="<?= htmlspecialchars($data['angkatan']); ?>" placeholder="contoh: 2022">
                </div>

                <div class="field">
                    <label>Foto Saat Ini</label>
                    <?php if (!empty($data['foto_extention'])): ?>
                        <img src="../../uploads/mahasiswa/<?= htmlspecialchars($data['nrp']) . '.' . htmlspecialchars($data['foto_extention']); ?>" class="thumb" height="90" alt="Foto Mahasiswa">
                    <?php else: ?>
                        <span class="pill">Tidak ada foto</span>
                    <?php endif; ?>
                    <input type="hidden" name="ext_foto_lama" value="<?= htmlspecialchars($data['foto_extention']); ?>">
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
