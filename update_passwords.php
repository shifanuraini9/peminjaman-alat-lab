<?php
// File: update_passwords.php
// Hapus setelah selesai!

include "koneksi.php";

$updates = [
    ['username' => 'shifa@gmail.com', 'password' => '1234'],
    ['username' => 'nur@gmail.com', 'password' => '1234'],
    ['username' => 'aini@gmail.com', 'password' => '1234'],
    ['username' => 'petugas1', 'password' => '123456'],
    ['username' => 'admin', 'password' => '098765']
];

echo "<h2>Update Password Users</h2>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>Username</th><th>Password</th><th>Status</th></tr>";

foreach ($updates as $data) {
    $username = $data['username'];
    $password = $data['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $query = "UPDATE users SET password = '$hash' WHERE username = '$username'";
    if (mysqli_query($koneksi, $query)) {
        $status = "✅ Berhasil diupdate";
    } else {
        $status = "❌ Gagal: " . mysqli_error($koneksi);
    }
    
    echo "<tr>";
    echo "<td>$username</td>";
    echo "<td>$password</td>";
    echo "<td>$status</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p style='color:red;'><strong>⚠️ Hapus file ini setelah selesai!</strong></p>";
?>