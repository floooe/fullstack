<?php
$host = "localhost";
$user = "root"; 
$pass = "";     
$db   = "fullstack"; //sesuai schema

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

//karakter supaya aman untuk teks
mysqli_set_charset($koneksi, "utf8");
?>