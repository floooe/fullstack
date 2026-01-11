<?php
session_start();

require_once __DIR__ . "/class/Auth.php";
$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $user = $auth->login($username, $password);

    if ($user === false) {
        $error = "Username atau password salah!";
    } else {
        $_SESSION['username'] = $user['username'];
        $_SESSION['level'] = $user['level'];

        header("Location: /fullstack/fullstack/home.php");
        exit;
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

            <form method="post">
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