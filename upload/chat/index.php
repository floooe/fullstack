<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}

require_once "../../class/Grup.php";
require_once "../../class/Thread.php";

$groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($groupId <= 0) {
    die("Grup tidak valid.");
}

$username = $_SESSION['username'];
$groupObj = new Grup();
$threadObj = new Thread();

$group = $groupObj->getById($groupId);
if (!$group) {
    die("Grup tidak ditemukan.");
}

$isOwner = ($group['username_pembuat'] === $username);
$isMember = $isOwner || $groupObj->isMember($groupId, $username);
if (!$isMember) {
    die("Anda bukan member grup ini.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_thread'])) {
    $threadObj->create($groupId, $username);
    header("Location: index.php?id=$groupId");
    exit;
}

if (isset($_GET['close'])) {
    $closeId = (int) $_GET['close'];
    $threadObj->closeThread($closeId, $username);
    header("Location: index.php?id=$groupId");
    exit;
}

$threads = $threadObj->getByGroup($groupId);
$groupName = $group['nama'] ?? 'Grup';
?>

<!DOCTYPE html>
<html>

<head>
    <title>Thread Grup <?= htmlspecialchars($groupName) ?></title>
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/group.css">
</head>

<body class="group-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Thread Grup <?= htmlspecialchars($groupName) ?></h2>
                <p class="page-subtitle">
                    Semua member grup bisa melihat thread dan terus chat di thread yang masih <b>Open</b>.
                </p>
            </div>
            <button class="btn btn-small" onclick="history.back()">Kembali</button>
        </div>

        <div class="card section">
            <form method="post">
                <button type="submit" name="buat_thread" class="btn btn-primary btn-small">+ Buat Thread</button>
                <p class="muted" style="margin-top: 8px;">
                    Thread akan otomatis terbuka (status Open) dan hanya pembuatnya yang bisa mengubah status ke Close.
                </p>
            </form>
        </div>

        <div class="card section">
            <h3>Daftar Thread</h3>
            <table class="table-compact">
                <tr>
                    <th>Pembuat</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
                <?php if ($threads->num_rows === 0): ?>
                    <tr>
                        <td colspan="4" class="muted">Belum ada thread.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($t = $threads->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['nama_pembuat'] ?? $t['username_pembuat']) ?></td>
                            <td><?= htmlspecialchars($t['status']) ?></td>
                            <td><?= htmlspecialchars($t['tanggal_pembuatan']) ?></td>
                            <td>
                                <?php if ($t['status'] === 'Open'): ?>
                                    <a href="thread_chat.php?idthread=<?= $t['idthread'] ?>" class="btn btn-small">Buka Chat</a>
                                <?php else: ?>
                                    <span class="muted">Thread tertutup</span>
                                <?php endif; ?>

                                <?php if ($t['username_pembuat'] === $username && $t['status'] === 'Open'): ?>
                                    <a href="?id=<?= $groupId ?>&close=<?= $t['idthread'] ?>" onclick="return confirm('Tutup thread ini?')"
                                        class="btn btn-danger btn-small">Close</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>

    </div>
</body>

</html>
