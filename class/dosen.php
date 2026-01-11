<?php
require_once "Database.php";

class Dosen extends Database
{
    public function countAll()
    {
        $q = mysqli_query($this->conn, "SELECT COUNT(*) AS total FROM dosen");
        $row = mysqli_fetch_assoc($q);
        return (int) $row['total'];
    }

    public function getAll($start, $limit)
    {
        $start = (int) $start;
        $limit = (int) $limit;

        return mysqli_query(
            $this->conn,
            "SELECT npk, nama, foto_extension
             FROM dosen
             ORDER BY npk ASC
             LIMIT $start, $limit"
        );
    }

    public function getByNpk($npk)
    {
        $stmt = $this->conn->prepare("
        SELECT 
            d.npk,
            d.nama,
            d.foto_extension,
            a.username AS akun_username
        FROM dosen d
        LEFT JOIN akun a ON a.username = d.npk
        WHERE d.npk = ?
    ");
        $stmt->bind_param("s", $npk);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function deleteByNpk($npk)
    {
        $this->conn->begin_transaction();

        try {
            // ambil data dulu
            $stmt = $this->conn->prepare(
                "SELECT foto_extension FROM dosen WHERE npk=?"
            );
            $stmt->bind_param("s", $npk);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$data) {
                throw new Exception("Data dosen tidak ditemukan");
            }

            // hapus dosen
            $del = $this->conn->prepare("DELETE FROM dosen WHERE npk=?");
            $del->bind_param("s", $npk);
            $del->execute();
            $del->close();

            // hapus akun (username = npk)
            $delAkun = $this->conn->prepare("DELETE FROM akun WHERE username=?");
            $delAkun->bind_param("s", $npk);
            $delAkun->execute();
            $delAkun->close();

            $this->conn->commit();

            return $data['foto_extension'];

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    public function akunExists($username)
    {
        $stmt = $this->conn->prepare(
            "SELECT 1 FROM akun WHERE username=? LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function updateDosen($npkLama, $npkBaru, $nama, $fotoExt)
    {
        $stmt = $this->conn->prepare(
            "UPDATE dosen SET npk=?, nama=?, foto_extension=? WHERE npk=?"
        );
        $stmt->bind_param("ssss", $npkBaru, $nama, $fotoExt, $npkLama);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        return true;
    }


    public function updateAkun($oldUser, $newUser, $password = null)
    {
        if (trim($password) !== '') {
            $stmt = $this->conn->prepare(
                "UPDATE akun SET username=?, password=MD5(?), isadmin=0 WHERE username=?"
            );
            $stmt->bind_param("sss", $newUser, $password, $oldUser);
        } else {
            $stmt = $this->conn->prepare(
                "UPDATE akun SET username=?, isadmin=0 WHERE username=?"
            );
            $stmt->bind_param("ss", $newUser, $oldUser);
        }

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
    }

    public function createAkun($username, $password)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO akun(username,password,isadmin) VALUES (?,MD5(?),0)"
        );
        $stmt->bind_param("ss", $username, $password);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
    }

    public function updateFull($data)
    {
        $this->conn->begin_transaction();
        try {
            $this->updateDosen(
                $data['npk_lama'],
                $data['npk_baru'],
                $data['nama'],
                $data['foto_ext']
            );

            if ($this->akunExists($data['akun_lama'])) {
                $this->updateAkun(
                    $data['akun_lama'],
                    $data['akun_baru'],
                    $data['password'] ?: null
                );
            } else {
                if (!empty($data['password'])) {
                    $this->createAkun($data['akun_baru'], $data['password']);
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    public function existsNpk($npk)
    {
        $stmt = $this->conn->prepare(
            "SELECT 1 FROM dosen WHERE npk=? LIMIT 1"
        );
        $stmt->bind_param("s", $npk);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function createDosenWithAkun($data)
    {
        $this->conn->begin_transaction();
        try {
            //insert dosen
            $stmt = $this->conn->prepare(
                "INSERT INTO dosen (npk, nama, foto_extension) VALUES (?, ?, ?)"
            );
            $stmt->bind_param(
                "sss",
                $data['npk'],
                $data['nama'],
                $data['foto_ext']
            );
            $stmt->execute();
            $stmt->close();

            //insert akun
            $stmtA = $this->conn->prepare(
                "INSERT INTO akun (username, password, isadmin) VALUES (?, MD5(?), 0)"
            );
            $stmtA->bind_param(
                "ss",
                $data['akun_username'],
                $data['password']
            );
            $stmtA->execute();
            $stmtA->close();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }


}
