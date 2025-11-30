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

function detect_events_table($conn) {
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'events'")) > 0) {
        return 'events';
    }
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'event'")) > 0) {
        return 'event';
    }
    return null;
}

$groupId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($groupId <= 0) {
    header("Location: groups.php");
    exit;
}

$group = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM grup WHERE idgrup=$groupId"));
if (!$group) {
    header("Location: groups.php?msg=Grup tidak ditemukan");
    exit;
}

// Parse fields
$groupName = $group['nama'];
$groupCode = $group['kode_pendaftaran'] ?? '';
// jenis ambil kolom, fallback dari deskripsi prefix
$groupJenis = !empty($group['jenis']) ? strtolower($group['jenis']) : 'public';
if (strpos($group['deskripsi'], '[public]') === 0) {
    $groupJenis = 'public';
} elseif (strpos($group['deskripsi'], '[private]') === 0) {
    $groupJenis = 'private';
}
$groupDesc = $group['deskripsi'];
$isCreator = $group['username_pembuat'] === $_SESSION['username'];
$createdBy = $group['username_pembuat'] ?? ($group['created_by'] ?? '-');
$createdAt = $group['tanggal_pembentukan'] ?? ($group['created_at'] ?? '-');

$info = isset($_GET['msg']) ? $_GET['msg'] : null;
$errors = [];

$eventsTable = detect_events_table($conn);
$eventsTableExists = $eventsTable !== null;
$eventGroupCol = null;
$eventScheduleCol = null;
$eventCreatedCol = null;
$eventTitleCol = null;
$eventDetailCol = null;
$eventIdCol = null;
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
    foreach (['id', 'id_event', 'idevent'] as $candidate) {
        if (in_array($candidate, $cols, true)) {
            $eventIdCol = $candidate;
            break;
        }
    }
    if (!$eventIdCol && !empty($cols)) {
        $eventIdCol = $cols[0]; // fallback kolom pertama
    }
}
$eventsTableReady = $eventsTableExists && $eventGroupCol && $eventTitleCol;
$eventOrderCol = $eventScheduleCol ?: ($eventCreatedCol ?: 'id');

// helper cek apakah ID grup valid untuk tabel relasi event
function event_group_exists($conn, $eventGroupCol, $groupId) {
    // jika kolom khas "idgrup" biasanya refer ke tabel "grup"
    if ($eventGroupCol === 'idgrup' || $eventGroupCol === 'id_grup') {
        $res = mysqli_query($conn, "SELECT 1 FROM grup WHERE idgrup=$groupId LIMIT 1");
        return mysqli_num_rows($res) > 0;
    }
    // default cek ke tabel groups
    $res = mysqli_query($conn, "SELECT 1 FROM groups WHERE id=$groupId LIMIT 1");
    return mysqli_num_rows($res) > 0;
}

