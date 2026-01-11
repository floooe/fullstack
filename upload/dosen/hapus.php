<?php
session_start();
if (
    !isset($_SESSION['username']) ||
    !isset($_SESSION['level']) ||
    $_SESSION['level'] !== 'admin'
) {
    header('Location: ../../home.php');
    exit;
}

require_once "../../class/Dosen.php";
$dosen = new Dosen();

if (!isset($_GET['npk'])) {
    die("Error: NPK dosen tidak ditemukan.");
}

$npk = $_GET['npk'];

try {
    $fotoExt = $dosen->deleteByNpk($npk);
    // hapus file foto jika ada
    if (!empty($fotoExt)) {
        $fileFoto = "../../uploads/dosen/" . $npk . '.' . $fotoExt;
        if (file_exists($fileFoto)) {
            unlink($fileFoto);
        }
    }
    header("Location: index.php?msg=deleted");
    exit;

} catch (Exception $e) {
    die("DATABASE ERROR: " . $e->getMessage());
}
