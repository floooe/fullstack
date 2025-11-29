<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
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

$group = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM groups WHERE id=$groupId"));
if (!$group) {
    header("Location: groups.php?msg=Grup tidak ditemukan");
    exit;
}

// Parse name, code, jenis, desc
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

list($groupName, $groupCode, $groupJenis, $groupDesc) = parse_group($group['name'], $group['description']);
$isCreator = $group['created_by'] === $_SESSION['username'];

$info = isset($_GET['msg']) ? $_GET['msg'] : null;
$errors = [];

$eventsTable = detect_events_table($conn);
$eventsTableExists = $eventsTable !== null;

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

    if ($eventsTableExists && $action === 'add_event') {
        $title = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
        $schedule = mysqli_real_escape_string($conn, trim($_POST['schedule'] ?? ''));
        $detail = mysqli_real_escape_string($conn, trim($_POST['detail'] ?? ''));
        if ($title !== '') {
            mysqli_query($conn, "INSERT INTO {$eventsTable}(group_id, title, schedule_at, detail, created_at) VALUES ($groupId, '$title', '$schedule', '$detail', NOW())");
            header("Location: group_detail.php?id=$groupId&msg=Event ditambahkan");
            exit;
        } else {
            $errors[] = "Judul event wajib diisi.";
        }
    }

    if ($eventsTableExists && $action === 'update_event') {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $title = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
        $schedule = mysqli_real_escape_string($conn, trim($_POST['schedule'] ?? ''));
        $detail = mysqli_real_escape_string($conn, trim($_POST['detail'] ?? ''));
        if ($title === '') {
            $errors[] = "Judul event wajib diisi.";
        } else {
            mysqli_query($conn, "UPDATE {$eventsTable} SET title='$title', schedule_at='$schedule', detail='$detail' WHERE id=$eventId AND group_id=$groupId");
            header("Location: group_detail.php?id=$groupId&msg=Event diperbarui");
            exit;
        }
    }
}

if ($isCreator && isset($_GET['remove_member'])) {
    $mid = (int)$_GET['remove_member'];
    mysqli_query($conn, "DELETE FROM group_members WHERE id=$mid AND group_id=$groupId");
    header("Location: group_detail.php?id=$groupId&msg=Member dihapus");
    exit;
}

if ($isCreator && $eventsTableExists && isset($_GET['delete_event'])) {
    $eid = (int)$_GET['delete_event'];
    mysqli_query($conn, "DELETE FROM {$eventsTable} WHERE id=$eid AND group_id=$groupId");
    header("Location: group_detail.php?id=$groupId&msg=Event dihapus");
    exit;
}

