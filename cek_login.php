<?php
// file: cek_login.php (letakkan di folder utama / root)
session_start();

function cek_login() {
    if (!isset($_SESSION['username'])) {
        echo "<script>
            alert('Silakan login terlebih dahulu!');
            window.location.href = '../login.php';
        </script>";
        exit;
    }
}

function cek_petugas() {
    cek_login();
    if ($_SESSION['role'] != 'petugas') {
        echo "<script>
            alert('Akses ditolak! Halaman ini hanya untuk petugas.');
            window.location.href = '../login.php';
        </script>";
        exit;
    }
}

function cek_user() {
    cek_login();
    if ($_SESSION['role'] != 'user') {
        echo "<script>
            alert('Akses ditolak! Halaman ini hanya untuk user.');
            window.location.href = '../login.php';
        </script>";
        exit;
    }
}
?>