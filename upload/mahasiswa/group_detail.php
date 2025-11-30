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
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/mahasiswa.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/group.css">
</head>
<body class="mahasiswa-page group-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Detail Group</h2>
                <p class="page-subtitle">Lihat informasi grup dan event aktif.</p>
            </div>
            <button type="button" class="btn btn-small" onclick="location.href='groups.php'">Kembali</button>
        </div>

        <?php if ($info) { ?>
            <div class="alert alert-success"><?= htmlspecialchars($info); ?></div>
        <?php } ?>
        <?php if (!empty($errors)) { ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e) { echo "<p>" . htmlspecialchars($e) . "</p>"; } ?>
            </div>
        <?php } ?>

        <div class="card section">
            <h3><?= htmlspecialchars($groupName); ?> <span class="badge"><?= htmlspecialchars(ucfirst($groupJenis)); ?></span></h3>
            <p><b>Kode Pendaftaran:</b> <span class="pill"><?= htmlspecialchars($groupCode); ?></span></p>
            <p class="muted"><b>Dosen Pembuat:</b> <?= htmlspecialchars($group['created_by']); ?> | <b>Dibuat:</b> <?= htmlspecialchars($group['created_at']); ?></p>
            <p><b>Deskripsi:</b> <?= htmlspecialchars($groupDesc); ?></p>

            <?php if ($isMember) { ?>
                <div class="toolbar mt-6">
                    <button type="button" class="btn btn-danger btn-small" onclick="if(confirm('Keluar dari grup?')) location.href='group_detail.php?id=<?= $groupId; ?>&leave=1'">Keluar dari grup</button>
                </div>
            <?php } else { ?>
                <p class="muted">Anda belum tergabung di grup ini.</p>
                <?php if ($groupJenis === 'public') { ?>
                    <form method="post" class="section mt-6">
                        <label>Masukkan kode pendaftaran untuk join:</label>
                        <div class="toolbar">
                            <input type="text" name="join_code" required placeholder="Kode pendaftaran" class="max-240">
                            <button type="submit" class="btn btn-small">Gabung</button>
                        </div>
                    </form>
                <?php } else { ?>
                    <p class="muted">Grup ini private. Hubungi dosen pembuat untuk diundang.</p>
                <?php } ?>
            <?php } ?>
        </div>

        <div class="card section">
            <h3>Member</h3>
            <div class="table-wrapper card-compact">
                <table class="table-compact">
                    <tr><th>Username</th><th>Nama</th><th>Tipe</th></tr>
                    <?php if (mysqli_num_rows($members) === 0) { ?>
                        <tr><td colspan="3" class="text-center">Belum ada member.</td></tr>
                    <?php } else { while($m = mysqli_fetch_assoc($members)) { ?>
                        <tr>
                            <td><?= htmlspecialchars($m['username']); ?></td>
                            <td><?= htmlspecialchars($m['nama'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($m['tipe']); ?></td>
                        </tr>
                    <?php } } ?>
                </table>
            </div>
        </div>

        <div class="card section">
            <h3>Event</h3>
            <?php if (!$eventsTableExists) { ?>
                <p>Tabel <code>events</code>/<code>event</code> belum tersedia.</p>
            <?php } elseif (!$eventsTableReady) { ?>
                <p>Tabel event ditemukan tetapi kolom wajib belum dikenali. Pastikan ada kolom relasi grup (group_id/id_grup/dll) dan kolom judul (title/judul/nama).</p>
            <?php } else { ?>
                <div class="table-wrapper card-compact">
                    <table class="table-compact">
                        <tr><th>Judul</th><th>Jadwal</th><th>Keterangan</th></tr>
                        <?php if (empty($events)) { ?>
                            <tr><td colspan="3" class="text-center">Belum ada event.</td></tr>
                        <?php } else { foreach ($events as $ev) { ?>
                            <tr>
                                <td><?= htmlspecialchars($ev[$eventTitleCol]); ?></td>
                                <td><?= htmlspecialchars($eventScheduleCol ? $ev[$eventScheduleCol] : ($eventCreatedCol ? $ev[$eventCreatedCol] : '')); ?></td>
                                <td><?= htmlspecialchars($eventDetailCol ? $ev[$eventDetailCol] : ''); ?></td>
                            </tr>
                        <?php } } ?>
                    </table>
                </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>
