<?php
session_start();
include "koneksi.php";
require_once __DIR__ . '/url.php';

if (isset($_POST['ubah'])) {
    $baru = $_POST['baru'] ?? '';
    $ulang = $_POST['ulang'] ?? '';
    $user = $_SESSION['username'] ?? '';

    if ($user === '') {
        redirect_rel('index.php');
    }

    if ($baru === $ulang) {
        $baruEsc = mysqli_real_escape_string($conn, $baru);
        $userEsc = mysqli_real_escape_string($conn, $user);
        $sql = "UPDATE akun SET password=MD5('$baruEsc') WHERE username='$userEsc'";
        mysqli_query($conn, $sql);
        redirect_rel('home.php');
    } else {
        redirect_rel('change_password.php');
    }
}
?>
    
