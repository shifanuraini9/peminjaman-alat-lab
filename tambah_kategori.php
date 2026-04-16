<?php
session_start();
require_once "../security.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include "../koneksi.php";

// Generate ID Kategori otomatis (KAT001, KAT002, dst)
$query_max = mysqli_query($koneksi, "SELECT id_kategori FROM kategori ORDER BY id_kategori DESC LIMIT 1");
$last = mysqli_fetch_assoc($query_max);
if($last) {
    $last_num = (int)substr($last['id_kategori'], 3);
    $new_num = $last_num + 1;
    $id_kategori = "KAT" . str_pad($new_num, 3, "0", STR_PAD_LEFT);
    $kode_kategori = "KAT-" . str_pad($new_num, 3, "0", STR_PAD_LEFT);
} else {
    $id_kategori = "KAT001";
    $kode_kategori = "KAT-001";
}

if(isset($_POST['simpan'])) {
    $id_kategori = mysqli_real_escape_string($koneksi, $_POST['id_kategori']);
    $kode_kategori = mysqli_real_escape_string($koneksi, $_POST['kode_kategori']);
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    
    $query = "INSERT INTO kategori (id_kategori, kode_kategori, nama_kategori) 
              VALUES ('$id_kategori', '$kode_kategori', '$nama_kategori')";
    
    if(mysqli_query($koneksi, $query)) {
        echo "<script>alert('✅ Kategori berhasil ditambahkan!'); window.location='kelola_kategori.php';</script>";
        exit;
    } else {
        $error = "❌ Gagal: " . mysqli_error($koneksi);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Kategori</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Arial, sans-serif; }
        body { background:#f0f8ff; padding:20px; }
        .container { max-width:500px; margin:50px auto; background:white; padding:30px; border-radius:16px; box-shadow:0 5px 20px rgba(0,0,0,0.1); }
        h2 { color:#0077b6; margin-bottom:20px; text-align:center; }
        .form-group { margin-bottom:15px; }
        label { display:block; margin-bottom:5px; font-weight:600; }
        input { width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; }
        button { width:100%; padding:12px; background:#0077b6; color:white; border:none; border-radius:8px; font-weight:bold; cursor:pointer; }
        .btn-back { display:inline-block; margin-top:15px; padding:10px 20px; background:#6c757d; color:white; text-decoration:none; border-radius:8px; text-align:center; }
        .info-box { background:#e0f2fe; padding:10px; border-radius:8px; margin-bottom:15px; text-align:center; font-size:14px; }
        .error { background:#fee2e2; color:#dc2626; padding:10px; border-radius:8px; margin-bottom:15px; }
    </style>
</head>
<body>
<div class="container">
    <h2>➕ Tambah Kategori</h2>
    
    <div class="info-box">
        🔑 ID Kategori: <strong><?= $id_kategori; ?></strong> | 
        Kode: <strong><?= $kode_kategori; ?></strong>
    </div>
    
    <?php if(isset($error)): ?>
        <div class="error">⚠️ <?= $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>ID Kategori</label>
            <input type="text" name="id_kategori" value="<?= $id_kategori; ?>" readonly style="background:#f0f0f0;">
        </div>
        <div class="form-group">
            <label>Kode Kategori</label>
            <input type="text" name="kode_kategori" value="<?= $kode_kategori; ?>" readonly style="background:#f0f0f0;">
        </div>
        <div class="form-group">
            <label>Nama Kategori</label>
            <input type="text" name="nama_kategori" required placeholder="Contoh: Komputer, Proyektor, Mouse, Keyboard">
        </div>
        <button type="submit" name="simpan">💾 Simpan</button>
        <a href="kelola_kategori.php" class="btn-back">← Kembali</a>
    </form>
</div>
</body>
</html>