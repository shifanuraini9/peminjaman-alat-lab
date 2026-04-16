<?php
session_start();
require_once "../security.php";
header("Cache-Control: no-cache, no-store, must-revalidate, private");
header("Pragma: no-cache");
header("Expires: 0");

// ========== CEK LOGIN DAN ROLE ==========
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

$total_users = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM users WHERE role='user'"));
$total_alat = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM alat"));
$total_peminjaman = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM peminjaman"));

$menunggu = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE status='Menunggu'"));
$disetujui = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE status='Disetujui'"));
$dikembalikan = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE status='Dikembalikan'"));
$ditolak = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE status='Ditolak'"));

$hari_ini = date('Y-m-d');
$peminjaman_hari_ini = mysqli_num_rows(mysqli_query($koneksi, "
    SELECT * FROM peminjaman WHERE DATE(tanggal_pinjam) = '$hari_ini'
"));

$aktivitas_terbaru = mysqli_query($koneksi, "
    SELECT 
        p.id,
        p.username,
        p.status,
        p.tanggal_pinjam,
        p.tanggal_kembali,
        a.nama_alat,
        CASE 
            WHEN p.status = 'Menunggu' THEN 'mengajukan peminjaman'
            WHEN p.status = 'Disetujui' THEN 'meminjam'
            WHEN p.status = 'Dikembalikan' THEN 'mengembalikan'
            WHEN p.status = 'Ditolak' THEN 'pengajuan ditolak'
            ELSE p.status
        END as aktivitas_text
    FROM peminjaman p
    JOIN alat a ON p.id_alat = a.id_alat
    ORDER BY p.id DESC 
    LIMIT 5
");

$total_aktivitas = mysqli_num_rows($aktivitas_terbaru);
$username = $_SESSION['nama'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Dashboard Petugas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f8fafc;
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
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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
            color: white;
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
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            font-size: 14px;
        }

        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.15);
            border-left-color: white;
        }

        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
            border-left-color: white;
            font-weight: 600;
        }

        .sidebar-menu .menu-icon {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .sidebar-menu .badge {
            background: #ef4444;
            color: white;
            padding: 2px 6px;
            border-radius: 30px;
            font-size: 10px;
            margin-left: 8px;
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
            transition: all 0.3s;
            font-size: 14px;
        }

        .sidebar-footer a:hover {
            background: rgba(255,255,255,0.2);
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
        }

        /* ===== TOP BAR ===== */
        .top-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .page-title {
            font-size: 20px;
            color: #0f172a;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-email {
            color: #0284c7;
            font-weight: 500;
            background: #e0f2fe;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: #0284c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* ===== WELCOME CARD ===== */
        .welcome-card {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            color: white;
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .welcome-card h2 {
            font-size: 22px;
            margin-bottom: 8px;
        }

        .welcome-card p {
            opacity: 0.9;
            font-size: 13px;
        }

        .welcome-icon {
            font-size: 50px;
            opacity: 0.3;
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-bottom: 3px solid #0284c7;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .stat-title {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5px;
        }

        .stat-desc {
            color: #94a3b8;
            font-size: 11px;
        }

        /* ===== SPECIAL GRID ===== */
        .special-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .special-card {
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .special-card:hover {
            transform: translateY(-3px);
        }

        .special-card.blue { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .special-card.yellow { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .special-card.green { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .special-card.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }

        .special-card .stat-header {
            margin-bottom: 10px;
        }

        .special-card .stat-value {
            color: white;
            font-size: 24px;
        }

        .special-card .stat-title, .special-card .stat-desc {
            color: rgba(255,255,255,0.9);
        }

        /* ===== MENU GRID ===== */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .menu-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #0f172a;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
        }

        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border-color: #0284c7;
        }

        .menu-icon {
            font-size: 35px;
            margin-bottom: 10px;
            color: #0284c7;
        }

        .menu-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .menu-desc {
            color: #64748b;
            font-size: 12px;
        }

        /* ===== ACTIVITY CARD ===== */
        .activity-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
        }

        .activity-header h3 {
            font-size: 16px;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .activity-icon {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .activity-icon.menunggu { background: #fef3c7; color: #92400e; }
        .activity-icon.disetujui { background: #dbeafe; color: #1e40af; }
        .activity-icon.dikembalikan { background: #dcfce7; color: #166534; }
        .activity-icon.ditolak { background: #fee2e2; color: #991b1b; }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            font-size: 13px;
            font-weight: 500;
            color: #0f172a;
        }

        .activity-date {
            font-size: 11px;
            color: #64748b;
        }

        footer {
            text-align: center;
            padding: 15px;
            color: #94a3b8;
            font-size: 11px;
            border-top: 1px solid #e2e8f0;
            margin-top: 30px;
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
                padding: 0 10px;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .top-bar {
                flex-direction: column;
                text-align: center;
            }
            
            .page-title {
                font-size: 18px;
            }
            
            .welcome-card h2 {
                font-size: 18px;
            }
            
            .welcome-icon {
                font-size: 40px;
            }
            
            .stats-grid, .special-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .stat-value, .special-card .stat-value {
                font-size: 20px;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-grid, .special-grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar-menu a {
                padding: 6px 10px;
                font-size: 11px;
            }
            
            .sidebar-menu .menu-icon {
                font-size: 14px;
                margin-right: 6px;
            }
            
            .welcome-card h2 {
                font-size: 16px;
            }
            
            .activity-text {
                font-size: 12px;
            }
        }
    </style>
    <script>
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };
    </script>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <span class="logo">🔧 Petugas Lab</span>
        <span class="role"><?= htmlspecialchars($username); ?></span>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="active">
                <span class="menu-icon">📊</span> Dashboard
            </a>
        </li>
        <li>
            <a href="pengajuan_peminjaman.php">
                <span class="menu-icon">📋</span> Pengajuan Peminjaman
                <span class="badge"><?= $menunggu; ?></span>
            </a>
        </li>
        <li>
            <a href="pengembalian.php">
                <span class="menu-icon">🔄</span> Pengembalian Alat
                <span class="badge"><?= $disetujui; ?></span>
            </a>
        </li>
        <li>
            <a href="laporan.php">
                <span class="menu-icon">📊</span> Laporan
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="../logout.php">
            <span class="menu-icon">🚪</span> Logout
        </a>
    </div>
</div>

<div class="main-content">
    <div class="top-bar">
        <div class="page-title">Dashboard Petugas</div>
        <div class="user-info">
            <div class="user-email"><?= htmlspecialchars($username); ?></div>
            <div class="user-avatar"><?= strtoupper(substr($username, 0, 1)); ?></div>
        </div>
    </div>
    
    <div class="welcome-card">
        <div>
            <h2>Selamat Datang, <?= htmlspecialchars($username); ?>!</h2>
            <p>Kelola peminjaman alat laboratorium</p>
            <p style="margin-top: 5px; font-size: 12px;">📅 <?= date('l, d F Y'); ?></p>
        </div>
        <div class="welcome-icon">🔧</div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Total User</span>
                <span class="stat-icon">👥</span>
            </div>
            <div class="stat-value"><?= $total_users; ?></div>
            <div class="stat-desc">Pengguna aktif</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Total Alat</span>
                <span class="stat-icon">🔧</span>
            </div>
            <div class="stat-value"><?= $total_alat; ?></div>
            <div class="stat-desc">Tersedia</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Total Peminjaman</span>
                <span class="stat-icon">📋</span>
            </div>
            <div class="stat-value"><?= $total_peminjaman; ?></div>
            <div class="stat-desc">Semua transaksi</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Hari Ini</span>
                <span class="stat-icon">📅</span>
            </div>
            <div class="stat-value"><?= $peminjaman_hari_ini; ?></div>
            <div class="stat-desc"><?= date('d/m/Y'); ?></div>
        </div>
    </div>
    
    <div class="special-grid">
        <div class="special-card blue">
            <div class="stat-header">
                <span class="stat-title">Menunggu</span>
                <span class="stat-icon">⏳</span>
            </div>
            <div class="stat-value"><?= $menunggu; ?></div>
            <div class="stat-desc">Perlu konfirmasi</div>
        </div>
        <div class="special-card yellow">
            <div class="stat-header">
                <span class="stat-title">Dipinjam</span>
                <span class="stat-icon">✅</span>
            </div>
            <div class="stat-value"><?= $disetujui; ?></div>
            <div class="stat-desc">Belum dikembalikan</div>
        </div>
        <div class="special-card green">
            <div class="stat-header">
                <span class="stat-title">Selesai</span>
                <span class="stat-icon">↩️</span>
            </div>
            <div class="stat-value"><?= $dikembalikan; ?></div>
            <div class="stat-desc">Transaksi selesai</div>
        </div>
        <div class="special-card purple">
            <div class="stat-header">
                <span class="stat-title">Ditolak</span>
                <span class="stat-icon">❌</span>
            </div>
            <div class="stat-value"><?= $ditolak; ?></div>
            <div class="stat-desc">Pengajuan ditolak</div>
        </div>
    </div>
    
    <div class="menu-grid">
        <a href="pengajuan_peminjaman.php" class="menu-card">
            <div class="menu-icon">📋</div>
            <div class="menu-title">Pengajuan Peminjaman</div>
            <div class="menu-desc">Konfirmasi peminjaman yang menunggu</div>
        </a>
        <a href="pengembalian.php" class="menu-card">
            <div class="menu-icon">🔄</div>
            <div class="menu-title">Pengembalian Alat</div>
            <div class="menu-desc">Pengembalian berlangsung</div>
        </a>
        <a href="laporan.php" class="menu-card">
            <div class="menu-icon">📊</div>
            <div class="menu-title">Laporan</div>
            <div class="menu-desc">Lihat laporan peminjaman</div>
        </a>
    </div>
    
    <div class="activity-card">
        <div class="activity-header">
            <h3>📋 Aktivitas Terbaru</h3>
        </div>
        
        <?php if($total_aktivitas == 0): ?>
            <div class="empty-state" style="text-align:center; padding:30px;">
                <div style="font-size: 40px;">📭</div>
                <p>Belum ada aktivitas</p>
            </div>
        <?php else: ?>
            <?php while($akt = mysqli_fetch_assoc($aktivitas_terbaru)): ?>
                <?php
                $icon_class = 'menunggu';
                if($akt['status'] == 'Disetujui') $icon_class = 'disetujui';
                elseif($akt['status'] == 'Dikembalikan') $icon_class = 'dikembalikan';
                elseif($akt['status'] == 'Ditolak') $icon_class = 'ditolak';
                $tanggal = date('d/m/Y', strtotime($akt['tanggal_pinjam']));
                ?>
                <div class="activity-item">
                    <div class="activity-icon <?= $icon_class; ?>">
                        <?php
                        if($akt['status'] == 'Menunggu') echo '⏳';
                        elseif($akt['status'] == 'Disetujui') echo '✅';
                        elseif($akt['status'] == 'Dikembalikan') echo '↩️';
                        elseif($akt['status'] == 'Ditolak') echo '❌';
                        ?>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text">
                            <strong><?= htmlspecialchars($akt['username']); ?></strong> 
                            <?= $akt['aktivitas_text']; ?> 
                            <strong><?= htmlspecialchars($akt['nama_alat']); ?></strong>
                        </div>
                        <div class="activity-date">📅 <?= $tanggal; ?></div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
    
    <footer>
        © <?= date('Y'); ?> Peminjaman Alat Laboratorium
    </footer>
</div>

</body>
</html>