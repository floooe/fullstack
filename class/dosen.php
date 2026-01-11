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
        $stmt = $this->conn->prepare(
            "SELECT npk, nama, foto_extension 
         FROM dosen 
         WHERE npk=?"
        );
        $stmt->bind_param("s", $npk);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
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
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("Data dosen tidak ditemukan atau tidak berubah");
        }
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
            // 1. Update dosen
            $this->updateDosen(
                $data['npk_lama'],
                $data['npk_baru'],
                $data['nama'],
                $data['foto_ext']
            );

            // 2. Cegah username ganda
            if (
                $data['akun_baru'] !== $data['akun_lama'] &&
                $this->akunExists($data['akun_baru'])
            ) {
                throw new Exception("Username akun baru sudah digunakan");
            }

            // 3. Update / insert akun
            if ($this->akunExists($data['akun_lama'])) {
                $this->updateAkun(
                    $data['akun_lama'],
                    $data['akun_baru'],
                    $data['password']
                );
            } else {
                if (trim($data['password']) !== '') {
                    $this->createAkun(
                        $data['akun_baru'],
                        $data['password']
                    );
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
