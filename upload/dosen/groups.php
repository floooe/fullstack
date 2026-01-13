<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}
if (!in_array($_SESSION['level'], ['admin', 'dosen'])) {
    header("Location: ../../home.php");
    exit;
}

require_once "../../class/group.php";

$grup = new Grup();
$username = $_SESSION['username'];

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($grup->isOwner($id, $username)) {
        $grup->deleteGrup($id);
        header("Location: groups.php?msg=Grup berhasil dihapus");
        exit;
    }
}

$data = $grup->getByDosen($username);
$message = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html>

<head>
    <title>Group Saya</title>
    <link rel="stylesheet" href="/fullstack/fullstack/asset/style.css">
</head>

<body>

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>Group Saya</h2>
        <div>
            <a href="../../home.php" class="btn btn-primary btn-small">Kembali</a>
            <a class="btn btn-small" href="create_Group.php">+ Buat Group</a>
        </div>
    </div>

    <?php if ($message) { ?>
        <p style="color:green"><?= htmlspecialchars($message) ?></p>
    <?php } ?>

    <table border="1" cellpadding="8">
        <tr>
            <th>Nama</th>
            <th>Jenis</th>
            <th>Kode</th>
            <th>Aksi</th>
        </tr>

        <?php if ($data->num_rows == 0) { ?>
            <tr>
                <td colspan="4">Belum ada grup</td>
            </tr>
        <?php } else {
            while ($row = $data->fetch_assoc()) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['jenis']) ?></td>
                    <td><b><?= htmlspecialchars($row['kode_pendaftaran']) ?></b></td>
                    <td>
                        <a href="group_detail.php?id=<?= $row['idgrup'] ?>">Detail</a> |
                        <a href="?delete=<?= $row['idgrup'] ?>" onclick="return confirm('Hapus grup?')">Hapus</a>
                    </td>
                </tr>
            <?php }
        } ?>
    </table>

</body>

</html>
