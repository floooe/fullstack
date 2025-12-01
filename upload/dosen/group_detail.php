<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}
if (!isset($_SESSION['level']) || !in_array($_SESSION['level'], ['admin', 'dosen'])) {
    header("Location: ../../home.php");
    exit;
}

include "../../proses/koneksi.php";

function detect_jenis_enum_values($conn, $table = 'grup')
{
    $res = mysqli_query($conn, "SHOW COLUMNS FROM {$table} LIKE 'jenis'");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        if (!empty($row['Type']) && preg_match("/enum\\((.+)\\)/i", $row['Type'], $matches)) {
            $raw = explode(',', $matches[1]);
            $values = [];
            foreach ($raw as $val) {
                $values[] = trim($val, " '\"");
            }
            if (!empty($values)) {
                return $values;
            }
        }
    }
    return ['public', 'private'];
}

function map_db_jenis_to_ui($value)
{
    $lower = strtolower($value);
    if ($lower === 'publik' || $lower === 'public') {
        return 'public';
    }
    if ($lower === 'privat' || $lower === 'private') {
        return 'private';
    }
    return $lower;
}

function map_ui_jenis_to_db($uiValue, $enumValues)
{
    $target = strtolower($uiValue);
    foreach ($enumValues as $enumVal) {
        $enumLower = strtolower($enumVal);
        if ($target === 'public' && in_array($enumLower, ['public', 'publik'], true)) {
            return $enumVal;
        }
        if ($target === 'private' && in_array($enumLower, ['private', 'privat'], true)) {
            return $enumVal;
        }
    }
    return $uiValue;
}

$jenisEnumValues = detect_jenis_enum_values($conn, 'grup');

function detect_events_table($conn)
{
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'events'")) > 0) {
        return 'events';
    }
    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'event'")) > 0) {
        return 'event';
    }
    return null;
}

$groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($groupId <= 0) {
    header("Location: groups.php");
    exit;
}

$group = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM grup WHERE idgrup=$groupId"));
if (!$group) {
    header("Location: groups.php?msg=Grup tidak ditemukan");
    exit;
}

$groupName = $group['nama'];
$groupCode = $group['kode_pendaftaran'] ?? '';
$groupJenisDb = isset($group['jenis']) ? trim((string) $group['jenis']) : '';
$groupJenis = $groupJenisDb !== '' ? map_db_jenis_to_ui($groupJenisDb) : '';
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
        $eventIdCol = $cols[0];
    }
}
$eventsTableReady = $eventsTableExists && $eventGroupCol && $eventTitleCol;
$eventOrderCol = $eventScheduleCol ?: ($eventCreatedCol ?: 'id');

$memberTable = null;
$memberGroupCol = null;
$memberUserCol = 'username';
$memberJoinedCol = 'joined_at';
$memberTables = ['member_grup', 'group_members'];
foreach ($memberTables as $mt) {
    $res = mysqli_query($conn, "SHOW TABLES LIKE '$mt'");
    if ($res && mysqli_num_rows($res) > 0) {
        $memberTable = $mt;
        break;
    }
}
if ($memberTable) {
    $cols = [];
    $res = mysqli_query($conn, "SHOW COLUMNS FROM {$memberTable}");
    while ($c = mysqli_fetch_assoc($res)) {
        $cols[] = $c['Field'];
    }
    foreach (['group_id', 'idgrup', 'id_grup'] as $cand) {
        if (in_array($cand, $cols, true)) {
            $memberGroupCol = $cand;
            break;
        }
    }
    if (!$memberGroupCol && !empty($cols)) {
        $memberGroupCol = $cols[0]; 
    }
    if (!in_array('username', $cols, true) && in_array('member_username', $cols, true)) {
        $memberUserCol = 'member_username';
    }
    $memberIdCol = 'id';
    foreach (['id', 'id_member', 'member_id', 'idmember'] as $candId) {
        if (in_array($candId, $cols, true)) {
            $memberIdCol = $candId;
            break;
        }
    }
    if ($memberIdCol === 'id') {
        foreach ($cols as $c) {
            if (stripos($c, 'id') === 0) {
                $memberIdCol = $c;
                break;
            }
        }
    }
    if (!in_array('joined_at', $cols, true)) {
        $memberJoinedCol = null;
    }
}

