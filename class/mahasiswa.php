<?php
require_once "Database.php";

class Mahasiswa extends Database
{
    public function countAll()
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(nrp) AS total FROM mahasiswa"
        );

        if (!$stmt->execute()) {
            throw new Exception("Gagal menghitung data mahasiswa");
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int) $row['total'];
    }


    public function getAll($limit, $offset)
    {
        $limit = (int) $limit;
        $offset = (int) $offset;

        $stmt = $this->conn->prepare(
            "SELECT nrp, nama, gender, tanggal_lahir, angkatan, foto_extention
         FROM mahasiswa
         ORDER BY nrp DESC
         LIMIT ? OFFSET ?"
        );

        $stmt->bind_param("ii", $limit, $offset);

        if (!$stmt->execute()) {
            throw new Exception("Gagal mengambil data mahasiswa");
        }
        return $stmt->get_result();
    }

    public function getByNrp($nrp)
    {
        $stmt = $this->conn->prepare(
            "SELECT nrp, nama, gender, tanggal_lahir, angkatan, foto_extention 
             FROM mahasiswa WHERE nrp=?"
        );
        $stmt->bind_param("s", $nrp);
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

    public function updateMahasiswa($oldNrp, $data)
    {
        $stmt = $this->conn->prepare(
            "UPDATE mahasiswa SET 
                nrp=?, nama=?, gender=?, tanggal_lahir=?, angkatan=?, foto_extention=? 
             WHERE nrp=?"
        );
        $stmt->bind_param(
            "sssssss",
            $data['nrp'],
            $data['nama'],
            $data['gender'],
            $data['tanggal_lahir'],
            $data['angkatan'],
            $data['foto_ext'],
            $oldNrp
        );
        return $stmt->execute();
    }

    public function updateAkun($oldUser, $newUser, $password = null)
    {
        if ($password) {
            $stmt = $this->conn->prepare(
                "UPDATE akun SET username=?, password=MD5(?) WHERE username=?"
            );
            $stmt->bind_param("sss", $newUser, $password, $oldUser);
        } else {
            $stmt = $this->conn->prepare(
                "UPDATE akun SET username=? WHERE username=?"
            );
            $stmt->bind_param("ss", $newUser, $oldUser);
        }
        return $stmt->execute();
    }

    public function createAkun($username, $password)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO akun (username,password,isadmin) VALUES (?,MD5(?),0)"
        );
        $stmt->bind_param("ss", $username, $password);
        return $stmt->execute();
    }

    public function updateFull($oldNrp, $data)
    {
        $this->conn->begin_transaction();
        try {
            $this->updateMahasiswa($oldNrp, $data);

            if ($this->akunExists($data['akun_lama'])) {
                $this->updateAkun(
                    $data['akun_lama'],
                    $data['akun_baru'],
                    $data['password']
                );
            } else {
                if ($data['password']) {
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
    public function deleteMahasiswa($nrp)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM mahasiswa WHERE nrp = ?"
        );
        $stmt->bind_param("s", $nrp);
        return $stmt->execute();
    }
    public function deleteAkun($username)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM akun WHERE username = ?"
        );
        $stmt->bind_param("s", $username);
        return $stmt->execute();
    }

    public function deleteFull($nrp)
    {
        $this->conn->begin_transaction();
        try {
            $data = $this->getByNrp($nrp);
            if (!$data) {
                throw new Exception("Mahasiswa tidak ditemukan");
            }
            // hapus foto
            if (!empty($data['foto_extention'])) {
                $path = __DIR__ . "/../uploads/mahasiswa/"
                    . $nrp . '.' . $data['foto_extention'];
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            // hapus mahasiswa
            $this->deleteMahasiswa($nrp);
            // hapus akun
            $this->deleteAkun($nrp);
            if (!empty($data['akun_username']) && $data['akun_username'] !== $nrp) {
                $this->deleteAkun($data['akun_username']);
            }
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    public function existsByNrp($nrp)
    {
        $stmt = $this->conn->prepare(
            "SELECT 1 FROM mahasiswa WHERE nrp = ? LIMIT 1"
        );
        $stmt->bind_param("s", $nrp);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function hasAkunColumn()
    {
        $res = $this->conn->query("SHOW COLUMNS FROM mahasiswa");
        while ($c = $res->fetch_assoc()) {
            if ($c['Field'] === 'akun_username') {
                return true;
            }
        }
        return false;
    }

    public function insertMahasiswa($data)
    {
        if ($this->hasAkunColumn()) {
            $stmt = $this->conn->prepare(
                "INSERT INTO mahasiswa 
                (nrp, nama, gender, tanggal_lahir, angkatan, foto_extention, akun_username)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "sssssss",
                $data['nrp'],
                $data['nama'],
                $data['gender'],
                $data['tanggal_lahir'],
                $data['angkatan'],
                $data['foto_ext'],
                $data['akun_username']
            );
        } else {
            $stmt = $this->conn->prepare(
                "INSERT INTO mahasiswa 
                (nrp, nama, gender, tanggal_lahir, angkatan, foto_extention)
                VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "ssssss",
                $data['nrp'],
                $data['nama'],
                $data['gender'],
                $data['tanggal_lahir'],
                $data['angkatan'],
                $data['foto_ext']
            );
        }

        return $stmt->execute();
    }

    public function createFull($data)
    {
        $this->conn->begin_transaction();
        try {
            $this->insertMahasiswa($data);
            $this->createAkun($data['akun_username'], $data['password']);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}
