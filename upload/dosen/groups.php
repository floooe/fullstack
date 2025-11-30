<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}
// izinkan dosen maupun admin
if (!isset($_SESSION['level']) || !in_array($_SESSION['level'], ['admin','dosen'])) {
    header("Location: ../../home.php");
    exit;
}

include "../../proses/koneksi.php";

$username = mysqli_real_escape_string($conn, $_SESSION['username']);
$success = isset($_GET['success']);
$createdCode = $_GET['kode'] ?? null;
$message = isset($_GET['msg']) ? $_GET['msg'] : null;

function detect_events_table_and_group_col($conn) {
    $table = null;
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'events'")) > 0) {
        $table = 'events';
    } elseif (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'event'")) > 0) {
        $table = 'event';
    }
    if (!$table) return [null, null];

    $groupCol = null;
    $colsRes = mysqli_query($conn, "SHOW COLUMNS FROM {$table}");
    $cols = [];
    while ($c = mysqli_fetch_assoc($colsRes)) {
        $cols[] = $c['Field'];
    }
    foreach (['group_id', 'id_grup', 'idgrup', 'groupid'] as $candidate) {
        if (in_array($candidate, $cols, true)) {
            $groupCol = $candidate;
            break;
        }
    }
    if (!$groupCol) {
        foreach ($cols as $c) {
            if (stripos($c, 'grup') !== false || stripos($c, 'group') !== false) {
                $groupCol = $c;
                break;
            }
        }
    }
    return [$table, $groupCol];
}

// helper to read jenis
function parseJenis($description) {
    return stripos($description, '[public]') === 0 ? 'Public' : 'Private';
}

$eventsTable = null;
$eventsGroupCol = null;
list($eventsTable, $eventsGroupCol) = detect_events_table_and_group_col($conn);

// Hapus grup milik sendiri
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $own = mysqli_fetch_assoc(mysqli_query($conn, "SELECT created_by FROM groups WHERE id=$delId"));
    if ($own && $own['created_by'] === $_SESSION['username']) {
        // bersihkan member & event bila ada
        mysqli_query($conn, "DELETE FROM member_grup WHERE group_id=$delId");
        if ($eventsTable && $eventsGroupCol) {
            mysqli_query($conn, "DELETE FROM {$eventsTable} WHERE {$eventsGroupCol}=$delId");
        }
        mysqli_query($conn, "DELETE FROM groups WHERE id=$delId");
        header("Location: groups.php?msg=Grup berhasil dihapus");
        exit;
    } else {
        $message = "Tidak bisa menghapus grup ini.";
    }
}

$q = mysqli_query($conn, "SELECT * FROM grup WHERE username_pembuat='$username' ORDER BY tanggal_pembentukan DESC");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Group Saya</title>
    <link rel="stylesheet" href="../../asset/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
    </style>
</head>
<body>
    <h2>Group Saya</h2>
    <p><a href="../../home.php">Kembali</a> | <a href="create_Group.php">+ Buat Group</a></p>

    <?php if ($success && $createdCode) { ?>
        <div style="background:#e8f5e9; padding:10px; border:1px solid #c8e6c9;">
            Grup berhasil dibuat. Kode pendaftaran: <b><?= htmlspecialchars($createdCode); ?></b> (ditampilkan juga di halaman detail grup).
        </div>
    <?php } ?>

    <?php if ($message) { ?>
        <div style="background:#e8f5e9; padding:10px; border:1px solid #c8e6c9; margin-top:10px;">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <table>
        <tr>
            <th>Nama Grup</th>
            <th>Jenis</th>
            <th>Kode</th>
            <th>Dibuat</th>
            <th>Aksi</th>
        </tr>
        <?php if (mysqli_num_rows($q) === 0) { ?>
            <tr><td colspan="5" style="text-align:center;">Belum ada grup yang Anda buat.</td></tr>
        <?php } else { 
            while($row = mysqli_fetch_assoc($q)){
                $parts = explode(" | ", $row['nama']);
                $nama = $parts[0];
                $kode = $parts[1] ?? '-';
                $jenis = parseJenis($row['deskripsi']);
        ?>
            <tr>
                <td><?= htmlspecialchars($nama); ?></td>
                <td><?= htmlspecialchars($jenis); ?></td>
                <td><b><?= htmlspecialchars($kode); ?></b></td>
                <td><?= htmlspecialchars($row['username_pembuat']); ?></td>
                <td>
                    <a href="group_detail.php?id=<?= $row['idgrup']; ?>">Detail</a> | 
                    <a href="groups.php?delete=<?= $row['idgrup']; ?>" onclick="return confirm('Hapus grup beserta data di dalamnya?')">Hapus</a>
                </td>
            </tr>
        <?php } } ?>
    </table>
</body>
</html>
