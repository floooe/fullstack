<?php
require_once "class/Grup.php";

$user = 'admin';
$grup = new Grup();
$res = $grup->getByDosen($user);

if (!$res) {
    echo "error\n";
    die($grup->getConn()->error);
}

echo mysqli_num_rows($res) . "\n";
while ($row = mysqli_fetch_assoc($res)) {
    echo json_encode($row) . "\n";
}
