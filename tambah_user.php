<?php
session_start();
require_once "../security.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
include "../koneksi.php";

$error = '';
$success = '';

if(isset($_POST['tambah'])) {
    $id_user = mysqli_real_escape_string($koneksi, $_POST['id_user']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    
    // Cek username sudah ada atau belum
    $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
    if(mysqli_num_rows($cek) > 0) {
        $error = "Username sudah digunakan!";
    } else {
        $query = "INSERT INTO users (id_user, username, password, role) 
                  VALUES ('$id_user', '$username', '$password', '$role')";
        
        if(mysqli_query($koneksi, $query)) {
            $success = "User berhasil ditambahkan!";
            echo "<script>setTimeout(function(){ window.location='tambah_user.php'; }, 1500);</script>";
        } else {
            $error = "Gagal menambahkan: " . mysqli_error($koneksi);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah User</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        body {
            background: #f0f8ff;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        h2 {
            color: #0077b6;
            margin-bottom: 25px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #0077b6;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #0077b6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background: #005f92;
        }
        .btn-back {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            width: 100%;
        }
        .btn-back:hover {
            background: #5a6268;
        }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Tambah User</h2>
    
    <?php if($error): ?>
        <div class="error"><?= $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="success"><?= $success; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>ID User</label>
            <input type="text" name="id_user" placeholder="Contoh: USR001" required>
        </div>
        
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Username" required>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Password" required>
        </div>
        
        <div class="form-group">
            <label>Role</label>
            <select name="role" required>
                <option value="user">User</option>
                <option value="petugas">Petugas</option>
            </select>
        </div>
        
        <button type="submit" name="tambah">Simpan</button>
        <a href="kelola_user.php" class="btn-back">Kembali</a>
    </form>
</div>
</body>
</html>