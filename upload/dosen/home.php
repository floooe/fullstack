<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}

require_once "../../class/Grup.php";
$grup = new Grup();

$error = '';

if (isset($_POST['tambah'])) {
    try {
        $id = $grup->create([
            'username'  => $_SESSION['username'],
            'nama'      => trim($_POST['nama']),
            'deskripsi' => '',
            'jenis'     => 'Private',
            'kode'      => $grup->generateKode()
        ]);

        header("Location: detail_grup.php?id=$id");
        exit;

    } catch (Exception $e) {
        $error = "Gagal menambahkan grup";
    }
}

$data = $grup->getByOwner($_SESSION['username']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Halaman Home Dosen</title>
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/dosen.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/group.css">
</head>
<body class="dosen-page group-page">
<div class="page">

    <div class="page-header">
        <div>
            <h2 class="page-title">Selamat Datang, <?= htmlspecialchars($_SESSION['username']) ?></h2>
            <p class="page-subtitle">Buat grup baru dan lihat daftar grup yang Anda kelola.</p>
        </div>
        <button class="btn btn-small" onclick="location.href='../../home.php'">Kembali ke Home</button>
    </div>

    <div class="card section">
        <h3>Tambah Group Baru</h3>
        <form method="POST" class="section max-420">
            <div class="field">
                <label>Nama Group</label>
                <input type="text" name="nama" required placeholder="Masukkan nama grup">
            </div>
            <button type="submit" name="tambah" class="btn btn-small">Tambah</button>
        </form>

        <?php if ($error): ?>
            <p class="alert alert-danger mt-10"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </div>

    <div class="card section">
        <h3>Daftar Group Anda</h3>
        <div class="table-wrapper card-compact max-720">
            <table class="table-compact">
                <tr>
                    <th>No</th>
                    <th>Nama Group</th>
                    <th>Aksi</th>
                </tr>
                <?php $no = 1; while ($row = $data->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td>
                        <button class="btn btn-small"
                            onclick="location.href='detail_grup.php?id=<?= $row['idgrup'] ?>'">
                            Detail
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

</div>
</body>
</html>
