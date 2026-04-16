<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Hitung statistik aktivitas
$total_menunggu = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE status='Menunggu'"));
$total_disetujui = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE status='Disetujui'"));
$total_dikembalikan = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE status='Dikembalikan'"));
$total_ditolak = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE status='Ditolak'"));

// Hitung aktivitas hari ini
$hari_ini = date('Y-m-d');
$aktivitas_hari_ini = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE DATE(tanggal_pinjam) = '$hari_ini'"));

// Filter
$filter_tanggal = $_GET['tanggal'] ?? '';
$filter_user = $_GET['user'] ?? '';
$filter_status = $_GET['status'] ?? '';

$where = "";
if($filter_tanggal) {
    $where .= " AND DATE(p.tanggal_pinjam) = '$filter_tanggal'";
}
if($filter_user) {
    $where .= " AND p.username LIKE '%$filter_user%'";
}
if($filter_status) {
    $where .= " AND p.status = '$filter_status'";
}

// Ambil aktivitas dari tabel peminjaman
$log = mysqli_query($koneksi, "
    SELECT 
        p.id,
        p.username,
        p.status as tipe,
        p.tanggal_pinjam as tanggal,
        p.tanggal_kembali,
        a.nama_alat,
        a.id_alat,
        CONCAT(
            p.username, ' ',
            CASE 
                WHEN p.status = 'Menunggu' THEN 'mengajukan peminjaman'
                WHEN p.status = 'Disetujui' THEN 'meminjam'
                WHEN p.status = 'Dikembalikan' THEN 'mengembalikan'
                WHEN p.status = 'Ditolak' THEN 'pengajuan ditolak'
            END,
            ' ', a.nama_alat
        ) as aktivitas
    FROM peminjaman p
    JOIN alat a ON p.id_alat = a.id_alat
    WHERE 1=1 $where
    ORDER BY p.id DESC
");

$total_log = mysqli_num_rows($log);
$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f8fafc;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0284c7, #0369a1);
            color: white;
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 0 25px;
            margin-bottom: 40px;
        }

        .sidebar-header .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }

        .sidebar-header .role {
            font-size: 14px;
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 10px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: white;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.15);
            border-left-color: white;
        }

        .sidebar-menu .menu-icon {
            margin-right: 12px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 30px;
            width: 100%;
            padding: 0 25px;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            transition: all 0.3s;
        }

        .sidebar-footer a:hover {
            background: rgba(255,255,255,0.2);
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }

        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .page-title {
            font-size: 24px;
            color: #0f172a;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-email {
            color: #0284c7;
            background: #e0f2fe;
            padding: 8px 16px;
            border-radius: 30px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #0284c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border-bottom: 3px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .stat-card:nth-child(1) {
            border-bottom-color: #3b82f6;
            background: linear-gradient(135deg, #ffffff 0%, #dbeafe 100%);
        }
        .stat-card:nth-child(2) {
            border-bottom-color: #f59e0b;
            background: linear-gradient(135deg, #ffffff 0%, #fef3c7 100%);
        }
        .stat-card:nth-child(3) {
            border-bottom-color: #10b981;
            background: linear-gradient(135deg, #ffffff 0%, #d1fae5 100%);
        }
        .stat-card:nth-child(4) {
            border-bottom-color: #8b5cf6;
            background: linear-gradient(135deg, #ffffff 0%, #ede9fe 100%);
        }
        .stat-card:nth-child(5) {
            border-bottom-color: #ef4444;
            background: linear-gradient(135deg, #ffffff 0%, #fee2e2 100%);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .stat-title {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 24px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5px;
        }

        .stat-desc {
            color: #94a3b8;
            font-size: 12px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h3 {
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header h3 span {
            color: #0284c7;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .filter-group {
            flex: 1;
            min-width: 150px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            color: #64748b;
            font-weight: 500;
            font-size: 12px;
        }

        .filter-group input, .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2,132,199,0.1);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #0284c7;
            color: white;
        }

        .btn-primary:hover {
            background: #0369a1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(2,132,199,0.3);
        }

        .btn-secondary {
            background: #64748b;
            color: white;
        }

        .btn-secondary:hover {
            background: #475569;
            transform: translateY(-2px);
        }

        .btn-reset {
            background: #94a3b8;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }

        .btn-reset:hover {
            background: #64748b;
            transform: translateY(-2px);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 10px;
            background: #f8fafc;
            color: #0f172a;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 15px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .badge-menunggu { background: #fef3c7; color: #92400e; }
        .badge-disetujui { background: #dbeafe; color: #1e40af; }
        .badge-dikembalikan { background: #dcfce7; color: #166534; }
        .badge-ditolak { background: #fee2e2; color: #991b1b; }

        .empty-state {
            text-align: center;
            padding: 60px !important;
            color: #94a3b8;
        }

        .info-note {
            background: #e0f2fe;
            color: #0369a1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            font-size: 13px;
            border-top: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <span class="logo">Admin Lab</span>
        <span class="role"><?= htmlspecialchars($username); ?></span>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php">
            <span class="menu-icon">📊</span> Dashboard
        </a>
        <a href="kelola_user.php">
            <span class="menu-icon">👥</span> Kelola User
        </a>
        <a href="kelola_alat.php">
            <span class="menu-icon">🔧</span> Kelola Alat
        </a>
        <a href="kelola_kategori.php">
            <span class="menu-icon">📂</span> Kelola Kategori
        </a>
        <a href="log_aktivitas.php" class="active">
            <span class="menu-icon">📋</span> Log Aktivitas
        </a>
    </div>
    
    <div class="sidebar-footer">
        <a href="../logout.php">
            <span class="menu-icon">🚪</span> Logout
        </a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <div class="page-title">📋 Log Aktivitas Peminjaman</div>
        <div class="user-info">
            <div class="user-email"><?= htmlspecialchars($username); ?></div>
            <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)); ?></div>
        </div>
    </div>

    <!-- Info Note -->
    <div class="info-note">
        <span>ℹ️</span>
        <span>Menampilkan semua aktivitas peminjaman alat laboratorium</span>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Hari Ini</span>
                <span class="stat-icon">📅</span>
            </div>
            <div class="stat-value"><?= $aktivitas_hari_ini; ?></div>
            <div class="stat-desc">Aktivitas hari ini</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Menunggu</span>
                <span class="stat-icon">⏳</span>
            </div>
            <div class="stat-value"><?= $total_menunggu; ?></div>
            <div class="stat-desc">Pengajuan baru</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Disetujui</span>
                <span class="stat-icon">✅</span>
            </div>
            <div class="stat-value"><?= $total_disetujui; ?></div>
            <div class="stat-desc">Sedang dipinjam</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Dikembalikan</span>
                <span class="stat-icon">↩️</span>
            </div>
            <div class="stat-value"><?= $total_dikembalikan; ?></div>
            <div class="stat-desc">Selesai</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Ditolak</span>
                <span class="stat-icon">❌</span>
            </div>
            <div class="stat-value"><?= $total_ditolak; ?></div>
            <div class="stat-desc">Pengajuan ditolak</div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><span>📋</span> Riwayat Aktivitas (Total: <?= $total_log; ?>)</h3>
        </div>
        
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>📅 Tanggal</label>
                <input type="date" name="tanggal" value="<?= $filter_tanggal; ?>">
            </div>
            <div class="filter-group">
                <label>👤 Username</label>
                <input type="text" name="user" value="<?= htmlspecialchars($filter_user); ?>" placeholder="Cari username...">
            </div>
            <div class="filter-group">
                <label>📊 Status</label>
                <select name="status">
                    <option value="">Semua Status</option>
                    <option value="Menunggu" <?= $filter_status == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                    <option value="Disetujui" <?= $filter_status == 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                    <option value="Dikembalikan" <?= $filter_status == 'Dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
                    <option value="Ditolak" <?= $filter_status == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
                <a href="log_aktivitas.php" class="btn-reset">↻ Reset</a>
            </div>
        </form>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Aktivitas</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($total_log == 0): ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                                <div style="font-size: 18px; margin-bottom: 10px;">Belum ada aktivitas</div>
                                <p style="color: #94a3b8;">Tidak ada data yang sesuai dengan filter</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        while($row = mysqli_fetch_assoc($log)): 
                            $badge_class = 'badge-menunggu';
                            if($row['tipe'] == 'Disetujui') $badge_class = 'badge-disetujui';
                            elseif($row['tipe'] == 'Dikembalikan') $badge_class = 'badge-dikembalikan';
                            elseif($row['tipe'] == 'Ditolak') $badge_class = 'badge-ditolak';
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong><?= htmlspecialchars($row['username']); ?></strong></td>
                            <td><?= htmlspecialchars($row['aktivitas']); ?></td>
                            <td><span class="badge <?= $badge_class; ?>"><?= $row['tipe']; ?></span></td>
                            <td><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <footer>
        © <?= date('Y'); ?> Sistem Informasi Peminjaman Alat Laboratorium
    </footer>
</div>

</body>
</html>