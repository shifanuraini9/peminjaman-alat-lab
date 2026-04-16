<?php
include "auth.php";

if($_SESSION['role'] != "petugas"){
    header("Location: /peminjaman_alat_lab/login.php");
    exit;
}
?>
