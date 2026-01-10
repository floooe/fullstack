<?php
require_once "Database.php";

class MemberGrup extends Database {

    public function getByGroup($idgrup) {
        return $this->conn->query(
            "SELECT * FROM member_grup WHERE idgrup=$idgrup"
        );
    }

    public function add($idgrup, $username) {
        $u = $this->conn->real_escape_string($username);

        return $this->conn->query(
            "INSERT INTO member_grup (idgrup, username) VALUES ($idgrup, '$u')"
        );
    }

    public function remove($idgrup, $username) {
        $u = $this->conn->real_escape_string($username);

        return $this->conn->query(
            "DELETE FROM member_grup WHERE idgrup=$idgrup AND username='$u'"
        );
    }

    public function isMember($idgrup, $username) {
        $u = $this->conn->real_escape_string($username);
        $q = $this->conn->query(
            "SELECT 1 FROM member_grup WHERE idgrup=$idgrup AND username='$u'"
        );
        return $q->num_rows > 0;
    }
}
