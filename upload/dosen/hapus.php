<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../home.php');
    exit;
}
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

if (isset($_GET['npk'])) {
    $npk_to_delete = $_GET['npk'];

    $colRes = $mysqli->query("SHOW COLUMNS FROM dosen");
    $hasAkunCol = false;
    while ($c = $colRes->fetch_assoc()) {
        if ($c['Field'] === 'akun_username') { $hasAkunCol = true; break; }
    }
    $colRes->free();

    $query_select = $hasAkunCol
        ? "SELECT foto_extension, akun_username FROM dosen WHERE npk = ?"
        : "SELECT foto_extension FROM dosen WHERE npk = ?";
    $stmt_select = $mysqli->prepare($query_select);
    $stmt_select->bind_param('s', $npk_to_delete);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $akun_username = null;
    
    if ($data = $result->fetch_assoc()) {
        $foto_extension = $data['foto_extension'];
        if ($hasAkunCol && !empty($data['akun_username'])) {
            $akun_username = $data['akun_username'];
        }
        
        if (!empty($foto_extension)) {
            $nama_file_foto = $npk_to_delete . '.' . $foto_extension;
            $path_to_file = "../../uploads/dosen/" . $nama_file_foto;
            
            if (file_exists($path_to_file)) {
                unlink($path_to_file);
            }
        }
    }
    $stmt_select->close();

    $query_delete = "DELETE FROM dosen WHERE npk = ?";
    $stmt_delete = $mysqli->prepare($query_delete);
    $stmt_delete->bind_param('s', $npk_to_delete);

    if ($stmt_delete->execute()) {
        $stmt_akun = $mysqli->prepare("DELETE FROM akun WHERE username = ?");
        $stmt_akun->bind_param('s', $npk_to_delete);
        $stmt_akun->execute();
        if ($akun_username && $akun_username !== $npk_to_delete) {
            $stmt_akun->bind_param('s', $akun_username);
            $stmt_akun->execute();
        }
        $stmt_akun->close();

        header("Location: index.php");
        exit;
    } else {
        echo "DATABASE ERROR: " . $stmt_delete->error;
    }
    $stmt_delete->close();
    
} else {
    die("Error: NPK dosen tidak ditemukan.");
}

$mysqli->close();
?>
