<?php
$server = "localhost";
$user = "root";
$pass = "";
$db = "fullstack";

// koneksi ke database
$conn = mysqli_connect($server, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
