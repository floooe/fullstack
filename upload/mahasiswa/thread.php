<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['level'] !== 'mahasiswa') {
    header("Location: ../../home.php");
    exit;
}

require_once "../../class/Thread.php";
$threadObj = new Thread();

$idgrup = (int) ($_GET['id'] ?? 0);
if ($idgrup <= 0) {
    header("Location: groups.php");
    exit;
}

$username = $_SESSION['username'];

//buat thread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat'])) {
    $threadObj->create($idgrup, $username);
    header("Location: thread.php?id=$idgrup");
    exit;
}

//close thread
if (isset($_GET['close'])) {
    $idthread = (int) $_GET['close'];
    $threadObj->closeThread($idthread, $username);
    header("Location: thread.php?id=$idgrup");
    exit;
}

$threads = $threadObj->getByGroup($idgrup);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Thread Grup</title>
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
</head>
<body>
    <h2>Thread Grup</h2>
    <form method="post">
        <button type="submit" name="buat" class="btn btn-small">+ Buat Thread</button>
    </form>
    <table class="table-compact">
        <tr>
            <th>Pembuat</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
        <?php while ($t = $threads->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($t['username_pembuat']) ?></td>
                <td><?= htmlspecialchars($t['status']) ?></td>
                <td>
                    <?php if ($t['status'] === 'Open'): ?>
                        <a href="chat.php?idthread=<?= $t['idthread'] ?>" class="btn btn-small">Chat</a>
                    <?php endif; ?>

                    <?php if ($t['username_pembuat'] === $username && $t['status'] === 'Open'): ?>
                        <a href="?id=<?= $idgrup ?>&close=<?= $t['idthread'] ?>" onclick="return confirm('Tutup thread?')"
                            class="btn btn-danger btn-small">Close</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>