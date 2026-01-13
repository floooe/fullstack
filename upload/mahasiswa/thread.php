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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thread Grup</title>
    <link rel="stylesheet" href="/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/asset/group.css">
    <style>
        body.mahasiswa-page .page {
            max-width: 960px;
            margin: 32px auto;
            padding: 0 16px 32px;
        }
        .thread-card {
            margin-bottom: 20px;
        }
        .thread-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .thread-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }
        .thread-table th,
        .thread-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            text-align: left;
        }
        .thread-table tr:last-child td {
            border-bottom: none;
        }
        @media (max-width: 768px) {
            .thread-table th {
                display: none;
            }
            .thread-table td {
                display: block;
                width: 100%;
            }
            .thread-table td::before {
                content: attr(data-label);
                font-weight: 700;
                display: block;
                margin-bottom: 4px;
            }
            .thread-table td:last-child {
                margin-top: 8px;
            }
        }
    </style>
</head>
<body class="mahasiswa-page group-page">
    <div class="page">
        <div class="page-header">
            <div>
                <h2 class="page-title">Thread Grup</h2>
                <p class="page-subtitle">Pilih thread open untuk mulai chat real-time.</p>
            </div>
            <a class="btn btn-primary btn-small" href="../chat/index.php?id=<?= $idgrup ?>">Buka Thread</a>
        </div>

        <div class="card thread-card section">
            <form method="post" class="toolbar">
                <button type="submit" name="buat" class="btn btn-small">+ Buat Thread</button>
            </form>
            <div class="table-responsive">
                <table class="thread-table">
                    <tr>
                        <th>Pembuat</th>
                        <th>Status</th>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                    <?php while ($t = $threads->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Pembuat"><?= htmlspecialchars($t['nama_pembuat'] ?? $t['username_pembuat']) ?></td>
                            <td data-label="Status"><?= htmlspecialchars($t['status']) ?></td>
                            <td data-label="Tanggal Dibuat"><?= htmlspecialchars($t['tanggal_pembuatan']) ?></td>
                            <td data-label="Aksi">
                                <div class="thread-actions">
                                    <?php if ($t['status'] === 'Open'): ?>
                                        <a href="../chat/thread_chat.php?idthread=<?= $t['idthread'] ?>" class="btn btn-small">Chat</a>
                                    <?php else: ?>
                                        <span class="muted">Thread tertutup</span>
                                    <?php endif; ?>

                                    <?php if ($t['username_pembuat'] === $username && $t['status'] === 'Open'): ?>
                                        <a href="?id=<?= $idgrup ?>&close=<?= $t['idthread'] ?>" onclick="return confirm('Tutup thread?')"
                                            class="btn btn-danger btn-small">Close</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
