<?php
include "koneksi.php";
session_start();

if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    function is_dosen_user($conn, $u) {
        $uEsc = mysqli_real_escape_string($conn, $u);
        $fields = [];
        $colRes = mysqli_query($conn, "SHOW COLUMNS FROM dosen");
        while ($c = mysqli_fetch_assoc($colRes)) {
            $fields[] = $c['Field'];
        }
        $conds = [];
        if (in_array('npk', $fields, true)) $conds[] = "npk='$uEsc'";
        if (in_array('username', $fields, true)) $conds[] = "username='$uEsc'";
        if (in_array('akun_username', $fields, true)) $conds[] = "akun_username='$uEsc'";
        if (!$conds) return false;
        $where = implode(' OR ', $conds);
        $q = mysqli_query($conn, "SELECT 1 FROM dosen WHERE $where LIMIT 1");
        return mysqli_num_rows($q) > 0;
    }

    function is_mahasiswa_user($conn, $u) {
        $uEsc = mysqli_real_escape_string($conn, $u);
        $fields = [];
        $colRes = mysqli_query($conn, "SHOW COLUMNS FROM mahasiswa");
        while ($c = mysqli_fetch_assoc($colRes)) {
            $fields[] = $c['Field'];
        }
        $conds = [];
        if (in_array('nrp', $fields, true)) $conds[] = "nrp='$uEsc'";
        if (in_array('username', $fields, true)) $conds[] = "username='$uEsc'";
        if (in_array('akun_username', $fields, true)) $conds[] = "akun_username='$uEsc'";
        if (!$conds) return false;
        $where = implode(' OR ', $conds);
        $q = mysqli_query($conn, "SELECT 1 FROM mahasiswa WHERE $where LIMIT 1");
        return mysqli_num_rows($q) > 0;
    }

    $sql = "SELECT * FROM akun WHERE username='$user' AND password=MD5('$pass')";
    $query = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($query);

    if ($data) {
        $_SESSION['username'] = $data['username'];
        if ($data['isadmin'] == 1) {
            $_SESSION['level'] = 'admin';
        } else {
            if (is_dosen_user($conn, $data['username'])) {
                $_SESSION['level'] = 'dosen';
            } elseif (is_mahasiswa_user($conn, $data['username'])) {
                $_SESSION['level'] = 'mahasiswa';
            } else {
                $_SESSION['level'] = 'mahasiswa';
            }
        }
        header("Location: /fullstack/fullstack/home.php");
        exit;
    } else {
        echo "<script>alert('Username atau password salah!');window.location='../login.php';</script>";
    }
}
?>
