<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'mahasiswa') {
    header("Location: ../../home.php");
    exit;
}

include "../../proses/koneksi.php";

// ======================
// INISIALISASI VARIABEL
// ======================
$username = mysqli_real_escape_string($conn, $_SESSION['username']);

$joined = isset($_GET['joined']);                // ?joined=1
$error  = isset($_GET['error']) ? $_GET['error'] : null; // ?error=...
$info   = isset($_GET['info'])  ? $_GET['info']  : null; // kalau nanti mau pakai ?info=...

// ====================
// 1. Grup yang diikuti
// ====================
$sqlJoined = "
    SELECT g.idgrup, g.nama, g.kode_pendaftaran, g.jenis, g.username_pembuat
    FROM member_grup m
    JOIN grup g ON g.idgrup = m.idgrup
    WHERE m.username = '$username'
    ORDER BY g.nama ASC
";
$qJoined = mysqli_query($conn, $sqlJoined);

// =============================
// 2. Daftar grup publik tersedia
//    (mahasiswa belum join)
// =============================
$sqlPublic = "
    SELECT g.idgrup, g.nama, g.kode_pendaftaran, g.username_pembuat
    FROM grup g
    LEFT JOIN member_grup m 
           ON m.idgrup = g.idgrup 
          AND m.username = '$username'
    WHERE LOWER(g.jenis) = 'publik'
      AND m.idgrup IS NULL
    ORDER BY g.nama ASC
";
$qPublic = mysqli_query($conn, $sqlPublic);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Group Saya (Mahasiswa)</title>
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/mahasiswa.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/group.css">
</head>
<body class="mahasiswa-page group-page">
<div class="page">

    <div class="page-header">
        <div>
            <h2 class="page-title">Group Saya (Mahasiswa)</h2>
            <p class="page-subtitle">Lihat grup yang diikuti dan gabung ke grup publik.</p>
        </div>
        <div class="toolbar">
            <button type="button" class="btn btn-small" onclick="location.href='../../home.php'">Kembali</button>
        </div>
    </div>

    <?php if ($joined) { ?>
        <div class="alert alert-success">
            Berhasil bergabung ke grup.
        </div>
    <?php } ?>

    <?php if ($error) { ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error); ?>
        </div>
    <?php } ?>

    <?php if ($info) { ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($info); ?>
        </div>
    <?php } ?>

    <div class="card-compact">
        <h3 class="section-title">Grup yang Diikuti</h3>
        <div class="table-wrapper card-compact-inner">
            <table class="table-compact">
                <tr>
                    <th>Nama Grup</th>
                    <th>Kode</th>
                    <th>Aksi</th>
                </tr>
                <?php if (!$qJoined || mysqli_num_rows($qJoined) === 0) { ?>
                    <tr>
                        <td colspan="3" class="text-center">Belum ada grup</td>
                    </tr>
                <?php } else { 
                    while ($row = mysqli_fetch_assoc($qJoined)) { ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><b><?= htmlspecialchars($row['kode_pendaftaran']); ?></b></td>
                            <td>
                            <button type="button" class="btn btn-small"
                                onclick="location.href='group_detail.php?id=<?= $row['idgrup']; ?>'">
                                Detail
                            </button>
                            </td>
                        </tr>
                <?php } } ?>
            </table>
        </div>
    </div>

    <div class="card-compact mt-3">
        <h3 class="section-title">Gabung Grup Publik (Kode diperlukan)</h3>

        <form method="post" action="join_group.php" class="form-inline">
            <input type="text" name="kode" placeholder="Masukkan kode grup" class="input-text">
            <button type="submit" class="btn btn-primary">Gabung</button>
        </form>

        <div class="table-wrapper card-compact-inner mt-2">
            <table class="table-compact">
                <tr>
                    <th>Nama Grup</th>
                    <th>Kode</th>
                    <th>Pembuat</th>
                    <th>Detail</th>
                </tr>
                <?php if (!$qPublic || mysqli_num_rows($qPublic) === 0) { ?>
                    <tr>
                        <td colspan="4" class="text-center">Tidak ada grup publik</td>
                    </tr>
                <?php } else { 
                    while ($row = mysqli_fetch_assoc($qPublic)) { ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><b><?= htmlspecialchars($row['kode_pendaftaran']); ?></b></td>
                            <td><?= htmlspecialchars($row['username_pembuat']); ?></td>
                            <td>
                            <button type="button" class="btn btn-small"
                                onclick="location.href='group_detail.php?id=<?= $row['idgrup']; ?>'">
                                Detail
                            </button>
                            </td>
                        </tr>
                <?php } } ?>
            </table>
        </div>
    </div>

</div>
</body>
</html>
