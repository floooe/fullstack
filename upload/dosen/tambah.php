<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../home.php');
    exit;
}

require_once "../../class/Dosen.php";
$dosen = new Dosen();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $npk = trim($_POST['npk']);
    $nama = trim($_POST['nama']);

    $akun_username = trim($_POST['akun_username']) !== ''
        ? trim($_POST['akun_username'])
        : $npk;

    $akun_password = $_POST['akun_password'] ?? '';

    if ($akun_password === '') {
        die("ERROR: Password akun harus diisi.");
    }

    if ($dosen->existsNpk($npk)) {
        die("ERROR: NPK '{$npk}' sudah terdaftar.");
    }

    /* upload foto */
    $foto_ext = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto_ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $target = "../../uploads/dosen/{$npk}.{$foto_ext}";
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {
            die("Gagal upload foto.");
        }
    }

    try {
        $dosen->createDosenWithAkun([
            'npk' => $npk,
            'nama' => $nama,
            'foto_ext' => $foto_ext,
            'akun_username' => $akun_username,
            'password' => $akun_password
        ]);

        header("Location: index.php?msg=added");
        exit;

    } catch (Exception $e) {
        if ($foto_ext && file_exists($target)) {
            @unlink($target);
        }
        die("DATABASE ERROR: " . $e->getMessage());
    }
}
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
            <button class="btn btn-secondary btn-small" onclick="location.href='index.php'">Kembali</button>
        </div>

        <div class="card">
            <form method="POST" enctype="multipart/form-data" class="section">
                <div class="field">
                    <label>NPK</label>
                    <input type="text" name="npk" required>
                </div>

                <div class="field">
                    <label>Nama</label>
                    <input type="text" name="nama" required>
                </div>

                <div class="card card-compact card-dashed">
                    <strong>Akun Login</strong>
                    <p class="muted">Username default menggunakan NPK.</p>

                    <div class="field">
                        <label>Username</label>
                        <input type="text" name="akun_username">
                    </div>

                    <div class="field">
                        <label>Password</label>
                        <input type="password" name="akun_password" required>
                    </div>
                </div>

                <div class="field">
                    <label>Foto</label>
                    <input type="file" name="foto">
                </div>

                <button type="submit" class="btn">Simpan</button>
            </form>
        </div>

    </div>
</body>

</html>