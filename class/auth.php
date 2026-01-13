<?php
require_once "Database.php";

class Auth extends Database
{
    public function login($username, $password)
    {
        $u = mysqli_real_escape_string($this->conn, $username);
        $p = md5($password);

        $q = mysqli_query(
            $this->conn,
            "SELECT username, isadmin FROM akun 
             WHERE username='$u' AND password='$p' LIMIT 1"
        );

        if (mysqli_num_rows($q) !== 1) {
            return false;
        }

        $user = mysqli_fetch_assoc($q);

        $user['level'] = $this->detectLevel($user['username'], $user['isadmin']);
        return $user;
    }

    private function detectLevel($username, $isadmin)
    {
        if ($isadmin == 1) {
            return 'admin';
        }

        $u = mysqli_real_escape_string($this->conn, $username);

        $conditions = ["npk='$u'"];
        if ($this->hasColumn('dosen', 'akun_username')) {
            $conditions[] = "akun_username='$u'";
        }
        $ck = implode(" OR ", $conditions);

        $cekDosen = mysqli_query(
            $this->conn,
            "SELECT 1 FROM dosen 
             WHERE {$ck} LIMIT 1"
        );

        if (mysqli_num_rows($cekDosen) > 0) {
            return 'dosen';
        }

        return 'mahasiswa';
    }
    public function changePassword($username, $newPassword)
    {
        $u = mysqli_real_escape_string($this->conn, $username);
        $p = md5($newPassword);

        return mysqli_query(
            $this->conn,
            "UPDATE akun SET password='$p' WHERE username='$u'"
        );
    }

}