function event_group_exists($conn, $eventGroupCol, $groupId)
{
    if ($eventGroupCol === 'idgrup' || $eventGroupCol === 'id_grup') {
        $res = mysqli_query($conn, "SELECT 1 FROM grup WHERE idgrup=$groupId LIMIT 1");
        return mysqli_num_rows($res) > 0;
    }
    $res = mysqli_query($conn, "SELECT 1 FROM groups WHERE id=$groupId LIMIT 1");
    return mysqli_num_rows($res) > 0;
}

function ensure_grup_row($conn, $eventGroupCol, $groupId, $groupName, $groupDesc, $groupJenis, $groupCode, $groupCreatedBy, $groupCreatedAt)
{
    if (!in_array($eventGroupCol, ['idgrup', 'id_grup'], true)) {
        return true; 
    }

    if (mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'grup'")) === 0) {
        return false;
    }

    $exists = mysqli_query($conn, "SELECT 1 FROM grup WHERE idgrup=$groupId LIMIT 1");
    if (mysqli_num_rows($exists) > 0) {
        return true;
    }

    $cols = [];
    $colRes = mysqli_query($conn, "SHOW COLUMNS FROM grup");
    while ($c = mysqli_fetch_assoc($colRes)) {
        $cols[] = $c['Field'];
    }

    $insertCols = [];
    $insertVals = [];
    $insertCols[] = in_array('idgrup', $cols, true) ? 'idgrup' : $eventGroupCol;
    $insertVals[] = (int) $groupId;

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
        $insertVals[] = "'" . mysqli_real_escape_string($conn, $groupJenis) . "'";
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

if ($isCreator && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_group') {
        $nama = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $jenisInput = strtolower(trim($_POST['jenis'] ?? ''));
        if (!in_array($jenisInput, ['public', 'private'], true)) {
            $errors[] = "Jenis tidak valid.";
        } else {
            $groupJenis = $jenisInput;
            $jenisStored = map_ui_jenis_to_db($jenisInput, $jenisEnumValues);
            $groupJenisDb = $jenisStored;
        }
        if ($nama === '') {
            $errors[] = "Nama grup wajib diisi.";
        } elseif (empty($errors)) {
            $groupName = $nama;
            $groupDesc = $desc;
            $nama_final = mysqli_real_escape_string($conn, $groupName);
            $jenis_final = mysqli_real_escape_string($conn, $jenisStored);
            $desc_final = mysqli_real_escape_string($conn, $groupDesc);
            $updateRes = mysqli_query($conn, "UPDATE grup SET nama='$nama_final', jenis='$jenis_final', deskripsi='$desc_final' WHERE idgrup=$groupId");
            if ($updateRes) {
                header("Location: group_detail.php?id=$groupId&msg=Perubahan disimpan");
                exit;
            } else {
                $errors[] = "Gagal menyimpan perubahan: " . mysqli_error($conn);
            }
        }
    }

    if ($action === 'add_member' && $memberTable && $memberGroupCol) {
        $memberUsername = trim($_POST['member_username'] ?? '');
        if ($memberUsername !== '') {
            $memberUsernameEsc = mysqli_real_escape_string($conn, $memberUsername);
            $already = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM {$memberTable} WHERE {$memberGroupCol}=$groupId AND {$memberUserCol}='$memberUsernameEsc'"));
            if ($already == 0) {
                if ($memberJoinedCol) {
                    mysqli_query($conn, "INSERT INTO {$memberTable}({$memberGroupCol}, {$memberUserCol}, {$memberJoinedCol}) VALUES ($groupId, '$memberUsernameEsc', NOW())");
                } else {
                    $checkAkun = mysqli_query($conn, "SELECT username FROM akun WHERE username='$memberUsernameEsc' LIMIT 1");

                    if (mysqli_num_rows($checkAkun) == 0) {
                        mysqli_query($conn, "INSERT INTO akun(username, password, isadmin) VALUES('$memberUsernameEsc', MD5('$memberUsernameEsc'), 0)");
                    }

                    mysqli_query($conn, "INSERT INTO {$memberTable}({$memberGroupCol}, {$memberUserCol}) VALUES ($groupId, '$memberUsernameEsc')");

                }
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
                if (!ensure_grup_row($conn, $eventGroupCol, $groupId, $groupName, $groupDesc, $groupJenisDb, $groupCode, $group['created_by'], $group['created_at'])) {
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
        $eventId = (int) ($_POST['event_id'] ?? 0);
        $title = mysqli_real_escape_string($conn, trim($_POST['title'] ?? ''));
        $schedule = mysqli_real_escape_string($conn, trim($_POST['schedule'] ?? ''));
        $detail = mysqli_real_escape_string($conn, trim($_POST['detail'] ?? ''));
        if ($title === '') {
            $errors[] = "Judul event wajib diisi.";
        } else {
            if (!event_group_exists($conn, $eventGroupCol, $groupId)) {
                if (!ensure_grup_row($conn, $eventGroupCol, $groupId, $groupName, $groupDesc, $groupJenisDb, $groupCode, $group['created_by'], $group['created_at'])) {
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
    $mid = isset($_GET['remove_member']) ? (int) $_GET['remove_member'] : 0;
    $muser = isset($_GET['member_username']) ? mysqli_real_escape_string($conn, $_GET['member_username']) : null;
    if ($memberTable) {
        $memberIdCol = 'id';
        $resId = mysqli_query($conn, "SHOW COLUMNS FROM {$memberTable}");
        if ($resId) {
            while ($c = mysqli_fetch_assoc($resId)) {
                if (in_array($c['Field'], ['id', 'id_member', 'member_id', 'idmember'], true)) {
                    $memberIdCol = $c['Field'];
                    break;
                }
                if ($memberIdCol === 'id' && stripos($c['Field'], 'id') === 0) {
                    $memberIdCol = $c['Field'];
                }
            }
        }
        $whereGroup = $memberGroupCol ? " {$memberGroupCol}=$groupId" : "1=1";
        if ($muser !== null) {
            mysqli_query($conn, "DELETE FROM {$memberTable} WHERE {$whereGroup} AND {$memberUserCol}='{$muser}'");
        } elseif ($mid > 0) {
            mysqli_query($conn, "DELETE FROM {$memberTable} WHERE {$whereGroup} AND {$memberIdCol}=$mid");
        }
    }
    header("Location: group_detail.php?id=$groupId&msg=Member dihapus");
    exit;
}

if ($isCreator && $eventsTableReady && isset($_GET['delete_event'])) {
    $eid = (int) $_GET['delete_event'];
    $idCol = $eventIdCol ?: 'id';
    mysqli_query($conn, "DELETE FROM {$eventsTable} WHERE {$idCol}=$eid AND {$eventGroupCol}=$groupId");
    header("Location: group_detail.php?id=$groupId&msg=Event dihapus");
    exit;
}

$members = null;
if ($memberTable && $memberGroupCol) {
    $userColExpr = "mg.`{$memberUserCol}`";
    $idColExpr = isset($memberIdCol) ? "mg.`{$memberIdCol}` AS member_id" : "mg.id AS member_id";
    $members = mysqli_query($conn, "
        SELECT {$idColExpr},
               {$userColExpr} AS username,
               COALESCE(d.nama, m.nama) AS nama,
               CASE 
                    WHEN d.npk IS NOT NULL THEN 'Dosen'
                    WHEN m.nrp IS NOT NULL THEN 'Mahasiswa'
                    ELSE 'User'
               END AS tipe
        FROM {$memberTable} mg
        LEFT JOIN dosen d ON d.npk = {$userColExpr}
        LEFT JOIN mahasiswa m ON m.nrp = {$userColExpr}
        WHERE mg.{$memberGroupCol}=$groupId
        ORDER BY tipe, nama
    ");
}


$memberUsernames = [];
$memberList = [];
if ($members) {
    while ($row = mysqli_fetch_assoc($members)) {
        $memberList[] = $row;
        $memberUsernames[] = "'" . mysqli_real_escape_string($conn, $row['username']) . "'";
    }
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
        if ($eventIdCol && isset($_GET['edit_event']) && (int) $_GET['edit_event'] === (int) $ev[$eventIdCol]) {
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
            <button type="button" class="btn btn-small" onclick="location.href='groups.php'">Kembali ke Group
                Saya</button>
        </div>

        <div class="card section">
            <h3><?= htmlspecialchars($groupName); ?> <span
                    class="badge"><?= htmlspecialchars($groupJenis !== '' ? ucfirst($groupJenis) : '-'); ?></span></h3>
            <p><b>Kode Pendaftaran:</b> <span class="pill"><?= htmlspecialchars($groupCode); ?></span></p>
            <p class="muted"><b>Dibuat oleh:</b> <?= htmlspecialchars($createdBy); ?> | <b>Tanggal:</b>
                <?= htmlspecialchars($createdAt); ?></p>
            <p><b>Deskripsi:</b> <?= htmlspecialchars($groupDesc); ?></p>

            <?php if ($isCreator) { ?>
                <div class="section mt-12">
                    <h4>Ubah Informasi Grup</h4>
                    <form method="post" class="section">
                        <input type="hidden" name="action" value="update_group">
                        <?php if (!empty($errors)) { ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $e) {
                                    echo "<p>" . htmlspecialchars($e) . "</p>";
                                } ?>
                            </div>
                        <?php } ?>
                        <div class="field">
                            <label>Nama</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($groupName); ?>" required>
                        </div>
                        <div class="field">
                            <label>Jenis</label>
                            <select name="jenis" required>
                                <option value="" disabled <?= $groupJenis === '' ? 'selected' : ''; ?>>-- Pilih jenis --
                                </option>
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
                        <tr>
                            <th>Username</th>
                            <th>Nama</th>
                            <th>Tipe</th><?php if ($isCreator) { ?>
                                <th>Aksi</th><?php } ?>
                        </tr>
                        <?php foreach ($memberList as $m) { ?>
                            <tr>
                                <td><?= htmlspecialchars($m['username']); ?></td>
                                <td><?= htmlspecialchars($m['nama'] ?? '-'); ?></td>
                                <td><?= htmlspecialchars($m['tipe']); ?></td>
                                <?php if ($isCreator) { ?>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-small"
                                            onclick="if(confirm('Hapus member ini?')) location.href='group_detail.php?id=<?= $groupId; ?>&remove_member=<?= isset($m['member_id']) ? (int) $m['member_id'] : 0; ?>&member_username=<?= urlencode($m['username']); ?>'">Hapus</button>
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
                                <input type="text" name="sd" value="<?= htmlspecialchars($searchDosen); ?>"
                                    placeholder="Cari nama/npk">
                                <button type="submit" class="btn btn-secondary btn-small">Cari</button>
                            </form>
                            <div class="table-wrapper card-compact">
                                <table class="table-compact">
                                    <tr>
                                        <th>NPK</th>
                                        <th>Nama</th>
                                        <th></th>
                                    </tr>
                                    <?php if (mysqli_num_rows($dosenList) === 0) { ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Tidak ada data</td>
                                        </tr>
                                    <?php } else {
                                        while ($d = mysqli_fetch_assoc($dosenList)) { ?>
                                            <tr>
                                                <td><?= htmlspecialchars($d['username']); ?></td>
                                                <td><?= htmlspecialchars($d['nama']); ?></td>
                                                <td>
                                                    <form method="post" class="no-margin">
                                                        <input type="hidden" name="action" value="add_member">
                                                        <input type="hidden" name="member_username"
                                                            value="<?= htmlspecialchars($d['username']); ?>">
                                                        <button type="submit" class="btn btn-small">Tambah</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php }
                                    } ?>
                                </table>
                            </div>
                        </div>

                        <div class="card card-compact flex-tile">
                            <b>Mahasiswa</b>
                            <form method="get" class="toolbar">
                                <input type="hidden" name="id" value="<?= $groupId; ?>">
                                <input type="text" name="sm" value="<?= htmlspecialchars($searchMhs); ?>"
                                    placeholder="Cari nama/nrp">
                                <button type="submit" class="btn btn-secondary btn-small">Cari</button>
                            </form>
                            <div class="table-wrapper card-compact">
                                <table class="table-compact">
                                    <tr>
                                        <th>NRP</th>
                                        <th>Nama</th>
                                        <th></th>
                                    </tr>
                                    <?php if (mysqli_num_rows($mhsList) === 0) { ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Tidak ada data</td>
                                        </tr>
                                    <?php } else {
                                        while ($m = mysqli_fetch_assoc($mhsList)) { ?>
                                            <tr>
                                                <td><?= htmlspecialchars($m['username']); ?></td>
                                                <td><?= htmlspecialchars($m['nama']); ?></td>
                                                <td>
                                                    <form method="post" class="no-margin">
                                                        <input type="hidden" name="action" value="add_member">
                                                        <input type="hidden" name="member_username"
                                                            value="<?= htmlspecialchars($m['username']); ?>">
                                                        <button type="submit" class="btn btn-small">Tambah</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php }
                                    } ?>
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
                <p>Tabel <code>events</code>/<code>event</code> belum tersedia. Buat tabel dengan kolom minimal:
                    <code>id</code> (PK, auto increment), kolom relasi grup, kolom judul, kolom jadwal (DATETIME), kolom
                    detail, kolom created_at.
                </p>
            <?php } elseif (!$eventsTableReady) { ?>
                <p>Tabel event ditemukan tetapi kolom wajib belum dikenali. Pastikan ada: kolom relasi grup
                    (group_id/id_grup/dll) dan kolom judul (title/judul/nama).</p>
            <?php } else { ?>
                <?php if (empty($events)) { ?>
                    <p class="muted">Belum ada event.</p>
                <?php } else { ?>
                    <div class="table-wrapper card-compact">
                        <table class="table-compact">
                            <tr>
                                <th>Judul</th>
                                <th>Jadwal</th>
                                <th>Keterangan</th><?php if ($isCreator) { ?>
                                    <th>Aksi</th><?php } ?>
                            </tr>
                            <?php foreach ($events as $ev) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($ev[$eventTitleCol]); ?></td>
                                    <td><?= htmlspecialchars($eventScheduleCol && isset($ev[$eventScheduleCol]) ? $ev[$eventScheduleCol] : ($eventCreatedCol && isset($ev[$eventCreatedCol]) ? $ev[$eventCreatedCol] : '')); ?>
                                    </td>
                                    <td><?= htmlspecialchars($eventDetailCol && isset($ev[$eventDetailCol]) ? $ev[$eventDetailCol] : ''); ?>
                                    </td>
                                    <?php if ($isCreator) { ?>
                                        <td>
                                            <div class="toolbar">
                                                <button type="button" class="btn btn-small"
                                                    onclick="location.href='group_detail.php?id=<?= $groupId; ?>&edit_event=<?= $eventIdCol ? $ev[$eventIdCol] : ''; ?>'">Edit</button>
                                                <button type="button" class="btn btn-danger btn-small"
                                                    onclick="if(confirm('Hapus event?')) location.href='group_detail.php?id=<?= $groupId; ?>&delete_event=<?= $eventIdCol ? $ev[$eventIdCol] : ''; ?>'">Hapus</button>
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
                                <input type="text" name="title"
                                    value="<?= $editEvent && isset($editEvent[$eventTitleCol]) ? htmlspecialchars($editEvent[$eventTitleCol]) : ''; ?>"
                                    required>
                            </div>
                            <div class="field">
                                <label>Jadwal</label>
                                <input type="datetime-local" name="schedule" value="<?= $scheduleValue; ?>"
                                    placeholder="Pilih tanggal & waktu">
                            </div>
                            <div class="field">
                                <label>Keterangan</label>
                                <textarea name="detail"
                                    rows="3"><?= $editEvent && $eventDetailCol && isset($editEvent[$eventDetailCol]) ? htmlspecialchars($editEvent[$eventDetailCol]) : ''; ?></textarea>
                            </div>
                            <button type="submit"
                                class="btn btn-small"><?= $editEvent ? 'Simpan Perubahan' : 'Tambah Event'; ?></button>
                        </form>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
</body>

</html>