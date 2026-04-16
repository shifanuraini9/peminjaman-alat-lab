<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    // proses update status jadi Dikembalikan
    $query = mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Dikembalikan' WHERE id = '$id'");
    
    if ($query) {
        echo json_encode(['success' => true, 'message' => 'Alat berhasil dikembalikan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengembalikan alat']);
    }
}
?>