<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'mahasiswa') {
    header("Location: ../../home.php");
    exit;
}

require_once "../../class/Group.php";

$groupObj = new Grup();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: groups.php");
    exit;
}

$kodeInput = trim($_POST['kode'] ?? '');
if ($kodeInput === '') {
    header("Location: groups.php?error=Kode wajib diisi");
    exit;
}

$username = $_SESSION['username'];

$group = $groupObj->getByKode($kodeInput);

if (!$group) {
    header("Location: groups.php?error=Kode salah atau grup tidak ditemukan");
    exit;
}

$jenis = strtolower(trim($group['jenis'] ?? ''));

if ($jenis === 'privat') {
    header("Location: groups.php?error=Grup ini privat. Tidak bisa join dengan kode.");
    exit;
}

$groupId = (int)$group['idgrup'];

if (!$groupObj->isMember($groupId, $username)) {
    $groupObj->joinGroup($groupId, $username);
}

header("Location: groups.php?joined=1");
exit;