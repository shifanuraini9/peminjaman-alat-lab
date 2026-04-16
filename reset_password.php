<?php
include 'koneksi.php';

// ======================================================
// RESET PASSWORD UNTUK SEMUA USER
// ======================================================

// Ambil semua user
$users = mysqli_query($koneksi, "SELECT * FROM users");

while($user = mysqli_fetch_assoc($users)) {
    $username = $user['username'];
    $role = $user['role'];
    
    // Set password berdasarkan role
    if($role == 'admin') {
        $new_password = "098765";
    } elseif($role == 'petugas') {
        $new_password = "123456";
    } else {
        $new_password = "1234";
    }
    
    // Hash password baru
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update database
    mysqli_query($koneksi, "UPDATE users SET password='$hashed_password' WHERE username='$username'");
    
    echo "✅ User: $username (Role: $role) - Password baru: $new_password<br>";
}

echo "<hr>";
echo "<h3>Reset password selesai!</h3>";
echo "<a href='login.php'>Klik disini untuk login</a>";
?>