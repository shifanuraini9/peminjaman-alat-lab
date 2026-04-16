<?php
session_start();

// Anti Back
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// CEK LOGIN DAN ROLE
if(!isset($_SESSION['nama'])) {
    header("Location: ../login.php");
    exit();
}

if($_SESSION['role'] != 'petugas') {
    if($_SESSION['role'] == 'admin') {
        header("Location: ../admin/dashboard.php");
        exit();
    }
    if($_SESSION['role'] == 'user') {
        header("Location: ../user/beranda.php");
        exit();
    }
    session_destroy();
    header("Location: ../login.php");
    exit();
}

include "../koneksi.php";

// Ambil data laporan
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : '';

if($filter == 'range' && $tanggal_mulai && $tanggal_selesai) {
    $query = mysqli_query($koneksi, "SELECT p.*, a.nama_alat, u.nama as nama_peminjam 
                                     FROM peminjaman p 
                                     JOIN alat a ON p.id_alat = a.id_alat 
                                     JOIN users u ON p.username = u.username
                                     WHERE DATE(p.tanggal_pinjam) BETWEEN '$tanggal_mulai' AND '$tanggal_selesai'
                                     ORDER BY p.tanggal_pinjam DESC");
} else {
    $query = mysqli_query($koneksi, "SELECT p.*, a.nama_alat, u.nama as nama_peminjam 
                                     FROM peminjaman p 
                                     JOIN alat a ON p.id_alat = a.id_alat 
                                     JOIN users u ON p.username = u.username
                                     ORDER BY p.tanggal_pinjam DESC");
}

$username = $_SESSION['nama'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Laporan - Petugas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        body {
            background: #f0f8ff;
            min-height: 100vh;
        }
        
        /* SIDEBAR RESPONSIF */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #0284c7, #0369a1);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 20px;
            margin-bottom: 30px;
        }
        
        .sidebar-header .logo {
            font-size: 20px;
            font-weight: 700;
        }
        
        .sidebar-header .role {
            font-size: 12px;
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 8px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
        }
        
        .sidebar-menu .menu-icon {
            margin-right: 12px;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 0 20px;
        }
        
        .sidebar-footer a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
        }
        
        .top-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .page-title {
            font-size: 20px;
            font-weight: 600;
            color: #0f172a;
        }
        
        .btn-pdf {
            background: #dc2626;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
        }
        
        .btn-pdf:hover {
            background: #b91c1c;
        }
        
        .filter-box {
            background: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .btn-filter {
            background: #0077b6;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .table-responsive {
            overflow-x: auto;
            background: white;
            border-radius: 12px;
            padding: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        th {
            background: #0077b6;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        footer {
            text-align: center;
            padding: 15px;
            color: #666;
            font-size: 11px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }
            .sidebar-menu a {
                padding: 8px 12px;
            }
            .sidebar-footer {
                position: relative;
                margin-top: 15px;
            }
            .main-content {
                margin-left: 0;
            }
            .filter-form {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <span class="logo">🔬 Petugas Lab</span>
        <span class="role"><?= htmlspecialchars($username); ?></span>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><span class="menu-icon">📊</span> Dashboard</a></li>
        <li><a href="pengajuan.php"><span class="menu-icon">📋</span> Pengajuan</a></li>
        <li><a href="peminjaman.php"><span class="menu-icon">🔄</span> Peminjaman</a></li>
        <li><a href="pengembalian.php"><span class="menu-icon">↩️</span> Pengembalian</a></li>
        <li><a href="laporan.php" class="active"><span class="menu-icon">📊</span> Laporan</a></li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="../logout.php"><span class="menu-icon">🚪</span> Logout</a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <div class="page-title">📊 Laporan Peminjaman</div>
        <a href="download_laporan.php?filter=<?= $filter; ?>&tanggal_mulai=<?= $tanggal_mulai; ?>&tanggal_selesai=<?= $tanggal_selesai; ?>" class="btn-pdf">📄 Download PDF</a>
    </div>
    
    <div class="filter-box">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>Filter</label>
                <select name="filter" id="filter">
                    <option value="semua" <?= $filter == 'semua' ? 'selected' : ''; ?>>Semua Data</option>
                    <option value="range" <?= $filter == 'range' ? 'selected' : ''; ?>>Rentang Tanggal</option>
                </select>
            </div>
            <div class="filter-group" id="rangeTanggal" style="display: <?= $filter == 'range' ? 'flex' : 'none'; ?>;">
                <label>Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" value="<?= $tanggal_mulai; ?>">
            </div>
            <div class="filter-group" id="rangeTanggal2" style="display: <?= $filter == 'range' ? 'flex' : 'none'; ?>;">
                <label>Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" value="<?= $tanggal_selesai; ?>">
            </div>
            <button type="submit" class="btn-filter">🔍 Tampilkan</button>
        </form>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Peminjam</th>
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
                if(mysqli_num_rows($query) > 0):
                    while($row = mysqli_fetch_assoc($query)): 
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['nama_peminjam']); ?></td>
                    <td><?= htmlspecialchars($row['nama_alat']); ?></td>
                    <td><?= $row['jumlah']; ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tanggal_pinjam'])); ?></td>
                    <td><?= date('d/m/Y', strtotime($row['tanggal_kembali'])); ?></td>
                    <td>
                        <?php
                        if($row['status'] == 'Menunggu') echo '<span class="badge" style="background:#fef3c7; color:#92400e;">Menunggu</span>';
                        elseif($row['status'] == 'Disetujui') echo '<span class="badge" style="background:#dbeafe; color:#1e40af;">Disetujui</span>';
                        elseif($row['status'] == 'Dikembalikan') echo '<span class="badge" style="background:#dcfce7; color:#166534;">Dikembalikan</span>';
                        else echo '<span class="badge" style="background:#fee2e2; color:#991b1b;">Ditolak</span>';
                        ?>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:40px;">Belum ada data peminjaman</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <footer>&copy; <?= date('Y'); ?> Peminjaman Alat Laboratorium</footer>
</div>

<script>
    document.getElementById('filter').addEventListener('change', function() {
        if(this.value == 'range') {
            document.getElementById('rangeTanggal').style.display = 'flex';
            document.getElementById('rangeTanggal2').style.display = 'flex';
        } else {
            document.getElementById('rangeTanggal').style.display = 'none';
            document.getElementById('rangeTanggal2').style.display = 'none';
        }
    });
</script>

</body>
</html>