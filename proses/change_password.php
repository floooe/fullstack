<?php
session_start();
include "koneksi.php";

if (isset($_POST['ubah'])) {
    $baru = $_POST['baru'];
    $ulang = $_POST['ulang'];
    $user = $_SESSION['username'];

    if ($baru == $ulang) {
        mysqli_query($koneksi, "UPDATE akun SET password='$baru' WHERE username='$user'");
        echo "<script>alert('Password berhasil diubah!');window.location='../home.php';</script>";
    } else {
        echo "<script>alert('Konfirmasi password tidak cocok!');window.location='../change_password.php';</script>";
    }
}
?>