// pastikan baris grup ada di tabel "grup" jika FK event menunjuk ke sana
function ensure_grup_row($conn, $eventGroupCol, $groupId, $groupName, $groupDesc, $groupJenis, $groupCode, $groupCreatedBy, $groupCreatedAt) {
    if (!in_array($eventGroupCol, ['idgrup', 'id_grup'], true)) {
        return true; // tidak butuh sinkron
    }

    // cek tabel grup ada
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'grup'")) === 0) {
        return false;
    }

    // sudah ada?
    $exists = mysqli_query($conn, "SELECT 1 FROM grup WHERE idgrup=$groupId LIMIT 1");
    if (mysqli_num_rows($exists) > 0) {
        return true;
    }

    // deteksi kolom yang tersedia
    $cols = [];
    $colRes = mysqli_query($conn, "SHOW COLUMNS FROM grup");
    while ($c = mysqli_fetch_assoc($colRes)) {
        $cols[] = $c['Field'];
    }

    $insertCols = [];
    $insertVals = [];
    $insertCols[] = in_array('idgrup', $cols, true) ? 'idgrup' : $eventGroupCol;
    $insertVals[] = (int)$groupId;

    if (in_array('username_pembuat', $cols, true)) {
        $insertCols[] = 'username_pembuat';
        $insertVals[] = "'" . mysqli_real_escape_string($conn, $groupCreatedBy) . "'";
    }
    if (in_array('nama', $cols, true)) {
        $insertCols[] = 'nama';
        $insertVals[] = "'" . mysqli_real_escape_string($conn, $groupName) . "'";
    }
    if (in_array('deskripsi', $cols, true)) {
        $insertCols[] = 'deskripsi';
        $insertVals[] = "'" . mysqli_real_escape_string($conn, $groupDesc) . "'";
    }
    if (in_array('jenis', $cols, true)) {
        $insertCols[] = 'jenis';
        $insertVals[] = "'" . mysqli_real_escape_string($conn, ucfirst($groupJenis)) . "'";
    }
    if (in_array('kode_pendaftaran', $cols, true)) {
        $insertCols[] = 'kode_pendaftaran';
        $insertVals[] = "'" . mysqli_real_escape_string($conn, $groupCode) . "'";
    }
    if (in_array('tanggal_pembentukan', $cols, true)) {
        $insertCols[] = 'tanggal_pembentukan';
        $insertVals[] = "'" . mysqli_real_escape_string($conn, $groupCreatedAt) . "'";
    }

    $colList = implode(',', $insertCols);
    $valList = implode(',', $insertVals);
    return mysqli_query($conn, "INSERT INTO grup($colList) VALUES ($valList)");
}

// ACTION HANDLERS
if ($isCreator && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_group') {
        $nama = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $jenis = ($_POST['jenis'] ?? 'public') === 'private' ? 'private' : 'public';
        if ($nama === '') {
            $errors[] = "Nama grup wajib diisi.";
        } else {
            $name_final = mysqli_real_escape_string($conn, $nama . " | " . $groupCode);
            $description_final = mysqli_real_escape_string($conn, "[" . $jenis . "] " . $desc);
            mysqli_query($conn, "UPDATE groups SET name='$name_final', description='$description_final' WHERE id=$groupId");
            header("Location: group_detail.php?id=$groupId&msg=Perubahan disimpan");
            exit;
        }
    }

    if ($action === 'add_member') {
        $memberUsername = trim($_POST['member_username'] ?? '');
        if ($memberUsername !== '') {
            $memberUsernameEsc = mysqli_real_escape_string($conn, $memberUsername);
            $already = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM group_members WHERE group_id=$groupId AND username='$memberUsernameEsc'"));
            if ($already == 0) {
                mysqli_query($conn, "INSERT INTO group_members(group_id, username, joined_at) VALUES ($groupId, '$memberUsernameEsc', NOW())");
                header("Location: group_detail.php?id=$groupId&msg=Member ditambahkan");
                exit;
            } else {
                $errors[] = "Member sudah ada di grup.";
            }
        }
    }

    if ($eventsTableReady && $action === 'add_event') {
        $title = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
        $schedule = mysqli_real_escape_string($conn, trim($_POST['schedule'] ?? ''));
        $detail = mysqli_real_escape_string($conn, trim($_POST['detail'] ?? ''));
        if ($title !== '') {
            if (!event_group_exists($conn, $eventGroupCol, $groupId)) {
                // coba sinkronkan baris di tabel grup jika diperlukan
                if (!ensure_grup_row($conn, $eventGroupCol, $groupId, $groupName, $groupDesc, $groupJenis, $groupCode, $group['created_by'], $group['created_at'])) {
                    $errors[] = "ID grup tidak ditemukan di tabel referensi event. Pastikan grup ada di tabel tujuan (mis. 'grup').";
                }
            }
            if (empty($errors)) {
                $scheduleField = $eventScheduleCol ?: 'schedule_at';
                $detailField = $eventDetailCol ?: 'detail';
                $columns = [$eventGroupCol, $eventTitleCol, $scheduleField, $detailField];
                $values = ["$groupId", "'$title'", "'$schedule'", "'$detail'"];
                if ($eventCreatedCol) {
                    $columns[] = $eventCreatedCol;
                    $values[] = "NOW()";
                }
                $colList = implode(',', $columns);
                $valList = implode(',', $values);
                mysqli_query($conn, "INSERT INTO {$eventsTable}({$colList}) VALUES ({$valList})");
                header("Location: group_detail.php?id=$groupId&msg=Event ditambahkan");
                exit;
            }
        } else {
            $errors[] = "Judul event wajib diisi.";
        }
    }

    if ($eventsTableReady && $action === 'update_event') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $title = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
        $schedule = mysqli_real_escape_string($conn, trim($_POST['schedule'] ?? ''));
        $detail = mysqli_real_escape_string($conn, trim($_POST['detail'] ?? ''));
        if ($title === '') {
            $errors[] = "Judul event wajib diisi.";
        } else {
            if (!event_group_exists($conn, $eventGroupCol, $groupId)) {
                // coba sinkronkan baris di tabel grup jika diperlukan
                if (!ensure_grup_row($conn, $eventGroupCol, $groupId, $groupName, $groupDesc, $groupJenis, $groupCode, $group['created_by'], $group['created_at'])) {
                    $errors[] = "ID grup tidak ditemukan di tabel referensi event. Pastikan grup ada di tabel tujuan (mis. 'grup').";
                }
            }
            if (empty($errors)) {
                $scheduleField = $eventScheduleCol ?: 'schedule_at';
                $detailField = $eventDetailCol ?: 'detail';
                mysqli_query($conn, "UPDATE {$eventsTable} SET {$eventTitleCol}='$title', {$scheduleField}='$schedule', {$detailField}='$detail' WHERE {$eventIdCol}=$eventId AND {$eventGroupCol}=$groupId");
                header("Location: group_detail.php?id=$groupId&msg=Event diperbarui");
                exit;
            }
        }
    }
}

