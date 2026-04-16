<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'];
$aksi = $_GET['aksi'];

// Ambil data peminjaman
$query = mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE id = '$id'");
$data = mysqli_fetch_assoc($query);

if ($aksi == 'acc') {
    // Cek stok alat
    $cek_stok = mysqli_query($koneksi, "SELECT stok FROM alat WHERE id_alat = '{$data['id_alat']}'");
    $stok = mysqli_fetch_assoc($cek_stok);
    
    if ($stok['stok'] >= $data['jumlah']) {
        // Update status peminjaman menjadi Disetujui
        $update = mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Disetujui' WHERE id = '$id'");
        
        if ($update) {
            // Kurangi stok alat
            $kurangi_stok = mysqli_query($koneksi, "UPDATE alat SET stok = stok - {$data['jumlah']} WHERE id_alat = '{$data['id_alat']}'");
            
            if ($kurangi_stok) {
                header("Location: pengajuan_peminjaman.php?success=acc");
            } else {
                header("Location: pengajuan_peminjaman.php?error=stok");
            }
        } else {
            header("Location: pengajuan_peminjaman.php?error=gagal");
        }
    } else {
        header("Location: pengajuan_peminjaman.php?error=stok");
    }
} elseif ($aksi == 'tolak') {
    // Update status menjadi Ditolak
    $update = mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Ditolak' WHERE id = '$id'");
    header("Location: pengajuan_peminjaman.php?success=tolak");
}
?>