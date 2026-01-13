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

    protected function hasColumn(string $table, string $column): bool
    {
        $table = mysqli_real_escape_string($this->conn, $table);
        $column = mysqli_real_escape_string($this->conn, $column);
        $result = mysqli_query($this->conn, "SHOW COLUMNS FROM {$table} LIKE '{$column}'");
        return $result && mysqli_num_rows($result) > 0;
    }
}
