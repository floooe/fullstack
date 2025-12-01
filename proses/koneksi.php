<?php
$server = "localhost";
$user = "root";
$pass = "";
$db = "fullstack";

$conn = null;
$hosts = ['127.0.0.1', 'localhost'];
foreach ($hosts as $host) {
    $conn = @mysqli_connect($host, $user, $pass, $db);
    if ($conn) {
        $server = $host;
        break;
    }
}

if (!$conn) {
    die("Koneksi gagal ke {$db}@{$server}: " . mysqli_connect_error());
}
?>