// DATA
$members = mysqli_query($conn, "
    SELECT gm.id, gm.username,
           COALESCE(d.nama, m.nama) AS nama,
           CASE 
                WHEN d.npk IS NOT NULL THEN 'Dosen'
                WHEN m.nrp IS NOT NULL THEN 'Mahasiswa'
                ELSE 'User'
           END AS tipe
    FROM group_members gm
    LEFT JOIN dosen d ON d.npk = gm.username
    LEFT JOIN mahasiswa m ON m.nrp = gm.username
    WHERE gm.group_id=$groupId
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
if ($eventsTableExists) {
    $eventsRes = mysqli_query($conn, "SELECT * FROM {$eventsTable} WHERE group_id=$groupId ORDER BY schedule_at DESC");
    while ($ev = mysqli_fetch_assoc($eventsRes)) {
        $events[] = $ev;
        if (isset($_GET['edit_event']) && (int)$_GET['edit_event'] === (int)$ev['id']) {
            $editEvent = $ev;
        }
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
        .section { margin-bottom: 25px; }
        .chip { display: inline-block; padding: 4px 8px; background: #eef; border-radius: 4px; }
    </style>
</head>
<body>
    <h2>Detail Group</h2>
    <p><a href="groups.php">Kembali ke Group Saya</a></p>

    <?php if ($info) { ?>
        <div style="background:#e8f5e9; padding:10px; border:1px solid #c8e6c9; margin-bottom:10px;"><?= htmlspecialchars($info); ?></div>
    <?php } ?>
    <?php if (!empty($errors)) { ?>
        <div style="background:#ffebee; padding:10px; border:1px solid #ffcdd2; margin-bottom:10px;">
            <?php foreach ($errors as $e) { echo "<p>" . htmlspecialchars($e) . "</p>"; } ?>
        </div>
    <?php } ?>

    <div class="section">
        <h3><?= htmlspecialchars($groupName); ?> <span class="chip"><?= htmlspecialchars(ucfirst($groupJenis)); ?></span></h3>
        <p><b>Kode Pendaftaran:</b> <span style="font-size:1.1em;"><?= htmlspecialchars($groupCode); ?></span></p>
        <p><b>Dibuat oleh:</b> <?= htmlspecialchars($group['created_by']); ?> | <b>Tanggal:</b> <?= htmlspecialchars($group['created_at']); ?></p>
        <p><b>Deskripsi:</b> <?= htmlspecialchars($groupDesc); ?></p>

        <?php if ($isCreator) { ?>
            <h4>Ubah Informasi Grup</h4>
            <form method="post">
                <input type="hidden" name="action" value="update_group">
                <div>
                    <label>Nama</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($groupName); ?>" required>
                </div>
                <div>
                    <label>Jenis</label>
                    <select name="jenis">
                        <option value="public" <?= $groupJenis === 'public' ? 'selected' : ''; ?>>Public</option>
                        <option value="private" <?= $groupJenis === 'private' ? 'selected' : ''; ?>>Private</option>
                    </select>
                </div>
                <div>
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3"><?= htmlspecialchars($groupDesc); ?></textarea>
                </div>
                <button type="submit">Simpan Perubahan</button>
            </form>
        <?php } ?>
    </div>

    <div class="section">
        <h3>Member</h3>
        <?php if (count($memberList) === 0) { ?>
            <p>Belum ada member.</p>
        <?php } else { ?>
            <table>
                <tr><th>Username</th><th>Nama</th><th>Tipe</th><?php if ($isCreator) { ?><th>Aksi</th><?php } ?></tr>
                <?php foreach ($memberList as $m) { ?>
                    <tr>
                        <td><?= htmlspecialchars($m['username']); ?></td>
                        <td><?= htmlspecialchars($m['nama'] ?? '-'); ?></td>
                        <td><?= htmlspecialchars($m['tipe']); ?></td>
                        <?php if ($isCreator) { ?>
                            <td><a href="group_detail.php?id=<?= $groupId; ?>&remove_member=<?= $m['id']; ?>" onclick="return confirm('Hapus member ini?')">Hapus</a></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>

        <?php if ($isCreator) { ?>
            <h4>Tambah Member</h4>
            <p>Pilih dari daftar dosen/mahasiswa di bawah (klik Tambah). Ketik untuk mencari.</p>
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                <div style="flex:1; min-width:320px;">
                    <b>Dosen</b>
                    <form method="get" style="margin:6px 0;">
                        <input type="hidden" name="id" value="<?= $groupId; ?>">
                        <input type="text" name="sd" value="<?= htmlspecialchars($searchDosen); ?>" placeholder="Cari nama/npk">
                        <button type="submit">Cari</button>
                    </form>
                    <table>
                        <tr><th>NPK</th><th>Nama</th><th></th></tr>
                        <?php if (mysqli_num_rows($dosenList) === 0) { ?>
                            <tr><td colspan="3" style="text-align:center;">Tidak ada data</td></tr>
                        <?php } else { while($d = mysqli_fetch_assoc($dosenList)) { ?>
                            <tr>
                                <td><?= htmlspecialchars($d['username']); ?></td>
                                <td><?= htmlspecialchars($d['nama']); ?></td>
                                <td>
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="action" value="add_member">
                                        <input type="hidden" name="member_username" value="<?= htmlspecialchars($d['username']); ?>">
                                        <button type="submit">Tambah</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } } ?>
                    </table>
                </div>

                <div style="flex:1; min-width:320px;">
                    <b>Mahasiswa</b>
                    <form method="get" style="margin:6px 0;">
                        <input type="hidden" name="id" value="<?= $groupId; ?>">
                        <input type="text" name="sm" value="<?= htmlspecialchars($searchMhs); ?>" placeholder="Cari nama/nrp">
                        <button type="submit">Cari</button>
                    </form>
                    <table>
                        <tr><th>NRP</th><th>Nama</th><th></th></tr>
                        <?php if (mysqli_num_rows($mhsList) === 0) { ?>
                            <tr><td colspan="3" style="text-align:center;">Tidak ada data</td></tr>
                        <?php } else { while($m = mysqli_fetch_assoc($mhsList)) { ?>
                            <tr>
                                <td><?= htmlspecialchars($m['username']); ?></td>
                                <td><?= htmlspecialchars($m['nama']); ?></td>
                                <td>
                                    <form method="post" style="margin:0;">
                                        <input type="hidden" name="action" value="add_member">
                                        <input type="hidden" name="member_username" value="<?= htmlspecialchars($m['username']); ?>">
                                        <button type="submit">Tambah</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } } ?>
                    </table>
                </div>
            </div>
        <?php } ?>
    </div>

    <div class="section">
        <h3>Event Group</h3>
        <?php if (!$eventsTableExists) { ?>
            <p>Tabel <code>events</code> belum tersedia. Buat tabel dengan kolom minimal: <code>id</code> (PK, auto increment), <code>group_id</code>, <code>title</code>, <code>schedule_at</code> (DATETIME), <code>detail</code>, <code>created_at</code>.</p>
        <?php } else { ?>
            <?php if (empty($events)) { ?>
                <p>Belum ada event.</p>
            <?php } else { ?>
                <table>
                    <tr><th>Judul</th><th>Jadwal</th><th>Keterangan</th><?php if ($isCreator) { ?><th>Aksi</th><?php } ?></tr>
                    <?php foreach ($events as $ev) { ?>
                        <tr>
                            <td><?= htmlspecialchars($ev['title']); ?></td>
                            <td><?= htmlspecialchars($ev['schedule_at']); ?></td>
                            <td><?= htmlspecialchars($ev['detail']); ?></td>
                            <?php if ($isCreator) { ?>
                                <td>
                                    <a href="group_detail.php?id=<?= $groupId; ?>&edit_event=<?= $ev['id']; ?>">Edit</a> |
                                    <a href="group_detail.php?id=<?= $groupId; ?>&delete_event=<?= $ev['id']; ?>" onclick="return confirm('Hapus event?')">Hapus</a>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </table>
            <?php } ?>

            <?php if ($isCreator) { ?>
                <h4><?= $editEvent ? 'Edit Event' : 'Tambah Event'; ?></h4>
                <form method="post">
                    <input type="hidden" name="action" value="<?= $editEvent ? 'update_event' : 'add_event'; ?>">
                    <?php if ($editEvent) { ?>
                        <input type="hidden" name="event_id" value="<?= $editEvent['id']; ?>">
                    <?php } ?>
                    <div>
                        <label>Judul</label>
                        <input type="text" name="title" value="<?= $editEvent ? htmlspecialchars($editEvent['title']) : ''; ?>" required>
                    </div>
                    <div>
                        <label>Jadwal (YYYY-MM-DD HH:MM:SS)</label>
                        <input type="text" name="schedule" value="<?= $editEvent ? htmlspecialchars($editEvent['schedule_at']) : ''; ?>" placeholder="2025-12-31 10:00:00">
                    </div>
                    <div>
                        <label>Keterangan</label>
                        <textarea name="detail" rows="3"><?= $editEvent ? htmlspecialchars($editEvent['detail']) : ''; ?></textarea>
                    </div>
                    <button type="submit"><?= $editEvent ? 'Simpan Perubahan' : 'Tambah Event'; ?></button>
                </form>
            <?php } ?>
        <?php } ?>
    </div>
</body>
</html>
