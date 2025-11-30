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

// deteksi tabel event (support events/event) dan kolom relasi grup
function detect_event_tables($conn) {
    $tables = [];
    foreach (['events', 'event'] as $candidate) {
        $res = mysqli_query($conn, "SHOW TABLES LIKE '{$candidate}'");
        if (!$res || mysqli_num_rows($res) === 0) {
            continue;
        }
        $colsRes = mysqli_query($conn, "SHOW COLUMNS FROM {$candidate}");
        if (!$colsRes) {
            $tables[] = ['table' => $candidate, 'group_col' => null];
            continue;
        }
        $cols = [];
        while ($c = mysqli_fetch_assoc($colsRes)) {
            $cols[] = $c['Field'];
        }
        $groupCol = null;
        foreach (['group_id', 'id_grup', 'idgrup', 'groupid'] as $colCandidate) {
            if (in_array($colCandidate, $cols, true)) {
                $groupCol = $colCandidate;
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
        $tables[] = ['table' => $candidate, 'group_col' => $groupCol];
    }
    return $tables;
}

$eventTables = detect_event_tables($conn);

// deteksi tabel member yang berisi relasi ke grup
function detect_member_group_relations($conn) {
    $relations = [];
    $memberTables = ['member_grup', 'group_members'];
    foreach ($memberTables as $table) {
        $res = mysqli_query($conn, "SHOW TABLES LIKE '{$table}'");
        if (!$res || mysqli_num_rows($res) === 0) {
            continue;
        }
        $colsRes = mysqli_query($conn, "SHOW COLUMNS FROM {$table}");
        if (!$colsRes) {
            $relations[] = ['table' => $table, 'group_col' => null];
            continue;
        }
        $cols = [];
        while ($c = mysqli_fetch_assoc($colsRes)) {
            $cols[] = $c['Field'];
        }
        $groupCol = null;
        foreach (['group_id', 'idgrup', 'id_grup', 'groupid'] as $colCandidate) {
            if (in_array($colCandidate, $cols, true)) {
                $groupCol = $colCandidate;
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
        $relations[] = ['table' => $table, 'group_col' => $groupCol];
    }
    return $relations;
}

$memberRelations = detect_member_group_relations($conn);

// Hapus grup milik sendiri
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $own = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username_pembuat FROM grup WHERE idgrup=$delId"));
    if ($own && $own['username_pembuat'] === $_SESSION['username']) {
        // bersihkan member & event bila ada
        foreach ($memberRelations as $relation) {
            if (empty($relation['group_col'])) {
                continue;
            }
            mysqli_query($conn, "DELETE FROM {$relation['table']} WHERE {$relation['group_col']}=$delId");
        }
        foreach ($eventTables as $eventInfo) {
            if (empty($eventInfo['group_col'])) {
                continue;
            }
            mysqli_query($conn, "DELETE FROM {$eventInfo['table']} WHERE {$eventInfo['group_col']}=$delId");
        }
        mysqli_query($conn, "DELETE FROM grup WHERE idgrup=$delId");
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
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/dosen.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/group.css">
</head>
<body class="dosen-page group-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Group Saya</h2>
                <p class="page-subtitle">Kelola grup yang Anda buat dan bagikan kode pendaftaran.</p>
            </div>
            <div class="toolbar">
                <button type="button" class="btn btn-small" onclick="location.href='../../home.php'">Home</button>
                <button type="button" class="btn btn-small" onclick="location.href='create_Group.php'">+ Buat Group</button>
            </div>
        </div>

        <?php if ($success && $createdCode) { ?>
            <div class="alert alert-success">
                Grup berhasil dibuat. Kode pendaftaran: <b><?= htmlspecialchars($createdCode); ?></b> (ditampilkan juga di halaman detail grup).
            </div>
        <?php } ?>

        <?php if ($message) { ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <div class="table-wrapper card-compact">
            <table class="table-compact">
                <tr>
                    <th>Nama Grup</th>
                    <th>Jenis</th>
                    <th>Kode</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
                <?php if (mysqli_num_rows($q) === 0) { ?>
                    <tr><td colspan="5" class="text-center">Belum ada grup yang Anda buat.</td></tr>
                <?php } else { 
                    while($row = mysqli_fetch_assoc($q)){
                        $nama = $row['nama'];
                        $kode = $row['kode_pendaftaran'] ?? '-';
                        $jenis = !empty($row['jenis']) ? ucfirst(strtolower($row['jenis'])) : 'Public';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($nama); ?></td>
                        <td><?= htmlspecialchars($jenis); ?></td>
                        <td><b><?= htmlspecialchars($kode); ?></b></td>
                        <td><?= htmlspecialchars($row['username_pembuat']); ?></td>
                        <td>
                            <div class="toolbar">
                                <button type="button" class="btn btn-small" onclick="location.href='group_detail.php?id=<?= $row['idgrup']; ?>'">Detail</button>
                                <button type="button" class="btn btn-danger btn-small" onclick="if(confirm('Hapus grup beserta data di dalamnya?')) location.href='groups.php?delete=<?= $row['idgrup']; ?>'">Hapus</button>
                            </div>
                        </td>
                    </tr>
                <?php } } ?>
            </table>
        </div>
    </div>
</body>
</html>
