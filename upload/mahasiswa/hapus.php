<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../home.php');
    exit;
}

require_once "../../class/Mahasiswa.php";

if (!isset($_GET['nrp'])) {
    die("Error: NRP mahasiswa tidak ditemukan.");
}

$nrp = $_GET['nrp'];
$mahasiswa = new Mahasiswa();

try {
    $mahasiswa->deleteFull($nrp);
    header("Location: index.php?msg=deleted");
    exit;
} catch (Exception $e) {
    die("DATABASE ERROR: " . $e->getMessage());
}
