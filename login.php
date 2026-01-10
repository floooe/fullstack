<?php
session_start();
require_once "class/auth.php";

if (isset($_POST['login'])) {

    $auth = new Auth();

    $result = $auth->login($_POST['username'], $_POST['password']);

    if ($result === false) {
        echo "<script>alert('Username atau password salah.'); window.location='../index.php';</script>";
        exit;
    }

    $_SESSION['username'] = $result['username'];
    $_SESSION['isadmin'] = $result['isadmin'];
    $_SESSION['level'] = $result['level'];

    header("Location: ../home.php");
    exit;

    $result = $auth->login($_POST['username'], $_POST['password']);

    if ($result === false) {
        die("LOGIN GAGAL - data tidak ditemukan");
    }

}
