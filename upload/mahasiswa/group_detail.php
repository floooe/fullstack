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

$groupId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($groupId <= 0) {
    header("Location: groups.php");
    exit;
}

function detect_events_table($conn) {
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'events'")) > 0) {
        return 'events';
    }
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'event'")) > 0) {
        return 'event';
    }
    return null;
}

function parse_group($name, $description) {
    $parts = explode(" | ", $name);
    $title = $parts[0];
    $code = $parts[1] ?? '';
    $jenis = 'public';
    $desc = $description;
    if (strpos($description, '[') === 0) {
        $end = strpos($description, ']');
        if ($end !== false) {
            $jenis = strtolower(substr($description, 1, $end - 1));
            $desc = trim(substr($description, $end + 1));
        }
    }
    return [$title, $code, $jenis, $desc];
}

$group = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM groups WHERE id=$groupId"));
if (!$group) {
    header("Location: groups.php?msg=Grup tidak ditemukan");
    exit;
}

list($groupName, $groupCode, $groupJenis, $groupDesc) = parse_group($group['name'], $group['description']);
$username = mysqli_real_escape_string($conn, $_SESSION['username']);

$isMember = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM group_members WHERE group_id=$groupId AND username='$username'")) > 0;
$info = isset($_GET['msg']) ? $_GET['msg'] : null;
$errors = [];

// leave
if (isset($_GET['leave']) && $isMember) {
    mysqli_query($conn, "DELETE FROM group_members WHERE group_id=$groupId AND username='$username'");
    header("Location: groups.php?msg=Berhasil keluar dari grup");
    exit;
}

// join from detail (still needs code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_code']) && !$isMember) {
    $kode = strtoupper(trim($_POST['join_code']));
    if ($kode === '' || $kode !== strtoupper($groupCode)) {
        $errors[] = "Kode salah.";
    } elseif ($groupJenis !== 'public') {
        $errors[] = "Grup private tidak bisa di-join langsung.";
    } else {
        mysqli_query($conn, "INSERT INTO group_members(group_id, username, joined_at) VALUES ($groupId, '$username', NOW())");
        header("Location: group_detail.php?id=$groupId&msg=Berhasil bergabung");
        exit;
    }
}

