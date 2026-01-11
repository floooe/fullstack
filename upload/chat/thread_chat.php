<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}

require_once "../../class/Database.php";

$db = new Database();
$conn = $db->getConn();

$idthread = isset($_GET['idthread']) ? (int) $_GET['idthread'] : 0;
if ($idthread <= 0) {
    die("Thread tidak valid");
}

$stmt = $conn->prepare("
    SELECT t.idthread, t.status, g.nama AS nama_grup
    FROM thread t
    JOIN grup g ON g.idgrup = t.idgrup
    WHERE t.idthread = ?
    LIMIT 1
");
$stmt->bind_param("i", $idthread);
$stmt->execute();
$thread = $stmt->get_result()->fetch_assoc();

if (!$thread) {
    die("Thread tidak ditemukan");
}

$statusThread = $thread['status']; // Open / Close
$namaGrup = $thread['nama_grup'];
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html>

<head>
    <title>Chat Thread</title>
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
    <link rel="stylesheet" href="/fullstack/fullstack/asset/group.css">
    <style>
        .chat-box {
            height: 420px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            background: #fafafa;
        }
    </style>
</head>

<body class="group-page">
    <div class="page">

        <div class="page-header">
            <div>
                <h2 class="page-title">Chat Thread</h2>
                <p class="page-subtitle">
                    Grup: <b><?= htmlspecialchars($namaGrup) ?></b> |
                    Status: <b><?= htmlspecialchars($statusThread) ?></b>
                </p>
            </div>
            <button class="btn btn-small" onclick="history.back()">Kembali</button>
        </div>

        <div class="card section">
            <div id="chatBox" class="chat-box"></div>
        </div>

        <?php if ($statusThread === 'Open'): ?>
            <div class="card section">
                <form id="chatForm">
                    <div class="toolbar">
                        <input type="text" id="chatText" class="w-full" placeholder="Ketik pesan..." autocomplete="off">
                        <button class="btn btn-small">Kirim</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                Thread sudah <b>Close</b>. Chat tidak bisa dikirim.
            </div>
        <?php endif; ?>

    </div>

    <script>
        let lastId = 0;
        const chatBox = document.getElementById('chatBox');

        function loadChat() {
            fetch(`load_chat.php?idthread=<?= $idthread ?>&last_id=${lastId}`)
                .then(res => res.text())
                .then(html => {
                    if (html.trim() !== '') {
                        chatBox.insertAdjacentHTML('beforeend', html);
                        const bubbles = chatBox.querySelectorAll('.chat-bubble');
                        bubbles.forEach(b => {
                            const id = parseInt(b.dataset.id);
                            if (id > lastId) lastId = id;
                        });
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                });
        }
        
        <?php if ($statusThread === 'Open'): ?>
            document.getElementById('chatForm').addEventListener('submit', function (e) {
                e.preventDefault();
                const text = chatText.value.trim();
                if (text === '') return;

                fetch('send_chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `idthread=<?= $idthread ?>&isi=${encodeURIComponent(text)}`
                }).then(() => {
                    chatText.value = '';
                    loadChat();
                });
            });
        <?php endif; ?>

        loadChat();
        setInterval(loadChat, 2000);
    </script>

</body>

</html>