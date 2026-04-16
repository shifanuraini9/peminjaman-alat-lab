<?php
session_start();
include "../koneksi.php";

$id = $_GET['id'];

// ambil data pinjam
$q = mysqli_query($koneksi,"
    SELECT * FROM peminjaman WHERE id='$id'
");
$d = mysqli_fetch_assoc($q);

// update status & tanggal kembali
mysqli_query($koneksi,"
    UPDATE peminjaman 
    SET status='Dikembalikan',
        tanggal_kembali=CURDATE()
    WHERE id='$id'
");

// kembalikan stok
mysqli_query($koneksi,"
    UPDATE alat 
    SET stok = stok + {$d['jumlah']}
    WHERE id_alat='{$d['id_alat']}'
");

header("Location: riwayat_peminjaman.php");
?>
