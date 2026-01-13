<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}

$idthread = isset($_GET['idthread']) ? (int) $_GET['idthread'] : 0;
if ($idthread <= 0) {
    die("Thread tidak valid");
}
require_once "../../class/Thread.php";

$threadObj = new Thread();
$thread = $threadObj->getById($idthread);

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
        .chat-bubble {
            display: inline-block;
            max-width: 75%;
            margin-bottom: 12px;
            padding: 10px 14px;
            border-radius: 14px;
            background: #e5e7eb;
            color: #111827;
            line-height: 1.4;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            position: relative;
        }
        .chat-bubble.chat-me {
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            color: #f8fafc;
            align-self: flex-end;
        }
        .chat-bubble.chat-other {
            background: linear-gradient(135deg, #e2e8f0, #cbd5f5);
            color: #0f172a;
            border: 1px solid rgba(15, 23, 42, 0.1);
        }
        .chat-bubble .chat-name {
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 4px;
        }
        .chat-bubble .chat-time {
            font-size: 0.75rem;
            color: inherit;
            opacity: 0.7;
            margin-top: 4px;
            text-align: right;
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
        const chatText = document.getElementById('chatText');

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
