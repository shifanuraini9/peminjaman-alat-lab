<?php
// ==========================================
// FILE: security.php
// ==========================================

// ANTI CACHE
header("Cache-Control: no-cache, no-store, must-revalidate, private");
header("Pragma: no-cache");
header("Expires: 0");

// CEK LOGIN - JIKA TIDAK ADA, LANGSUNG KE LOGIN
if(!isset($_SESSION['nama'])) {
    header("Location: login.php");
    exit();
}

// CEK SESSION TIMEOUT (24 jam)
if(isset($_SESSION['login_time'])) {
    $timeout = 86400;
    if(time() - $_SESSION['login_time'] > $timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }
} else {
    $_SESSION['login_time'] = time();
}
?>