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
            "SELECT *
             FROM thread
             WHERE idgrup = ?
             ORDER BY tanggal_pembuatan DESC"
        );
        $stmt->bind_param("i", $idgrup);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getById($idthread)
    {
        $stmt = $this->conn->prepare(
            "SELECT *
             FROM thread
             WHERE idthread = ?
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
