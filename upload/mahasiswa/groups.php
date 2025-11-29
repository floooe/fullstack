<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'user') {
    header("Location: ../../home.php");
    exit;
}

include "../../proses/koneksi.php";

function parse_group($name, $description) {
    $parts = explode(" | ", $name);
    $title = $parts[0];
    $code = $parts[1] ?? '';
    $jenis = 'public';
    if (strpos($description, '[public]') === 0) {
        $jenis = 'public';
    } elseif (strpos($description, '[private]') === 0) {
        $jenis = 'private';
    }
    return [$title, $code, $jenis];
}

$username = mysqli_real_escape_string($conn, $_SESSION['username']);
$info = isset($_GET['msg']) ? $_GET['msg'] : null;
$errors = [];

// Leave group
if (isset($_GET['leave'])) {
    $gid = (int)$_GET['leave'];
    mysqli_query($conn, "DELETE FROM group_members WHERE group_id=$gid AND username='$username'");
    header("Location: groups.php?msg=Berhasil keluar dari grup");
    exit;
}

// Join via code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_code'])) {
    $kode = strtoupper(trim($_POST['join_code']));
    if ($kode === '') {
        $errors[] = "Kode wajib diisi.";
    } else {
        $kodeEsc = mysqli_real_escape_string($conn, $kode);
        $q = mysqli_query($conn, "SELECT * FROM groups WHERE name LIKE '%| $kodeEsc'");
        if (mysqli_num_rows($q) === 0) {
            $errors[] = "Kode tidak ditemukan.";
        } else {
            $g = mysqli_fetch_assoc($q);
            list($gn, $gc, $gj) = parse_group($g['name'], $g['description']);
            if ($gj !== 'public') {
                $errors[] = "Grup ini private. Tidak bisa join dengan kode.";
            } else {
                $already = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM group_members WHERE group_id={$g['id']} AND username='$username'"));
                if ($already > 0) {
                    $errors[] = "Anda sudah tergabung di grup ini.";
                } else {
                    mysqli_query($conn, "INSERT INTO group_members(group_id, username, joined_at) VALUES ({$g['id']}, '$username', NOW())");
                    header("Location: groups.php?msg=Berhasil bergabung ke grup $gn");
                    exit;
                }
            }
        }
    }
}

$joined = mysqli_query($conn, "
    SELECT g.*, gm.joined_at 
    FROM group_members gm 
    JOIN groups g ON g.id = gm.group_id
    WHERE gm.username='$username'
    ORDER BY gm.joined_at DESC
");

$public = mysqli_query($conn, "
    SELECT * FROM groups g 
    WHERE g.description LIKE '[public]%' 
      AND g.id NOT IN (SELECT group_id FROM group_members WHERE username='$username')
    ORDER BY g.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Group Saya</title>
    <link rel="stylesheet" href="../../asset/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
    </style>
</head>
<body>
    <h2>Group Saya (Mahasiswa)</h2>
    <p><a href="../../home.php">Kembali</a></p>

    <?php if ($info) { ?>
        <div style="background:#e8f5e9; padding:10px; border:1px solid #c8e6c9; margin-bottom:10px;"><?= htmlspecialchars($info); ?></div>
    <?php } ?>
    <?php if (!empty($errors)) { ?>
        <div style="background:#ffebee; padding:10px; border:1px solid #ffcdd2; margin-bottom:10px;">
            <?php foreach ($errors as $e) { echo "<p>" . htmlspecialchars($e) . "</p>"; } ?>
        </div>
    <?php } ?>

    <div class="section">
        <h3>Grup yang Diikuti</h3>
        <table>
            <tr><th>Nama Grup</th><th>Kode</th><th>Bergabung</th><th>Aksi</th></tr>
            <?php if (mysqli_num_rows($joined) === 0) { ?>
                <tr><td colspan="4" style="text-align:center;">Belum bergabung dengan grup mana pun.</td></tr>
            <?php } else { while($g = mysqli_fetch_assoc($joined)) { 
                list($gn, $gc, $gj) = parse_group($g['name'], $g['description']);
            ?>
                <tr>
                    <td><?= htmlspecialchars($gn); ?> (<?= htmlspecialchars($gj); ?>)</td>
                    <td><b><?= htmlspecialchars($gc); ?></b></td>
                    <td><?= htmlspecialchars($g['joined_at']); ?></td>
                    <td>
                        <a href="group_detail.php?id=<?= $g['id']; ?>">Detail</a> |
                        <a href="groups.php?leave=<?= $g['id']; ?>" onclick="return confirm('Keluar dari grup ini?')">Keluar</a>
                    </td>
                </tr>
            <?php } } ?>
        </table>
    </div>

    <div class="section" id="join">
        <h3>Gabung ke Grup Publik (butuh kode)</h3>
        <form method="post" style="margin-bottom:10px;">
            <input type="text" name="join_code" placeholder="Masukkan kode pendaftaran" required>
            <button type="submit">Gabung</button>
        </form>

        <h4>Daftar Grup Publik yang Bisa Anda Lihat</h4>
        <table>
            <tr><th>Nama Grup</th><th>Kode</th><th>Dosen Pembuat</th><th>Aksi</th></tr>
            <?php if (mysqli_num_rows($public) === 0) { ?>
                <tr><td colspan="4" style="text-align:center;">Tidak ada grup publik lain.</td></tr>
            <?php } else { while($p = mysqli_fetch_assoc($public)) { 
                list($pn, $pc, $pj) = parse_group($p['name'], $p['description']);
            ?>
                <tr>
                    <td><?= htmlspecialchars($pn); ?></td>
                    <td><b><?= htmlspecialchars($pc); ?></b></td>
                    <td><?= htmlspecialchars($p['created_by']); ?></td>
                    <td><a href="group_detail.php?id=<?= $p['id']; ?>">Lihat Detail</a></td>
                </tr>
            <?php } } ?>
        </table>
    </div>
</body>
</html>