if ($isCreator && isset($_GET['remove_member'])) {
    $mid = (int)$_GET['remove_member'];
    mysqli_query($conn, "DELETE FROM group_members WHERE id=$mid AND group_id=$groupId");
    header("Location: group_detail.php?id=$groupId&msg=Member dihapus");
    exit;
}

if ($isCreator && $eventsTableReady && isset($_GET['delete_event'])) {
    $eid = (int)$_GET['delete_event'];
    $idCol = $eventIdCol ?: 'id';
    mysqli_query($conn, "DELETE FROM {$eventsTable} WHERE {$idCol}=$eid AND {$eventGroupCol}=$groupId");
    header("Location: group_detail.php?id=$groupId&msg=Event dihapus");
    exit;
}

$members = mysqli_query($conn, "
    SELECT mg.username,
           COALESCE(d.nama, m.nama) AS nama,
           CASE 
                WHEN d.npk IS NOT NULL THEN 'Dosen'
                WHEN m.nrp IS NOT NULL THEN 'Mahasiswa'
                ELSE 'User'
           END AS tipe
    FROM member_grup mg
    LEFT JOIN dosen d ON d.npk = mg.username
    LEFT JOIN mahasiswa m ON m.nrp = mg.username
    WHERE mg.idgrup=$groupId
    ORDER BY tipe, nama
");


$memberUsernames = [];
$memberList = [];
while ($row = mysqli_fetch_assoc($members)) {
    $memberList[] = $row;
    $memberUsernames[] = "'" . mysqli_real_escape_string($conn, $row['username']) . "'";
}
$membersClause = $memberUsernames ? implode(",", $memberUsernames) : "''";

$searchDosen = isset($_GET['sd']) ? mysqli_real_escape_string($conn, $_GET['sd']) : '';
$searchMhs = isset($_GET['sm']) ? mysqli_real_escape_string($conn, $_GET['sm']) : '';

$sqlDosen = "SELECT npk AS username, nama FROM dosen";
if ($membersClause !== "''") {
    $sqlDosen .= " WHERE npk NOT IN ($membersClause)";
} else {
    $sqlDosen .= " WHERE 1=1";
}
if ($searchDosen !== '') {
    $sqlDosen .= " AND (npk LIKE '%$searchDosen%' OR nama LIKE '%$searchDosen%')";
}
$sqlDosen .= " ORDER BY nama ASC LIMIT 10";
$dosenList = mysqli_query($conn, $sqlDosen);

$sqlMhs = "SELECT nrp AS username, nama FROM mahasiswa";
if ($membersClause !== "''") {
    $sqlMhs .= " WHERE nrp NOT IN ($membersClause)";
} else {
    $sqlMhs .= " WHERE 1=1";
}
if ($searchMhs !== '') {
    $sqlMhs .= " AND (nrp LIKE '%$searchMhs%' OR nama LIKE '%$searchMhs%')";
}
$sqlMhs .= " ORDER BY nama ASC LIMIT 10";
$mhsList = mysqli_query($conn, $sqlMhs);

$events = [];
$editEvent = null;
if ($eventsTableReady) {
    $eventsRes = mysqli_query($conn, "SELECT * FROM {$eventsTable} WHERE {$eventGroupCol}=$groupId ORDER BY {$eventOrderCol} DESC");
    while ($ev = mysqli_fetch_assoc($eventsRes)) {
        $events[] = $ev;
        if ($eventIdCol && isset($_GET['edit_event']) && (int)$_GET['edit_event'] === (int)$ev[$eventIdCol]) {
            $editEvent = $ev;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Detail Group</title>
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/dosen.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/group.css">
</head>
<body class="dosen-page group-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Detail Group</h2>
                <p class="page-subtitle">Kelola informasi, anggota, dan event grup.</p>
            </div>
            <button type="button" class="btn btn-small" onclick="location.href='groups.php'">Kembali ke Group Saya</button>
        </div>

        <div class="card section">
            <h3><?= htmlspecialchars($groupName); ?> <span class="badge"><?= htmlspecialchars(ucfirst($groupJenis)); ?></span></h3>
            <p><b>Kode Pendaftaran:</b> <span class="pill"><?= htmlspecialchars($groupCode); ?></span></p>
            <p class="muted"><b>Dibuat oleh:</b> <?= htmlspecialchars($createdBy); ?> | <b>Tanggal:</b> <?= htmlspecialchars($createdAt); ?></p>
            <p><b>Deskripsi:</b> <?= htmlspecialchars($groupDesc); ?></p>

            <?php if ($isCreator) { ?>
                <div class="section mt-12">
                    <h4>Ubah Informasi Grup</h4>
                    <form method="post" class="section">
                        <input type="hidden" name="action" value="update_group">
                        <div class="field">
                            <label>Nama</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($groupName); ?>" required>
                        </div>
                        <div class="field">
                            <label>Jenis</label>
                            <select name="jenis">
                                <option value="public" <?= $groupJenis === 'public' ? 'selected' : ''; ?>>Public</option>
                                <option value="private" <?= $groupJenis === 'private' ? 'selected' : ''; ?>>Private</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Deskripsi</label>
                            <textarea name="description" rows="3"><?= htmlspecialchars($groupDesc); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-small">Simpan Perubahan</button>
                    </form>
                </div>
            <?php } ?>
        </div>

        <div class="card section">
            <div class="page-header page-header-tight">
                <h3>Member</h3>
                <span class="table-note"><?= count($memberList); ?> anggota</span>
            </div>
            <?php if (count($memberList) === 0) { ?>
                <p class="muted">Belum ada member.</p>
            <?php } else { ?>
                <div class="table-wrapper card-compact">
                    <table class="table-compact">
                        <tr><th>Username</th><th>Nama</th><th>Tipe</th><?php if ($isCreator) { ?><th>Aksi</th><?php } ?></tr>
                        <?php foreach ($memberList as $m) { ?>
                            <tr>
                        <td><?= htmlspecialchars($m['username']); ?></td>
                        <td><?= htmlspecialchars($m['nama'] ?? '-'); ?></td>
                        <td><?= htmlspecialchars($m['tipe']); ?></td>
                        <?php if ($isCreator) { ?>
                            <td>
                                <button type="button" class="btn btn-danger btn-small" onclick="if(confirm('Hapus member ini?')) location.href='group_detail.php?id=<?= $groupId; ?>&remove_member=<?= $m['id']; ?>'">Hapus</button>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </table>
        </div>
            <?php } ?>

            <?php if ($isCreator) { ?>
                <div class="section">
                    <h4>Tambah Member</h4>
                    <p class="muted">Pilih dari daftar dosen/mahasiswa di bawah (klik Tambah). Ketik untuk mencari.</p>
                    <div class="stack">
                        <div class="card card-compact flex-tile">
                            <b>Dosen</b>
                            <form method="get" class="toolbar">
                                <input type="hidden" name="id" value="<?= $groupId; ?>">
                                <input type="text" name="sd" value="<?= htmlspecialchars($searchDosen); ?>" placeholder="Cari nama/npk">
                                <button type="submit" class="btn btn-secondary btn-small">Cari</button>
                            </form>
                            <div class="table-wrapper card-compact">
                                <table class="table-compact">
                                    <tr><th>NPK</th><th>Nama</th><th></th></tr>
                                    <?php if (mysqli_num_rows($dosenList) === 0) { ?>
                                        <tr><td colspan="3" class="text-center">Tidak ada data</td></tr>
                                    <?php } else { while($d = mysqli_fetch_assoc($dosenList)) { ?>
                                        <tr>
                                            <td><?= htmlspecialchars($d['username']); ?></td>
                                            <td><?= htmlspecialchars($d['nama']); ?></td>
                                            <td>
                                                <form method="post" class="no-margin">
                                                    <input type="hidden" name="action" value="add_member">
                                                    <input type="hidden" name="member_username" value="<?= htmlspecialchars($d['username']); ?>">
                                                    <button type="submit" class="btn btn-small">Tambah</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php } } ?>
                                </table>
                            </div>
                        </div>

                        <div class="card card-compact flex-tile">
                            <b>Mahasiswa</b>
                            <form method="get" class="toolbar">
                                <input type="hidden" name="id" value="<?= $groupId; ?>">
                                <input type="text" name="sm" value="<?= htmlspecialchars($searchMhs); ?>" placeholder="Cari nama/nrp">
                                <button type="submit" class="btn btn-secondary btn-small">Cari</button>
                            </form>
                            <div class="table-wrapper card-compact">
                                <table class="table-compact">
                                    <tr><th>NRP</th><th>Nama</th><th></th></tr>
                                    <?php if (mysqli_num_rows($mhsList) === 0) { ?>
                                        <tr><td colspan="3" class="text-center">Tidak ada data</td></tr>
                                    <?php } else { while($m = mysqli_fetch_assoc($mhsList)) { ?>
                                        <tr>
                                            <td><?= htmlspecialchars($m['username']); ?></td>
                                            <td><?= htmlspecialchars($m['nama']); ?></td>
                                            <td>
                                                <form method="post" class="no-margin">
                                                    <input type="hidden" name="action" value="add_member">
                                                    <input type="hidden" name="member_username" value="<?= htmlspecialchars($m['username']); ?>">
                                                    <button type="submit" class="btn btn-small">Tambah</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php } } ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <div class="card section">
            <div class="page-header page-header-tight">
                <h3>Event Group</h3>
                <?php if ($eventsTableReady) { ?><span class="table-note"><?= count($events); ?> event</span><?php } ?>
            </div>
            <?php if (!$eventsTableExists) { ?>
                <p>Tabel <code>events</code>/<code>event</code> belum tersedia. Buat tabel dengan kolom minimal: <code>id</code> (PK, auto increment), kolom relasi grup, kolom judul, kolom jadwal (DATETIME), kolom detail, kolom created_at.</p>
            <?php } elseif (!$eventsTableReady) { ?>
                <p>Tabel event ditemukan tetapi kolom wajib belum dikenali. Pastikan ada: kolom relasi grup (group_id/id_grup/dll) dan kolom judul (title/judul/nama).</p>
            <?php } else { ?>
                <?php if (empty($events)) { ?>
                    <p class="muted">Belum ada event.</p>
                <?php } else { ?>
                    <div class="table-wrapper card-compact">
                        <table class="table-compact">
                            <tr><th>Judul</th><th>Jadwal</th><th>Keterangan</th><?php if ($isCreator) { ?><th>Aksi</th><?php } ?></tr>
                            <?php foreach ($events as $ev) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($ev[$eventTitleCol]); ?></td>
                                    <td><?= htmlspecialchars($eventScheduleCol && isset($ev[$eventScheduleCol]) ? $ev[$eventScheduleCol] : ($eventCreatedCol && isset($ev[$eventCreatedCol]) ? $ev[$eventCreatedCol] : '')); ?></td>
                                <td><?= htmlspecialchars($eventDetailCol && isset($ev[$eventDetailCol]) ? $ev[$eventDetailCol] : ''); ?></td>
                                <?php if ($isCreator) { ?>
                                    <td>
                                        <div class="toolbar">
                                            <button type="button" class="btn btn-small" onclick="location.href='group_detail.php?id=<?= $groupId; ?>&edit_event=<?= $eventIdCol ? $ev[$eventIdCol] : ''; ?>'">Edit</button>
                                            <button type="button" class="btn btn-danger btn-small" onclick="if(confirm('Hapus event?')) location.href='group_detail.php?id=<?= $groupId; ?>&delete_event=<?= $eventIdCol ? $ev[$eventIdCol] : ''; ?>'">Hapus</button>
                                        </div>
                                    </td>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                        </table>
                    </div>
                <?php } ?>

                <?php if ($isCreator) { ?>
                    <div class="section">
                        <h4><?= $editEvent ? 'Edit Event' : 'Tambah Event'; ?></h4>
                        <?php
                            $scheduleValue = '';
                            if ($editEvent) {
                                $raw = $eventScheduleCol && isset($editEvent[$eventScheduleCol]) ? $editEvent[$eventScheduleCol] : '';
                                if ($raw !== '') {
                                    $scheduleValue = htmlspecialchars(str_replace(' ', 'T', substr($raw, 0, 16)));
                                }
                            }
                        ?>
                        <form method="post" class="section">
                            <input type="hidden" name="action" value="<?= $editEvent ? 'update_event' : 'add_event'; ?>">
                            <?php if ($editEvent) { ?>
                                <input type="hidden" name="event_id" value="<?= $eventIdCol ? $editEvent[$eventIdCol] : ''; ?>">
                            <?php } ?>
                            <div class="field">
                                <label>Judul</label>
                                <input type="text" name="title" value="<?= $editEvent && isset($editEvent[$eventTitleCol]) ? htmlspecialchars($editEvent[$eventTitleCol]) : ''; ?>" required>
                            </div>
                            <div class="field">
                                <label>Jadwal</label>
                                <input type="datetime-local" name="schedule" value="<?= $scheduleValue; ?>" placeholder="Pilih tanggal & waktu">
                            </div>
                            <div class="field">
                                <label>Keterangan</label>
                                <textarea name="detail" rows="3"><?= $editEvent && $eventDetailCol && isset($editEvent[$eventDetailCol]) ? htmlspecialchars($editEvent[$eventDetailCol]) : ''; ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-small"><?= $editEvent ? 'Simpan Perubahan' : 'Tambah Event'; ?></button>
                        </form>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
</body>
</html>
