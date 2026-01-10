<?php
require_once "Database.php";

class GrupDetail extends Database {

    public function getGrup($idgrup) {
        $idgrup = (int)$idgrup;
        return mysqli_fetch_assoc(
            mysqli_query($this->conn, "SELECT * FROM grup WHERE idgrup=$idgrup")
        );
    }

    public function updateGrup($idgrup, $nama, $jenis, $deskripsi) {
        $idgrup = (int)$idgrup;
        $nama = mysqli_real_escape_string($this->conn, $nama);
        $jenis = mysqli_real_escape_string($this->conn, $jenis);
        $deskripsi = mysqli_real_escape_string($this->conn, $deskripsi);

        return mysqli_query(
            $this->conn,
            "UPDATE grup 
             SET nama='$nama', jenis='$jenis', deskripsi='$deskripsi'
             WHERE idgrup=$idgrup"
        );
    }

    //member

    public function getMembers($idgrup) {
        $idgrup = (int)$idgrup;
        return mysqli_query($this->conn, "
            SELECT mg.username,
                   COALESCE(d.nama, m.nama) AS nama,
                   CASE 
                        WHEN d.npk IS NOT NULL THEN 'Dosen'
                        WHEN m.nrp IS NOT NULL THEN 'Mahasiswa'
                        ELSE 'User'
                   END AS tipe
            FROM member_grup mg
            LEFT JOIN dosen d ON d.npk = mg.username
            LEFT JOIN mahasiswa m ON m.nrp = mg.username
            WHERE mg.idgrup=$idgrup
            ORDER BY tipe, nama
        ");
    }

    public function addMember($idgrup, $username) {
        $idgrup = (int)$idgrup;
        $username = mysqli_real_escape_string($this->conn, $username);

        // pastikan akun ada
        $cek = mysqli_query($this->conn,
            "SELECT username FROM akun WHERE username='$username' LIMIT 1"
        );
        if (mysqli_num_rows($cek) == 0) {
            mysqli_query($this->conn,
                "INSERT INTO akun(username,password,isadmin)
                 VALUES('$username', MD5('$username'), 0)"
            );
        }

        return mysqli_query(
            $this->conn,
            "INSERT INTO member_grup(idgrup, username)
             VALUES ($idgrup, '$username')"
        );
    }

    public function removeMember($idgrup, $username) {
        $idgrup = (int)$idgrup;
        $username = mysqli_real_escape_string($this->conn, $username);

        return mysqli_query(
            $this->conn,
            "DELETE FROM member_grup 
             WHERE idgrup=$idgrup AND username='$username'"
        );
    }

    //event

    public function getEvents($idgrup) {
        $idgrup = (int)$idgrup;
        return mysqli_query(
            $this->conn,
            "SELECT * FROM events 
             WHERE idgrup=$idgrup
             ORDER BY jadwal DESC"
        );
    }

    public function addEvent($idgrup, $judul, $jadwal, $detail) {
        $idgrup = (int)$idgrup;
        $judul = mysqli_real_escape_string($this->conn, $judul);
        $jadwal = mysqli_real_escape_string($this->conn, $jadwal);
        $detail = mysqli_real_escape_string($this->conn, $detail);

        return mysqli_query(
            $this->conn,
            "INSERT INTO events(idgrup, judul, jadwal, detail, created_at)
             VALUES($idgrup,'$judul','$jadwal','$detail',NOW())"
        );
    }

    public function deleteEvent($id) {
        $id = (int)$id;
        return mysqli_query($this->conn, "DELETE FROM events WHERE id=$id");
    }
}
