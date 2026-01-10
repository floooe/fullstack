<?php
require_once "Database.php";

class Event extends Database {

    public function getByGroup($idgrup) {
        return $this->conn->query(
            "SELECT * FROM event WHERE idgrup=$idgrup ORDER BY tanggal DESC"
        );
    }

    public function insert($data) {
        $sql = "
            INSERT INTO event
            (idgrup, judul, tanggal, keterangan)
            VALUES
            ({$data['idgrup']}, '{$data['judul']}', '{$data['tanggal']}', '{$data['ket']}')
        ";
        return $this->conn->query($sql);
    }
}
