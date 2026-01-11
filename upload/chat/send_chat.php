<?php
session_start();
if (!isset($_SESSION['username'])) exit;

require_once "../../class/Chat.php";

$idthread = (int)($_POST['idthread'] ?? 0);
$isi      = trim($_POST['isi'] ?? '');
$username = $_SESSION['username'];

if ($isi === '') exit;

$chat = new Chat();
$chat->send($idthread, $username, $isi);
