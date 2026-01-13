<?php
require_once "Database.php";

class Thread extends Database
{
    public function create($idgrup, $username)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO thread (idgrup, username_pembuat, tanggal_pembuatan, status)
             VALUES (?, ?, NOW(), 'Open')"
        );
        $stmt->bind_param("is", $idgrup, $username);
        return $stmt->execute();
    }
    public function getByGroup($idgrup)
    {
        $stmt = $this->conn->prepare(
            "SELECT t.*,
                    COALESCE(d.nama, m.nama, t.username_pembuat) AS nama_pembuat
             FROM thread t
             LEFT JOIN dosen d ON d.npk = t.username_pembuat
             LEFT JOIN mahasiswa m ON m.nrp = t.username_pembuat
             WHERE t.idgrup = ?
             ORDER BY t.tanggal_pembuatan DESC"
        );
        $stmt->bind_param("i", $idgrup);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getById($idthread)
    {
        $stmt = $this->conn->prepare(
            "SELECT t.*, g.nama AS nama_grup
             FROM thread t
             JOIN grup g ON g.idgrup = t.idgrup
             WHERE t.idthread = ?
             LIMIT 1"
        );
        $stmt->bind_param("i", $idthread);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function closeThread($idthread, $username)
    {
        $stmt = $this->conn->prepare(
            "UPDATE thread
             SET status = 'Close'
             WHERE idthread = ?
               AND username_pembuat = ?"
        );
        $stmt->bind_param("is", $idthread, $username);
        return $stmt->execute();
    }

}
