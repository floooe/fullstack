<?php
require_once "Database.php";

class Mahasiswa extends Database
{
    public function countAll()
    {
        $q = mysqli_query($this->conn, "SELECT COUNT(nrp) AS total FROM mahasiswa");
        $row = mysqli_fetch_assoc($q);
        return (int)$row['total'];
    }

    public function getAll($limit, $offset)
    {
        $limit  = (int)$limit;
        $offset = (int)$offset;

        return mysqli_query(
            $this->conn,
            "SELECT nrp, nama, gender, tanggal_lahir, angkatan, foto_extention
             FROM mahasiswa
             ORDER BY nrp DESC
             LIMIT $limit OFFSET $offset"
        );
    }
}
