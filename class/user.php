<?php
require_once "Database.php";

class User extends Database
{
    public function detectLevel($username)
    {
        $u = $this->esc($username);

        // cek dosen
        $qDosen = mysqli_query(
            $this->conn,
            "SELECT 1 FROM dosen 
             WHERE username='$u' OR akun_username='$u' OR npk='$u'
             LIMIT 1"
        );

        if (mysqli_num_rows($qDosen) > 0) {
            return 'dosen';
        }

        // cek mahasiswa
        $qMhs = mysqli_query(
            $this->conn,
            "SELECT 1 FROM mahasiswa 
             WHERE username='$u' OR akun_username='$u' OR nrp='$u'
             LIMIT 1"
        );

        if (mysqli_num_rows($qMhs) > 0) {
            return 'mahasiswa';
        }

        // default fallback
        return 'mahasiswa';
    }
}
