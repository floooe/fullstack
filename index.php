<?php
session_start();
include __DIR__ . '/proses/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    function is_dosen_user($conn, $u) {
        $uEsc = mysqli_real_escape_string($conn, $u);
        $cols = [];
        $res = mysqli_query($conn, "SHOW COLUMNS FROM dosen");
        while ($c = mysqli_fetch_assoc($res)) {
            $cols[] = $c['Field'];
        }

        $conds = [];
        if (in_array('npk', $cols, true)) $conds[] = "npk='$uEsc'";
        if (in_array('username', $cols, true)) $conds[] = "username='$uEsc'";
        if (in_array('akun_username', $cols, true)) $conds[] = "akun_username='$uEsc'";
        if (!$conds) return false;

        $q = mysqli_query($conn, "SELECT 1 FROM dosen WHERE " . implode(' OR ', $conds) . " LIMIT 1");
        return mysqli_num_rows($q) > 0;
    }

    function is_mahasiswa_user($conn, $u) {
        $uEsc = mysqli_real_escape_string($conn, $u);
        $cols = [];
        $res = mysqli_query($conn, "SHOW COLUMNS FROM mahasiswa");
        while ($c = mysqli_fetch_assoc($res)) {
            $cols[] = $c['Field'];
        }

        $conds = [];
        if (in_array('nrp', $cols, true)) $conds[] = "nrp='$uEsc'";
        if (in_array('username', $cols, true)) $conds[] = "username='$uEsc'";
        if (in_array('akun_username', $cols, true)) $conds[] = "akun_username='$uEsc'";
        if (!$conds) return false;

        $q = mysqli_query($conn, "SELECT 1 FROM mahasiswa WHERE " . implode(' OR ', $conds) . " LIMIT 1");
        return mysqli_num_rows($q) > 0;
    }

    $sql = "SELECT * FROM akun 
            WHERE username='$username' 
            AND password=MD5('$password') 
            LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) === 1) {

        $data = mysqli_fetch_assoc($result);
        $_SESSION['username'] = $data['username'];

        if ((int)$data['isadmin'] === 1) {
            $_SESSION['level'] = 'admin';
        } else {
            if (is_dosen_user($conn, $data['username'])) {
                $_SESSION['level'] = 'dosen';
            } else {
                $_SESSION['level'] = 'mahasiswa';
            }
        }

        header("Location: /fullstack/fullstack/home.php");
        exit;

    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <link rel="stylesheet" href="asset/style.css">
    <link rel="stylesheet" href="asset/login.css">
</head>
<body>
<div class="login-shell">
    <div class="login-card">
        <h2>Masuk ke Portal</h2>
        <p>Kelola grup, dosen, dan mahasiswa dalam satu tempat.</p>

        <?php if (!empty($error)) { ?>
            <div class="error"><?= htmlspecialchars($error); ?></div>
        <?php } ?>

        <form method="post" action="">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-block">Masuk</button>
        </form>
    </div>
    <div class="footer-note">
        Gunakan akun yang sudah terdaftar. Hubungi admin jika mengalami kendala.
    </div>
</div>
</body>
</html>
