<?php
include "auth.php";

if($_SESSION['role'] != "admin"){
    header("Location: /peminjaman_alat_lab/login.php");
    exit;
}
?>
