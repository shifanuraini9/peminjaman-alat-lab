<?php
session_start();

// ========== CEK LOGIN ==========
if(!isset($_SESSION['nama'])) {
    header("Location: ../login.php");
    exit();
}

// ========== CEK ROLE ==========
if($_SESSION['role'] != 'user') {
    if($_SESSION['role'] == 'admin') {
        header("Location: ../admin/dashboard.php");
        exit();
    }
    if($_SESSION['role'] == 'petugas') {
        header("Location: ../petugas/dashboard.php");
        exit();
    }
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// Cek apakah ada data konfirmasi
if(!isset($_SESSION['konfirmasi'])) {
    header("Location: peminjaman.php");
    exit();
}

include "../koneksi.php";

if(!$koneksi) {
    die("Koneksi database gagal");
}

$username = $_SESSION['nama'];
$data = $_SESSION['konfirmasi'];

$id_alat = $data['id_alat'];
$nama_alat = $data['nama_alat'];
$jumlah = $data['jumlah'];
$tanggal_pinjam = $data['tanggal_pinjam'];
$tanggal_kembali = $data['tanggal_kembali'];

// Proses konfirmasi (user menyetujui)
if(isset($_POST['konfirmasi'])) {
    // Simpan ke database dengan status 'Menunggu'
    $query = "INSERT INTO peminjaman (username, id_alat, jumlah, tanggal_pinjam, tanggal_kembali, status) 
              VALUES (?, ?, ?, ?, ?, 'Menunggu')";
    
    $stmt_insert = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt_insert, "ssiss", $username, $id_alat, $jumlah, $tanggal_pinjam, $tanggal_kembali);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        // Hapus session konfirmasi
        unset($_SESSION['konfirmasi']);
        
        $_SESSION['pesan'] = "✅ Pengajuan peminjaman $nama_alat berhasil! Menunggu konfirmasi petugas.";
        $_SESSION['pesan_tipe'] = "success";
        header("Location: riwayat_peminjaman.php");
        exit;
    } else {
        $error = "❌ Gagal menyimpan: " . mysqli_error($koneksi);
    }
}

// Proses batal
if(isset($_POST['batal'])) {
    unset($_SESSION['konfirmasi']);
    header("Location: peminjaman.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Peminjaman</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f1f5f9;
            color: #0f172a;
        }

        nav {
            background: linear-gradient(90deg, #0284c7, #0369a1);
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            color: white;
            gap: 10px;
        }
        nav .logo {
            font-weight: bold;
            font-size: 16px;
        }
        nav div:last-child {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
        }
        nav a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 500px;
            margin: 40px auto;
            padding: 20px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            color: #0369a1;
            margin-bottom: 20px;
            text-align: center;
        }

        .info-peminjaman {
            background: #e0f2fe;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .info-peminjaman p {
            margin-bottom: 8px;
        }

        .info-peminjaman .label {
            font-weight: 600;
            color: #0f172a;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-konfirmasi {
            flex: 1;
            padding: 12px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-konfirmasi:hover {
            background: #059669;
        }

        .btn-batal {
            flex: 1;
            padding: 12px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-batal:hover {
            background: #dc2626;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 13px;
        }

        footer {
            text-align: center;
            padding: 15px;
            color: #64748b;
            font-size: 11px;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            nav { flex-direction: column; text-align: center; }
            .container { margin: 20px auto; padding: 15px; }
            .card { padding: 20px; }
            .btn-group { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>

<nav>
    <div class="logo">🔬 Peminjaman Alat Lab</div>
    <div>
        <a href="beranda.php">Beranda</a>
        <a href="daftar_alat.php">Daftar Alat</a>
        <a href="peminjaman.php">Pinjam Alat</a>
        <a href="riwayat_peminjaman.php">Riwayat</a>
        <a href="../logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="card">
        <h2>📋 Konfirmasi Peminjaman</h2>
        
        <?php if(isset($error)): ?>
            <div class="alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="info-peminjaman">
            <p><span class="label">👤 Peminjam:</span> <?= htmlspecialchars($username) ?></p>
            <p><span class="label">🔧 Alat:</span> <?= htmlspecialchars($nama_alat) ?></p>
            <p><span class="label">🔢 Jumlah:</span> <?= $jumlah ?> unit</p>
            <p><span class="label">📅 Tanggal Pinjam:</span> <?= date('d-m-Y H:i', strtotime($tanggal_pinjam)) ?></p>
            <p><span class="label">📅 Tanggal Kembali:</span> <?= date('d-m-Y H:i', strtotime($tanggal_kembali)) ?></p>
            <p><span class="label">⏱️ Durasi:</span> <?= round((strtotime($tanggal_kembali) - strtotime($tanggal_pinjam)) / 3600, 1) ?> jam</p>
        </div>
        
        <div class="hint-box" style="background:#f0f9ff; padding:10px; border-radius:10px; font-size:12px; color:#0369a1;">
            ⚠️ Pastikan data peminjaman sudah benar. Setelah dikonfirmasi, akan menunggu persetujuan petugas.
        </div>
        
        <form method="POST">
            <div class="btn-group">
                <button type="submit" name="konfirmasi" class="btn-konfirmasi">✅ Konfirmasi & Ajukan</button>
                <button type="submit" name="batal" class="btn-batal">❌ Batal</button>
            </div>
        </form>
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Peminjaman Alat Laboratorium
</footer>

</body>
</html>