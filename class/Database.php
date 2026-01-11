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
            "fullstack"
        );

        if (!$this->conn) {
            die("Koneksi database gagal");
        }

    }
    protected function esc($str)
    {
        return mysqli_real_escape_string($this->conn, $str);
    }

}
