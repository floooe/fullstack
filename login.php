<?php
session_start();
include "koneksi.php";

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);

    $query = mysqli_query($conn, "SELECT * FROM akun WHERE username='$username' AND password='$password'");
    
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);

        $_SESSION['username'] = $data['username'];
        $_SESSION['isadmin'] = $data['isadmin'];

        if ($data['isadmin'] == 1) {
            $_SESSION['level'] = 'admin';
        
        } else {
            $cekDosen = mysqli_query($conn, 
                "SELECT npk FROM dosen WHERE akun_username='$username' OR npk='$username'"
            );
            
            if (mysqli_num_rows($cekDosen) > 0) {
                $_SESSION['level'] = 'dosen';
            } else {
                $_SESSION['level'] = 'mahasiswa';
            }
        }

        header("Location: ../home.php");
        exit;

    } else {
        echo "<script>alert('Username atau password salah.'); window.location='../index.php';</script>";
    }
}
?>
