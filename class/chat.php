<?php
require_once "Database.php";

class Chat extends Database
{
    public function getByThread($idthread, $lastId = 0)
    {
        $idthread = (int) $idthread;
        $lastId   = (int) $lastId;

        $sql = "
            SELECT 
                c.idchat,
                c.isi,
                c.tanggal_pembuatan,
                c.username_pembuat,
                COALESCE(d.nama, m.nama, c.username_pembuat) AS nama
            FROM chat c
            LEFT JOIN dosen d 
                ON d.npk = c.username_pembuat
            LEFT JOIN mahasiswa m 
                ON m.nrp = c.username_pembuat
            WHERE c.idthread = ?
              AND c.idchat > ?
            ORDER BY c.idchat ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $idthread, $lastId);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function send($idthread, $username, $isi)
    {
        $sql = "
            INSERT INTO chat (idthread, username_pembuat, isi, tanggal_pembuatan)
            VALUES (?, ?, ?, NOW())
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $idthread, $username, $isi);

        return $stmt->execute();
    }
}
