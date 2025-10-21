<?php
session_start();
include "koneksi.php";

if (isset($_POST['ubah'])) {
    $baru = $_POST['baru'] ?? '';
    $ulang = $_POST['ulang'] ?? '';
    $user = $_SESSION['username'] ?? '';

    if ($user === '') {
        header("Location: ../Project%20Full-Stack/Project%20Full-Stack/index.php");
        exit;
    }

    if ($baru === $ulang) {
        $baruEsc = mysqli_real_escape_string($conn, $baru);
        $userEsc = mysqli_real_escape_string($conn, $user);
        $sql = "UPDATE akun SET password=MD5('$baruEsc') WHERE username='$userEsc'";
        mysqli_query($conn, $sql);
        header("Location: ../Project%20Full-Stack/Project%20Full-Stack/home.php");
        exit;
    } else {
        header("Location: ../Project%20Full-Stack/Project%20Full-Stack/change_password.php");
        exit;
    }
}
?>
    
