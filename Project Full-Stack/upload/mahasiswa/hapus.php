<?php
// 1. KONEKSI DATABASE
$mysqli = new mysqli("localhost", 'root', '', 'fullstack');
if ($mysqli->connect_errno) {
    die("Koneksi Gagal: " . $mysqli->connect_error);
}

// 2. AMBIL NRP DARI URL
// Cek apakah 'nrp' ada di URL
if (isset($_GET['nrp'])) {
    $nrp_to_delete = $_GET['nrp'];

    // 3. AMBIL NAMA FILE FOTO DARI DATABASE SEBELUM DATA DIHAPUS
    // Kita perlu ini untuk menghapus file gambarnya dari folder
    $query_select = "SELECT foto_extention FROM mahasiswa WHERE nrp = ?";
    $stmt_select = $mysqli->prepare($query_select);
    $stmt_select->bind_param('s', $nrp_to_delete);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    
    if ($data = $result->fetch_assoc()) {
        $foto_extension = $data['foto_extention'];
        
        // 4. HAPUS FILE FOTO DARI FOLDER UPLOADS
        if (!empty($foto_extension)) {
            // Gabungkan nrp dan ekstensinya untuk mendapatkan nama file lengkap
            $nama_file_foto = $nrp_to_delete . '.' . $foto_extension;
            // Gunakan path yang benar (naik 2 folder)
            $path_to_file = "../../uploads/mahasiswa/" . $nama_file_foto;
            
            // Cek jika file ada, lalu hapus
            if (file_exists($path_to_file)) {
                unlink($path_to_file);
            }
        }
    }
    $stmt_select->close();

    // 5. HAPUS DATA MAHASISWA DARI DATABASE
    $query_delete = "DELETE FROM mahasiswa WHERE nrp = ?";
    $stmt_delete = $mysqli->prepare($query_delete);
    $stmt_delete->bind_param('s', $nrp_to_delete);

    if ($stmt_delete->execute()) {
        // Jika berhasil, kembali ke halaman utama
        header("Location: index.php");
        exit;
    } else {
        // Jika gagal, tampilkan error
        echo "DATABASE ERROR: " . $stmt_delete->error;
    }
    $stmt_delete->close();
    
} else {
    // Jika tidak ada 'nrp' di URL
    die("Error: NRP mahasiswa tidak ditemukan.");
}

$mysqli->close();
?>