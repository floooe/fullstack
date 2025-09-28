<?php
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $npk = $_POST['npk'];
    $nama = $_POST['nama'];

    $check_stmt = $mysqli->prepare("SELECT npk FROM dosen WHERE npk = ?");
    $check_stmt->bind_param('s', $npk);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        die("ERROR: NPK '{$npk}' sudah terdaftar. Silakan gunakan NPK lain.");
    }
    $check_stmt->close();

    $foto_extension = null;
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = $_FILES['foto'];
        $foto_extension = pathinfo($foto['name'], PATHINFO_EXTENSION);
        $nama_file_baru = $npk . '.' . $foto_extension;
        $lokasi_upload = "../../uploads/dosen/" . $nama_file_baru;

        if (!move_uploaded_file($foto['tmp_name'], $lokasi_upload)) {
            die("GAGAL UPLOAD FILE: Pastikan folder 'uploads/dosen' ada dan memiliki izin tulis.");
        }
    }

    $query = "INSERT INTO dosen (npk, nama, foto_extension) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sss', $npk, $nama, $foto_extension);
    
    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "DATABASE ERROR: " . $stmt->error;
    }
    
    $stmt->close();
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Dosen</title>
</head>
<body>
    <div class="container">
        <h2>Tambah Data Dosen</h2>
        
        <form action="tambah.php" method="POST" enctype="multipart/form-data">
            <label for="npk">NPK</label>
            <input type="text" id="npk" name="npk" required>

            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" required>

            <label for="foto">Foto</label>
            <input type="file" id="foto" name="foto">

            <button type="submit">💾 Simpan</button>
        </form>

        <a href="index.php" class="back-link">← Kembali ke Daftar Dosen</a>
    </div>

    <!-- CSS dipisah di bawah -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            margin: 40px auto;
            padding: 25px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #444;
        }

        input[type="text"],
        input[type="file"] {
            width: 100%;
            padding: 10px 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-bottom: 18px;
            font-size: 14px;
        }

        input[type="file"] {
            padding: 4px;
        }

        button {
            display: block;
            width: 100%;
            padding: 10px;
            background: #007BFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s ease-in-out;
        }

        button:hover {
            background: #0056b3;
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #007BFF;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</body>
</html>
