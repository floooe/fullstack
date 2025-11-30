<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}
if ($_SESSION['level'] !== 'mahasiswa') {
    header("Location: ../../home.php");
    exit;
}

include "../../proses/koneksi.php";

$username = mysqli_real_escape_string($conn, $_SESSION['username']);
$info = isset($_GET['msg']) ? $_GET['msg'] : null;
$errors = [];

if (isset($_GET['leave'])) {
    $gid = (int) $_GET['leave'];
    mysqli_query($conn, "DELETE FROM member_grup WHERE idgrup=$gid AND username='$username'");
    header("Location: groups.php?msg=Berhasil keluar dari grup");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_code'])) {
    $kode = strtoupper(trim($_POST['join_code']));

    if ($kode === '') {
        $errors[] = "Kode wajib diisi.";
    } else {
        $kodeEsc = mysqli_real_escape_string($conn, $kode);
        $q = mysqli_query($conn, "SELECT * FROM grup WHERE kode_pendaftaran='$kodeEsc'");

        if (mysqli_num_rows($q) === 0) {
            $errors[] = "Kode tidak ditemukan.";
        } else {
            $g = mysqli_fetch_assoc($q);
            $gn = $g['nama'];
            $gc = $g['kode_pendaftaran'];
            $gj = strtolower($g['jenis'] ?? 'public');

            if ($gj !== 'public') {
                $errors[] = "Grup ini private. Tidak bisa join dengan kode.";
            } else {

                $already = mysqli_num_rows(mysqli_query(
                    $conn,
                    "SELECT 1 FROM member_grup WHERE idgrup={$g['idgrup']} AND username='$username'"
                ));

                if ($already > 0) {
                    $errors[] = "Anda sudah tergabung di grup ini.";
                } else {
                    mysqli_query($conn, "INSERT INTO member_grup(idgrup, username) VALUES ({$g['idgrup']}, '$username')");
                    header("Location: groups.php?msg=Berhasil bergabung ke grup $gn");
                    exit;
                }
            }
        }
    }
}

$joined = mysqli_query($conn, "
    SELECT g.*, mg.idgrup 
    FROM member_grup mg
    JOIN grup g ON g.idgrup = mg.idgrup
    WHERE mg.username='$username'
    ORDER BY g.tanggal_pembentukan DESC
");


$public = mysqli_query($conn, "
    SELECT g.* FROM grup g 
    WHERE g.jenis = 'Public'
      AND g.idgrup NOT IN (SELECT idgrup FROM member_grup WHERE username='$username')
    ORDER BY g.tanggal_pembentukan DESC
");

?>
<!DOCTYPE html>
<html>

<head>
    <title>Group Saya</title>
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
            <button type="button" class="btn btn-small" onclick="location.href='../../home.php'">Kembali</button>
        </div>

        <?php if ($info)
            echo "<div class='alert alert-success'>$info</div>"; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e)
                    echo "<p>$e</p>"; ?>
            </div>
        <?php endif; ?>

        <div class="card section">
            <h3>Grup yang Diikuti</h3>
            <div class="table-wrapper card-compact">
                <table class="table-compact">
                    <tr>
                        <th>Nama Grup</th>
                        <th>Kode</th>
                        <th>Aksi</th>
                    </tr>
                    <?php if (mysqli_num_rows($joined) === 0): ?>
                        <tr>
                            <td colspan="3" align="center">Belum ada grup</td>
                        </tr>
                    <?php else:
                        while ($g = mysqli_fetch_assoc($joined)):
                            $gn = $g['nama'];
                            $gc = $g['kode_pendaftaran'] ?? '-';
                            $gj = $g['jenis'] ?? 'Public';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($gn); ?> (<?= htmlspecialchars($gj); ?>)</td>
                                <td><b><?= htmlspecialchars($gc); ?></b></td>
                                <td>
                                    <div class="toolbar">
                                        <button type="button" class="btn btn-small" onclick="location.href='group_detail.php?id=<?= $g['idgrup']; ?>'">Detail</button>
                                        <button type="button" class="btn btn-danger btn-small" onclick="if(confirm('Keluar dari grup ini?')) location.href='?leave=<?= $g['idgrup']; ?>'">Keluar</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; endif; ?>
                </table>
            </div>
        </div>

        <div class="card section" id="join">
            <h3>Gabung Grup Publik (Kode diperlukan)</h3>
            <form method="post" class="toolbar">
                <input type="text" name="join_code" required placeholder="Masukkan kode grup" class="max-260">
                <button type="submit" class="btn btn-small">Gabung</button>
            </form>

            <div class="table-wrapper card-compact">
                <table class="table-compact">
                    <tr>
                        <th>Nama Grup</th>
                        <th>Kode</th>
                        <th>Pembuat</th>
                        <th>Detail</th>
                    </tr>
                    <?php if (mysqli_num_rows($public) === 0): ?>
                        <tr>
                            <td colspan="4" align="center">Tidak ada grup publik</td>
                        </tr>
                    <?php else:
                        while ($p = mysqli_fetch_assoc($public)):
                            $pn = $p['nama'];
                            $pc = $p['kode_pendaftaran'] ?? '-';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($pn); ?></td>
                                <td><b><?= htmlspecialchars($pc); ?></b></td>
                                <td><?= htmlspecialchars($p['username_pembuat'] ?? '-'); ?></td>
                                <td><button type="button" class="btn btn-small" onclick="location.href='group_detail.php?id=<?= $p['idgrup']; ?>'">Lihat</button></td>
                            </tr>
                        <?php endwhile; endif; ?>
                </table>
            </div>
        </div>
    </div>

</body>

</html>
