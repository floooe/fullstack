<?php
include "koneksi.php";
session_start();

if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Cocokkan password dengan hash MD5
    $sql = "SELECT * FROM akun WHERE username='$user' AND password=MD5('$pass')";
    $query = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($query);

    if ($data) {
        $_SESSION['username'] = $data['username'];
        $_SESSION['level'] = ($data['isadmin'] == 1) ? 'admin' : 'user';
        header("Location: ../home.php");
        exit;
    } else {
        echo "<script>alert('Username atau password salah!');window.location='../login.php';</script>";
    }
}
?>
