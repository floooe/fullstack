<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin</title>
  <style>
    body { 
      font-family: Arial, sans-serif; 
      margin: 0;
      background-color: lightblue; 
      text-align: center; 
    }
    h2 { 
      margin-top: 100px; 
      margin-bottom: 10px; 
      color: black; 
    }
    p {
      margin-bottom: 20px;
      color: gray;
    }
    .menu { 
      margin-top: 20px; 
    }
    .menu a {
      display: inline-block;
      margin: 10px;
      padding: 12px 24px;
      background: blue; 
      color: white;
      text-decoration: none;
      border-radius: 6px;
      transition: background 0.3s;
      font-weight: bold;
    }
    .menu a:hover {
      background: navy; 
    }
  </style>
</head>
<body>

<h2>Dashboard Admin</h2>
<p>Silakan pilih menu untuk mengelola data:</p>

<div class="menu">
  <a href="Project Full-Stack/upload/dosen/index.php">Kelola Data Dosen</a>
  <a href="Project Full-Stack/upload/mahasiswa/index.php">Kelola Data Mahasiswa</a>
</div>

</body>
</html>
