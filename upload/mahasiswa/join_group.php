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

include "../../proses/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kodeInput = trim($_POST['kode'] ?? '');
    if ($kodeInput === '') {
        header("Location: groups.php?error=Kode wajib diisi");
        exit;
    }

    $kode = strtoupper($kodeInput);

    $username = mysqli_real_escape_string($conn, $_SESSION['username']);
    $kodeEsc  = mysqli_real_escape_string($conn, $kode);

    $sqlGrup = "
        SELECT *
        FROM grup
        WHERE TRIM(UPPER(kode_pendaftaran)) = '$kodeEsc'
        LIMIT 1
    ";
    $qGrup = mysqli_query($conn, $sqlGrup);

    if (!$qGrup || mysqli_num_rows($qGrup) == 0) {
        header("Location: groups.php?error=Kode salah atau grup tidak ditemukan");
        exit;
    }

    $grup = mysqli_fetch_assoc($qGrup);

    $jenis = strtolower(trim($grup['jenis'] ?? ''));

    if ($jenis === 'privat') {
        header("Location: groups.php?error=Grup ini privat. Tidak bisa join dengan kode.");
        exit;
    }

    $group_id = (int)$grup['idgrup'];

    $sqlCek = "
        SELECT 1 
        FROM member_grup 
        WHERE idgrup = $group_id 
          AND username = '$username'
        LIMIT 1
    ";
    $qCek = mysqli_query($conn, $sqlCek);
    $sudah = $qCek && mysqli_num_rows($qCek) > 0;

    if (!$sudah) {
        $sqlInsert = "
        INSERT INTO member_grup (idgrup, username)
        VALUES ($group_id, '$username')
        ";
        mysqli_query($conn, $sqlInsert);
    }


    header("Location: groups.php?joined=1");
    exit;
}

header("Location: groups.php");
exit;