// load members
$members = mysqli_query($conn, "
    SELECT gm.id, gm.username, COALESCE(d.nama, m.nama) AS nama,
           CASE WHEN d.npk IS NOT NULL THEN 'Dosen'
                WHEN m.nrp IS NOT NULL THEN 'Mahasiswa'
                ELSE 'User' END AS tipe
    FROM group_members gm
    LEFT JOIN dosen d ON d.npk = gm.username
    LEFT JOIN mahasiswa m ON m.nrp = gm.username
    WHERE gm.group_id=$groupId
    ORDER BY tipe, nama
");

$eventsTable = detect_events_table($conn);
$eventsTableExists = $eventsTable !== null;
$eventGroupCol = null;
$eventScheduleCol = null;
$eventCreatedCol = null;
$eventTitleCol = null;
$eventDetailCol = null;
if ($eventsTableExists) {
    $colsRes = mysqli_query($conn, "SHOW COLUMNS FROM {$eventsTable}");
    $cols = [];
    while ($c = mysqli_fetch_assoc($colsRes)) {
        $cols[] = $c['Field'];
    }
    foreach (['group_id', 'id_grup', 'idgrup', 'groupid'] as $candidate) {
        if (in_array($candidate, $cols, true)) {
            $eventGroupCol = $candidate;
            break;
        }
    }
    if (!$eventGroupCol) {
        foreach ($cols as $c) {
            if (stripos($c, 'grup') !== false || stripos($c, 'group') !== false) {
                $eventGroupCol = $c;
                break;
            }
        }
    }
    foreach (['title', 'judul', 'nama', 'nama_event'] as $candidate) {
        if (in_array($candidate, $cols, true)) {
            $eventTitleCol = $candidate;
            break;
        }
    }
    foreach (['detail', 'deskripsi', 'keterangan'] as $candidate) {
        if (in_array($candidate, $cols, true)) {
            $eventDetailCol = $candidate;
            break;
        }
    }
    foreach (['schedule_at', 'jadwal', 'tanggal', 'waktu'] as $candidate) {
        if (in_array($candidate, $cols, true)) {
            $eventScheduleCol = $candidate;
            break;
        }
    }
    foreach (['created_at', 'created', 'dibuat', 'createdAt'] as $candidate) {
        if (in_array($candidate, $cols, true)) {
            $eventCreatedCol = $candidate;
            break;
        }
    }
}
$eventsTableReady = $eventsTableExists && $eventGroupCol && $eventTitleCol;
$eventOrderCol = $eventScheduleCol ?: ($eventCreatedCol ?: 'id');
$events = [];
if ($eventsTableReady) {
    $res = mysqli_query($conn, "SELECT * FROM {$eventsTable} WHERE {$eventGroupCol}=$groupId ORDER BY {$eventOrderCol} DESC");
    while ($ev = mysqli_fetch_assoc($res)) {
        $events[] = $ev;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Detail Group</title>
    <link rel="stylesheet" href="../../asset/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        .chip { display: inline-block; padding: 4px 8px; background:#eef; border-radius:4px; }
    </style>
</head>
<body>
    <h2>Detail Group</h2>
    <p><a href="groups.php">Kembali</a></p>

    <?php if ($info) { ?>
        <div style="background:#e8f5e9; padding:10px; border:1px solid #c8e6c9; margin-bottom:10px;"><?= htmlspecialchars($info); ?></div>
    <?php } ?>
    <?php if (!empty($errors)) { ?>
        <div style="background:#ffebee; padding:10px; border:1px solid #ffcdd2; margin-bottom:10px;">
            <?php foreach ($errors as $e) { echo "<p>" . htmlspecialchars($e) . "</p>"; } ?>
        </div>
    <?php } ?>

    <h3><?= htmlspecialchars($groupName); ?> <span class="chip"><?= htmlspecialchars(ucfirst($groupJenis)); ?></span></h3>
    <p><b>Kode Pendaftaran:</b> <?= htmlspecialchars($groupCode); ?></p>
    <p><b>Dosen Pembuat:</b> <?= htmlspecialchars($group['created_by']); ?> | <b>Dibuat:</b> <?= htmlspecialchars($group['created_at']); ?></p>
    <p><b>Deskripsi:</b> <?= htmlspecialchars($groupDesc); ?></p>

    <?php if ($isMember) { ?>
        <p><a href="group_detail.php?id=<?= $groupId; ?>&leave=1" onclick="return confirm('Keluar dari grup?')">Keluar dari grup</a></p>
    <?php } else { ?>
        <p>Anda belum tergabung di grup ini.</p>
        <?php if ($groupJenis === 'public') { ?>
            <form method="post">
                <label>Masukkan kode pendaftaran untuk join:</label>
                <input type="text" name="join_code" required>
                <button type="submit">Gabung</button>
            </form>
        <?php } else { ?>
            <p>Grup ini private. Hubungi dosen pembuat untuk diundang.</p>
        <?php } ?>
    <?php } ?>

    <h3>Member</h3>
    <table>
        <tr><th>Username</th><th>Nama</th><th>Tipe</th></tr>
        <?php if (mysqli_num_rows($members) === 0) { ?>
            <tr><td colspan="3" style="text-align:center;">Belum ada member.</td></tr>
        <?php } else { while($m = mysqli_fetch_assoc($members)) { ?>
            <tr>
                <td><?= htmlspecialchars($m['username']); ?></td>
                <td><?= htmlspecialchars($m['nama'] ?? '-'); ?></td>
                <td><?= htmlspecialchars($m['tipe']); ?></td>
            </tr>
        <?php } } ?>
    </table>

    <h3>Event</h3>
    <?php if (!$eventsTableExists) { ?>
        <p>Tabel <code>events</code>/<code>event</code> belum tersedia.</p>
    <?php } elseif (!$eventsTableReady) { ?>
        <p>Tabel event ditemukan tetapi kolom wajib belum dikenali. Pastikan ada kolom relasi grup (group_id/id_grup/dll) dan kolom judul (title/judul/nama).</p>
    <?php } else { ?>
        <table>
            <tr><th>Judul</th><th>Jadwal</th><th>Keterangan</th></tr>
            <?php if (empty($events)) { ?>
                <tr><td colspan="3" style="text-align:center;">Belum ada event.</td></tr>
            <?php } else { foreach ($events as $ev) { ?>
                <tr>
                    <td><?= htmlspecialchars($ev[$eventTitleCol]); ?></td>
                    <td><?= htmlspecialchars($eventScheduleCol ? $ev[$eventScheduleCol] : ($eventCreatedCol ? $ev[$eventCreatedCol] : '')); ?></td>
                    <td><?= htmlspecialchars($eventDetailCol ? $ev[$eventDetailCol] : ''); ?></td>
                </tr>
            <?php } } ?>
        </table>
    <?php } ?>
</body>
</html>
