<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['login']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$users = mysqli_query($koneksi, "SELECT * FROM users ORDER BY role");
?>

<!DOCTYPE html>
<html>
<head>
<title>Kelola User</title>
<style>
body{background:#f1f5f9;font-family:'Segoe UI';}
.container{padding:30px;}
table{width:100%;background:white;border-radius:14px;border-collapse:collapse;box-shadow:0 10px 25px rgba(0,0,0,.12);}
th,td{padding:14px;text-align:left;}
th{background:#0284c7;color:white;}
tr:nth-child(even){background:#f8fafc;}
.badge{padding:6px 12px;border-radius:999px;color:white;font-size:12px;}
.user{background:#16a34a;}
.petugas{background:#f59e0b;}
.admin{background:#dc2626;}
a.btn{padding:6px 12px;border-radius:8px;text-decoration:none;font-size:13px;}
.edit{background:#0284c7;color:white;}
.hapus{background:#dc2626;color:white;}
</style>
</head>

<body>
<div class="container">
<h2>Kelola User</h2>
<br>
<a href="user_tambah.php" class="btn edit">+ Tambah User</a>
<br><br>

<table>
<tr>
    <th>No</th>
    <th>Username</th>
    <th>Role</th>
    <th>Aksi</th>
</tr>

<?php $no=1; while($u=mysqli_fetch_assoc($users)): ?>
<tr>
    <td><?= $no++ ?></td>
    <td><?= $u['username'] ?></td>
    <td>
        <span class="badge <?= $u['role'] ?>">
            <?= strtoupper($u['role']) ?>
        </span>
    </td>
    <td>
        <a class="btn edit" href="user_edit.php?id=<?= $u['id'] ?>">Edit</a>
        <?php if($u['username'] != $_SESSION['username']): ?>
        <a class="btn hapus" href="user_hapus.php?id=<?= $u['id'] ?>" onclick="return confirm('Hapus user?')">Hapus</a>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</table>
</div>
</body>
</html>
