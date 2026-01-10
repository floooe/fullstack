<?php
require_once "Database.php";

class Akun extends Database {

    public function login($username, $password) {
        $u = $this->conn->real_escape_string($username);
        $p = md5($password);

        $sql = "SELECT * FROM akun WHERE username='$u' AND password='$p'";
        return $this->conn->query($sql);
    }

    public function getRole($username) {
        $u = $this->conn->real_escape_string($username);

        // admin
        $q = $this->conn->query("SELECT isadmin FROM akun WHERE username='$u'");
        if ($row = $q->fetch_assoc()) {
            if ($row['isadmin'] == 1) return 'admin';
        }

        // dosen
        $q = $this->conn->query("SELECT npk FROM dosen WHERE akun_username='$u'");
        if ($q->num_rows > 0) return 'dosen';

        return 'mahasiswa';
    }
}
