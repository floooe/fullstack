<?php
session_start();
include '../../proses/koneksi.php';
require_once __DIR__ . '/../../proses/url.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Ambil data akun berdasarkan username
    $sql = "SELECT * FROM akun WHERE username='$username' AND password=MD5('$password')";
    $result = mysqli_query($conn, $sql);


    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $_SESSION['username'] = $data['username'];
        $_SESSION['level'] = ($data['isadmin'] == 1) ? 'admin' : 'user';
        header("Location: home.php");
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
    <link rel="stylesheet" href="<?= url_from_app('../asset/login.css') ?>">
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post" action="">
            <label>Username:</label>
            <input type="text" name="username" required>
            <label>Password:</label>
            <input type="password" name="password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>

