<?php
require_once "Database.php";

class Grup extends Database {

    // ambil grup milik dosen
    public function getByDosen($username) {
        $username = mysqli_real_escape_string($this->conn, $username);
        return mysqli_query(
            $this->conn,
            "SELECT * FROM grup 
             WHERE username_pembuat='$username'
             ORDER BY tanggal_pembentukan DESC"
        );
    }

    // cek kepemilikan grup
    public function isOwner($idgrup, $username) {
        $idgrup = (int)$idgrup;
        $username = mysqli_real_escape_string($this->conn, $username);

        $q = mysqli_query(
            $this->conn,
            "SELECT 1 FROM grup 
             WHERE idgrup=$idgrup AND username_pembuat='$username'"
        );
        return mysqli_num_rows($q) > 0;
    }

    // hapus grup + relasi
    public function deleteGrup($idgrup) {
        $idgrup = (int)$idgrup;

        // hapus member
        if ($this->tableExists('member_grup')) {
            mysqli_query($this->conn, "DELETE FROM member_grup WHERE idgrup=$idgrup");
        }

        // hapus event
        if ($this->tableExists('events')) {
            mysqli_query($this->conn, "DELETE FROM events WHERE idgrup=$idgrup");
        }
        if ($this->tableExists('event')) {
            mysqli_query($this->conn, "DELETE FROM event WHERE idgrup=$idgrup");
        }

        // hapus grup
        mysqli_query($this->conn, "DELETE FROM grup WHERE idgrup=$idgrup");
    }

    private function tableExists($table) {
        $res = mysqli_query($this->conn, "SHOW TABLES LIKE '$table'");
        return $res && mysqli_num_rows($res) > 0;
    }

    //create group
    public function createGroup($nama, $jenis, $deskripsi, $username) {
        $kode = strtoupper(substr(md5(uniqid()), 0, 6));

        $stmt = $this->conn->prepare(
            "INSERT INTO grup 
            (nama, jenis, deskripsi, kode_pendaftaran, username_pembuat, tanggal_pembentukan)
            VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param(
            "sssss",
            $nama,
            $jenis,
            $deskripsi,
            $kode,
            $username
        );

        if ($stmt->execute()) {
            return $kode;
        }
        return false;
    }
}
