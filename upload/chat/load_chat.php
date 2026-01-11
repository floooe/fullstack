<?php
session_start();

if (!isset($_SESSION['username'])) {
    exit;
}

require_once "../../class/Chat.php";

$chat = new Chat();

$idthread = isset($_GET['idthread']) ? (int) $_GET['idthread'] : 0;
$lastId = isset($_GET['last_id']) ? (int) $_GET['last_id'] : 0;

if ($idthread <= 0) {
    exit;
}

$usernameLogin = $_SESSION['username'];

$result = $chat->getByThread($idthread, $lastId);

while ($row = $result->fetch_assoc()) {

    $isMe = ($row['username_pembuat'] === $usernameLogin);
    $class = $isMe ? 'chat-me' : 'chat-other';

    $nama = htmlspecialchars($row['nama']);
    $isi = nl2br(htmlspecialchars($row['isi']));
    $time = date('H:i', strtotime($row['tanggal_pembuatan']));
    $id = (int) $row['idchat'];
    ?>

    <div class="chat-bubble <?= $class ?>" data-id="<?= $id ?>">
        <div class="chat-name"><?= $nama ?></div>
        <div class="chat-text"><?= $isi ?></div>
        <div class="chat-time"><?= $time ?></div>
    </div>

<?php } ?>