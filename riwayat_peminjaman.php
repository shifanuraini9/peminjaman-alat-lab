<?php
session_start();

// ========== CEK LOGIN - PASTIKAN SUDAH LOGIN ==========
if(!isset($_SESSION['nama'])) {
    header('location:../login.php');
    exit();
}

// ========== CEK ROLE - HANYA USER YANG BOLEH AKSES ==========
if($_SESSION['role'] != 'user') {
    if($_SESSION['role'] == 'admin') {
        header('location:../admin/dashboard.php');
        exit();
    }
    if($_SESSION['role'] == 'petugas') {
        header('location:../petugas/dashboard.php');
        exit();
    }
    session_destroy();
    header('location:../login.php');
    exit();
}

include "../koneksi.php";

if(!$koneksi) {
    die("Koneksi database gagal");
}

$username = $_SESSION['nama'];

$cek_user = mysqli_prepare($koneksi, "SELECT * FROM users WHERE username = ?");
mysqli_stmt_bind_param($cek_user, "s", $username);
mysqli_stmt_execute($cek_user);
$user_result = mysqli_stmt_get_result($cek_user);
$user_valid = mysqli_fetch_assoc($user_result);

if(!$user_valid) {
    session_destroy();
    header('location:../login.php');
    exit();
}

$query = mysqli_prepare($koneksi, "SELECT p.*, a.nama_alat 
                                   FROM peminjaman p 
                                   JOIN alat a ON p.id_alat = a.id_alat 
                                   WHERE p.username = ? 
                                   ORDER BY p.tanggal_pinjam DESC");
mysqli_stmt_bind_param($query, "s", $username);
mysqli_stmt_execute($query);
$result = mysqli_stmt_get_result($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Riwayat Peminjaman</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        
        body {
            background: #f0f8ff;
        }
        
        /* ===== NAVBAR RESPONSIF ===== */
        nav {
            background: #0077b6;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            color: white;
            gap: 10px;
        }
        nav b {
            font-size: 16px;
        }
        nav ul {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
        }
        nav ul li a:hover {
            text-decoration: underline;
        }
        
        /* ===== CONTAINER ===== */
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 15px;
        }
        
        h2 {
            color: #0077b6;
            margin-bottom: 20px;
            font-size: 22px;
        }
        
        /* ===== TABEL RESPONSIF ===== */
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            box-shadow: 0 5px 20px rgba(0,0,0,.1);
            border-radius: 12px;
            overflow: hidden;
        }
        
        th {
            background: #0077b6;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 14px;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        
        .status-Menunggu {
            background: #fff3cd;
            color: #856404;
            padding: 5px 12px;
            border-radius: 20px;
            display: inline-block;
            font-size: 12px;
            font-weight: bold;
        }
        .status-Disetujui {
            background: #d4edda;
            color: #155724;
            padding: 5px 12px;
            border-radius: 20px;
            display: inline-block;
            font-size: 12px;
            font-weight: bold;
        }
        .status-Dikembalikan {
            background: #e2e3e5;
            color: #383d41;
            padding: 5px 12px;
            border-radius: 20px;
            display: inline-block;
            font-size: 12px;
            font-weight: bold;
        }
        
        .btn-kembali {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #0077b6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            transition: background 0.3s;
        }
        .btn-kembali:hover {
            background: #005f92;
        }
        
        /* ========== RESPONSIVE HP ========== */
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                text-align: center;
            }
            nav ul {
                justify-content: center;
            }
            
            .container {
                padding: 10px;
                margin: 10px auto;
            }
            
            h2 {
                font-size: 18px;
                margin-bottom: 15px;
            }
            
            /* TABLE JADI CARD VIEW */
            table, thead, tbody, th, td, tr {
                display: block;
            }
            
            thead {
                display: none;
            }
            
            tr {
                margin-bottom: 15px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 3px 10px rgba(0,0,0,0.1);
                padding: 10px;
            }
            
            td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 12px;
                border-bottom: 1px solid #eee;
                font-size: 13px;
            }
            
            td:before {
                content: attr(data-label);
                font-weight: bold;
                width: 40%;
                color: #0077b6;
            }
            
            td:last-child {
                border-bottom: none;
            }
            
            .btn-kembali {
                padding: 8px 16px;
                font-size: 13px;
            }
        }
        
        /* HP KECIL */
        @media (max-width: 480px) {
            nav b {
                font-size: 14px;
            }
            nav ul li a {
                font-size: 11px;
            }
            
            h2 {
                font-size: 16px;
            }
            
            td {
                font-size: 12px;
                padding: 8px 10px;
            }
            
            td:before {
                font-size: 12px;
            }
            
            .status-Menunggu, .status-Disetujui, .status-Dikembalikan {
                font-size: 10px;
                padding: 3px 8px;
            }
            
            .btn-kembali {
                padding: 7px 14px;
                font-size: 12px;
            }
        }
        
        /* LAPTOP */
        @media (min-width: 1024px) {
            .container {
                margin: 40px auto;
                padding: 20px;
            }
            h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<nav>
    <b>🔬 Peminjaman Alat Lab</b>
    <ul>
        <li><a href="beranda.php">Beranda</a></li>
        <li><a href="daftar_alat.php">Daftar Alat</a></li>
        <li><a href="peminjaman.php">Pinjam Alat</a></li>
        <li><a href="riwayat_peminjaman.php">Riwayat Peminjaman</a></li>
    </ul>
</nav>

<div class="container">
    <h2>📋 Riwayat Peminjaman</h2>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Alat</th>
                <th>Jumlah</th>
                <th>Tgl Pinjam</th>
                <th>Tgl Kembali</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if(mysqli_num_rows($result) > 0):
                while($row = mysqli_fetch_assoc($result)): 
                    $status_class = 'status-' . $row['status'];
            ?>
            <tr>
                <td data-label="No"><?= $no++ ?></td>
                <td data-label="Nama Alat"><?= htmlspecialchars($row['nama_alat']) ?></td>
                <td data-label="Jumlah"><?= (int)$row['jumlah'] ?></td>
                <td data-label="Tgl Pinjam"><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])) ?></td>
                <td data-label="Tgl Kembali"><?= date('d/m/Y', strtotime($row['tanggal_kembali'])) ?></td>
                <td data-label="Status"><span class="<?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span></td>
            </tr>
            <?php 
                endwhile;
            else:
            ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:30px;">📭 Belum ada peminjaman</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="beranda.php" class="btn-kembali">← Kembali ke Beranda</a>
</div>

</body>
</html>