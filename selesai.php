<?php
include "../koneksi.php";

$id     = $_GET['id'];
$alat   = $_GET['alat'];
$jumlah = $_GET['jumlah'];

mysqli_query($koneksi,"
UPDATE peminjaman 
SET status='Selesai' 
WHERE id='$id'
");

mysqli_query($koneksi,"
UPDATE alat 
SET stok = stok + $jumlah 
WHERE id_alat='$alat'
");

header("Location: pengembalian.php");
