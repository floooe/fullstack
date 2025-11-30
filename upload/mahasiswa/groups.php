<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../index.php");
    exit;
}
if (!isset($_SESSION['level']) || $_SESSION['level'] !== 'mahasiswa') {
    header("Location: ../../home.php");
    exit;
}

include "../../proses/koneksi.php";

function parse_group($name, $description) {
    $parts = explode(" | ", $name);
    $title = $parts[0];
    $code = $parts[1] ?? '';
    $jenis = 'public';
    if (strpos($description, '[public]') === 0) {
        $jenis = 'public';
    } elseif (strpos($description, '[private]') === 0) {
        $jenis = 'private';
    }
    return [$title, $code, $jenis];
}

$username = mysqli_real_escape_string($conn, $_SESSION['username']);
$info = isset($_GET['msg']) ? $_GET['msg'] : null;
$errors = [];

// Leave group
if (isset($_GET['leave'])) {
    $gid = (int)$_GET['leave'];
    mysqli_query($conn, "DELETE FROM group_members WHERE group_id=$gid AND username='$username'");
    header("Location: groups.php?msg=Berhasil keluar dari grup");
    exit;
}

// Join via code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_code'])) {
    $kode = strtoupper(trim($_POST['join_code']));
    if ($kode === '') {
        $errors[] = "Kode wajib diisi.";
    } else {
        $kodeEsc = mysqli_real_escape_string($conn, $kode);
        $q = mysqli_query($conn, "SELECT * FROM groups WHERE name LIKE '%| $kodeEsc'");
        if (mysqli_num_rows($q) === 0) {
            $errors[] = "Kode tidak ditemukan.";
        } else {
            $g = mysqli_fetch_assoc($q);
            list($gn, $gc, $gj) = parse_group($g['name'], $g['description']);
            if ($gj !== 'public') {
                $errors[] = "Grup ini private. Tidak bisa join dengan kode.";
            } else {
                $already = mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM group_members WHERE group_id={$g['id']} AND username='$username'"));
                if ($already > 0) {
                    $errors[] = "Anda sudah tergabung di grup ini.";
                } else {
                    mysqli_query($conn, "INSERT INTO group_members(group_id, username, joined_at) VALUES ({$g['id']}, '$username', NOW())");
                    header("Location: groups.php?msg=Berhasil bergabung ke grup $gn");
                    exit;
                }
            }
        }
    }
}

$joined = mysqli_query($conn, "
    SELECT g.*, gm.joined_at 
    FROM group_members gm 
    JOIN groups g ON g.id = gm.group_id
    WHERE gm.username='$username'
    ORDER BY gm.joined_at DESC
");

$public = mysqli_query($conn, "
    SELECT * FROM groups g 
    WHERE g.description LIKE '[public]%' 
      AND g.id NOT IN (SELECT group_id FROM group_members WHERE username='$username')
    ORDER BY g.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Group Saya</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 20px;
        }

        h2, h3, h4 {
            margin-bottom: 10px;
        }

        .section {
            background: white;
            padding: 20px;
            margin-top: 25px;
            border-radius: 10px;
            box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
        }

        a {
            color: #2980b9;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }

        .info-box {
            background: #e8f5e9;
            padding: 12px;
            border-left: 5px solid #4caf50;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .error-box {
            background: #ffebee;
            padding: 12px;
            border-left: 5px solid #f44336;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            border-radius: 8px;
            overflow: hidden;
        }

        th {
            background: #3498db;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }

        td {
            background: #ffffff;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        tr:hover td {
            background: #f1faff;
        }

        button {
            background: #3498db;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #217dbb;
        }

        input[type="text"] {
            padding: 10px 12px;
            width: 250px;
            border-radius: 6px;
            border: 1px solid #bbb;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #3498db;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            background: #ddd;
            padding: 6px 12px;
            border-radius: 6px;
        }
        .back-link:hover {
            background: #ccc;
        }

    </style>
</head>
<body>

    <h2>Group Saya (Mahasiswa)</h2>
    <p><a href="../../home.php" class="back-link">Kembali</a></p>

    <?php if ($info) { ?>
        <div class="info-box"><?= htmlspecialchars($info); ?></div>
    <?php } ?>

    <?php if (!empty($errors)) { ?>
        <div class="error-box">
            <?php foreach ($errors as $e) { echo "<p>" . htmlspecialchars($e) . "</p>"; } ?>
        </div>
    <?php } ?>

    <div class="section">
        <h3>Grup yang Diikuti</h3>
        <table>
            <tr><th>Nama Grup</th><th>Kode</th><th>Bergabung</th><th>Aksi</th></tr>
            <?php if (mysqli_num_rows($joined) === 0) { ?>
                <tr><td colspan="4" style="text-align:center;">Belum bergabung dengan grup mana pun.</td></tr>
            <?php } else { while($g = mysqli_fetch_assoc($joined)) { 
                list($gn, $gc, $gj) = parse_group($g['name'], $g['description']);
            ?>
                <tr>
                    <td><?= htmlspecialchars($gn); ?> (<?= htmlspecialchars($gj); ?>)</td>
                    <td><b><?= htmlspecialchars($gc); ?></b></td>
                    <td><?= htmlspecialchars($g['joined_at']); ?></td>
                    <td>
                        <a href="group_detail.php?id=<?= $g['id']; ?>">Detail</a> |
                        <a href="groups.php?leave=<?= $g['id']; ?>" onclick="return confirm('Keluar dari grup ini?')">Keluar</a>
                    </td>
                </tr>
            <?php } } ?>
        </table>
    </div>

    <div class="section" id="join">
        <h3>Gabung ke Grup Publik (butuh kode)</h3>
        <form method="post" style="margin-bottom:10px;">
            <input type="text" name="join_code" placeholder="Masukkan kode pendaftaran" required>
            <button type="submit">Gabung</button>
        </form>

        <h4>Daftar Grup Publik yang Bisa Anda Lihat</h4>
        <table>
            <tr><th>Nama Grup</th><th>Kode</th><th>Dosen Pembuat</th><th>Aksi</th></tr>
            <?php if (mysqli_num_rows($public) === 0) { ?>
                <tr><td colspan="4" style="text-align:center;">Tidak ada grup publik lain.</td></tr>
            <?php } else { while($p = mysqli_fetch_assoc($public)) { 
                list($pn, $pc, $pj) = parse_group($p['name'], $p['description']);
            ?>
                <tr>
                    <td><?= htmlspecialchars($pn); ?></td>
                    <td><b><?= htmlspecialchars($pc); ?></b></td>
                    <td><?= htmlspecialchars($p['created_by']); ?></td>
                    <td><a href="group_detail.php?id=<?= $p['id']; ?>">Lihat Detail</a></td>
                </tr>
            <?php } } ?>
        </table>
    </div>

</body>
</html>
