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
    $kode = strtoupper(trim($_POST['kode'] ?? ''));
    $username = mysqli_real_escape_string($conn, $_SESSION['username']);

    if ($kode === '') {
        die("Kode wajib diisi");
    }

    $kodeEsc = mysqli_real_escape_string($conn, $kode);
    $q = mysqli_query($conn,
        "SELECT * FROM groups WHERE name LIKE '%| $kodeEsc'"
    );

    if (mysqli_num_rows($q) == 0) {
        die("Kode salah atau grup tidak ditemukan");
    }

    $row = mysqli_fetch_assoc($q);
    // cek jenis public
    $jenis = (strpos($row['description'], '[public]') === 0) ? 'public' : 'private';
    if ($jenis !== 'public') {
        die("Grup ini private. Tidak bisa join dengan kode.");
    }

    $group_id = $row['id'];

    // hindari double join
    $exists = mysqli_num_rows(mysqli_query($conn,
        "SELECT 1 FROM group_members WHERE group_id='$group_id' AND username='$username'"
    ));
    if ($exists == 0) {
        mysqli_query($conn,
            "INSERT INTO group_members(group_id, username, joined_at)
             VALUES ('$group_id', '$username', NOW())"
        );
    }

    header("Location: groups.php?joined=1");
    exit;
}
?>
