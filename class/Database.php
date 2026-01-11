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

    // ✅ TAMBAHAN INI
    public function getConn()
    {
        return $this->conn;
    }

    protected function esc($str)
    {
        return mysqli_real_escape_string($this->conn, $str);
    }
}
