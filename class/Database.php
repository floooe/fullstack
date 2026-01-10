<?php
class Database
{
    protected $conn;

    public function __construct()
    {
        $this->conn = mysqli_connect(
            "localhost",
            "root",
            "",
            "fullstack" // NAMA DATABASE
        );

        if (!$this->conn) {
            die("Koneksi database gagal");
        }
    }
}
