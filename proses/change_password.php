<?php
session_start();
require_once "../class/Auth.php";

if (isset($_POST['ubah'])) {

    $baru  = $_POST['baru'] ?? '';
    $ulang = $_POST['ulang'] ?? '';
    $user  = $_SESSION['username'] ?? '';

    if ($user === '') {
        header('Location: ../index.php');
        exit;
    }

    if ($baru === $ulang) {
        $auth = new Auth();
        $auth->changePassword($user, $baru);

        header('Location: ../home.php');
        exit;
    } else {
        header('Location: ../change_password.php');
        exit;
    }
}
