<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['level'] !== 'admin') {
    header("Location: ../../home.php");
    exit;
}

require_once "../../class/Mahasiswa.php";
$mahasiswa = new Mahasiswa();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nrp   = $_POST['nrp'];
    $nama  = $_POST['nama'];
    $gender = $_POST['gender'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $angkatan = $_POST['angkatan'];

    $akun_username = trim($_POST['akun_username']) !== ''
        ? trim($_POST['akun_username'])
        : $nrp;

    $password = $_POST['akun_password'] ?? '';

    if ($password === '') {
        die("ERROR: Password akun harus diisi.");
    }

    if ($mahasiswa->existsByNrp($nrp)) {
        die("ERROR: NRP sudah terdaftar.");
    }

    if ($mahasiswa->akunExists($akun_username)) {
        die("ERROR: Username akun sudah digunakan.");
    }

    /* =========================
       UPLOAD FOTO
       ========================= */
    $fotoExt = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fotoExt = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $target = "../../uploads/mahasiswa/" . $nrp . "." . $fotoExt;

        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
            die("Gagal upload foto.");
        }
    }

    try {
        $mahasiswa->createFull([
            'nrp' => $nrp,
            'nama' => $nama,
            'gender' => $gender,
            'tanggal_lahir' => $tanggal_lahir,
            'angkatan' => $angkatan,
            'foto_ext' => $fotoExt,
            'akun_username' => $akun_username,
            'password' => $password
        ]);

        header("Location: index.php?msg=created");
        exit;

    } catch (Exception $e) {
        if ($fotoExt) {
            @unlink("../../uploads/mahasiswa/" . $nrp . "." . $fotoExt);
        }
        die("DATABASE ERROR: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Mahasiswa</title>
    <link rel="stylesheet" href="/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/asset/mahasiswa.css">
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
