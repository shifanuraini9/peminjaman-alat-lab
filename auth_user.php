<?php
include "auth.php";

if($_SESSION['role'] != "user"){
    header("Location: /peminjaman_alat_lab/login.php");
    exit;
}
?>
