<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("location:../login.php");
    exit;
}

$awal  = $_GET['awal'] ?? '';
$akhir = $_GET['akhir'] ?? '';

$where = "";
if ($awal && $akhir) {
    $where = "WHERE p.tanggal_pinjam BETWEEN '$awal' AND '$akhir'";
}

$query = mysqli_query($koneksi, "
    SELECT p.*, a.nama_alat
    FROM peminjaman p
    JOIN alat a ON p.id_alat = a.id_alat
    $where
    ORDER BY p.id DESC
");

// Hitung statistik
$total_peminjaman = mysqli_num_rows($query);
$total_dipinjam = 0;
$total_dikembalikan = 0;
$total_ditolak = 0;

if ($total_peminjaman > 0) {
    mysqli_data_seek($query, 0);
    while($row = mysqli_fetch_assoc($query)) {
        if($row['status'] == 'Disetujui') $total_dipinjam++;
        elseif($row['status'] == 'Dikembalikan') $total_dikembalikan++;
        elseif($row['status'] == 'Ditolak') $total_ditolak++;
    }
    mysqli_data_seek($query, 0);
}

$total_jumlah = 0;
if ($total_peminjaman > 0) {
    mysqli_data_seek($query, 0);
    while($row = mysqli_fetch_assoc($query)) {
        if($row['status'] == 'Disetujui' || $row['status'] == 'Dikembalikan') {
            $total_jumlah += $row['jumlah'];
        }
    }
    mysqli_data_seek($query, 0);
}

$username = $_SESSION['nama'] ?? 'Petugas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Laporan Peminjaman - Petugas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f1f5f9;
            min-height: 100vh;
        }

        /* ===== SIDEBAR RESPONSIF ===== */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #0284c7, #0369a1);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            z-index: 100;
        }

        .sidebar-header {
            padding: 0 20px;
            margin-bottom: 30px;
        }

        .sidebar-header .logo {
            font-size: 20px;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }

        .sidebar-header .role {
            font-size: 12px;
            opacity: 0.9;
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 8px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            font-size: 14px;
        }

        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: white;
        }

        .sidebar-menu a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: white;
        }

        .sidebar-menu .menu-icon {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
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
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .sidebar-footer a:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
        }

        /* ===== PAGE HEADER ===== */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .page-header h1 {
            color: #0369a1;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .date-info {
            color: #64748b;
            font-size: 12px;
            background: #f8fafc;
            padding: 6px 12px;
            border-radius: 20px;
        }

        /* ===== STATS CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            border-radius: 12px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            background: rgba(255,255,255,0.2);
        }

        .stat-info h4 {
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            color: rgba(255,255,255,0.9);
        }

        .stat-info p {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.2;
            color: white;
        }

        .stat-desc {
            font-size: 10px;
            color: rgba(255,255,255,0.7);
        }

        /* ===== CARD ===== */
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }

        .card-header h3 {
            color: #0369a1;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ===== FILTER FORM RESPONSIF ===== */
        .filter-form {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
            background: #f8fafc;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .form-group input {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            width: 160px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 13px;
        }

        .btn-primary {
            background: #0284c7;
            color: white;
        }

        .btn-primary:hover {
            background: #0369a1;
        }

        .btn-secondary {
            background: #64748b;
            color: white;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn-success {
            background: #16a34a;
            color: white;
        }

        .btn-success:hover {
            background: #15803d;
        }

        .btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-left: auto;
        }

        /* ===== TABLE RESPONSIF ===== */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .table th {
            background: #f8fafc;
            color: #0f172a;
            font-weight: 600;
            padding: 12px 10px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
            font-size: 12px;
        }

        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-size: 12px;
        }

        .table tr:hover td {
            background: #f1f5f9;
        }

        .empty {
            text-align: center;
            padding: 40px !important;
            color: #64748b;
            font-style: italic;
        }

        /* ===== BADGE ===== */
        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }

        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }

        /* ===== FOOTER ===== */
        footer {
            text-align: center;
            padding: 15px;
            color: #64748b;
            font-size: 11px;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        /* ===== PRINT STYLE ===== */
        @media print {
            .sidebar, .page-header, .sidebar-footer, .stats-grid, .filter-form, footer, .btn-group {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }

        /* ========== RESPONSIVE HP ========== */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 10px 0;
            }
            
            .sidebar-header {
                text-align: center;
                margin-bottom: 15px;
            }
            
            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 5px;
                padding: 0 10px;
            }
            
            .sidebar-menu li {
                display: inline-block;
                margin-bottom: 0;
            }
            
            .sidebar-menu a {
                padding: 8px 12px;
                border-left: none;
                border-radius: 8px;
                font-size: 12px;
            }
            
            .sidebar-menu a.active {
                border-left: none;
                background: rgba(255,255,255,0.3);
            }
            
            .sidebar-footer {
                position: relative;
                bottom: auto;
                margin-top: 15px;
                text-align: center;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .page-header h1 {
                font-size: 18px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .stat-card {
                padding: 12px;
            }
            
            .stat-info p {
                font-size: 18px;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-group input {
                width: 100%;
            }
            
            .btn-group {
                margin-left: 0;
                width: 100%;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar-menu a {
                padding: 6px 10px;
                font-size: 11px;
            }
            
            .sidebar-menu .menu-icon {
                font-size: 14px;
                margin-right: 5px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .table th, .table td {
                padding: 8px 6px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <span class="logo">🔬 Peminjaman Lab</span>
        <span class="role">👤 <?= htmlspecialchars($username); ?></span>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><span class="menu-icon">📊</span> Dashboard</a></li>
        <li><a href="pengajuan_peminjaman.php"><span class="menu-icon">📝</span> Pengajuan Peminjaman</a></li>
        <li><a href="pengembalian.php"><span class="menu-icon">↩️</span> Pengembalian Alat</a></li>
        <li><a href="laporan.php" class="active"><span class="menu-icon">📄</span> Laporan</a></li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="../logout.php"><span class="menu-icon">🚪</span> Logout</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    
    <div class="page-header">
        <h1><span>📄</span> Laporan Peminjaman</h1>
        <div class="date-info"><?= date('l, d F Y'); ?></div>
    </div>
    
    <!-- STATS CARDS -->
    <div class="stats-grid">
        <div class="stat-card" style="background: linear-gradient(135deg, #0284c7, #0369a1);">
            <div class="stat-icon">📋</div>
            <div class="stat-info">
                <h4>Total Transaksi</h4>
                <p><?= $total_peminjaman; ?></p>
                <div class="stat-desc">Semua peminjaman</div>
            </div>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <h4>Dipinjam</h4>
                <p><?= $total_dipinjam; ?></p>
                <div class="stat-desc">Sedang dipinjam</div>
            </div>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #27ae60, #229954);">
            <div class="stat-icon">↩️</div>
            <div class="stat-info">
                <h4>Dikembalikan</h4>
                <p><?= $total_dikembalikan; ?></p>
                <div class="stat-desc">Selesai</div>
            </div>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #c0392b, #a93226);">
            <div class="stat-icon">❌</div>
            <div class="stat-info">
                <h4>Ditolak</h4>
                <p><?= $total_ditolak; ?></p>
                <div class="stat-desc">Tidak disetujui</div>
            </div>
        </div>
    </div>
    
    <!-- MAIN CARD -->
    <div class="card">
        <div class="card-header">
            <h3><span>📊</span> Laporan Peminjaman</h3>
            <?php if($awal && $akhir): ?>
            <span class="badge badge-info">Periode: <?= date('d/m/Y', strtotime($awal)); ?> - <?= date('d/m/Y', strtotime($akhir)); ?></span>
            <?php endif; ?>
        </div>
        
        <!-- FILTER FORM -->
        <form method="get" class="filter-form">
            <div class="form-group">
                <label>Tanggal Awal</label>
                <input type="date" name="awal" value="<?= $awal ?>" required>
            </div>
            <div class="form-group">
                <label>Tanggal Akhir</label>
                <input type="date" name="akhir" value="<?= $akhir ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <?php if($awal && $akhir): ?>
                <a href="laporan.php" class="btn btn-secondary">↻ Reset</a>
                <div class="btn-group">
                    <a href="cetak_pdf.php?awal=<?= $awal ?>&akhir=<?= $akhir ?>" class="btn btn-success" target="_blank">📥 PDF</a>
                </div>
            <?php endif; ?>
        </form>

        <!-- TABLE -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Peminjam</th>
                        <th>Alat</th>
                        <th>Jumlah</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(mysqli_num_rows($query) == 0): ?>
                    <tr>
                        <td colspan="7" class="empty">
                            📭 Tidak ada data peminjaman
                            <?php if($awal && $akhir): ?>
                                pada periode <?= date('d/m/Y', strtotime($awal)); ?> - <?= date('d/m/Y', strtotime($akhir)); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; ?>
                    <?php while($d = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><strong><?= htmlspecialchars($d['username']); ?></strong></td>
                        <td><?= htmlspecialchars($d['nama_alat']); ?></td>
                        <td><?= $d['jumlah']; ?> unit</td>
                        <td><?= date('d/m/Y', strtotime($d['tanggal_pinjam'])); ?></td>
                        <td><?= $d['tanggal_kembali'] ? date('d/m/Y', strtotime($d['tanggal_kembali'])) : '-'; ?></td>
                        <td>
                            <?php
                                if($d['status'] == 'Disetujui'){
                                    echo '<span class="badge badge-pending">📌 Dipinjam</span>';
                                } elseif($d['status'] == 'Dikembalikan'){
                                    echo '<span class="badge badge-success">✅ Dikembalikan</span>';
                                } elseif($d['status'] == 'Ditolak'){
                                    echo '<span class="badge badge-danger">❌ Ditolak</span>';
                                } elseif($d['status'] == 'Menunggu'){
                                    echo '<span class="badge badge-info">⏳ Menunggu</span>';
                                }
                            ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- TOTAL KESELURUHAN -->
        <?php if(mysqli_num_rows($query) > 0): ?>
        <div style="margin-top: 15px; padding: 12px; background: #f8fafc; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <span><strong>Total Data:</strong> <?= mysqli_num_rows($query); ?> transaksi</span>
                <span><strong>Total Alat Dipinjam:</strong> <?= $total_jumlah; ?> unit</span>
            </div>
            <?php if($awal && $akhir): ?>
            <span style="color: #0284c7; font-weight: 500; font-size: 12px;">
                Periode: <?= date('d/m/Y', strtotime($awal)); ?> - <?= date('d/m/Y', strtotime($akhir)); ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <footer>
        © <?= date('Y'); ?> Peminjaman Alat Laboratorium<br>
        <span style="font-size: 10px;">Laporan digenerate: <?= date('d/m/Y H:i:s'); ?> | Petugas: <?= htmlspecialchars($username); ?></span>
    </footer>
    
</div>

</body>
</html>