<?php
session_start();
include "../koneksi.php";

if(!isset($_SESSION['nama'])) {
    header('location:../login.php');
    exit();
}

if(isset($_POST['pinjam'])) {
    $username = $_SESSION['nama'];
    $id_alat = $_POST['id_alat'];
    $jumlah = $_POST['jumlah'];
    $tanggal = $_POST['tanggal'];
    $tanggal_kembali = $_POST['tanggal_kembali'];
    
    // CEK STOK
    $cek = mysqli_query($koneksi, "SELECT * FROM alat WHERE id_alat='$id_alat'");
    $alat = mysqli_fetch_assoc($cek);
    
    if($alat['stok'] < $jumlah) {
        echo "<script>alert('Stok tidak mencukupi! Stok tersedia: " . $alat['stok'] . "'); window.location='peminjaman.php';</script>";
        exit();
    }
    
    // SIMPAN PEMINJAMAN
    $query = "INSERT INTO peminjaman (id_alat, username, jumlah, tanggal_pinjam, tanggal_kembali, status) 
              VALUES ('$id_alat', '$username', '$jumlah', '$tanggal', '$tanggal_kembali', 'Menunggu')";
    
    if(mysqli_query($koneksi, $query)) {
        // REDIRECT KE HALAMAN KONFIRMASI
        header('location:konfirmasi_peminjaman.php');
    } else {
        echo "<script>alert('Gagal: " . mysqli_error($koneksi) . "'); window.location='peminjaman.php';</script>";
    }
} else {
    header('location:peminjaman.php');
}
?>