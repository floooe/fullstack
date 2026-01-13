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
require_once "../../class/Thread.php";

$groupObj = new Grup();
$threadObj = new Thread();
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'join_group' && !$isMember && isset($_POST['join_code'])) {
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

    if ($action === 'create_thread' && $isMember) {
        $created = $threadObj->create($groupId, $username);
        $msg = $created ? 'Thread dibuat' : 'Gagal membuat thread';
        header("Location: group_detail.php?id=$groupId&msg=" . urlencode($msg));
        exit;
    }
}

if (isset($_GET['close_thread'])) {
    $closeId = (int) $_GET['close_thread'];
    if ($closeId > 0) {
        $success = $threadObj->closeThread($closeId, $username);
        $msg = $success ? 'Thread ditutup' : 'Gagal menutup thread';
        header("Location: group_detail.php?id=$groupId&msg=" . urlencode($msg));
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

$threads = $threadObj->getByGroup($groupId);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Group</title>
    <link rel="stylesheet" href="/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/asset/mahasiswa.css">
    <link rel="stylesheet" href="/fullstack/asset/group.css">
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

        <div class="card section">
            <div class="page-header page-header-tight">
                <h3>Thread Grup</h3>
                <span class="table-note"><?= $threads ? $threads->num_rows : 0 ?> thread</span>
            </div>

            <?php if ($isMember): ?>
                <form method="post" class="section">
                    <input type="hidden" name="action" value="create_thread">
                    <button type="submit" class="btn btn-primary btn-small">+ Buat Thread</button>
                    <p class="muted" style="margin-top: 8px;">Thread yang baru otomatis open; hanya pembuatnya yang bisa menutup dari sini.</p>
                </form>
            <?php endif; ?>

            <?php if ($threads && $threads->num_rows > 0): ?>
                <div class="table-wrapper card-compact">
                    <table class="table-compact">
                        <tr>
                            <th>Pembuat</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                        <?php while ($t = $threads->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['nama_pembuat'] ?? $t['username_pembuat']) ?></td>
                                <td><?= htmlspecialchars($t['status']) ?></td>
                                <td><?= htmlspecialchars($t['tanggal_pembuatan']) ?></td>
                                <td>
                                    <?php if ($t['status'] === 'Open'): ?>
                                        <a class="btn btn-small" href="../chat/thread_chat.php?idthread=<?= $t['idthread'] ?>">Buka Chat</a>
                                    <?php else: ?>
                                        <span class="muted">Thread tertutup</span>
                                    <?php endif; ?>
                                    <?php if ($t['username_pembuat'] === $username && $t['status'] === 'Open'): ?>
                                        <a class="btn btn-danger btn-small"
                                           onclick="return confirm('Tutup thread ini?')"
                                           href="group_detail.php?id=<?= $groupId ?>&close_thread=<?= $t['idthread'] ?>">Close</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            <?php else: ?>
                <p class="muted">Belum ada thread di grup ini.</p>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
