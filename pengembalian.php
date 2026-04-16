<?php
session_start();
include "../koneksi.php";

if(!isset($_SESSION['username'])){
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'];
$username = $_SESSION['username'];

// ambil data peminjaman
$q = mysqli_query($koneksi,"
    SELECT p.*, a.nama_alat
    FROM peminjaman p
    JOIN alat a ON p.id_alat = a.id_alat
    WHERE p.id='$id' AND p.username='$username'
");

$data = mysqli_fetch_assoc($q);

// proses pengembalian
if(isset($_POST['kembalikan'])){
    mysqli_query($koneksi,"
        UPDATE peminjaman 
        SET status='Dikembalikan'
        WHERE id='$id'
    ");

    echo "<script>
        alert('Pengembalian berhasil diajukan');
        window.location='riwayat_peminjaman.php';
    </script>";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Pengembalian Alat</title>

<style>
body{margin:0;font-family:Arial;background:#eef6fb}
nav{background:#0077b6;padding:15px 30px;display:flex;justify-content:space-between;color:white}
nav ul{list-style:none;display:flex;gap:20px;margin:0;padding:0}
nav ul li a{color:white;text-decoration:none;font-weight:bold}

.box{
    max-width:400px;
    background:white;
    margin:50px auto;
    padding:25px;
    border-radius:10px;
    box-shadow:0 5px 20px rgba(0,0,0,.15);
}
button{
    width:100%;
    padding:12px;
    background:#0077b6;
    color:white;
    border:none;
    font-weight:bold;
    cursor:pointer;
}
</style>

</head>
<body>

<nav>
<b>Peminjaman Alat Lab</b>
<ul>
    <li><a href="beranda.php">Beranda</a></li>
    <li><a href="riwayat_peminjaman.php">Riwayat</a></li>
    <li><a href="../logout.php">Logout</a></li>
</ul>
</nav>

<div class="box">
<h2>Pengembalian Alat</h2>

<p><b>Nama Alat:</b> <?= $data['nama_alat'] ?></p>
<p><b>Jumlah:</b> <?= $data['jumlah'] ?></p>

<form method="POST">
<button name="kembalikan">Ajukan Pengembalian</button>
</form>
</div>

</body>
</html>
