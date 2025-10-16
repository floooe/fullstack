<?php
include "koneksi.php";
session_start();

if(isset($_POST['login'])){
  $user = $_POST['username'];
  $pass = $_POST['password'];

  $q = mysqli_query($koneksi, "SELECT * FROM akun WHERE username='$user' AND password='$pass'");
  $data = mysqli_fetch_array($q);

  if($data){
    $_SESSION['username'] = $data['username'];
    $_SESSION['level'] = $data['level'];
    header("Location: ../home.php");
  } else {
    echo "<script>alert('Login gagal');window.location='../index.php';</script>";
  }
}
?>
