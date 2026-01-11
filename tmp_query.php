<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli('127.0.0.1', 'root', '', 'fullstack');
$user = 'admin';
$res = mysqli_query($conn, "SELECT * FROM grup WHERE username_pembuat='$user'");
if (!$res) {
    echo "error\n";
    die($conn->error);
}
echo mysqli_num_rows($res) . "\n";
while ($row = mysqli_fetch_assoc($res)) {
    echo json_encode($row) . "\n";
}
?>
