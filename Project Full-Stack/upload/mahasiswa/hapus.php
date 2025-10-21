<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../../Project Full-Stack/home.php');
    exit;
}
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

if (isset($_GET['nrp'])) {
    $nrp_to_delete = $_GET['nrp'];

    $query_select = "SELECT foto_extention FROM mahasiswa WHERE nrp = ?";
    $stmt_select = $mysqli->prepare($query_select);
    $stmt_select->bind_param('s', $nrp_to_delete);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    
    if ($data = $result->fetch_assoc()) {
        $foto_extension = $data['foto_extention'];
        
        if (!empty($foto_extension)) {
            $nama_file_foto = $nrp_to_delete . '.' . $foto_extension;
            $path_to_file = "../../uploads/mahasiswa/" . $nama_file_foto;
            
            if (file_exists($path_to_file)) {
                unlink($path_to_file);
            }
        }
    }
    $stmt_select->close();

    $query_delete = "DELETE FROM mahasiswa WHERE nrp = ?";
    $stmt_delete = $mysqli->prepare($query_delete);
    $stmt_delete->bind_param('s', $nrp_to_delete);

    if ($stmt_delete->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "DATABASE ERROR: " . $stmt_delete->error;
    }
    $stmt_delete->close();
    
} else {
    die("Error: NRP mahasiswa tidak ditemukan.");
}

$mysqli->close();
?>
