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

require_once "../../class/Group.php";

$groupObj = new Grup();
$username = $_SESSION['username'];

$groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($groupId <= 0) {
    header("Location: groups.php");
    exit;
}

$group = $groupObj->getById($groupId);
if (!$group) {
    header("Location: groups.php?msg=Grup tidak ditemukan");
    exit;
}

$groupName = $group['nama'];
$groupCode = $group['kode_pendaftaran'] ?? '';
$groupJenis = strtolower($group['jenis'] ?? 'public');
$groupDesc = $group['deskripsi'] ?? '';
$createdBy = $group['username_pembuat'] ?? '-';
$createdAt = $group['tanggal_pembentukan'] ?? '-';

$isMember = $groupObj->isMember($groupId, $username);
$info = $_GET['msg'] ?? null;
$errors = [];

if (isset($_GET['leave']) && $isMember) {
    $groupObj->leaveGroup($groupId, $username);
    header("Location: groups.php?msg=Berhasil keluar dari grup");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_code']) && !$isMember) {
    $kode = strtoupper(trim($_POST['join_code']));

    if ($kode === '') {
        $errors[] = "Kode tidak boleh kosong.";
    } elseif ($kode !== strtoupper($groupCode)) {
        $errors[] = "Kode pendaftaran salah.";
    } elseif ($groupJenis !== 'public') {
        $errors[] = "Grup private tidak bisa di-join langsung.";
    } else {
        $groupObj->joinGroup($groupId, $username);
        header("Location: group_detail.php?id=$groupId&msg=Berhasil bergabung");
        exit;
    }
}

$members = $groupObj->getMembers($groupId);

function detect_events_table($conn)
{
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'events'")) > 0)
        return 'events';
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'event'")) > 0)
        return 'event';
    return null;
}

include "../../proses/koneksi.php";

$eventsTable = detect_events_table($conn);
$events = [];
$eventGroupCol = null;
$eventTitleCol = null;
$eventScheduleCol = null;
$eventDetailCol = null;
$eventCreatedCol = null;

if ($eventsTable) {
    $cols = [];
    $res = mysqli_query($conn, "SHOW COLUMNS FROM {$eventsTable}");
    while ($c = mysqli_fetch_assoc($res)) {
        $cols[] = $c['Field'];
    }

    foreach (['group_id', 'idgrup', 'id_grup'] as $c)
        if (in_array($c, $cols))
            $eventGroupCol = $c;
    foreach (['title', 'judul', 'nama'] as $c)
        if (in_array($c, $cols))
            $eventTitleCol = $c;
    foreach (['schedule_at', 'jadwal', 'tanggal'] as $c)
        if (in_array($c, $cols))
            $eventScheduleCol = $c;
    foreach (['detail', 'deskripsi', 'keterangan'] as $c)
        if (in_array($c, $cols))
            $eventDetailCol = $c;
    foreach (['created_at', 'created'] as $c)
        if (in_array($c, $cols))
            $eventCreatedCol = $c;

    if ($eventGroupCol && $eventTitleCol) {
        $order = $eventScheduleCol ?: ($eventCreatedCol ?: 'id');
        $q = mysqli_query($conn, "SELECT * FROM {$eventsTable} WHERE {$eventGroupCol}=$groupId ORDER BY {$order} DESC");
        while ($row = mysqli_fetch_assoc($q)) {
            $events[] = $row;
        }
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
                <p class="page-subtitle">Informasi grup dan event</p>
            </div>
            <button class="btn btn-small" onclick="location.href='groups.php'">Kembali</button>
        </div>

        <?php if ($info): ?>
            <div class="alert alert-success"><?= htmlspecialchars($info) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): ?>
                    <p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card section">
            <h3><?= htmlspecialchars($groupName) ?>
                <span class="badge"><?= ucfirst($groupJenis) ?></span>
            </h3>
            <p><b>Kode:</b> <span class="pill"><?= htmlspecialchars($groupCode) ?></span></p>
            <p class="muted"><b>Dibuat oleh:</b> <?= htmlspecialchars($createdBy) ?> |
                <?= htmlspecialchars($createdAt) ?></p>
            <p><b>Deskripsi:</b> <?= htmlspecialchars($groupDesc) ?></p>

            <?php if ($isMember): ?>
                <button class="btn btn-danger btn-small"
                    onclick="if(confirm('Keluar dari grup?')) location.href='group_detail.php?id=<?= $groupId ?>&leave=1'">
                    Keluar dari Grup
                </button>
            <?php else: ?>
                <?php if ($groupJenis === 'public'): ?>
                    <form method="post" class="section">
                        <label>Masukkan kode pendaftaran:</label>
                        <div class="toolbar">
                            <input type="text" name="join_code" required>
                            <button class="btn btn-small">Gabung</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="muted">Grup private, hubungi dosen.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="card section">
            <h3>Member</h3>
            <table class="table-compact">
                <tr>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Tipe</th>
                </tr>
                <?php if ($members->num_rows === 0): ?>
                    <tr>
                        <td colspan="3">Belum ada member</td>
                    </tr>
                <?php else:
                    while ($m = $members->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['username']) ?></td>
                            <td><?= htmlspecialchars($m['nama']) ?></td>
                            <td><?= htmlspecialchars($m['tipe']) ?></td>
                        </tr>
                    <?php endwhile; endif; ?>
            </table>
        </div>

        <div class="card section">
            <h3>Event</h3>
            <?php if (!$events): ?>
                <p class="muted">Belum ada event.</p>
            <?php else: ?>
                <table class="table-compact">
                    <tr>
                        <th>Judul</th>
                        <th>Jadwal</th>
                        <th>Keterangan</th>
                    </tr>
                    <?php foreach ($events as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e[$eventTitleCol]) ?></td>
                            <td><?= htmlspecialchars($eventScheduleCol ? $e[$eventScheduleCol] : '') ?></td>
                            <td><?= htmlspecialchars($eventDetailCol ? $e[$eventDetailCol] : '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>