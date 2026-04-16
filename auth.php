<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['role'])){
    header("Location: /peminjaman_alat_lab/login.php");
    exit;
}

// cegah back setelah logout
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
